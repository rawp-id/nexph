#!/usr/bin/env php
<?php
/**
 * Simple workload test with proper metrics integration.
 */

require_once __DIR__ . '/../autoload.php';

use Core\Runtime\Queue\QueueFactory;

echo "=== Nexph Simple Workload Test ===\n\n";

$driver = 'memory';
$queue = QueueFactory::create($driver, [
    'workers' => 4,
    'poll_interval' => 0.5,
]);

// Register handlers
$queue->register('send_email', 'App\\Jobs\\SendEmailJob');
$queue->register('process_webhook', 'App\\Jobs\\ProcessWebhookJob');
$queue->register('send_notification', 'App\\Jobs\\SendNotificationJob');
$queue->register('generate_report', 'App\\Jobs\\GenerateReportJob');

echo "Registered 4 job handlers\n\n";

// Enqueue jobs
echo "Enqueueing jobs...\n";
$jobCount = 0;

for ($i = 0; $i < 10; $i++) {
    $queue->push('send_email', [
        'to' => "user{$i}@example.com",
        'subject' => "Test #{$i}",
        'body' => "Test email {$i}",
    ]);
    $jobCount++;
}

for ($i = 0; $i < 5; $i++) {
    $queue->push('process_webhook', [
        'url' => "https://api.example.com/webhook/{$i}",
        'data' => ['event' => 'test', 'id' => $i],
    ]);
    $jobCount++;
}

for ($i = 0; $i < 3; $i++) {
    $queue->push('send_notification', [
        'user_id' => 1000 + $i,
        'title' => "Notification #{$i}",
        'message' => "Test notification",
        'channels' => ['push', 'email'],
    ]);
    $jobCount++;
}

$queue->push('generate_report', [
    'type' => 'sales',
    'start_date' => '2026-05-01',
    'end_date' => '2026-05-14',
]);
$jobCount++;

echo "Enqueued {$jobCount} jobs\n\n";

// Process
echo "Processing jobs...\n";
echo str_repeat('-', 80) . "\n\n";

$startTime = microtime(true);

// Set up signal handler to stop queue gracefully
if (function_exists('pcntl_signal')) {
    pcntl_signal(SIGTERM, function() use ($queue) {
        echo "\n[Signal] Stopping queue...\n";
        $queue->stop();
    });
    pcntl_signal(SIGINT, function() use ($queue) {
        echo "\n[Signal] Stopping queue...\n";
        $queue->stop();
    });
}

// Spawn a timer to stop after all jobs complete
if (class_exists('\\Runtime\\Runtime') && \Runtime\Runtime::available()) {
    \Runtime\Runtime::spawn(function() use ($queue, $jobCount) {
        $maxWait = 30; // 30 seconds max
        $checkInterval = 1;
        $elapsed = 0;
        
        while ($elapsed < $maxWait) {
            \Runtime\Runtime::sleep($checkInterval);
            $elapsed += $checkInterval;
            
            $status = $queue->status();
            $completed = $status['metrics']['counters']['jobs_completed'];
            $failed = $status['metrics']['counters']['jobs_failed'];
            
            // Stop if all jobs processed
            if ($completed + $failed >= $jobCount) {
                \Runtime\Runtime::sleep(1); // Wait 1s for final metrics
                echo "\n[Test] All jobs processed, stopping queue...\n";
                $queue->stop();
                break;
            }
        }
        
        // Timeout - force stop
        if ($elapsed >= $maxWait) {
            echo "\n[Timeout] Forcing queue stop...\n";
            $queue->stop();
        }
    });
}

try {
    $queue->work();
    
    $duration = microtime(true) - $startTime;
    $status = $queue->status();
    
    echo "\n" . str_repeat('-', 80) . "\n";
    echo "Processing complete!\n\n";
    
    echo "RESULTS:\n";
    echo "  Duration:     " . number_format($duration, 2) . "s\n";
    echo "  Jobs:         {$jobCount}\n";
    echo "  Completed:    {$status['metrics']['counters']['jobs_completed']}\n";
    echo "  Failed:       {$status['metrics']['counters']['jobs_failed']}\n";
    echo "  Retried:      {$status['metrics']['counters']['jobs_retried']}\n";
    
    if ($duration > 0) {
        $throughput = $status['metrics']['counters']['jobs_completed'] / $duration;
        echo "  Throughput:   " . number_format($throughput, 2) . " jobs/s\n";
    }
    
    echo "\n✓ Test completed successfully!\n";
    
} catch (\Throwable $e) {
    echo "\n✗ Error: {$e->getMessage()}\n";
    exit(1);
}
