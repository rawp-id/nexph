<?php
namespace Core\Queue;

use Core\Database\DB;

class Worker {
    private static int $maxAttempts = 3;
    private static array $handlers = [];

    public static function register(string $jobName, string $handlerClass): void {
        self::$handlers[$jobName] = $handlerClass;
    }

    public static function run(): void {
        $jobs = DB::query(
            "SELECT id, name, payload, status, attempts, progress, created_at FROM job_workers WHERE status IN ('pending', 'failed') AND attempts < ? LIMIT 5",
            [self::$maxAttempts]
        );
        
        foreach ($jobs as $job) {
            DB::query("UPDATE job_workers SET status = 'running', attempts = attempts + 1 WHERE id = ?", [$job['id']]);
            
            try {
                $payload = json_decode($job['payload'], true) ?? [];
                
                if (isset(self::$handlers[$job['name']])) {
                    $handlerClass = self::$handlers[$job['name']];
                    $handler = new $handlerClass();
                    $handler->handle($payload);
                } else {
                    for ($i = 1; $i <= 10; $i++) {
                        usleep(100000);
                        $progress = $i * 10;
                        DB::query("UPDATE job_workers SET progress = ? WHERE id = ?", [$progress, $job['id']]);
                    }
                }
                
                DB::query("UPDATE job_workers SET status = 'completed', progress = 100 WHERE id = ?", [$job['id']]);
            } catch (\Exception $e) {
                $status = ($job['attempts'] + 1 >= self::$maxAttempts) ? 'failed' : 'pending';
                DB::query(
                    "UPDATE job_workers SET status = ?, error = ? WHERE id = ?",
                    [$status, $e->getMessage(), $job['id']]
                );
            }
        }
    }
}
