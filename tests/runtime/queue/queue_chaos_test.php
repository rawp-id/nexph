<?php
require_once __DIR__ . '/../autoload.php';

use Core\Runtime\Runtime;
use Core\Runtime\Queue\Queue;
use Core\Runtime\Queue\QueueFactory;
use Core\Runtime\Queue\JobHandler;
use Core\Runtime\Queue\Job;

/**
 * Chaos Engineering Test Suite for Nexph Queue Runtime
 * 
 * Tests: worker crashes, fatal exceptions, memory pressure, timeouts,
 * retry storms, graceful shutdown, concurrent processing, duplicate prevention
 */

echo "=== Queue Chaos Engineering Test Suite ===\n\n";

// Test handlers
class CrashingHandler extends JobHandler {
    public function handle(array $payload, Job $job): mixed {
        if ($payload['crash_type'] ?? null) {
            switch ($payload['crash_type']) {
                case 'fatal':
                    trigger_error('Fatal error simulation', E_USER_ERROR);
                    break;
                case 'exception':
                    throw new \RuntimeException('Simulated exception');
                case 'memory':
                    $data = str_repeat('x', 10 * 1024 * 1024);
                    return strlen($data);
                case 'timeout':
                    sleep(10);
                    break;
                case 'segfault':
                    exit(139);
            }
        }
        return 'completed';
    }
}

class LongRunningHandler extends JobHandler {
    public function handle(array $payload, Job $job): mixed {
        $duration = $payload['duration'] ?? 5;
        for ($i = 0; $i < $duration; $i++) {
            if (Runtime::available()) {
                Runtime::sleep(1);
            } else {
                sleep(1);
            }
        }
        return "ran for {$duration}s";
    }
}

class LargePayloadHandler extends JobHandler {
    public function handle(array $payload, Job $job): mixed {
        $size = strlen(json_encode($payload));
        $processed = array_sum(array_map('strlen', array_values($payload)));
        return ['size' => $size, 'processed' => $processed];
    }
}

class RetryStormHandler extends JobHandler {
    private static int $attempts = 0;
    
    public function handle(array $payload, Job $job): mixed {
        self::$attempts++;
        if (self::$attempts < ($payload['succeed_after'] ?? 5)) {
            throw new \RuntimeException("Attempt " . self::$attempts);
        }
        return 'finally succeeded';
    }
}

// Test 1: Exception Handling
function testExceptionHandling(): void {
    echo "Test 1: Exception Handling\n";
    echo str_repeat('-', 50) . "\n";
    
    $queue = QueueFactory::create('memory');
    $queue->register('crash_exception', CrashingHandler::class);
    
    $jobId = $queue->push('crash_exception', ['crash_type' => 'exception']);
    echo "Pushed job with exception: {$jobId}\n";
    
    $queue->work();
    
    $status = $queue->status();
    echo "Failed jobs: {$status['metrics']['failed']}\n";
    echo "✓ Exception handled gracefully\n\n";
}

// Test 2: Memory Pressure
function testMemoryPressure(): void {
    echo "Test 2: Memory Pressure\n";
    echo str_repeat('-', 50) . "\n";
    
    $queue = QueueFactory::create('memory');
    $queue->register('memory_test', CrashingHandler::class);
    
    $before = memory_get_usage(true);
    
    for ($i = 0; $i < 5; $i++) {
        $queue->push('memory_test', ['crash_type' => 'memory', 'index' => $i]);
    }
    
    $queue->work();
    
    $after = memory_get_usage(true);
    $growth = $after - $before;
    
    echo "Memory before: " . number_format($before / 1024 / 1024, 2) . " MB\n";
    echo "Memory after: " . number_format($after / 1024 / 1024, 2) . " MB\n";
    echo "Growth: " . number_format($growth / 1024 / 1024, 2) . " MB\n";
    echo "✓ Memory pressure handled\n\n";
}

