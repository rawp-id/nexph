#!/usr/bin/env php
<?php
require_once __DIR__ . '/../autoload.php';

use Core\Support\CacheBenchmark;

$iterations = isset($argv[1]) ? (int)$argv[1] : 1000;

echo "Running cache benchmark with {$iterations} iterations...\n\n";

$results = CacheBenchmark::run($iterations);
CacheBenchmark::display($results);
