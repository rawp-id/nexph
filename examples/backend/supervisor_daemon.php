#!/usr/bin/env php
<?php
/**
 * Supervisor daemon example.
 * Demonstrates worker supervision with auto-restart and health monitoring.
 */

require_once __DIR__ . '/../autoload.php';

use Core\Runtime\Supervisor\Supervisor;
use Core\Runtime\Observability\Logger;
use Core\Runtime\Queue\QueueFactory;
use Core\Runtime\Scheduler\Schedule;

echo "=== Nexph Supervisor Daemon ===\n\n";

$logger = new Logger(STDOUT, 'info');
$supervisor = new Supervisor([
    'max_restarts' => 3,
    'restart_window' => 60,
    'restart_delay' => 5,
    'health_check_interval' => 30,
    'shutdown_timeout' => 30,
    'panic_recovery' => true,
], $logger);

// Register queue worker
$supervisor->register('queue-worker', function($workerId) use ($logger) {
    $logger->info("Queue worker {$workerId} starting");
    
    $queue = QueueFactory::create('file', [
        'workers' => 2,
        'poll_interval' => 1.0,
    ]);
    
    // Register handlers
    $queue->register('send_email', 'App\\Jobs\\SendEmailJob');
    $queue->register('process_webhook', 'App\\Jobs\\ProcessWebhookJob');
    $queue->register('send_notification', 'App\\Jobs\\SendNotificationJob');
    $queue->register('generate_report', 'App\\Jobs\\GenerateReportJob');
    
    $logger->info("Queue worker {$workerId} registered handlers");
    
    // Work loop
    $queue->work();
    
    $logger->info("Queue worker {$workerId} stopped");
}, [
    'max_restarts' => 5,
    'panic_recovery' => true,
]);

// Register scheduler worker
$supervisor->register('scheduler', function($workerId) use ($logger) {
    $logger->info("Scheduler worker {$workerId} starting");
    
    $schedule = new Schedule();
    
    // Load schedule
    $scheduleFile = __DIR__ . '/../app/schedule.php';
    if (file_exists($scheduleFile)) {
        require $scheduleFile;
    }
    
    $logger->info("Scheduler worker {$workerId} loaded " . count($schedule->getTasks()) . " tasks");
    
    // Run scheduler
    $schedule->run();
    
    $logger->info("Scheduler worker {$workerId} stopped");
}, [
    'max_restarts' => 3,
    'panic_recovery' => true,
]);

// Signal handling
$shouldStop = false;

if (function_exists('pcntl_signal')) {
    pcntl_async_signals(true);
    
    $signalHandler = function(int $signal) use (&$shouldStop, $supervisor, $logger) {
        $signalName = match($signal) {
            SIGTERM => 'SIGTERM',
            SIGINT => 'SIGINT',
            SIGHUP => 'SIGHUP',
            default => "Signal {$signal}",
        };
        
        $logger->info("Received {$signalName}, stopping supervisor");
        $shouldStop = true;
        $supervisor->stop();
    };
    
    pcntl_signal(SIGTERM, $signalHandler);
    pcntl_signal(SIGINT, $signalHandler);
    pcntl_signal(SIGHUP, $signalHandler);
}

// Write PID file
$pidFile = __DIR__ . '/../storage/supervisor.pid';
file_put_contents($pidFile, getmypid());

echo "Supervisor PID: " . getmypid() . "\n";
echo "Workers: " . count($supervisor->getAllStatus()) . "\n";
echo "\n";

// Start supervision
try {
    $supervisor->start();
    
    echo "\nSupervisor stopped gracefully\n";
    
} catch (\Throwable $e) {
    $logger->error("Supervisor error: {$e->getMessage()}");
    echo "\nSupervisor error: {$e->getMessage()}\n";
    exit(1);
} finally {
    if (file_exists($pidFile)) {
        unlink($pidFile);
    }
}

exit(0);
