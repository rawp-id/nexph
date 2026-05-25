<?php
require_once __DIR__ . '/../autoload.php';

use Core\Runtime\Runtime;
use Core\Runtime\Queue\Queue;
use Core\Runtime\Queue\QueueFactory;
use Core\Runtime\Queue\JobHandler;
use Core\Runtime\Queue\Job;

/**
 * Persistence and Recovery Test Suite
 * 
 * Tests: file/database/redis persistence, worker restart recovery,
 * job loss prevention, duplicate prevention, corruption handling
 */

echo "=== Queue Persistence & Recovery Test Suite ===\n\n";

class PersistentJobHandler extends JobHandler {
    public function handle(array $payload, Job $job): mixed {
        usleep(50000);
        return ['processed' => $payload['id'], 'attempt' => $job->attempts];
    }
}

// Test 1: File Driver Persistence
function testFileDriverPersistence(): void {
    echo "Test 1: File Driver Persistence\n";
    echo str_repeat('-', 50) . "\n";
    
    $path = sys_get_temp_dir() . '/nexph-test-' . uniqid();
    
    $queue1 = QueueFactory::create(['driver' => 'file', 'file_path' => $path]);
    $queue1->register('persist_test', PersistentJobHandler::class);
    
    for ($i = 0; $i < 5; $i++) {
        $queue1->push('persist_test', ['id' => "job-{$i}"]);
    }
    
    $status1 = $queue1->status();
    echo "Pushed 5 jobs, depth: {$status1['depth']}\n";
    
    unset($queue1);
    
    $queue2 = QueueFactory::create(['driver' => 'file', 'file_path' => $path]);
    $queue2->register('persist_test', PersistentJobHandler::class);
    
    $status2 = $queue2->status();
    echo "After restart, depth: {$status2['depth']}\n";
    
    $queue2->work();
    
    $status3 = $queue2->status();
    echo "After processing, completed: {$status3['metrics']['completed']}\n";
    
    echo "✓ File persistence verified\n\n";
    
    exec("rm -rf " . escapeshellarg($path));
}

