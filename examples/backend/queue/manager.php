<?php
/**
 * Queue manager example - managing multiple queues.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Runtime\Queue\QueueFactory;
use Core\Runtime\Queue\QueueManager;
use Core\Runtime\Runtime;

// Create multiple queues for different purposes
$emailQueue = QueueFactory::createWithDriver('memory', ['workers' => 2]);
$reportQueue = QueueFactory::createWithDriver('memory', ['workers' => 1]);
$notificationQueue = QueueFactory::createWithDriver('memory', ['workers' => 3]);

// Register handlers
$emailQueue->register('send-email', function($payload, $job) {
    echo "[Email] Sending to {$payload['to']}\n";
    Runtime::sleep(1.0);
    return ['sent' => true];
});

$reportQueue->register('generate-report', function($payload, $job) {
    echo "[Report] Generating {$payload['type']} report\n";
    Runtime::sleep(3.0);
    return ['generated' => true];
});

$notificationQueue->register('push-notification', function($payload, $job) {
    echo "[Notification] Pushing to user {$payload['user_id']}\n";
    Runtime::sleep(0.5);
    return ['pushed' => true];
});

// Create queue manager
$manager = new QueueManager();
$manager->addQueue('emails', $emailQueue);
$manager->addQueue('reports', $reportQueue);
$manager->addQueue('notifications', $notificationQueue);

// Push jobs to different queues
echo "Pushing jobs to multiple queues...\n\n";

for ($i = 1; $i <= 5; $i++) {
    $emailQueue->push('send-email', ['to' => "user{$i}@example.com"]);
}

for ($i = 1; $i <= 3; $i++) {
    $reportQueue->push('generate-report', ['type' => "monthly-{$i}"]);
}

for ($i = 1; $i <= 10; $i++) {
    $notificationQueue->push('push-notification', ['user_id' => $i]);
}

echo "Starting all queues...\n\n";

// Start all queues concurrently
Runtime::spawn(function() use ($manager) {
    $manager->startAll();
});

// Monitor status
Runtime::spawn(function() use ($manager) {
    for ($i = 0; $i < 5; $i++) {
        Runtime::sleep(2.0);
        
        echo "\n=== Queue Status ===\n";
        $status = $manager->status();
        
        foreach ($status as $name => $queueStatus) {
            echo "{$name}: depth={$queueStatus['depth']}, workers={$queueStatus['workers']}\n";
        }
    }
    
    Runtime::sleep(5.0);
    $manager->stopAll();
    Runtime::stop();
});

Runtime::run();

echo "\n\nAll queues completed!\n";

// Final status
echo "\n=== Final Status ===\n";
$status = $manager->status();
foreach ($status as $name => $queueStatus) {
    $metrics = $queueStatus['metrics'];
    echo "{$name}: completed={$metrics['completed']}, failed={$metrics['failed']}\n";
}
