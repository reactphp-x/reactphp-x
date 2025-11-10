<?php

return [
    'default' => 'daily',
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily'],
            'ignore_exceptions' => false,
        ],
        'daily' => [
            'driver' => 'single',
            'path' => base_path('storage/logs/app-access.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
            'adapter' => app('fs'),
        ],
        'error' => [
            'driver' => 'single',
            'path' => base_path('storage/logs/app-error-access.log'),
            'level' => 'error',
            'replace_placeholders' => true,
            'adapter' => app('fs'),
        ],
        'sql' => [
            'driver' => 'single',
            'path' => base_path('storage/logs/app-sql-access.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
            'adapter' => app('fs'),
        ],
        'stdout' => [
            'driver' => 'stdout',
            'level' => env('LOG_LEVEL', 'debug'),
            'replace_placeholders' => true,
        ],
    ],
];


