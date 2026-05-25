<?php
/**
 * Profiled API Endpoint - Direct measurement
 */

$GLOBALS['__prof'] = ['start' => microtime(true)];

function prof_mark($label) {
    $GLOBALS['__prof'][$label] = microtime(true);
}

prof_mark('script_start');

require_once __DIR__ . '/autoload.php';
prof_mark('autoload');

use Core\Support\Config;
use Core\Database\DB;
use Core\Database\Metadata;
use Core\Database\QueryLogger;
use Core\Database\FieldControl;

Config::loadEnv(__DIR__ . '/.env');
Config::load(__DIR__ . '/config/app.php');
prof_mark('config');

if ($dbConfig = Config::get('db')) {
    DB::connect($dbConfig);
}
prof_mark('db_connect');

QueryLogger::enable();

// Load metadata for task table
prof_mark('pre_metadata');
$meta = Metadata::load('task');
prof_mark('metadata_loaded');

// Simulate the API request logic from ApiGenerator
$table = 'task';
$params = [];

prof_mark('pre_query_build');
$fields = array_column($meta['fields'], 'name');
$fields[] = 'id';
$where = [];
$values = [];

foreach ($params as $key => $val) {
    if (in_array($key, ['limit', 'offset'], true)) {
        continue;
    }
    if (!is_scalar($val)) {
        continue;
    }
    if (in_array($key, $fields, true)) {
        $where[] = "`{$key}` = ?";
        $values[] = $val;
    }
}

$selectFields = array_filter($fields, fn($f) => $f !== 'password');
$selectCols = implode(', ', array_map(fn($f) => "`{$f}`", $selectFields));
$sql = "SELECT {$selectCols} FROM `{$table}`";
if ($where) $sql .= " WHERE " . implode(' AND ', $where);
$limit = 20;
$offset = 0;
$sql .= " LIMIT {$limit} OFFSET {$offset}";
prof_mark('query_built');

// Execute query
prof_mark('pre_query');
$data = DB::query($sql, $values);
prof_mark('query_executed');

// Apply transformations
prof_mark('pre_transform');
$transformed = [];
foreach ($data as $row) {
    $hidden = FieldControl::applyHidden($row, $meta);
    $casted = FieldControl::applyCasts($hidden, $meta);
    $transformed[] = $casted;
}
prof_mark('transform_complete');

// JSON encode
prof_mark('pre_json');
$json = json_encode(['data' => $transformed], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
prof_mark('json_encoded');

// Get queries
$queries = QueryLogger::getQueries();

// Calculate timings
$timings = [];
$labels = array_keys($GLOBALS['__prof']);
for ($i = 1; $i < count($labels); $i++) {
    $prev = $labels[$i - 1];
    $curr = $labels[$i];
    $timings[$curr] = round(($GLOBALS['__prof'][$curr] - $GLOBALS['__prof'][$prev]) * 1000, 3);
}

$total = round((microtime(true) - $GLOBALS['__prof']['start']) * 1000, 3);

// Output profiling data
echo "\n=== API REQUEST PROFILE ===\n";
echo "Total Time: {$total}ms\n";
echo "Result Count: " . count($data) . " rows\n";
echo "JSON Size: " . strlen($json) . " bytes\n\n";

echo "=== TIMING BREAKDOWN ===\n";
printf("%-25s %12s\n", "Phase", "Time (ms)");
echo str_repeat("-", 40) . "\n";
foreach ($timings as $label => $time) {
    printf("%-25s %12.3f\n", $label, $time);
}

echo "\n=== QUERY LOG ===\n";
foreach ($queries as $q) {
    echo sprintf("[%.3fms] %s\n", $q['time'] * 1000, $q['sql']);
}

echo "\n=== PERCENTAGE BREAKDOWN ===\n";
foreach ($timings as $label => $time) {
    $pct = round(($time / $total) * 100, 1);
    echo sprintf("%-25s: %5.1f%%\n", $label, $pct);
}

echo "\n=== CRITICAL ANALYSIS ===\n";
$metadataTime = $timings['metadata_loaded'] ?? 0;
$queryTime = $timings['query_executed'] ?? 0;
$transformTime = $timings['transform_complete'] ?? 0;
$jsonTime = $timings['json_encoded'] ?? 0;

echo "Metadata Loading:     {$metadataTime}ms\n";
echo "Query Execution:      {$queryTime}ms\n";
echo "Data Transformation:  {$transformTime}ms\n";
echo "JSON Encoding:        {$jsonTime}ms\n";

echo "\n=== RESPONSE PREVIEW ===\n";
echo substr($json, 0, 200) . "...\n";
