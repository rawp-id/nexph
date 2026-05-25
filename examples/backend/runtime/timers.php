<?php
/**
 * Example: Timers
 * 
 * Demonstrates one-shot and repeating timers.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Runtime\Runtime;
use Core\Runtime\Timer;

if (!Runtime::available()) {
    die("Runtime requires CLI mode and PHP 8.1+ Fibers\n");
}

echo "=== Timer Example ===\n\n";

$count = 0;

// One-shot timer
Timer::after(2.0, function() {
    echo "[Timer] One-shot fired after 2 seconds\n";
});

// Repeating timer
$repeatId = Timer::every(0.5, function() use (&$count) {
    $count++;
    echo "[Timer] Repeat #{$count} at " . date('H:i:s.u') . "\n";
    
    if ($count >= 5) {
        echo "[Timer] Stopping repeat timer\n";
        Timer::cancel($GLOBALS['repeatId']);
    }
});
$GLOBALS['repeatId'] = $repeatId;

// Deferred callback
Timer::defer(function() {
    echo "[Timer] Deferred callback (next tick)\n";
});

// Stop after 5 seconds
Timer::after(5.0, function() {
    echo "[Timer] Stopping event loop\n";
    Runtime::stop();
});

echo "Starting timers...\n\n";
Runtime::run();

echo "\nDone!\n";
