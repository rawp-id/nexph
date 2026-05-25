<?php
/**
 * Example: Worker Process
 * 
 * Demonstrates long-running worker with graceful shutdown.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Runtime\Worker;
use Core\Runtime\Runtime;

if (!Runtime::available()) {
    die("Runtime requires CLI mode and PHP 8.1+ Fibers\n");
}

echo "=== Worker Process Example ===\n";
echo "Press Ctrl+C to stop gracefully\n\n";

$iteration = 0;

Worker::start(function() use (&$iteration) {
    $iteration++;
    echo "[Worker] Iteration {$iteration} at " . date('H:i:s') . "\n";
    
    // Simulate work
    Runtime::sleep(0.5);
    
    // Simulate occasional error
    if ($iteration % 10 === 0) {
        throw new \Exception("Simulated error at iteration {$iteration}");
    }
}, [
    'sleep' => 1.0,
    'max_iterations' => 20, // Stop after 20 iterations
]);

echo "\nWorker stopped\n";
