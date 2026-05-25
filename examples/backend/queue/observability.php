<?php
/**
 * Queue observability and metrics example.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Runtime\Queue\QueueFactory;
use Core\Runtime\Queue\QueueObserver;
use Core\Runtime\Runtime;

// Create queue
$queue = QueueFactory::createWithDriver('memory', [
    'workers' => 3,
]);

// Register handlers
$queue->register('fast-job', function($payload, $job) {
    Runtime::sleep(0.1);
    return ['status' => 'completed'];
});

$queue->register('slow-job', function($payload, $job) {
    Runtime::sleep(2.0);
    return ['status' => 'completed'];
});

$queue->register('failing-job', function($payload, $job) {
    if (rand(0, 10) > 7) {
        throw new \Exception('Random failure');
    }
    return ['status' => 'completed'];
});

// Create observer
$observer = new QueueObserver($queue);

// Push various jobs
echo "Pushing 50 jobs...\n";
for ($i = 1; $i <= 30; $i++) {
    $queue->push('fast-job', ['id' => $i]);
}
for ($i = 1; $i <= 10; $i++) {
    $queue->push('slow-job', ['id' => $i]);
}
for ($i = 1; $i <= 10; $i++) {
    $queue->push('failing-job', ['id' => $i]);
}

echo "Starting workers with metrics reporting...\n\n";

// Start periodic metrics reporting
$observer->startReporting(5);

// Start workers
Runtime::spawn(function() use ($queue) {
    $queue->work();
});

// Let it run for a bit
Runtime::spawn(function() use ($queue, $observer) {
    Runtime::sleep(20);
    
    echo "\n=== Final Metrics ===\n";
    $observer->printMetrics();
    
    $queue->stop();
    Runtime::stop();
});

Runtime::run();

echo "Done!\n";