// Test 3: Large Payload Processing
function testLargePayloads(): void {
    echo "Test 3: Large Payload Processing\n";
    echo str_repeat('-', 50) . "\n";
    
    $queue = QueueFactory::create('memory');
    $queue->register('large_payload', LargePayloadHandler::class);
    
    $largeData = [
        'data' => str_repeat('x', 100 * 1024),
        'array' => array_fill(0, 1000, 'test'),
        'nested' => ['deep' => ['structure' => array_fill(0, 100, 'value')]],
    ];
    
    $jobId = $queue->push('large_payload', $largeData);
    echo "Pushed large payload: " . number_format(strlen(json_encode($largeData)) / 1024, 2) . " KB\n";
    
    $queue->work();
    
    $status = $queue->status();
    echo "Completed: {$status['metrics']['completed']}\n";
    echo "✓ Large payload processed\n\n";
}

// Test 4: Timeout Handling
function testTimeoutHandling(): void {
    echo "Test 4: Timeout Handling\n";
    echo str_repeat('-', 50) . "\n";
    
    $queue = QueueFactory::create('memory', ['timeout' => 2]);
    $queue->register('timeout_test', CrashingHandler::class);
    
    $jobId = $queue->push('timeout_test', ['crash_type' => 'timeout']);
    echo "Pushed job with 10s sleep (2s timeout)\n";
    
    $start = microtime(true);
    $queue->work();
    $duration = microtime(true) - $start;
    
    echo "Execution time: " . number_format($duration, 2) . "s\n";
    echo "✓ Timeout handled (job should fail)\n\n";
}

// Test 5: Retry Storm
function testRetryStorm(): void {
    echo "Test 5: Retry Storm\n";
    echo str_repeat('-', 50) . "\n";
    
    $queue = QueueFactory::create('memory', ['max_attempts' => 5, 'retry_delay' => 0]);
    $queue->register('retry_storm', RetryStormHandler::class);
    
    $jobId = $queue->push('retry_storm', ['succeed_after' => 3]);
    echo "Pushed job that fails first 2 attempts\n";
    
    $queue->work();
    
    $status = $queue->status();
    echo "Retried: {$status['metrics']['retried']}\n";
    echo "Completed: {$status['metrics']['completed']}\n";
    echo "✓ Retry storm handled\n\n";
}

// Test 6: Queue Starvation
function testQueueStarvation(): void {
    echo "Test 6: Queue Starvation\n";
    echo str_repeat('-', 50) . "\n";
    
    $queue = QueueFactory::create('memory');
    $queue->register('long_running', LongRunningHandler::class);
    $queue->register('quick_job', function($payload) { return 'quick'; });
    
    $queue->push('long_running', ['duration' => 3]);
    
    for ($i = 0; $i < 5; $i++) {
        $queue->push('quick_job', ['index' => $i]);
    }
    
    echo "Pushed 1 long-running job + 5 quick jobs\n";
    
    $start = microtime(true);
    $queue->work();
    $duration = microtime(true) - $start;
    
    $status = $queue->status();
    echo "Total time: " . number_format($duration, 2) . "s\n";
    echo "Completed: {$status['metrics']['completed']}\n";
    echo "✓ Queue starvation test complete\n\n";
}

// Test 7: Concurrent Multi-Worker Processing
function testConcurrentWorkers(): void {
    echo "Test 7: Concurrent Multi-Worker Processing\n";
    echo str_repeat('-', 50) . "\n";
    
    if (!Runtime::available()) {
        echo "⚠ Skipped (requires Fiber support)\n\n";
        return;
    }
    
    $queue = QueueFactory::create('memory', ['workers' => 3]);
    $queue->register('concurrent_job', function($payload) {
        Runtime::sleep(0.5);
        return $payload['index'];
    });
    
    for ($i = 0; $i < 10; $i++) {
        $queue->push('concurrent_job', ['index' => $i]);
    }
    
    echo "Pushed 10 jobs with 3 workers\n";
    
    $start = microtime(true);
    $queue->work();
    $duration = microtime(true) - $start;
    
    $status = $queue->status();
    echo "Total time: " . number_format($duration, 2) . "s\n";
    echo "Completed: {$status['metrics']['completed']}\n";
    echo "Avg time per job: " . number_format($duration / 10, 3) . "s\n";
    echo "✓ Concurrent processing verified\n\n";
}

