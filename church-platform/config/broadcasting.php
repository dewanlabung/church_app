<?php

return [

    'default' => env('BROADCAST_DRIVER', 'null'),

    'connections' => [

        /*
         * Workerman / GatewayWorker socket server.
         * Laravel does NOT use this connection directly via broadcast().
         * Instead, NotificationService calls SocketBroadcaster::send() which
         * POSTs to the internal HTTP endpoint of the running socket-server.php.
         */
        'workerman' => [
            'driver'         => 'workerman',
            'internal_url'   => env('SOCKET_INTERNAL_URL', 'http://127.0.0.1:8080'),
            'internal_token' => env('SOCKET_INTERNAL_TOKEN', ''),
            'port'           => env('SOCKET_PORT', 8080),
        ],

        'pusher' => [
            'driver' => 'pusher',
            'key' => env('PUSHER_APP_KEY'),
            'secret' => env('PUSHER_APP_SECRET'),
            'app_id' => env('PUSHER_APP_ID'),
            'options' => [
                'host' => env('PUSHER_HOST') ?: 'api-'.env('PUSHER_APP_CLUSTER', 'mt1').'.pusher.com',
                'port' => env('PUSHER_PORT', 443),
                'scheme' => env('PUSHER_SCHEME', 'https'),
                'encrypted' => true,
                'useTLS' => env('PUSHER_SCHEME', 'https') === 'https',
            ],
            'client_options' => [],
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
