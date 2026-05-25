<?php
/**
 * Multiple workers example.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Runtime\Queue\QueueFactory;
use Core\Runtime\Runtime;

// Create queue with 4 workers
$queue = QueueFactory::createWithDriver('memory', [
    'workers' => 4,
]);

// Register CPU-intensive job
$queue->register('process', function($payload, $job) {
    $workerId = $payload['worker_id'] ?? 'unknown';
    echo "[Worker {$workerId}] Processing job {$job->id}...\n";
    
    // Simulate work
    Runtime::sleep(2.0);
    
    echo "[Worker {$workerId}] Completed job {$job->id}\n";
    return ['processed' => true];
});

// Push 20 jobs
echo "Pushing 20 jobs...\n";
for ($i = 1; $i <= 20; $i++) {
    $queue->push('process', ['job_number' => $i]);
}

echo "Starting 4 workers...\n\n";

// Start workers (async mode will process jobs concurrently)
$startTime = microtime(true);
$queue->work();
$duration = microtime(true) - $startTime;

echo "\nAll jobs completed in " . number_format($duration, 2) . " seconds\n";
echo "Expected time with 1 worker: ~40 seconds\n";
echo "Expected time with 4 workers: ~10 seconds\n";
