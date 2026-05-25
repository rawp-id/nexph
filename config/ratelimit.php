<?php
return [
    // Rate limiter driver: database, redis, memory
    'driver' => env('RATE_LIMIT_DRIVER', 'database'),

    // Default limits
    'default' => [
        'max_attempts' => 60,
        'window' => 60, // seconds
    ],

    // Per-route limits
    'routes' => [
        'api' => ['max' => 60, 'window' => 60],
        'auth' => ['max' => 5, 'window' => 300],
        'upload' => ['max' => 10, 'window' => 60],
    ],

    'redis' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD', null),
    ],
];
