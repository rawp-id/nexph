<?php
/**
 * Instrumented profiler - patches core classes to measure performance
 */

// Start profiling
$GLOBALS['__prof_start'] = microtime(true);
$GLOBALS['__prof_marks'] = [];
$GLOBALS['__prof_metadata_reads'] = 0;
$GLOBALS['__prof_model_creates'] = 0;
$GLOBALS['__prof_qb_creates'] = 0;

function mark($label) {
    $GLOBALS['__prof_marks'][] = [
        'label' => $label,
        'time' => microtime(true) - $GLOBALS['__prof_start'],
        'memory' => memory_get_usage(true) / 1024 / 1024
    ];
}

mark('start');

// Monkey-patch approach: wrap the actual request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api/task';
$_SERVER['QUERY_STRING'] = '';

// Capture everything
ob_start();

mark('pre_bootstrap');

// Include the actual index.php but capture its output
include __DIR__ . '/public/index.php';

$output = ob_get_clean();

mark('request_complete');

// Now output profiling data
$total = microtime(true) - $GLOBALS['__prof_start'];

echo "\n\n=== REQUEST PROFILING RESULTS ===\n";
echo "Total Time: " . round($total * 1000, 3) . "ms\n";
echo "Peak Memory: " . round(memory_get_peak_usage(true) / 1024 / 1024, 2) . "MB\n";
echo "Output Size: " . strlen($output) . " bytes\n\n";

echo "=== TIMELINE ===\n";
printf("%-30s %12s %12s\n", "Checkpoint", "Time (ms)", "Memory (MB)");
echo str_repeat("-", 60) . "\n";
foreach ($GLOBALS['__prof_marks'] as $mark) {
    printf("%-30s %12.3f %12.2f\n", $mark['label'], $mark['time'] * 1000, $mark['memory']);
}

echo "\n=== RESPONSE PREVIEW ===\n";
echo substr($output, 0, 300) . "...\n";
