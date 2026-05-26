<?php
namespace Core\Auth;

use Core\Database\DB;

class DatabaseSessionDriver implements SessionDriver {
    public static function schema(): array {
        return [
            'table' => 'string|required',
        ];
    }
    private int $lifetime;

    public function __construct(array $config) {
        $this->lifetime = $config['lifetime'] ?? 7200;
        $this->ensureTable();
    }

    public function read(string $id): array {
        $result = DB::query(
            "SELECT data, last_activity FROM sessions WHERE id = ?",
            [$id]
        );
        
        if (empty($result)) {
            return [];
        }
        
        $row = $result[0];
        
        if ((time() - $row['last_activity']) > $this->lifetime) {
            $this->destroy($id);
            return [];
        }
        
        return json_decode($row['data'], true) ?? [];
    }

    public function write(string $id, array $data): bool {
        $jsonData = json_encode($data);
        $lastActivity = time();
        
        $existing = DB::query("SELECT id FROM sessions WHERE id = ?", [$id]);
        
        if (empty($existing)) {
            DB::query(
                "INSERT INTO sessions (id, data, last_activity) VALUES (?, ?, ?)",
                [$id, $jsonData, $lastActivity]
            );
        } else {
            DB::query(
                "UPDATE sessions SET data = ?, last_activity = ? WHERE id = ?",
                [$jsonData, $lastActivity, $id]
            );
        }
        
        return true;
    }

    public function destroy(string $id): bool {
        DB::query("DELETE FROM sessions WHERE id = ?", [$id]);
        return true;
    }

    public function exists(string $id): bool {
        $result = DB::query("SELECT id FROM sessions WHERE id = ?", [$id]);
        return !empty($result);
    }

    public function gc(int $maxLifetime): void {
        $expiry = time() - $maxLifetime;
        DB::query("DELETE FROM sessions WHERE last_activity < ?", [$expiry]);
    }

    private function ensureTable(): void {
        DB::query("
            CREATE TABLE IF NOT EXISTS sessions (
                id TEXT PRIMARY KEY,
                data TEXT NOT NULL,
                last_activity INTEGER NOT NULL
            )
        ");
        
        DB::query("
            CREATE INDEX IF NOT EXISTS idx_sessions_last_activity 
            ON sessions(last_activity)
        ");
        
        DB::query("
            CREATE TABLE IF NOT EXISTS job_workers (
                id TEXT PRIMARY KEY,
                name TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'pending',
                attempts INTEGER NOT NULL DEFAULT 0,
                progress INTEGER NOT NULL DEFAULT 0,
                error TEXT,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        DB::query("
            CREATE INDEX IF NOT EXISTS idx_job_workers_status 
            ON job_workers(status)
        ");
    }
}
