<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SocketBroadcaster
 *
 * Sends real-time events to connected WebSocket clients by POSTing to the
 * internal HTTP endpoint of the running Workerman socket server (socket-server.php).
 *
 * Usage:
 *   SocketBroadcaster::send($userId, 'notification', $payload);
 *   SocketBroadcaster::sendToRoom('user_15', 'refresh', []);
 */
class SocketBroadcaster
{
    /**
     * Send an event to a specific user's private room.
     *
     * @param  int    $userId  Target user ID
     * @param  string $event   Event name (e.g. 'notification', 'message')
     * @param  array  $data    Payload to send
     */
    public static function send(int $userId, string $event, array $data = []): void
    {
        static::sendToRoom("user_{$userId}", $event, $data);
    }

    /**
     * Send an event to any named room (group).
     *
     * @param  string $room   Room name (e.g. 'user_15', 'broadcast')
     * @param  string $event  Event name
     * @param  array  $data   Payload
     */
    public static function sendToRoom(string $room, string $event, array $data = []): void
    {
        $internalUrl   = config('broadcasting.connections.workerman.internal_url', 'http://127.0.0.1:8080');
        $internalToken = config('broadcasting.connections.workerman.internal_token', '');

        try {
            Http::timeout(2)
                ->withHeaders(['X-Socket-Token' => $internalToken])
                ->post("{$internalUrl}/internal/broadcast", compact('room', 'event', 'data'));
        } catch (\Throwable $e) {
            // Never let a WebSocket failure take down the main request
            Log::warning("SocketBroadcaster: failed to send event [{$event}] to [{$room}]: " . $e->getMessage());
        }
    }
}
