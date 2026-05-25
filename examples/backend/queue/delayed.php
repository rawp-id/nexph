<?php
/**
 * Delayed jobs example.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Runtime\Queue\QueueFactory;

// Create queue
$queue = QueueFactory::createWithDriver('memory', [
    'workers' => 1,
]);

// Register handler
$queue->register('reminder', function($payload, $job) {
    echo "[" . date('H:i:s') . "] Reminder: {$payload['message']}\n";
});

echo "Scheduling delayed jobs...\n";
echo "Current time: " . date('H:i:s') . "\n\n";

// Immediate job
$queue->push('reminder', ['message' => 'This runs immediately']);

// Delayed jobs
$queue->later(3, 'reminder', ['message' => 'This runs after 3 seconds']);
$queue->later(5, 'reminder', ['message' => 'This runs after 5 seconds']);
$queue->later(10, 'reminder', ['message' => 'This runs after 10 seconds']);

echo "Jobs scheduled. Starting worker...\n\n";

// Start worker
$queue->work();

echo "\nAll jobs completed!\n";
