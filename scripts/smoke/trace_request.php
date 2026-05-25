<?php
/**
 * Single Request Profiler - Traces one ORM request in detail
 */
error_reporting(0);
ini_set('display_errors', '0');

$GLOBALS['__profile'] = [];
$GLOBALS['__profile_start'] = microtime(true);

function prof(string $label): void {
    $GLOBALS['__profile'][] = [
        'label' => $label,
        'time' => microtime(true),
        'memory' => memory_get_usage(true)
    ];
}

prof('start');

// Intercept file_get_contents to track metadata reads
$GLOBALS['__file_reads'] = [];
$originalFileGetContents = 'file_get_contents';

// Patch Metadata class to track reads
require_once __DIR__ . '/autoload.php';
prof('autoload');

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
prof('config');

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
prof('session');

if ($dbConfig = Config::get('db')) {
    DB::connect($dbConfig);
}
prof('db_connect');

$router = new Router();
$request = new Request();
$response = new Response();
prof('objects');

QueryLogger::enable();

// Track metadata loading
$metaStart = microtime(true);
$allMeta = Metadata::all();
$metaEnd = microtime(true);
prof('metadata_all');
$GLOBALS['__meta_time'] = ($metaEnd - $metaStart) * 1000;

$apiPolicyFile = __DIR__ . '/config/api.json';
if (!file_exists($apiPolicyFile)) {
    $legacyApiPolicy = __DIR__ . '/config/api.php';
    $legacyConfig = file_exists($legacyApiPolicy) ? require $legacyApiPolicy : [];
    ApiPolicy::saveJson($apiPolicyFile, $legacyConfig ?: []);
}
$apiPolicy = ApiPolicy::fromFile($apiPolicyFile);
prof('policy');

foreach ($allMeta as $meta) {
    ApiGenerator::register($router, $meta, $apiPolicy);
    UiGenerator::register($router, $meta, $allMeta);
}
prof('generators');

require __DIR__ . '/routes/auth.php';
require __DIR__ . '/routes/api.php';
require __DIR__ . '/routes/admin.php';
if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
    require __DIR__ . '/routes/debug.php';
}
prof('routes');

// Simulate the actual request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/api/task';
$_SERVER['QUERY_STRING'] = '';

prof('pre_dispatch');

// Wrap dispatch to catch exit
$dispatchStart = microtime(true);
ob_start();

// Manually trace through the dispatch
$method = 'GET';
$uri = '/api/task';

// Get compiled routes (this happens in Router::dispatch)
$reflection = new ReflectionClass($router);
$compileMethod = $reflection->getMethod('compileRoutes');

$compileStart = microtime(true);
$compiled = $compileMethod->invoke($router);
$compileEnd = microtime(true);
prof('routes_compiled');
$GLOBALS['__compile_time'] = ($compileEnd - $compileStart) * 1000;

// Find matching route
$matchStart = microtime(true);
$matched = null;
foreach ($compiled as $route) {
    if ($route['method'] === $method && preg_match($route['pattern'], $uri, $matches)) {
        $matched = $route;
        break;
    }
}
$matchEnd = microtime(true);
prof('route_matched');
$GLOBALS['__match_time'] = ($matchEnd - $matchStart) * 1000;

if ($matched) {
    $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
    
    // Execute middleware
    $mwStart = microtime(true);
    foreach ($matched['middleware'] as $mw) {
        $mw($request, $response, $params);
    }
    $mwEnd = microtime(true);
    prof('middleware_executed');
    $GLOBALS['__middleware_time'] = ($mwEnd - $mwStart) * 1000;
    
    // Execute handler
    $handlerStart = microtime(true);
    try {
        if (is_callable($matched['handler'])) {
            $matched['handler']($request, $response, $params);
        }
    } catch (\Exception $e) {
        // Catch exit
    }
    $handlerEnd = microtime(true);
    prof('handler_executed');
    $GLOBALS['__handler_time'] = ($handlerEnd - $handlerStart) * 1000;
}

$output = ob_get_clean();
$dispatchEnd = microtime(true);
prof('dispatch_complete');
$GLOBALS['__dispatch_time'] = ($dispatchEnd - $dispatchStart) * 1000;

// Get query log
$queries = QueryLogger::getQueries();

// Calculate timeline
$results = [];
$start = $GLOBALS['__profile'][0];
for ($i = 1; $i < count($GLOBALS['__profile']); $i++) {
    $prev = $GLOBALS['__profile'][$i - 1];
    $curr = $GLOBALS['__profile'][$i];
    $results[] = [
        'step' => $curr['label'],
        'time_ms' => round(($curr['time'] - $prev['time']) * 1000, 3),
        'cumulative_ms' => round(($curr['time'] - $start['time']) * 1000, 3),
    ];
}

$totalTime = round((microtime(true) - $GLOBALS['__profile_start']) * 1000, 3);

echo "\n=== SINGLE REQUEST TRACE ===\n";
echo "Total Time: {$totalTime}ms\n";
echo "Output Size: " . strlen($output) . " bytes\n\n";

echo "=== TIMELINE ===\n";
printf("%-25s %12s %15s\n", "Step", "Time (ms)", "Cumulative");
echo str_repeat("-", 55) . "\n";
foreach ($results as $r) {
    printf("%-25s %12.3f %15.3f\n", $r['step'], $r['time_ms'], $r['cumulative_ms']);
}

echo "\n=== CRITICAL PATH BREAKDOWN ===\n";
echo "Metadata::all():      " . round($GLOBALS['__meta_time'], 3) . "ms\n";
echo "Route Compilation:    " . round($GLOBALS['__compile_time'], 3) . "ms\n";
echo "Route Matching:       " . round($GLOBALS['__match_time'], 3) . "ms\n";
echo "Middleware:           " . round($GLOBALS['__middleware_time'], 3) . "ms\n";
echo "Handler Execution:    " . round($GLOBALS['__handler_time'], 3) . "ms\n";
echo "Total Dispatch:       " . round($GLOBALS['__dispatch_time'], 3) . "ms\n";

echo "\n=== DATABASE QUERIES ===\n";
echo "Total Queries: " . count($queries) . "\n";
$totalQueryTime = 0;
foreach ($queries as $q) {
    $totalQueryTime += $q['time'];
    echo sprintf("[%.3fms] %s\n", $q['time'] * 1000, $q['sql']);
}
echo "Total Query Time: " . round($totalQueryTime * 1000, 3) . "ms\n";

echo "\n=== BOTTLENECK ANALYSIS ===\n";
$bootstrap = $results[array_search('pre_dispatch', array_column($results, 'step'))]['cumulative_ms'] ?? 0;
$handlerPct = round(($GLOBALS['__handler_time'] / $totalTime) * 100, 1);
$bootstrapPct = round(($bootstrap / $totalTime) * 100, 1);
$queryPct = round(($totalQueryTime * 1000 / $totalTime) * 100, 1);

echo "Bootstrap Time:       {$bootstrap}ms ({$bootstrapPct}%)\n";
echo "Handler Time:         " . round($GLOBALS['__handler_time'], 3) . "ms ({$handlerPct}%)\n";
echo "Query Time:           " . round($totalQueryTime * 1000, 3) . "ms ({$queryPct}%)\n";
echo "Other (serialization): " . round($totalTime - $bootstrap - $GLOBALS['__handler_time'], 3) . "ms\n";

echo "\n=== RESPONSE PREVIEW ===\n";
echo substr($output, 0, 500) . "\n";
