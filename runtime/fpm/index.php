<?php
$GLOBALS['__nexph_start'] = microtime(true);

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' data: https://fonts.gstatic.com; connect-src 'self' https://cdn.jsdelivr.net https://unpkg.com; frame-ancestors 'none';");

set_exception_handler(function (\Throwable $e) {
    http_response_code(500);
    if (!headers_sent())
        header('Content-Type: application/json');
    $isDev = ($_ENV['APP_ENV'] ?? 'production') === 'development';
    
    if ($isDev) {
        echo json_encode([
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ], JSON_PRETTY_PRINT);
    } else {
        echo json_encode(['error' => 'Internal Server Error']);
        error_log($e->getMessage() . "\n" . $e->getTraceAsString());
    }
    exit;
});

register_shutdown_function(function () {
    if (!isset($_SERVER['REQUEST_TIME_FLOAT'])) {
        return;
    }
    $ms = round((microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) * 1000, 2);
    $data = [
        'time' => $ms,
        'memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
        'status' => http_response_code(),
        'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
        'uri' => $_SERVER['REQUEST_URI'] ?? '/',
        'timestamp' => microtime(true),
    ];
    $metricsFile = sys_get_temp_dir() . '/nexph_metrics.json';
    file_put_contents($metricsFile, json_encode($data, JSON_PRETTY_PRINT));
    chmod($metricsFile, 0600);
});

require_once __DIR__ . '/../autoload.php';

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
use Core\Health\HealthCheck;

Config::loadEnv(__DIR__ . '/../.env');
Config::load(__DIR__ . '/../config/app.php');

Session::configure([
    'driver' => 'file',
    'lifetime' => 7200,
    'path' => __DIR__ . '/../storage/sessions',
    'cookie_name' => 'nexph_session',
    'cookie_secure' => true,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'regenerate_interval' => 300,
]);

if ($dbConfig = Config::get('db')) {
    DB::connect($dbConfig);
}

$router = new Router();
$request = new Request();
$response = new Response();

if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
    QueryLogger::enable();
}

$allMeta = Metadata::all();
$apiPolicyFile = __DIR__ . '/../config/api.json';
if (!file_exists($apiPolicyFile)) {
    $legacyApiPolicy = __DIR__ . '/../config/api.php';
    $legacyConfig = file_exists($legacyApiPolicy) ? require $legacyApiPolicy : [];
    ApiPolicy::saveJson($apiPolicyFile, $legacyConfig ?: []);
}
$apiPolicy = ApiPolicy::fromFile($apiPolicyFile);

// OPTIMIZATION: Only register API routes (always needed)
foreach ($allMeta as $meta) {
    ApiGenerator::register($router, $meta, $apiPolicy);
}

// OPTIMIZATION: Lazy-load UI routes only for /admin requests
$uri = $_SERVER['REQUEST_URI'] ?? '/';
$isAdminRequest = str_starts_with($uri, '/admin');

if ($isAdminRequest) {
    foreach ($allMeta as $meta) {
        UiGenerator::register($router, $meta, $allMeta);
    }
}

$maxSize = 10 * 1024 * 1024;
if (($_SERVER['CONTENT_LENGTH'] ?? 0) > $maxSize) {
    http_response_code(413);
    echo json_encode(['error' => 'Request too large']);
    exit;
}

require __DIR__ . '/../routes/auth.php';
require __DIR__ . '/../routes/api.php';

// Health check routes
$healthRoutes = require __DIR__ . '/../routes/health.php';
$healthRoutes($router);

HealthCheck::init();

// OPTIMIZATION: Lazy-load admin routes only for /admin requests
if ($isAdminRequest) {
    require __DIR__ . '/../routes/admin.php';
}

if (($_ENV['APP_ENV'] ?? 'production') === 'development') {
    require __DIR__ . '/../routes/debug.php';
}

$router->dispatch($request, $response);
