<?php
/**
 * Example: Parallel Tasks
 * 
 * Demonstrates running multiple tasks concurrently and waiting for all.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Runtime\Runtime;
use Core\Runtime\Coroutine;

if (!Runtime::available()) {
    die("Runtime requires CLI mode and PHP 8.1+ Fibers\n");
}

echo "=== Parallel Tasks Example ===\n\n";

/**
 * Simulate async task.
 */
function asyncTask(int $id, float $duration): Coroutine {
    return Runtime::spawn(function() use ($id, $duration) {
        echo "[Task {$id}] Starting (will take {$duration}s)\n";
        Runtime::sleep($duration);
        echo "[Task {$id}] Completed\n";
        return "Result from task {$id}";
    });
}

// Spawn multiple tasks
$tasks = [
    asyncTask(1, 1.0),
    asyncTask(2, 0.5),
    asyncTask(3, 1.5),
    asyncTask(4, 0.8),
    asyncTask(5, 1.2),
];

echo "All tasks spawned, starting event loop...\n\n";

// Run event loop in background
Runtime::spawn(function() use ($tasks) {
    // Wait for all tasks
    $results = [];
    foreach ($tasks as $i => $task) {
        $results[$i] = $task->await();
    }
    
    echo "\n=== All Tasks Complete ===\n";
    foreach ($results as $i => $result) {
        echo "Task " . ($i + 1) . ": {$result}\n";
    }
    
    Runtime::stop();
});

$start = microtime(true);
Runtime::run();
$elapsed = round(microtime(true) - $start, 2);

echo "\nTotal time: {$elapsed}s (parallel execution)\n";
echo "Sequential would take: " . (1.0 + 0.5 + 1.5 + 0.8 + 1.2) . "s\n";