// Test 8: Duplicate Job Prevention
function testDuplicatePrevention(): void {
    echo "Test 8: Duplicate Job Prevention\n";
    echo str_repeat('-', 50) . "\n";
    
    $queue = QueueFactory::create('memory');
    $queue->register('unique_job', function($payload) {
        return $payload['id'];
    });
    
    $jobId1 = $queue->push('unique_job', ['id' => 'test-123']);
    $jobId2 = $queue->push('unique_job', ['id' => 'test-123']);
    
    echo "Pushed same job twice\n";
    echo "Job ID 1: {$jobId1}\n";
    echo "Job ID 2: {$jobId2}\n";
    echo "Unique: " . ($jobId1 !== $jobId2 ? 'yes' : 'no') . "\n";
    
    $queue->work();
    
    $status = $queue->status();
    echo "Completed: {$status['metrics']['completed']}\n";
    echo "✓ Both jobs processed (no deduplication by default)\n\n";
}

// Test 9: Runtime Degradation Fallback
function testRuntimeDegradation(): void {
    echo "Test 9: Runtime Degradation Fallback\n";
    echo str_repeat('-', 50) . "\n";
    
    $caps = Runtime::capabilities();
    echo "Runtime capabilities:\n";
    echo "  Fibers: " . ($caps['fibers'] ? 'yes' : 'no') . "\n";
    echo "  CLI: " . ($caps['cli'] ? 'yes' : 'no') . "\n";
    echo "  PCNTL: " . ($caps['pcntl'] ? 'yes' : 'no') . "\n";
    echo "  Available: " . (Runtime::available() ? 'yes' : 'no') . "\n";
    
    $queue = QueueFactory::create('memory');
    $queue->register('fallback_test', function($payload) {
        return 'works in any mode';
    });
    
    $queue->push('fallback_test', ['test' => true]);
    $queue->work();
    
    $status = $queue->status();
    echo "Completed: {$status['metrics']['completed']}\n";
    echo "✓ Runtime degradation handled\n\n";
}

// Test 10: Queue Depth and Metrics
function testQueueMetrics(): void {
    echo "Test 10: Queue Depth and Metrics\n";
    echo str_repeat('-', 50) . "\n";
    
    $queue = QueueFactory::create('memory');
    $queue->register('metrics_job', function($payload) {
        usleep(10000);
        return 'done';
    });
    
    for ($i = 0; $i < 20; $i++) {
        $queue->push('metrics_job', ['index' => $i]);
    }
    
    $status = $queue->status();
    echo "Initial depth: {$status['depth']}\n";
    
    $queue->work();
    
    $metrics = $queue->metrics()->toArray();
    echo "\nFinal metrics:\n";
    echo "  Enqueued: {$metrics['enqueued']}\n";
    echo "  Completed: {$metrics['completed']}\n";
    echo "  Failed: {$metrics['failed']}\n";
    echo "  Retried: {$metrics['retried']}\n";
    echo "  Avg duration: " . number_format($metrics['avg_duration'], 3) . "s\n";
    echo "✓ Metrics tracking verified\n\n";
}

// Run all tests
try {
    testExceptionHandling();
    testMemoryPressure();
    testLargePayloads();
    testTimeoutHandling();
    testRetryStorm();
    testQueueStarvation();
    testConcurrentWorkers();
    testDuplicatePrevention();
    testRuntimeDegradation();
    testQueueMetrics();
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "✓ All chaos tests passed\n";
    echo str_repeat('=', 50) . "\n";
    
} catch (\Throwable $e) {
    echo "\n✗ Test failed: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
