<?php
/**
 * Performance Profiler for Nexph ORM Integration
 * Measures request lifecycle step-by-step to identify bottlenecks
 */

$GLOBALS['__nexph_start'] = microtime(true);
$GLOBALS['__profile'] = [];

function profile_mark(string $label): void {
    $GLOBALS['__profile'][] = [
        'label' => $label,
        'time' => microtime(true),
        'memory' => memory_get_usage(true)
    ];
}

profile_mark('script_start');

require_once __DIR__ . '/autoload.php';
profile_mark('autoload_complete');

use Core\Support\Config;
use Core\Database\DB;
use Core\Database\Metadata;
use Core\Database\QueryLogger;
use Core\Generator\ApiGenerator;
use Core\UI\UiGenerator;
use Core\Http\Router;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\ApiPolicy;
use Core\Auth\Session;

Config::loadEnv(__DIR__ . '/.env');
Config::load(__DIR__ . '/config/app.php');
profile_mark('config_loaded');

Session::configure([
    'driver' => 'file',
    'lifetime' => 7200,
    'path' => __DIR__ . '/storage/sessions',
    'cookie_name' => 'nexph_session',
    'cookie_secure' => false,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'regenerate_interval' => 300,
]);
profile_mark('session_configured');

if ($dbConfig = Config::get('db')) {
    DB::connect($dbConfig);
}
profile_mark('db_connected');

$router = new Router();
$request = new Request();
$response = new Response();
profile_mark('core_objects_created');

QueryLogger::enable();
profile_mark('query_logger_enabled');

// CRITICAL: Metadata loading
$metaStart = microtime(true);
$allMeta = Metadata::all();
$metaEnd = microtime(true);
profile_mark('metadata_all_loaded');
$GLOBALS['__profile_metadata_time'] = ($metaEnd - $metaStart) * 1000;
$GLOBALS['__profile_metadata_count'] = count($allMeta);

// CRITICAL: API Policy loading
$apiPolicyFile = __DIR__ . '/config/api.json';
if (!file_exists($apiPolicyFile)) {
    $legacyApiPolicy = __DIR__ . '/config/api.php';
    $legacyConfig = file_exists($legacyApiPolicy) ? require $legacyApiPolicy : [];
    ApiPolicy::saveJson($apiPolicyFile, $legacyConfig ?: []);
}
$policyStart = microtime(true);
$apiPolicy = ApiPolicy::fromFile($apiPolicyFile);
$policyEnd = microtime(true);
profile_mark('api_policy_loaded');
$GLOBALS['__profile_policy_time'] = ($policyEnd - $policyStart) * 1000;

// CRITICAL: API Generator registration (loops through all metadata)
$genStart = microtime(true);
foreach ($allMeta as $meta) {
    $genMetaStart = microtime(true);
    ApiGenerator::register($router, $meta, $apiPolicy);
    $genMetaEnd = microtime(true);
    $GLOBALS['__profile_api_gen'][$meta['table']] = ($genMetaEnd - $genMetaStart) * 1000;
    
    $uiMetaStart = microtime(true);
    UiGenerator::register($router, $meta, $allMeta);
    $uiMetaEnd = microtime(true);
    $GLOBALS['__profile_ui_gen'][$meta['table']] = ($uiMetaEnd - $uiMetaStart) * 1000;
}
$genEnd = microtime(true);
profile_mark('generators_registered');
$GLOBALS['__profile_generator_time'] = ($genEnd - $genStart) * 1000;

require __DIR__ . '/routes/auth.php';
profile_mark('auth_routes_loaded');
require __DIR__ . '/routes/api.php';
profile_mark('api_routes_loaded');
require __DIR__ . '/routes/admin.php';
profile_mark('admin_routes_loaded');

if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
    require __DIR__ . '/routes/debug.php';
    profile_mark('debug_routes_loaded');
}

profile_mark('routes_complete');

