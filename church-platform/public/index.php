<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// ── Shared-hosting installer bootstrap ───────────────────────────────────────
// If .env doesn't exist, silently copy .env.example so Laravel can boot enough
// to serve the /install wizard. The wizard will fill real values and rewrite it.
if (!file_exists(__DIR__ . '/../.env')) {
    $example = __DIR__ . '/../.env.example';
    $env     = __DIR__ . '/../.env';
    if (file_exists($example)) {
        copy($example, $env);
        // Inject a temporary APP_KEY so sessions work during install
        $content = file_get_contents($env);
        if (strpos($content, 'APP_KEY=') !== false && strpos($content, 'APP_KEY=base64:') === false) {
            $key = 'base64:' . base64_encode(random_bytes(32));
            $content = preg_replace('/^APP_KEY=.*/m', 'APP_KEY=' . $key, $content);
            file_put_contents($env, $content);
        }
    }
}
// ─────────────────────────────────────────────────────────────────────────────

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = $kernel->handle(
    $request = Request::capture()
)->send();

$kernel->terminate($request, $response);
