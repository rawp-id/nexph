<?php
require_once __DIR__ . '/../autoload.php';

use Core\Support\Config;
use Core\Database\DB;
use Core\Queue\Job;

Config::load(__DIR__ . '/../config/app.php');
DB::connect(Config::get('db'));

echo "=== Queue All Tests ===\n\n";

DB::query("DELETE FROM job_workers");

echo "Test 1: Basic Job Enqueue & Process\n";
Job::enqueue('test_1', ['data' => 'value1']);
Job::enqueue('test_2', ['data' => 'value2']);
Job::enqueue('test_3', ['data' => 'value3']);
exec('php ' . __DIR__ . '/../worker.php 2>&1', $output);
$completed = DB::query("SELECT COUNT(*) as count FROM job_workers WHERE status = 'completed'")[0]['count'];
echo $completed == 3 ? "✓ PASS\n" : "✗ FAIL\n";

DB::query("DELETE FROM job_workers");

echo "\nTest 2: Batch Processing (10 jobs, 5 per batch)\n";
for ($i = 1; $i <= 10; $i++) {
    Job::enqueue("batch_job_{$i}", ['index' => $i]);
}
exec('php ' . __DIR__ . '/../worker.php 2>&1', $output);
$completed = DB::query("SELECT COUNT(*) as count FROM job_workers WHERE status = 'completed'")[0]['count'];
echo $completed == 5 ? "✓ PASS (first batch)\n" : "✗ FAIL\n";
exec('php ' . __DIR__ . '/../worker.php 2>&1', $output);
$completed = DB::query("SELECT COUNT(*) as count FROM job_workers WHERE status = 'completed'")[0]['count'];
echo $completed == 10 ? "✓ PASS (second batch)\n" : "✗ FAIL\n";

DB::query("DELETE FROM job_workers");

echo "\nTest 3: Retry Logic\n";
DB::query("INSERT INTO job_workers (id, name, payload, status, attempts) VALUES (?, ?, ?, ?, ?)", [bin2hex(random_bytes(16)), 'retry_test', '{}', 'pending', 0]);
exec('php ' . __DIR__ . '/../worker.php 2>&1', $output);
$job = DB::query("SELECT attempts FROM job_workers WHERE name = 'retry_test'")[0];
echo $job['attempts'] == 1 ? "✓ PASS (attempt incremented)\n" : "✗ FAIL\n";

DB::query("DELETE FROM job_workers");

echo "\nTest 4: Max Attempts\n";
DB::query("INSERT INTO job_workers (id, name, payload, status, attempts) VALUES (?, ?, ?, ?, ?)", [bin2hex(random_bytes(16)), 'max_test', '{}', 'failed', 3]);
exec('php ' . __DIR__ . '/../worker.php 2>&1', $output);
$job = DB::query("SELECT attempts FROM job_workers WHERE name = 'max_test'")[0];
echo $job['attempts'] == 3 ? "✓ PASS (not processed)\n" : "✗ FAIL\n";

DB::query("DELETE FROM job_workers");

echo "\nTest 5: Progress Tracking\n";
Job::enqueue('progress_test', ['data' => 'test']);
exec('php ' . __DIR__ . '/../worker.php 2>&1', $output);
$job = DB::query("SELECT progress FROM job_workers WHERE name = 'progress_test'")[0];
echo $job['progress'] == 100 ? "✓ PASS\n" : "✗ FAIL\n";

echo "\n=== All Tests Complete ===\n";
