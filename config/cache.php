<?php
return [
    // Cache driver: file, memory, redis, memcached
    'driver' => env('CACHE_DRIVER', 'file'),
    'prefix' => env('CACHE_PREFIX', 'nexph:'),

    'file' => [
        'path' => env('CACHE_FILE_PATH', BASE_PATH . '/storage/cache'),
    ],

    'redis' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'port' => env('REDIS_PORT', 6379),
        'password' => env('REDIS_PASSWORD', null),
        'database' => env('REDIS_CACHE_DB', 1),
    ],

    'memcached' => [
        'host' => env('MEMCACHED_HOST', '127.0.0.1'),
        'port' => env('MEMCACHED_PORT', 11211),
    ],
];
