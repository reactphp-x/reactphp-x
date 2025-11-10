<?php

return [
    'default' => 'default',
    'databases' => [
        'default' => [
            'driver' => 'mysql',
            'prefix' => ''
        ],
    ],
    'connections' => [
        'mysql' => [
            'database' => env('DB_DATABASE', 'test'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => (int) env('DB_PORT', 3306),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'user' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', 'secret'),
            'pool' => [
                'minConnections' => (int) env('DB_POOL_MIN', 2),
                'maxConnections' => (int) env('DB_POOL_MAX', 10),
                'waitQueue' => (int) env('DB_POOL_QUEUE', 1000),
                'waitTimeout' => (int) env('DB_POOL_TIMEOUT', 0),
            ],
        ],
    ],
];


