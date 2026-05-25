<?php
require_once __DIR__ . '/../autoload.php';

use Core\Support\Config;
use Core\Database\DB;
use Core\Queue\Job;
use Core\Queue\Worker;

Config::load(__DIR__ . '/../config/app.php');
DB::connect(Config::get('db'));

echo "=== Queue System Test ===\n\n";

echo "1. Clearing old jobs...\n";
DB::query("DELETE FROM job_workers");

echo "2. Enqueuing test jobs...\n";
Job::enqueue('test_job_1', ['data' => 'payload1']);
Job::enqueue('test_job_2', ['data' => 'payload2']);
Job::enqueue('test_job_3', ['data' => 'payload3']);

$pending = DB::query("SELECT COUNT(*) as count FROM job_workers WHERE status = 'pending'")[0]['count'];
echo "   Pending jobs: {$pending}\n";

echo "\n3. Running worker (single pass)...\n";
Worker::run();

$completed = DB::query("SELECT COUNT(*) as count FROM job_workers WHERE status = 'completed'")[0]['count'];
$running = DB::query("SELECT COUNT(*) as count FROM job_workers WHERE status = 'running'")[0]['count'];
$failed = DB::query("SELECT COUNT(*) as count FROM job_workers WHERE status = 'failed'")[0]['count'];

echo "   Completed: {$completed}\n";
echo "   Running: {$running}\n";
echo "   Failed: {$failed}\n";

echo "\n4. Job details:\n";
$jobs = DB::query("SELECT id, name, status, progress, attempts FROM job_workers ORDER BY id");
foreach ($jobs as $job) {
    echo "   Job #{$job['id']}: {$job['name']} - {$job['status']} ({$job['progress']}%) - attempts: {$job['attempts']}\n";
}

echo "\n✓ Test complete\n";
