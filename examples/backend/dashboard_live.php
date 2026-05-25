#!/usr/bin/env php
<?php
/**
 * Dashboard live view.
 * Real-time terminal dashboard for runtime monitoring.
 */

require_once __DIR__ . '/../autoload.php';

use Core\Runtime\Observability\RuntimeMetrics;
use Core\Runtime\Observability\HealthMonitor;
use Core\Runtime\Observability\Dashboard;
use Core\Runtime\Queue\QueueFactory;

$refreshInterval = (int)($argv[1] ?? 2);
$driver = $argv[2] ?? getenv('QUEUE_DRIVER') ?: 'file';

echo "Starting dashboard (refresh every {$refreshInterval}s)...\n";
echo "Press Ctrl+C to stop\n\n";

sleep(1);

$metrics = new RuntimeMetrics();
$health = new HealthMonitor();
$dashboard = new Dashboard($metrics, $health);

// Register queue if available
try {
    $queue = QueueFactory::create($driver);
    $dashboard->registerQueue('default', $queue);
} catch (\Throwable $e) {
    // Queue not available
}

// Signal handling
$shouldStop = false;

if (function_exists('pcntl_signal')) {
    pcntl_async_signals(true);
    
    $handler = function() use (&$shouldStop) {
        $shouldStop = true;
    };
    
    pcntl_signal(SIGTERM, $handler);
    pcntl_signal(SIGINT, $handler);
}

// Main loop
while (!$shouldStop) {
    // Clear screen and move cursor to top
    echo "\033[2J\033[H";
    
    // Update metrics
    $metrics->updateMemory();
    
    // Render dashboard
    echo $dashboard->render();
    
    echo "\n";
    echo "Refreshing every {$refreshInterval}s... (Ctrl+C to stop)\n";
    
    // Sleep
    sleep($refreshInterval);
}

echo "\nDashboard stopped\n";
exit(0);
