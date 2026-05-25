<?php
// Migration: create_job_workers_table
// Created: 2024-01-01 00:00:00

use Core\Database\DB;

return [
    'up' => function () {
        DB::query("
            CREATE TABLE IF NOT EXISTS job_workers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(255) NOT NULL,
                payload TEXT,
                status VARCHAR(20) DEFAULT 'pending',
                attempts INTEGER DEFAULT 0,
                progress INTEGER DEFAULT 0,
                error TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                started_at DATETIME,
                completed_at DATETIME
            )
        ");
        DB::query("CREATE INDEX IF NOT EXISTS idx_job_workers_status ON job_workers(status)");
        DB::query("CREATE INDEX IF NOT EXISTS idx_job_workers_name ON job_workers(name)");
    },

    'down' => function () {
        DB::query("DROP TABLE IF EXISTS job_workers");
    },
];
