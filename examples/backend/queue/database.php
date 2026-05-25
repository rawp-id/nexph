<?php
/**
 * Database driver example.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Runtime\Queue\QueueFactory;

// Create database connection
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create queue with database driver
$queue = QueueFactory::createWithDriver('database', [
    'database' => $pdo,
    'workers' => 2,
]);

// Register handlers
$queue->register('process-order', function($payload, $job) {
    echo "Processing order #{$payload['order_id']}...\n";
    sleep(1);
    echo "Order #{$payload['order_id']} completed\n";
    return ['order_id' => $payload['order_id'], 'status' => 'completed'];
});

// Push jobs
echo "Pushing orders to database queue...\n";
for ($i = 1; $i <= 10; $i++) {
    $queue->push('process-order', ['order_id' => $i]);
}

// Check queue depth
$status = $queue->status();
echo "Queue depth: {$status['depth']} jobs\n\n";

echo "Starting workers...\n";
$queue->work();

echo "\nAll orders processed!\n";

// Show final metrics
$metrics = $queue->metrics()->toArray();
echo "\nMetrics:\n";
echo "- Completed: {$metrics['completed']}\n";
echo "- Failed: {$metrics['failed']}\n";
echo "- Avg duration: {$metrics['avg_duration']}s\n";
echo "- Throughput: {$metrics['throughput']} jobs/sec\n";
