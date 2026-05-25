<?php
/**
 * Basic queue usage example.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Runtime\Queue\QueueFactory;

// Create queue with memory driver
$queue = QueueFactory::createWithDriver('memory', [
    'workers' => 2,
]);

// Register simple job handler
$queue->register('greet', function($payload, $job) {
    echo "Hello, {$payload['name']}!\n";
    sleep(1); // Simulate work
    return ['greeted' => $payload['name']];
});

// Register another handler
$queue->register('calculate', function($payload, $job) {
    $result = $payload['a'] + $payload['b'];
    echo "Calculation: {$payload['a']} + {$payload['b']} = {$result}\n";
    return ['result' => $result];
});

// Push some jobs
echo "Pushing jobs...\n";
$queue->push('greet', ['name' => 'Alice']);
$queue->push('greet', ['name' => 'Bob']);
$queue->push('calculate', ['a' => 5, 'b' => 3]);
$queue->push('greet', ['name' => 'Charlie']);
$queue->push('calculate', ['a' => 10, 'b' => 20]);

echo "Starting workers...\n";

// Start workers (will run until queue is empty)
$queue->work();

echo "All jobs completed!\n";