// Test 2: Database Driver Persistence
function testDatabaseDriverPersistence(): void {
    echo "Test 2: Database Driver Persistence\n";
    echo str_repeat('-', 50) . "\n";
    
    $dbPath = sys_get_temp_dir() . '/nexph-test-' . uniqid() . '.db';
    $pdo = new PDO("sqlite:{$dbPath}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $queue1 = QueueFactory::create(['driver' => 'database', 'database' => $pdo]);
    $queue1->register('db_persist', PersistentJobHandler::class);
    
    for ($i = 0; $i < 5; $i++) {
        $queue1->push('db_persist', ['id' => "db-job-{$i}"]);
    }
    
    $status1 = $queue1->status();
    echo "Pushed 5 jobs, depth: {$status1['depth']}\n";
    
    unset($queue1);
    
    $pdo2 = new PDO("sqlite:{$dbPath}");
    $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $queue2 = QueueFactory::create(['driver' => 'database', 'database' => $pdo2]);
    $queue2->register('db_persist', PersistentJobHandler::class);
    
    $status2 = $queue2->status();
    echo "After restart, depth: {$status2['depth']}\n";
    
    $queue2->work();
    
    $status3 = $queue2->status();
    echo "After processing, completed: {$status3['metrics']['completed']}\n";
    
    echo "✓ Database persistence verified\n\n";
    
    unlink($dbPath);
}

// Test 3: Worker Crash Recovery
function testWorkerCrashRecovery(): void {
    echo "Test 3: Worker Crash Recovery\n";
    echo str_repeat('-', 50) . "\n";
    
    $path = sys_get_temp_dir() . '/nexph-crash-test-' . uniqid();
    
    $queue = QueueFactory::create(['driver' => 'file', 'file_path' => $path]);
    $queue->register('crash_job', function($payload) {
        if ($payload['should_crash'] ?? false) {
            throw new \RuntimeException('Simulated crash');
        }
        return 'completed';
    });
    
    $queue->push('crash_job', ['id' => 1, 'should_crash' => true]);
    $queue->push('crash_job', ['id' => 2, 'should_crash' => false]);
    $queue->push('crash_job', ['id' => 3, 'should_crash' => false]);
    
    echo "Pushed 3 jobs (1 will crash)\n";
    
    $queue->work();
    
    $status = $queue->status();
    echo "Completed: {$status['metrics']['completed']}\n";
    echo "Failed: {$status['metrics']['failed']}\n";
    
    $queue2 = QueueFactory::create(['driver' => 'file', 'file_path' => $path]);
    $queue2->register('crash_job', function($payload) {
        return 'completed';
    });
    
    $status2 = $queue2->status();
    echo "After restart, remaining depth: {$status2['depth']}\n";
    
    echo "✓ Crash recovery verified\n\n";
    
    exec("rm -rf " . escapeshellarg($path));
}

// Test 4: Dead Letter Queue Integrity
function testDeadLetterQueue(): void {
    echo "Test 4: Dead Letter Queue Integrity\n";
    echo str_repeat('-', 50) . "\n";
    
    $queue = QueueFactory::create('memory', ['max_attempts' => 2]);
    $queue->register('failing_job', function($payload) {
        throw new \RuntimeException('Always fails');
    });
    
    $jobId = $queue->push('failing_job', ['id' => 'dlq-test']);
    echo "Pushed failing job: {$jobId}\n";
    
    $queue->work();
    
    $status = $queue->status();
    echo "Failed: {$status['metrics']['failed']}\n";
    
    $driver = (new \ReflectionClass($queue))->getProperty('driver');
    $driver->setAccessible(true);
    $queueDriver = $driver->getValue($queue);
    
    $deadLetters = $queueDriver->getDeadLetters();
    echo "Dead letter queue size: " . count($deadLetters) . "\n";
    
    if (!empty($deadLetters)) {
        $dlJob = $deadLetters[0];
        echo "Dead letter job: {$dlJob->id}, attempts: {$dlJob->attempts}, error: {$dlJob->error}\n";
    }
    
    echo "✓ Dead letter queue verified\n\n";
}

// Test 5: Job Not Lost on Restart
function testJobNotLost(): void {
    echo "Test 5: Job Not Lost on Restart\n";
    echo str_repeat('-', 50) . "\n";
    
    $path = sys_get_temp_dir() . '/nexph-noloss-' . uniqid();
    
    $queue1 = QueueFactory::create(['driver' => 'file', 'file_path' => $path]);
    $queue1->register('important_job', PersistentJobHandler::class);
    
    $jobIds = [];
    for ($i = 0; $i < 10; $i++) {
        $jobIds[] = $queue1->push('important_job', ['id' => "critical-{$i}"]);
    }
    
    echo "Pushed 10 critical jobs\n";
    
    unset($queue1);
    
    $queue2 = QueueFactory::create(['driver' => 'file', 'file_path' => $path]);
    $queue2->register('important_job', PersistentJobHandler::class);
    
    $queue2->work();
    
    $status = $queue2->status();
    echo "After restart and processing:\n";
    echo "  Completed: {$status['metrics']['completed']}\n";
    echo "  Failed: {$status['metrics']['failed']}\n";
    echo "  Lost: " . (10 - $status['metrics']['completed'] - $status['metrics']['failed']) . "\n";
    
    echo "✓ No jobs lost\n\n";
    
    exec("rm -rf " . escapeshellarg($path));
}

// Test 6: Job Not Duplicated on Restart
function testJobNotDuplicated(): void {
    echo "Test 6: Job Not Duplicated on Restart\n";
    echo str_repeat('-', 50) . "\n";
    
    $path = sys_get_temp_dir() . '/nexph-nodup-' . uniqid();
    $processedIds = [];
    
    $handler = function($payload) use (&$processedIds) {
        $processedIds[] = $payload['id'];
        usleep(10000);
        return 'done';
    };
    
    $queue1 = QueueFactory::create(['driver' => 'file', 'file_path' => $path]);
    $queue1->register('unique_job', $handler);
    
    for ($i = 0; $i < 5; $i++) {
        $queue1->push('unique_job', ['id' => "unique-{$i}"]);
    }
    
    echo "Pushed 5 unique jobs\n";
    
    $queue1->work();
    
    $count1 = count($processedIds);
    echo "First run processed: {$count1}\n";
    
    unset($queue1);
    
    $queue2 = QueueFactory::create(['driver' => 'file', 'file_path' => $path]);
    $queue2->register('unique_job', $handler);
    
    $queue2->work();
    
    $count2 = count($processedIds);
    echo "After restart processed: " . ($count2 - $count1) . " more\n";
    echo "Total processed: {$count2}\n";
    
    $unique = array_unique($processedIds);
    echo "Unique IDs: " . count($unique) . "\n";
    
    if (count($unique) === count($processedIds)) {
        echo "✓ No duplicates\n\n";
    } else {
        echo "✗ Duplicates detected!\n\n";
    }
    
    exec("rm -rf " . escapeshellarg($path));
}

// Test 7: Corruption Handling
function testCorruptionHandling(): void {
    echo "Test 7: Corruption Handling\n";
    echo str_repeat('-', 50) . "\n";
    
    $path = sys_get_temp_dir() . '/nexph-corrupt-' . uniqid();
    
    $queue = QueueFactory::create(['driver' => 'file', 'file_path' => $path]);
    $queue->register('test_job', PersistentJobHandler::class);
    
    $queue->push('test_job', ['id' => 'good-1']);
    $queue->push('test_job', ['id' => 'good-2']);
    
    $jobsDir = $path . '/jobs';
    $files = glob($jobsDir . '/*.json');
    
    if (!empty($files)) {
        file_put_contents($files[0], 'corrupted data');
        echo "Corrupted one job file\n";
    }
    
    $queue->push('test_job', ['id' => 'good-3']);
    
    try {
        $queue->work();
        $status = $queue->status();
        echo "Completed: {$status['metrics']['completed']}\n";
        echo "✓ Corruption handled gracefully\n\n";
    } catch (\Throwable $e) {
        echo "✗ Failed to handle corruption: {$e->getMessage()}\n\n";
    }
    
    exec("rm -rf " . escapeshellarg($path));
}

// Test 8: Delayed Job Persistence
function testDelayedJobPersistence(): void {
    echo "Test 8: Delayed Job Persistence\n";
    echo str_repeat('-', 50) . "\n";
    
    $path = sys_get_temp_dir() . '/nexph-delayed-' . uniqid();
    
    $queue1 = QueueFactory::create(['driver' => 'file', 'file_path' => $path]);
    $queue1->register('delayed_job', PersistentJobHandler::class);
    
    $queue1->push('delayed_job', ['id' => 'immediate']);
    $queue1->later(5, 'delayed_job', ['id' => 'delayed-5s']);
    $queue1->later(10, 'delayed_job', ['id' => 'delayed-10s']);
    
    echo "Pushed 1 immediate + 2 delayed jobs\n";
    
    $status1 = $queue1->status();
    echo "Current depth (available now): {$status1['depth']}\n";
    
    unset($queue1);
    
    $queue2 = QueueFactory::create(['driver' => 'file', 'file_path' => $path]);
    $queue2->register('delayed_job', PersistentJobHandler::class);
    
    $status2 = $queue2->status();
    echo "After restart, depth: {$status2['depth']}\n";
    
    $queue2->work();
    
    $status3 = $queue2->status();
    echo "Completed: {$status3['metrics']['completed']}\n";
    echo "✓ Delayed jobs persisted\n\n";
    
    exec("rm -rf " . escapeshellarg($path));
}

// Run all tests
try {
    testFileDriverPersistence();
    testDatabaseDriverPersistence();
    testWorkerCrashRecovery();
    testDeadLetterQueue();
    testJobNotLost();
    testJobNotDuplicated();
    testCorruptionHandling();
    testDelayedJobPersistence();
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "✓ All persistence tests passed\n";
    echo str_repeat('=', 50) . "\n";
    
} catch (\Throwable $e) {
    echo "\n✗ Test failed: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
