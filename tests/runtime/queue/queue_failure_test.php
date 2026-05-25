<?php
require_once __DIR__ . '/../autoload.php';

use Core\Support\Config;
use Core\Database\DB;
use Core\Queue\Job;
use Core\Queue\Worker;
use Core\Queue\JobHandler;

Config::load(__DIR__ . '/../config/app.php');
DB::connect(Config::get('db'));

class FailingJobHandler extends JobHandler {
    public function handle(array $payload): void {
        $failUntil = $payload['fail_until'] ?? 0;
        $currentAttempt = $payload['current_attempt'] ?? 1;
        
        if ($currentAttempt < $failUntil) {
            throw new \Exception("Job failed on attempt {$currentAttempt}");
        }
        
        usleep(50000);
    }
}

Worker::register('failing_job', FailingJobHandler::class);

echo "=== Queue Failure & Retry Test ===\n\n";

echo "1. Clearing old jobs...\n";
DB::query("DELETE FROM job_workers");

echo "2. Creating failing job (fails first 2 attempts)...\n";
DB::query(
    "INSERT INTO job_workers (id, name, payload, status, progress, attempts) VALUES (?, ?, ?, ?, ?, ?)",
    [bin2hex(random_bytes(16)), 'failing_job', json_encode(['fail_until' => 3]), 'pending', 0, 0]
);

echo "3. First attempt (should fail)...\n";
Worker::run();
$job = DB::query("SELECT * FROM job_workers WHERE name = 'failing_job'")[0];
echo "   Status: {$job['status']}, Attempts: {$job['attempts']}, Error: " . ($job['error'] ?? 'none') . "\n";

echo "\n4. Second attempt (should fail)...\n";
Worker::run();
$job = DB::query("SELECT * FROM job_workers WHERE name = 'failing_job'")[0];
echo "   Status: {$job['status']}, Attempts: {$job['attempts']}, Error: " . ($job['error'] ?? 'none') . "\n";

echo "\n5. Third attempt (max attempts, should fail permanently)...\n";
Worker::run();
$job = DB::query("SELECT * FROM job_workers WHERE name = 'failing_job'")[0];
echo "   Status: {$job['status']}, Attempts: {$job['attempts']}, Error: " . ($job['error'] ?? 'none') . "\n";

echo "\n6. Fourth attempt (should not process)...\n";
Worker::run();
$job = DB::query("SELECT * FROM job_workers WHERE name = 'failing_job'")[0];
echo "   Status: {$job['status']}, Attempts: {$job['attempts']}\n";

echo "\n✓ Test complete\n";
