<?php
require_once __DIR__ . '/../autoload.php';

use Core\Support\Config;
use Core\Database\DB;
use Core\Queue\Job;
use Core\Queue\Worker;

Config::load(__DIR__ . '/../config/app.php');
DB::connect(Config::get('db'));

echo "=== Queue Concurrency Test ===\n\n";

echo "1. Clearing old jobs...\n";
DB::query("DELETE FROM job_workers");

echo "2. Enqueuing 10 jobs...\n";
for ($i = 1; $i <= 10; $i++) {
    Job::enqueue("job_{$i}", ['index' => $i]);
}

$pending = DB::query("SELECT COUNT(*) as count FROM job_workers WHERE status = 'pending'")[0]['count'];
echo "   Pending: {$pending}\n";

echo "\n3. First worker pass (processes 5)...\n";
Worker::run();

$completed = DB::query("SELECT COUNT(*) as count FROM job_workers WHERE status = 'completed'")[0]['count'];
$pending = DB::query("SELECT COUNT(*) as count FROM job_workers WHERE status = 'pending'")[0]['count'];
echo "   Completed: {$completed}, Pending: {$pending}\n";

echo "\n4. Second worker pass (processes remaining 5)...\n";
Worker::run();

$completed = DB::query("SELECT COUNT(*) as count FROM job_workers WHERE status = 'completed'")[0]['count'];
$pending = DB::query("SELECT COUNT(*) as count FROM job_workers WHERE status = 'pending'")[0]['count'];
echo "   Completed: {$completed}, Pending: {$pending}\n";

echo "\n5. Third worker pass (nothing to process)...\n";
Worker::run();

$completed = DB::query("SELECT COUNT(*) as count FROM job_workers WHERE status = 'completed'")[0]['count'];
echo "   Completed: {$completed}\n";

echo "\n✓ Test complete\n";
