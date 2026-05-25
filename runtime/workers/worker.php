<?php
require_once __DIR__ . '/autoload.php';

use Core\Support\Config;
use Core\Database\DB;
use Core\Queue\Worker;

Config::load(__DIR__ . '/config/app.php');
DB::connect(Config::get('db'));

$isDaemon = in_array('--daemon', $argv);

if ($isDaemon) {
    echo "Worker running in daemon mode...\n";
    while (true) {
        Worker::run();
        sleep(1);
    }
} else {
    echo "Worker starting (single run)...\n";
    Worker::run();
    echo "Worker finished.\n";
}
