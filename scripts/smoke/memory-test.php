<?php
// Memory leak detection test script
// Usage: php scripts/memory-test.php

require_once __DIR__ . '/../autoload.php';

use Core\Runtime\MemoryLeakDetector;
use Core\Runtime\MemoryMonitor;

echo "=== Nexph Memory Leak Detection ===\n\n";

// Test 1: MemoryMonitor (runtime sampling)
echo "1. MemoryMonitor - Runtime Sampling\n";
$monitor = new MemoryMonitor();
$monitor->setThreshold(512 * 1024); // 512KB threshold

$leakyArray = [];
for ($i = 0; $i < 30; $i++) {
    $leakyArray[] = str_repeat('x', 50000); // Simulate leak
    $monitor->sample();
    usleep(10000);
}

if ($monitor->detectLeak()) {
    echo "   ⚠ Leak detected: " . $monitor->getReport() . "\n";
} else {
    echo "   ✓ No leak detected\n";
}
echo "   Stats: " . json_encode($monitor->getStats()) . "\n\n";

unset($leakyArray);
gc_collect_cycles();

// Test 2: MemoryLeakDetector (detailed analysis)
echo "2. MemoryLeakDetector - Detailed Analysis\n";
$detector = new MemoryLeakDetector();

$result = $detector->track(function () {
    // Simulate work
    $data = [];
    for ($i = 0; $i < 100; $i++) {
        $data[] = ['id' => $i, 'value' => str_repeat('a', 1000)];
    }
    // Proper cleanup
    unset($data);
}, 10);

echo "   Memory diff: {$result['memory_diff_human']}\n";
echo "   Per iteration: {$result['per_iteration']}\n";
echo "   Trend: {$result['trend']}\n";
if (!empty($result['suspects'])) {
    echo "   Suspects:\n";
    foreach ($result['suspects'] as $s) {
        echo "     - [{$s['severity']}] {$s['type']}: " . ($s['detail'] ?? $s['class'] ?? '') . "\n";
    }
}

echo "\n3. Simulated Leak Test\n";
$detector->reset();

// Intentional leak simulation
$leaked = [];
$result = $detector->track(function () use (&$leaked) {
    $leaked[] = str_repeat('LEAK', 10000);
}, 20);

echo "   Memory diff: {$result['memory_diff_human']}\n";
echo "   Per iteration: {$result['per_iteration']}\n";
echo "   Trend: {$result['trend']}\n";

echo "\n=== Test Complete ===\n";
