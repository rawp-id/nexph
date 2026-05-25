<?php
return [
    'channel' => env('LOG_CHANNEL', 'app'),
    'level' => env('LOG_LEVEL', 'debug'),

    // Handlers: file, stdout, syslog
    'handlers' => [
        [
            'type' => 'file',
            'path' => env('LOG_FILE_PATH', BASE_PATH . '/storage/logs/app.log'),
            'max_size' => 10 * 1024 * 1024, // 10MB
            'max_files' => 5,
            'json' => env('LOG_JSON', false),
        ],
    ],

    // Production: JSON to stdout for log aggregators
    'production' => [
        'handlers' => [
            ['type' => 'stdout', 'json' => true],
        ],
    ],
];
