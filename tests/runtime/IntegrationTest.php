<?php
/**
 * Integration test for runtime layer.
 * 
 * Tests all core runtime features.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Runtime;
use Core\Core\Runtime\Channel;
use Core\Core\Runtime\Timer;
use Core\Core\Runtime\Worker;

echo "=== Nexph Runtime Integration Test ===\n\n";

// Test 1: Capability Detection
echo "Test 1: Capability Detection\n";
Runtime::init();
$available = Runtime::available();
echo "  Runtime available: " . ($available ? "YES" : "NO") . "\n";

if (!$available) {
    echo "\n✗ Runtime not available. Tests require CLI mode and PHP 8.1+\n";
    exit(1);
}

$caps = Runtime::capabilities();
echo "  Fibers: " . ($caps['fibers'] ? "✓" : "✗") . "\n";
echo "  CLI: " . ($caps['cli'] ? "✓" : "✗") . "\n";
echo "  PCNTL: " . ($caps['pcntl'] ? "✓" : "✗") . "\n";
echo "  Sockets: " . ($caps['sockets'] ? "✓" : "✗") . "\n";
echo "  ✓ Passed\n\n";

// Test 2: Basic Coroutine
echo "Test 2: Basic Coroutine\n";
$executed = false;
Runtime::spawn(function() use (&$executed) {
    $executed = true;
});
Runtime::run();
echo "  Coroutine executed: " . ($executed ? "✓" : "✗") . "\n";
if (!$executed) {
    echo "  ✗ Failed\n\n";
    exit(1);
}
echo "  ✓ Passed\n\n";

// Test 3: Cooperative Sleep
echo "Test 3: Cooperative Sleep\n";
$start = microtime(true);
Runtime::spawn(function() {
    Runtime::sleep(0.1);
});
Runtime::run();
$elapsed = microtime(true) - $start;
echo "  Sleep duration: " . round($elapsed * 1000, 1) . "ms\n";
if ($elapsed < 0.09 || $elapsed > 0.15) {
    echo "  ✗ Failed (expected ~100ms)\n\n";
    exit(1);
}
echo "  ✓ Passed\n\n";

// Test 4: Multiple Coroutines
echo "Test 4: Multiple Coroutines\n";
$count = 0;
for ($i = 0; $i < 5; $i++) {
    Runtime::spawn(function() use (&$count) {
        $count++;
    });
}
Runtime::run();
echo "  Coroutines executed: {$count}/5\n";
if ($count !== 5) {
    echo "  ✗ Failed\n\n";
    exit(1);
}
echo "  ✓ Passed\n\n";

// Test 5: Channel Communication
echo "Test 5: Channel Communication\n";
$ch = new Channel(5);
$received = [];

Runtime::spawn(function() use ($ch) {
    for ($i = 1; $i <= 3; $i++) {
        $ch->send($i);
    }
    $ch->close();
});

Runtime::spawn(function() use ($ch, &$received) {
    while (($val = $ch->receive()) !== null) {
        $received[] = $val;
    }
});

Runtime::run();
echo "  Messages received: " . count($received) . "/3\n";
echo "  Values: [" . implode(", ", $received) . "]\n";
if (count($received) !== 3 || $received !== [1, 2, 3]) {
    echo "  ✗ Failed\n\n";
    exit(1);
}
echo "  ✓ Passed\n\n";

// Test 6: Timers
echo "Test 6: Timers\n";
$timerFired = false;

Runtime::spawn(function() use (&$timerFired) {
    Timer::after(0.05, function() use (&$timerFired) {
        $timerFired = true;
    });
    
    Runtime::sleep(0.1);
    // Don't call stop - let loop finish naturally when no more work
});

Runtime::run();
echo "  Timer fired: " . ($timerFired ? "✓" : "✗") . "\n";
if (!$timerFired) {
    echo "  ✗ Failed\n\n";
    exit(1);
}
echo "  ✓ Passed\n\n";

// Test 7: Coroutine Return Value
echo "Test 7: Coroutine Return Value\n";
$coro = Runtime::spawn(function() {
    Runtime::sleep(0.05);
    return 42;
});
$result = $coro->await();
echo "  Return value: {$result}\n";
if ($result !== 42) {
    echo "  ✗ Failed\n\n";
    exit(1);
}
echo "  ✓ Passed\n\n";

// Test 8: Parallel Execution
echo "Test 8: Parallel Execution\n";
$start = microtime(true);
$tasks = [];
for ($i = 0; $i < 3; $i++) {
    $tasks[] = Runtime::spawn(function() {
        Runtime::sleep(0.1);
    });
}
foreach ($tasks as $task) {
    $task->await();
}
$elapsed = microtime(true) - $start;
echo "  Parallel execution time: " . round($elapsed * 1000, 1) . "ms\n";
if ($elapsed > 0.2) { // Should be ~100ms, not 300ms
    echo "  ✗ Failed (not parallel)\n\n";
    exit(1);
}
echo "  ✓ Passed\n\n";

// Test 9: Error Handling
echo "Test 9: Error Handling\n";
$errorHandled = false;
Runtime::spawn(function() use (&$errorHandled) {
    try {
        throw new \Exception("Test error");
    } catch (\Throwable $e) {
        $errorHandled = true;
    }
});
Runtime::run();
echo "  Error handled: " . ($errorHandled ? "✓" : "✗") . "\n";
if (!$errorHandled) {
    echo "  ✗ Failed\n\n";
    exit(1);
}
echo "  ✓ Passed\n\n";

// Test 10: Yield
echo "Test 10: Yield\n";
$order = [];
Runtime::spawn(function() use (&$order) {
    $order[] = 1;
    Runtime::yield();
    $order[] = 3;
});
Runtime::spawn(function() use (&$order) {
    $order[] = 2;
});
Runtime::run();
echo "  Execution order: [" . implode(", ", $order) . "]\n";
if ($order !== [1, 2, 3]) {
    echo "  ✗ Failed\n\n";
    exit(1);
}
echo "  ✓ Passed\n\n";

echo "=== All Tests Passed ✓ ===\n";
echo "\nRuntime layer is working correctly!\n";
