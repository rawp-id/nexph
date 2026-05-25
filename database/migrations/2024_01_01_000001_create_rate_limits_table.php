<?php
// Migration: create_rate_limits_table
// Created: 2024-01-01 00:00:00

use Core\Database\DB;

return [
    'up' => function () {
        DB::query("
            CREATE TABLE IF NOT EXISTS rate_limits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                key VARCHAR(255) NOT NULL,
                timestamp INTEGER NOT NULL
            )
        ");
        DB::query("CREATE INDEX IF NOT EXISTS idx_rate_limits_key ON rate_limits(key)");
        DB::query("CREATE INDEX IF NOT EXISTS idx_rate_limits_timestamp ON rate_limits(timestamp)");
    },

    'down' => function () {
        DB::query("DROP TABLE IF EXISTS rate_limits");
    },
];
