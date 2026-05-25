<?php
namespace Core\Queue;

use Core\Database\DB;
use Core\Support\Config;

class Job {
    public static function enqueue(string $name, array $payload = []): void {
        $id = bin2hex(random_bytes(16));
        DB::query(
            "INSERT INTO job_workers (id, name, payload, status, progress, attempts) VALUES (?, ?, ?, ?, ?, ?)",
            [$id, $name, json_encode($payload), 'pending', 0, 0]
        );
    }
}
