<?php
/**
 * Dead letter queue example.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Runtime\Queue\QueueFactory;
use Core\Runtime\Queue\Drivers\MemoryDriver;

// Create queue
$driver = new MemoryDriver();
$queue = QueueFactory::createWithDriver('memory', [
    'workers' => 1,
    'max_attempts' => 3,
]);

// Register handler that fails sometimes
$queue->register('risky-job', function($payload, $job) {
    echo "Processing job {$job->id} (attempt {$job->attempts})...\n";
    
    // Fail jobs with even IDs
    if ($payload['id'] % 2 === 0) {
        throw new \Exception("Job {$payload['id']} failed");
    }
    
    echo "Job {$payload['id']} succeeded\n";
    return ['success' => true];
});

// Push jobs (some will fail)
echo "Pushing 10 jobs (even IDs will fail)...\n\n";
for ($i = 1; $i <= 10; $i++) {
    $queue->push('risky-job', ['id' => $i]);
}

echo "Starting worker...\n";
$queue->work();

// Check dead letter queue
echo "\n=== Dead Letter Queue ===\n";
$deadLetters = $driver->getDeadLetters();

if (empty($deadLetters)) {
    echo "No failed jobs\n";
} else {
    echo "Found " . count($deadLetters) . " failed jobs:\n\n";
    
    foreach ($deadLetters as $job) {
        echo "Job ID: {$job->id}\n";
        echo "Name: {$job->name}\n";
        echo "Payload: " . json_encode($job->payload) . "\n";
        echo "Attempts: {$job->attempts}/{$job->max_attempts}\n";
        echo "Error: {$job->error}\n";
        echo "Failed at: " . date('Y-m-d H:i:s', $job->failed_at) . "\n";
        echo "---\n";
    }
}

// Show metrics
echo "\n=== Metrics ===\n";
$metrics = $queue->metrics()->toArray();
echo "Completed: {$metrics['completed']}\n";
echo "Failed: {$metrics['failed']}\n";
echo "Retried: {$metrics['retried']}\n";