// Simulate request dispatch
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api/task';

$dispatchStart = microtime(true);
ob_start();
try {
    $router->dispatch($request, $response);
} catch (\Exception $e) {
    // Catch any exit() calls
}
$output = ob_get_clean();
$dispatchEnd = microtime(true);
profile_mark('dispatch_complete');
$GLOBALS['__profile_dispatch_time'] = ($dispatchEnd - $dispatchStart) * 1000;
$GLOBALS['__profile_output_size'] = strlen($output);

// Calculate deltas
$results = [];
$start = $GLOBALS['__profile'][0];
for ($i = 1; $i < count($GLOBALS['__profile']); $i++) {
    $prev = $GLOBALS['__profile'][$i - 1];
    $curr = $GLOBALS['__profile'][$i];
    $results[] = [
        'step' => $curr['label'],
        'time_ms' => round(($curr['time'] - $prev['time']) * 1000, 3),
        'memory_mb' => round($curr['memory'] / 1024 / 1024, 3),
        'memory_delta_kb' => round(($curr['memory'] - $prev['memory']) / 1024, 2)
    ];
}

$totalTime = round((microtime(true) - $GLOBALS['__nexph_start']) * 1000, 3);
$peakMemory = round(memory_get_peak_usage(true) / 1024 / 1024, 3);

echo "\n=== NEXPH PERFORMANCE PROFILE ===\n\n";
echo "Total Time: {$totalTime}ms\n";
echo "Peak Memory: {$peakMemory}MB\n";
echo "Metadata Files: {$GLOBALS['__profile_metadata_count']}\n";
echo "Metadata Load Time: " . round($GLOBALS['__profile_metadata_time'], 3) . "ms\n";
echo "API Policy Load Time: " . round($GLOBALS['__profile_policy_time'], 3) . "ms\n";
echo "Generator Registration Time: " . round($GLOBALS['__profile_generator_time'], 3) . "ms\n";
echo "Request Dispatch Time: " . round($GLOBALS['__profile_dispatch_time'], 3) . "ms\n\n";

echo "=== STEP-BY-STEP BREAKDOWN ===\n";
printf("%-35s %12s %12s %15s\n", "Step", "Time (ms)", "Memory (MB)", "Delta (KB)");
echo str_repeat("-", 80) . "\n";
foreach ($results as $r) {
    printf("%-35s %12.3f %12.3f %15.2f\n", $r['step'], $r['time_ms'], $r['memory_mb'], $r['memory_delta_kb']);
}

echo "\n=== API GENERATOR PER TABLE ===\n";
foreach ($GLOBALS['__profile_api_gen'] as $table => $time) {
    echo sprintf("%-20s: %8.3fms\n", $table, $time);
}

echo "\n=== UI GENERATOR PER TABLE ===\n";
foreach ($GLOBALS['__profile_ui_gen'] as $table => $time) {
    echo sprintf("%-20s: %8.3fms\n", $table, $time);
}

echo "\n=== QUERY LOG ===\n";
$queries = QueryLogger::getQueries();
echo "Total Queries: " . count($queries) . "\n";
foreach ($queries as $q) {
    echo sprintf("[%.3fms] %s\n", $q['time'] * 1000, $q['sql']);
}

echo "\n=== ANALYSIS ===\n";
$bootTime = 0;
foreach ($results as $r) {
    if ($r['step'] === 'dispatch_complete') break;
    $bootTime += $r['time_ms'];
}
echo "Bootstrap Time (before dispatch): " . round($bootTime, 3) . "ms\n";
echo "Dispatch Time: " . round($GLOBALS['__profile_dispatch_time'], 3) . "ms\n";
echo "Bootstrap %: " . round(($bootTime / $totalTime) * 100, 1) . "%\n";
echo "Dispatch %: " . round(($GLOBALS['__profile_dispatch_time'] / $totalTime) * 100, 1) . "%\n";
