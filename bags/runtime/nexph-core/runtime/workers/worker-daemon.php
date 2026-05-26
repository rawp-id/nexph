<?php
// Nexph Worker Daemon
// Usage: php worker-daemon.php [--batch=5] [--sleep=1000]

require_once __DIR__ . '/autoload.php';

use Core\Support\Config;
use Core\Database\DB;
use Core\Queue\WorkerDaemon;
use Core\Queue\JobHandler;

Config::loadEnv(__DIR__ . '/.env');
Config::load(__DIR__ . '/config/app.php');

if ($dbConfig = Config::get('db')) {
    DB::connect($dbConfig);
}

// Parse CLI args
$options = getopt('', ['batch::', 'sleep::']);
$batch = (int) ($options['batch'] ?? 5);
$sleep = (int) ($options['sleep'] ?? 1000);

$worker = new WorkerDaemon('nexph-worker');
$worker->setBatchSize($batch)->setSleepMs($sleep);

// Register handlers
$handlersDir = __DIR__ . '/app/Jobs';
if (is_dir($handlersDir)) {
    foreach (glob($handlersDir . '/*.php') as $file) {
        require_once $file;
        $class = 'App\\Jobs\\' . basename($file, '.php');
        if (class_exists($class) && defined("$class::NAME")) {
            $worker->register($class::NAME, $class);
        }
    }
}

// Example handlers
$worker->register('send_email', \Core\Queue\JobHandler::class);
$worker->register('process_upload', \Core\Queue\JobHandler::class);

echo "Starting worker daemon...\n";
echo "Batch size: {$batch}, Sleep: {$sleep}ms\n";
echo "Press Ctrl+C to stop\n\n";

$worker->run();
