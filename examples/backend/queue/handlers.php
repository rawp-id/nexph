<?php
/**
 * Custom job handlers example.
 */

require_once __DIR__ . '/../../autoload.php';

use Core\Runtime\Queue\QueueFactory;
use Core\Runtime\Queue\JobHandler;
use Core\Runtime\Queue\Job;

// Custom handler with lifecycle hooks
class EmailHandler extends JobHandler {
    public function handle(array $payload, Job $job): mixed {
        echo "Sending email to {$payload['to']}...\n";
        
        // Simulate email sending
        if (rand(0, 10) > 8) {
            throw new \Exception('SMTP connection failed');
        }
        
        sleep(1);
        echo "Email sent to {$payload['to']}\n";
        
        return ['sent' => true, 'timestamp' => time()];
    }
    
    public function before(Job $job): void {
        echo "Preparing to send email (attempt {$job->attempts})\n";
    }
    
    public function after(Job $job, mixed $result): void {
        echo "Email sent successfully at {$result['timestamp']}\n";
    }
    
    public function failed(Job $job, \Throwable $e): void {
        echo "Email failed after {$job->attempts} attempts: {$e->getMessage()}\n";
        // Could send alert, log to monitoring system, etc.
    }
    
    public function retryDelay(Job $job): int {
        // Custom retry delay: 10, 30, 60 seconds
        return [10, 30, 60][$job->attempts - 1] ?? 60;
    }
}

// Create queue
$queue = QueueFactory::createWithDriver('memory', [
    'workers' => 1,
    'max_attempts' => 3,
]);

// Register handler
$queue->register('send-email', EmailHandler::class);

// Push jobs
echo "Pushing email jobs...\n\n";
$queue->push('send-email', ['to' => 'alice@example.com', 'subject' => 'Hello']);
$queue->push('send-email', ['to' => 'bob@example.com', 'subject' => 'Welcome']);
$queue->push('send-email', ['to' => 'charlie@example.com', 'subject' => 'Update']);

echo "Starting worker...\n\n";
$queue->work();

echo "\nAll jobs processed!\n";
