#!/usr/bin/env php
<?php
/**
 * Real-world workload integration test.
 * Tests runtime behavior under realistic async load.
 */

require_once __DIR__ . '/../autoload.php';

use Core\Runtime\Queue\QueueFactory;
use Core\Runtime\Observability\RuntimeMetrics;
use Core\Runtime\Observability\HealthMonitor;
use Core\Runtime\Observability\Dashboard;
use Core\Runtime\Observability\Logger;

echo "=== Nexph Real-World Workload Test ===\n\n";

// Initialize components
$metrics = new RuntimeMetrics();
$health = new HealthMonitor();
$dashboard = new Dashboard($metrics, $health);
$logger = new Logger(STDOUT, 'info');

// Create queue
$driver = getenv('QUEUE_DRIVER') ?: 'memory';
echo "Using driver: {$driver}\n";

$queue = QueueFactory::create($driver, [
    'workers' => 4,
    'max_attempts' => 3,
    'poll_interval' => 0.5,
]);

// Register job handlers
$queue->register('send_email', 'App\\Jobs\\SendEmailJob');
$queue->register('process_webhook', 'App\\Jobs\\ProcessWebhookJob');
$queue->register('send_notification', 'App\\Jobs\\SendNotificationJob');
$queue->register('generate_report', 'App\\Jobs\\GenerateReportJob');

echo "Registered 4 job handlers\n\n";

// Enqueue realistic workload
echo "Enqueueing jobs...\n";

$jobCount = 0;

// Email jobs (fast, occasional failures)
for ($i = 0; $i < 20; $i++) {
    $queue->push('send_email', [
        'to' => "user{$i}@example.com",
        'subject' => "Test Email #{$i}",
        'body' => "This is test email number {$i}",
    ]);
    $jobCount++;
    $metrics->incrementCounter('jobs_enqueued');
}

// Webhook jobs (medium speed, more failures)
for ($i = 0; $i < 15; $i++) {
    $queue->push('process_webhook', [
        'url' => "https://api.example.com/webhook/{$i}",
        'data' => ['event' => 'test', 'id' => $i],
    ]);
    $jobCount++;
    $metrics->incrementCounter('jobs_enqueued');
}

// Notification jobs (multi-channel, variable speed)
for ($i = 0; $i < 10; $i++) {
    $queue->push('send_notification', [
        'user_id' => 1000 + $i,
        'title' => "Notification #{$i}",
        'message' => "You have a new notification",
        'channels' => ['push', 'email'],
    ]);
    $jobCount++;
    $metrics->incrementCounter('jobs_enqueued');
}

// Report jobs (slow, CPU intensive)
for ($i = 0; $i < 5; $i++) {
    $queue->push('generate_report', [
        'type' => 'sales',
        'start_date' => date('Y-m-01'),
        'end_date' => date('Y-m-d'),
    ]);
    $jobCount++;
    $metrics->incrementCounter('jobs_enqueued');
}

echo "Enqueued {$jobCount} jobs\n\n";

// Update metrics
$metrics->setGauge('queue_depth', $jobCount);
$metrics->setGauge('active_workers', 4);

// Display initial dashboard
echo $dashboard->render();
echo "\n";

// Process jobs
echo "Processing jobs...\n";
echo str_repeat('-', 80) . "\n\n";

$startTime = microtime(true);

try {
    $queue->work();
    
    $duration = microtime(true) - $startTime;
    
    echo "\n" . str_repeat('-', 80) . "\n";
    echo "Processing complete!\n\n";
    
    // Final metrics
    $finalMetrics = $metrics->toArray();
    
    echo "FINAL RESULTS:\n";
    echo "  Duration:     " . number_format($duration, 2) . "s\n";
    echo "  Completed:    {$finalMetrics['counters']['jobs_completed']}\n";
    echo "  Failed:       {$finalMetrics['counters']['jobs_failed']}\n";
    echo "  Retried:      {$finalMetrics['counters']['jobs_retried']}\n";
    echo "  Throughput:   " . number_format($finalMetrics['computed']['throughput'], 2) . " jobs/s\n";
    echo "  Success Rate: " . number_format($finalMetrics['computed']['success_rate'], 1) . "%\n";
    echo "  Memory Peak:  {$finalMetrics['computed']['memory_peak_mb']} MB\n";
    echo "\n";
    
    // Health check
    $healthStatus = $health->check();
    echo "Health Status: {$healthStatus['state']}\n";
    
    if (!empty($healthStatus['degradation_reasons'])) {
        echo "Degradation: " . implode(', ', $healthStatus['degradation_reasons']) . "\n";
    }
    
    echo "\n";
    
} catch (\Throwable $e) {
    echo "\nError: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "Test completed successfully!\n";
exit(0);
