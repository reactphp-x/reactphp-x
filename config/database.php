<?php

return [
    'default' => env('DB_CONNECTION', 'default'),

    'databases' => [
        'default' => [
            'driver' => env('DB_DRIVER', 'mysql'),
            'prefix' => '',
        ],
    ],

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'database' => env('DB_DATABASE', 'test'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => (int) env('DB_PORT', 3306),
            'charset' => env('DB_CHARSET', 'utf8mb4'),
            'user' => env('DB_USERNAME', 'root'),
            'password' => env('DB_PASSWORD', 'secret'),
            'timezone' => env('DB_TIMEZONE', 'Asia/Shanghai'),
            'pool' => [
                'minConnections' => (int) env('DB_POOL_MIN', 2),
                'maxConnections' => (int) env('DB_POOL_MAX', 10),
                'waitQueue' => (int) env('DB_POOL_QUEUE', 1000),
                'waitTimeout' => (int) env('DB_POOL_TIMEOUT', 0),
            ],
            'options' => [
                'logInterpolatedQueries' => true,
            ],
        ],

        'sqlite' => [
            'driver' => 'sqlite',
            // Use :memory: for in-memory database, or a file path such as storage/database.sqlite
            'database' => env('DB_DATABASE', base_path('storage/database.sqlite')),
            'timezone' => env('DB_TIMEZONE', 'Asia/Shanghai'),
            'options' => [
                'idle' => (int) env('DB_SQLITE_IDLE', 60),
                'logInterpolatedQueries' => true,

            ],
        ],
    ],
];
