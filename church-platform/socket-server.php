<?php

/**
 * Church Platform — Workerman GatewayWorker Socket Server
 *
 * Replaces Laravel Reverb for shared hosting (cPanel) environments.
 * Kept alive via cPanel cron watchdog every 5 minutes.
 *
 * Start:  php socket-server.php start
 * Stop:   php socket-server.php stop
 * Status: php socket-server.php status
 *
 * cPanel Cron watchdog (add this to cPanel Cron Jobs):
 *   EVERY 5 MINUTES:
 *   % /5 * * * * [ $(pgrep -f "socket-server.php" | wc -l) -eq 0 ] && php /home/user/public_html/socket-server.php start > /dev/null 2>&1 &
 */

require_once __DIR__ . '/vendor/autoload.php';

use GatewayWorker\BusinessWorker;
use GatewayWorker\Gateway;
use GatewayWorker\Register;
use Workerman\Worker;

// ── Load environment from .env ────────────────────────────────────────────────
$dotenv = __DIR__ . '/.env';
if (file_exists($dotenv)) {
    foreach (file($dotenv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
    }
}

$host          = $_ENV['SOCKET_HOST']           ?? '0.0.0.0';
$port          = (int) ($_ENV['SOCKET_PORT']    ?? 8080);
$internalToken = $_ENV['SOCKET_INTERNAL_TOKEN'] ?? '';

// DB config for token verification
$dbHost = $_ENV['DB_HOST']     ?? '127.0.0.1';
$dbPort = $_ENV['DB_PORT']     ?? 3306;
$dbName = $_ENV['DB_DATABASE'] ?? 'church_platform';
$dbUser = $_ENV['DB_USERNAME'] ?? 'root';
$dbPass = $_ENV['DB_PASSWORD'] ?? '';

// ── Register server (internal service discovery) ──────────────────────────────
$register = new Register('text://127.0.0.1:1238');

// ── Gateway (WebSocket listener for browser clients) ─────────────────────────
$gateway = new Gateway("websocket://{$host}:{$port}");
$gateway->name                = 'Gateway';
$gateway->count               = 1;           // single process — keep it simple on shared hosting
$gateway->lanIp               = '127.0.0.1';
$gateway->startPort           = 2900;
$gateway->pingInterval        = 25;
$gateway->pingNotResponseLimit = 2;          // close after 2 missed pongs
$gateway->pingData            = '{"type":"ping"}';
$gateway->registerAddress     = '127.0.0.1:1238';

// ── BusinessWorker (application logic) ───────────────────────────────────────
$worker = new BusinessWorker();
$worker->name             = 'BusinessWorker';
$worker->count            = 1;
$worker->registerAddress  = '127.0.0.1:1238';
$worker->eventHandler     = SocketEventHandler::class;

// ── Internal HTTP endpoint (receives broadcasts from Laravel) ─────────────────
// Laravel's SocketBroadcaster POSTs here to push events to connected clients.
$httpWorker = new Worker("http://127.0.0.1:{$port}");
$httpWorker->name  = 'InternalHttp';
$httpWorker->count = 1;
$httpWorker->onMessage = function ($connection, $request) use ($internalToken) {
    if ($request->path() !== '/internal/broadcast') {
        $connection->send(http_response(404, 'Not found'));
        return;
    }
    if ($request->method() !== 'POST') {
        $connection->send(http_response(405, 'Method not allowed'));
        return;
    }

    // Verify shared secret
    $token = $request->header('x-socket-token', '');
    if ($internalToken && $token !== $internalToken) {
        $connection->send(http_response(403, 'Forbidden'));
        return;
    }

    $body  = json_decode($request->rawBody(), true);
    $room  = $body['room']  ?? null;
    $event = $body['event'] ?? null;
    $data  = $body['data']  ?? [];

    if (!$room || !$event) {
        $connection->send(http_response(422, 'Missing room or event'));
        return;
    }

    // Push to all clients in the room
    $payload = json_encode(['type' => $event, 'data' => $data]);
    \GatewayWorker\Lib\Gateway::sendToGroup($room, $payload);

    $connection->send(http_response(200, 'OK'));
};

/**
 * Build a minimal HTTP response string for Workerman's HTTP worker.
 */
function http_response(int $code, string $body): string
{
    $status = match($code) {
        200 => 'OK', 403 => 'Forbidden', 404 => 'Not Found',
        405 => 'Method Not Allowed', 422 => 'Unprocessable Entity',
        default => 'Internal Server Error',
    };
    return "HTTP/1.1 {$code} {$status}\r\nContent-Type: application/json\r\nContent-Length: "
        . strlen($body) . "\r\n\r\n{$body}";
}

// ── Run all workers ───────────────────────────────────────────────────────────
Worker::runAll();

// =============================================================================
// Event Handler class — embedded in same file for simplicity
// =============================================================================

/**
 * Handles all socket events: connect, message, close.
 */
class SocketEventHandler
{
    /**
     * Called when a client connects.
     * The client must authenticate within 5 seconds by sending:
     *   { "type": "auth", "token": "<sanctum_token>" }
     */
    public static function onConnect(string $clientId): void
    {
        // Set a 5-second auth timeout
        \GatewayWorker\Lib\Gateway::sendToClient($clientId, json_encode([
            'type' => 'connected',
            'message' => 'Please authenticate within 5 seconds.',
        ]));

        \Workerman\Timer::add(5, function () use ($clientId) {
            $session = \GatewayWorker\Lib\Gateway::getSession($clientId);
            if (!($session['authenticated'] ?? false)) {
                \GatewayWorker\Lib\Gateway::closeClient($clientId);
            }
        }, [], false); // false = run once
    }

    /**
     * Called on every message from a client.
     */
    public static function onMessage(string $clientId, string $message): void
    {
        $data = json_decode($message, true);
        if (!$data) return;

        $type = $data['type'] ?? '';

        if ($type === 'pong') {
            // Heartbeat reply — nothing to do
            return;
        }

        if ($type === 'auth') {
            self::handleAuth($clientId, $data['token'] ?? '');
            return;
        }
    }

    /**
     * Called when a client disconnects.
     */
    public static function onClose(string $clientId): void
    {
        // Nothing to clean up — GatewayWorker removes the client automatically
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private static function handleAuth(string $clientId, string $token): void
    {
        if (empty($token)) {
            \GatewayWorker\Lib\Gateway::closeClient($clientId);
            return;
        }

        $userId = self::verifyToken($token);

        if (!$userId) {
            \GatewayWorker\Lib\Gateway::sendToClient($clientId, json_encode([
                'type' => 'auth_failed',
                'message' => 'Invalid token.',
            ]));
            \GatewayWorker\Lib\Gateway::closeClient($clientId);
            return;
        }

        // Store user in session + join their private room
        \GatewayWorker\Lib\Gateway::updateSession($clientId, ['authenticated' => true, 'user_id' => $userId]);
        \GatewayWorker\Lib\Gateway::joinGroup($clientId, "user_{$userId}");

        \GatewayWorker\Lib\Gateway::sendToClient($clientId, json_encode([
            'type' => 'auth_success',
            'user_id' => $userId,
        ]));
    }

    /**
     * Verify a Laravel Sanctum personal access token by querying the DB directly.
     * Returns the user ID on success, null on failure.
     */
    private static function verifyToken(string $token): ?int
    {
        // Sanctum tokens are stored as SHA-256 hash of the plain-text token
        // Format: "{id}|{plain_token}"  e.g. "3|AbCdEfGh..."
        if (!str_contains($token, '|')) return null;

        [$id, $plain] = explode('|', $token, 2);

        try {
            $pdo = self::getDb();
            $stmt = $pdo->prepare(
                "SELECT tokenable_id FROM personal_access_tokens
                  WHERE id = ? AND token = ? AND (expires_at IS NULL OR expires_at > NOW())
                  LIMIT 1"
            );
            $stmt->execute([(int) $id, hash('sha256', $plain)]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $row ? (int) $row['tokenable_id'] : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private static ?\PDO $pdo = null;

    private static function getDb(): \PDO
    {
        if (self::$pdo === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                $_ENV['DB_HOST'] ?? '127.0.0.1',
                $_ENV['DB_PORT'] ?? 3306,
                $_ENV['DB_DATABASE'] ?? 'church_platform'
            );
            self::$pdo = new \PDO(
                $dsn,
                $_ENV['DB_USERNAME'] ?? 'root',
                $_ENV['DB_PASSWORD'] ?? '',
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        }
        return self::$pdo;
    }
}
