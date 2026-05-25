<?php
/**
 * Cache warming script - preload metadata and routes
 */

require_once __DIR__ . '/autoload.php';

use Core\Database\Metadata;
use Core\Http\Router;
use Core\Generator\ApiGenerator;
use Core\Http\ApiPolicy;

echo "Warming caches...\n";

// Warm metadata cache
$start = microtime(true);
$allMeta = Metadata::all();
$metaTime = round((microtime(true) - $start) * 1000, 2);
echo "  Metadata: {$metaTime}ms (" . count($allMeta) . " tables)\n";

// Warm route cache
$start = microtime(true);
$router = new Router();
$apiPolicy = ApiPolicy::fromFile(__DIR__ . '/config/api.json');
foreach ($allMeta as $meta) {
    ApiGenerator::register($router, $meta, $apiPolicy);
}
$routeTime = round((microtime(true) - $start) * 1000, 2);
echo "  Routes: {$routeTime}ms\n";

echo "Cache warming complete!\n";
