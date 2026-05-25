<?php
return [
    'driver' => env('SESSION_DRIVER', 'file'),
    'lifetime' => env('SESSION_LIFETIME', 7200),
    'cookie_name' => env('SESSION_COOKIE_NAME', 'nexph_session'),
    'cookie_secure' => env('SESSION_COOKIE_SECURE', true),
    'cookie_httponly' => env('SESSION_COOKIE_HTTPONLY', true),
    'cookie_samesite' => env('SESSION_COOKIE_SAMESITE', 'Lax'),
    'regenerate_interval' => env('SESSION_REGENERATE_INTERVAL', 300),
    'drivers' => [
        'file' => [
            'path' => env('SESSION_FILE_PATH', BASE_PATH . '/storage/sessions'),
        ],
        'database' => [
            'table' => env('SESSION_DB_TABLE', 'sessions'),
        ],
        'redis' => [
            'host' => env('SESSION_REDIS_HOST', '127.0.0.1'),
            'port' => env('SESSION_REDIS_PORT', 6379),
            'password' => env('SESSION_REDIS_PASSWORD', null),
            'database' => env('SESSION_REDIS_DB', 0),
            'prefix' => env('SESSION_REDIS_PREFIX', 'sess:'),
        ],
    ],
];
