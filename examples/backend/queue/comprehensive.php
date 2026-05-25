<?php
/**
 * Comprehensive queue system demonstration.
 * 
 * Shows all features: multiple drivers, workers, retry logic,
 * dead letter queue, metrics, and observability.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Runtime\Queue\QueueFactory;
use Core\Runtime\Queue\QueueObserver;
use Core\Runtime\Queue\JobHandler;
use Core\Runtime\Queue\Job;
use Core\Runtime\Runtime;

// Custom job handler with lifecycle hooks
class EmailJobHandler extends JobHandler {
    public function handle(array $payload, Job $job): mixed {
        echo "  → Sending email to {$payload['to']}\n";
        Runtime::sleep(0.5);
        return ['sent' => true, 'timestamp' => time()];
    }
    
    public function failed(Job $job, \Throwable $e): void {
        echo "  ✗ Email failed: {$e->getMessage()}\n";
    }
}

class ReportJobHandler extends JobHandler {
    public function handle(array $payload, Job $job): mixed {
        echo "  → Generating {$payload['type']} report\n";
        Runtime::sleep(2.0);
        return ['report_id' => uniqid(), 'rows' => rand(100, 1000)];
    }
}

class ImageProcessingHandler extends JobHandler {
    public function handle(array $payload, Job $job): mixed {
        echo "  → Processing image {$payload['filename']}\n";
        Runtime::sleep(1.5);
        
        // Simulate occasional failure
        if (rand(0, 10) > 8) {
            throw new \Exception('Image processing failed');
        }
        
        return ['thumbnail' => $payload['filename'] . '.thumb.jpg'];
    }
    
    public function retryDelay(Job $job): int {
        return 5; // Quick retry for image processing
    }
}

echo "=== Nexph Runtime Queue System Demo ===\n\n";

// Create queue with memory driver (auto-detects best available)
$queue = QueueFactory::create([
    'workers' => 4,
    'max_attempts' => 3,
    'retry_delay' => 5,
    'metrics_interval' => 0, // Disable auto-metrics for demo
]);

// Register job handlers
$queue->register('send-email', EmailJobHandler::class);
$queue->register('generate-report', ReportJobHandler::class);
$queue->register('process-image', ImageProcessingHandler::class);

// Create observer for metrics
$observer = new QueueObserver($queue);

echo "1. Pushing jobs to queue...\n\n";

// Push various jobs
for ($i = 1; $i <= 5; $i++) {
    $queue->push('send-email', [
        'to' => "user{$i}@example.com",
        'subject' => 'Welcome',
        'body' => 'Hello!',
    ]);
}

$queue->push('generate-report', ['type' => 'monthly-sales']);
$queue->push('generate-report', ['type' => 'user-activity']);

for ($i = 1; $i <= 8; $i++) {
    $queue->push('process-image', ['filename' => "photo{$i}.jpg"]);
}

// Push delayed job
$queue->later(3, 'send-email', [
    'to' => 'admin@example.com',
    'subject' => 'Delayed notification',
    'body' => 'This was delayed by 3 seconds',
]);

$status = $queue->status();
echo "Queue depth: {$status['depth']} jobs\n";
echo "Workers: {$status['workers']}\n\n";

echo "2. Starting workers...\n\n";

// Start workers in background
Runtime::spawn(function() use ($queue) {
    $queue->work();
});

// Monitor progress
Runtime::spawn(function() use ($observer, $queue) {
    $lastCompleted = 0;
    
    for ($i = 0; $i < 15; $i++) {
        Runtime::sleep(1.0);
        
        $metrics = $observer->getMetrics();
        $completed = $metrics['jobs']['completed'];
        $failed = $metrics['jobs']['failed'];
        $depth = $metrics['queue']['depth'];
        
        if ($completed !== $lastCompleted || $depth > 0) {
            echo "[Monitor] Completed: {$completed}, Failed: {$failed}, Queue: {$depth}\n";
            $lastCompleted = $completed;
        }
        
        // Stop when queue is empty
        if ($depth === 0 && $completed > 0) {
            Runtime::sleep(2.0);
            $queue->stop();
            Runtime::stop();
            break;
        }
    }
});

Runtime::run();

echo "\n3. Final Results\n\n";
$observer->printMetrics();

// Check dead letter queue
$driver = new \Runtime\Queue\Drivers\MemoryDriver();
// Note: In real usage, you'd use the same driver instance
echo "Note: Dead letter queue would contain any jobs that failed after max attempts\n";

echo "\n=== Demo Complete ===\n";
