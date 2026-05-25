<?php
require_once __DIR__ . '/../autoload.php';

use Core\Support\Config;
use Core\Database\DB;
use Core\Queue\Job;
use Core\Queue\Worker;
use Core\Queue\JobHandler;

Config::load(__DIR__ . '/../config/app.php');
DB::connect(Config::get('db'));

class EmailJobHandler extends JobHandler {
    public function handle(array $payload): void {
        $to = $payload['to'] ?? 'unknown';
        $subject = $payload['subject'] ?? 'No subject';
        
        echo "   Sending email to {$to}: {$subject}\n";
        usleep(200000);
        echo "   Email sent successfully\n";
    }
}

class DataProcessingJobHandler extends JobHandler {
    public function handle(array $payload): void {
        $records = $payload['records'] ?? 0;
        
        echo "   Processing {$records} records...\n";
        for ($i = 1; $i <= 5; $i++) {
            usleep(100000);
            echo "   Progress: " . ($i * 20) . "%\n";
        }
        echo "   Processing complete\n";
    }
}

Worker::register('send_email', EmailJobHandler::class);
Worker::register('process_data', DataProcessingJobHandler::class);

echo "=== Queue Custom Handlers Test ===\n\n";

echo "1. Clearing old jobs...\n";
DB::query("DELETE FROM job_workers");

echo "\n2. Enqueuing custom jobs...\n";
Job::enqueue('send_email', ['to' => 'user@example.com', 'subject' => 'Welcome!']);
Job::enqueue('process_data', ['records' => 1000]);
Job::enqueue('send_email', ['to' => 'admin@example.com', 'subject' => 'Report']);

echo "\n3. Running worker...\n";
Worker::run();

echo "\n4. Job results:\n";
$jobs = DB::query("SELECT id, name, status, progress, attempts FROM job_workers ORDER BY id");
foreach ($jobs as $job) {
    echo "   Job #{$job['id']}: {$job['name']} - {$job['status']} ({$job['progress']}%)\n";
}

echo "\n✓ Test complete\n";
