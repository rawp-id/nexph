<?php
/**
 * Example: Basic Coroutines
 * 
 * Demonstrates spawning multiple coroutines and cooperative scheduling.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Runtime\Runtime;

if (!Runtime::available()) {
    die("Runtime requires CLI mode and PHP 8.1+ Fibers\n");
}

echo "=== Basic Coroutines Example ===\n\n";

// Spawn multiple coroutines
Runtime::spawn(function() {
    echo "[Task 1] Starting\n";
    Runtime::sleep(1.0);
    echo "[Task 1] After 1 second\n";
    Runtime::sleep(1.0);
    echo "[Task 1] Finished\n";
});

Runtime::spawn(function() {
    echo "[Task 2] Starting\n";
    Runtime::sleep(0.5);
    echo "[Task 2] After 0.5 seconds\n";
    Runtime::sleep(0.5);
    echo "[Task 2] After 1 second\n";
    Runtime::sleep(0.5);
    echo "[Task 2] Finished\n";
});

Runtime::spawn(function() {
    for ($i = 1; $i <= 5; $i++) {
        echo "[Task 3] Tick {$i}\n";
        Runtime::sleep(0.3);
    }
    echo "[Task 3] Finished\n";
});

echo "Starting event loop...\n\n";
$start = microtime(true);

Runtime::run();

$elapsed = round(microtime(true) - $start, 2);
echo "\nEvent loop finished in {$elapsed}s\n";
