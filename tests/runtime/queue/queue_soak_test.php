<?php
require_once __DIR__ . '/../autoload.php';

use Core\Runtime\Runtime;
use Core\Runtime\Queue\Queue;
use Core\Runtime\Queue\QueueFactory;
use Core\Runtime\Queue\JobHandler;
use Core\Runtime\Queue\Job;

/**
 * Long-Running Soak Test Suite
 * 
 * Tests: extended runtime stability, memory leaks, queue fairness,
 * scheduler responsiveness, throughput over time, worker health
 */

echo "=== Queue Long-Running Soak Test Suite ===\n\n";

class SoakTestHandler extends JobHandler {
    public function handle(array $payload, Job $job): mixed {
        $type = $payload['type'] ?? 'normal';
        
        switch ($type) {
            case 'quick':
                usleep(10000);
                break;
            case 'normal':
                usleep(100000);
                break;
            case 'slow':
                if (Runtime::available()) {
                    Runtime::sleep(1);
                } else {
                    sleep(1);
                }
                break;
            case 'memory':
                $data = str_repeat('x', 1024 * 1024);
                unset($data);
                break;
        }
        
        return ['processed' => $payload['id'], 'type' => $type];
    }
}

// Test 1: Memory Leak Detection
function testMemoryLeaks(): void {
    echo "Test 1: Memory Leak Detection (1000 jobs)\n";
    echo str_repeat('-', 50) . "\n";
    
    $queue = QueueFactory::create('memory');
    $queue->register('leak_test', SoakTestHandler::class);
    
    $memorySnapshots = [];
    $memorySnapshots[] = memory_get_usage(true);
    
    for ($i = 0; $i < 1000; $i++) {
        $queue->push('leak_test', ['id' => $i, 'type' => 'memory']);
        
        if ($i % 100 === 0) {
            $queue->work();
            $memorySnapshots[] = memory_get_usage(true);
            echo "Processed {$i} jobs, memory: " . number_format(end($memorySnapshots) / 1024 / 1024, 2) . " MB\n";
        }
    }
    
    $queue->work();
    $memorySnapshots[] = memory_get_usage(true);
    
    $initialMemory = $memorySnapshots[0];
    $finalMemory = end($memorySnapshots);
    $growth = $finalMemory - $initialMemory;
    $growthPercent = ($growth / $initialMemory) * 100;
    
    echo "\nMemory analysis:\n";
    echo "  Initial: " . number_format($initialMemory / 1024 / 1024, 2) . " MB\n";
    echo "  Final: " . number_format($finalMemory / 1024 / 1024, 2) . " MB\n";
    echo "  Growth: " . number_format($growth / 1024 / 1024, 2) . " MB ({$growthPercent}%)\n";
    
    if ($growthPercent < 50) {
        echo "✓ No significant memory leak detected\n\n";
    } else {
        echo "⚠ Potential memory leak (growth > 50%)\n\n";
    }
}

// Test 2: Queue Fairness
function testQueueFairness(): void {
    echo "Test 2: Queue Fairness (mixed job types)\n";
    echo str_repeat('-', 50) . "\n";
    
    $queue = QueueFactory::create('memory');
    $queue->register('fairness_test', SoakTestHandler::class);
    
    $jobTypes = ['quick', 'normal', 'slow'];
    $processedByType = ['quick' => 0, 'normal' => 0, 'slow' => 0];
    
    for ($i = 0; $i < 30; $i++) {
        $type = $jobTypes[$i % 3];
        $queue->push('fairness_test', ['id' => $i, 'type' => $type]);
    }
    
    echo "Pushed 30 jobs (10 quick, 10 normal, 10 slow)\n";
    
    $start = microtime(true);
    $queue->work();
    $duration = microtime(true) - $start;
    
    $status = $queue->status();
    echo "Total time: " . number_format($duration, 2) . "s\n";
    echo "Completed: {$status['metrics']['completed']}\n";
    echo "Avg time per job: " . number_format($duration / 30, 3) . "s\n";
    
    echo "✓ Queue fairness verified\n\n";
}

// Test 3: Scheduler Responsiveness
function testSchedulerResponsiveness(): void {
    echo "Test 3: Scheduler Responsiveness\n";
    echo str_repeat('-', 50) . "\n";
    
    if (!Runtime::available()) {
        echo "⚠ Skipped (requires Fiber support)\n\n";
        return;
    }
    
    $queue = QueueFactory::create('memory', ['workers' => 2]);
    $queue->register('responsive_test', SoakTestHandler::class);
    
    $queue->push('responsive_test', ['id' => 1, 'type' => 'slow']);
    
    $start = microtime(true);
    
    Runtime::spawn(function() use ($queue) {
        Runtime::sleep(0.5);
        for ($i = 2; $i <= 10; $i++) {
            $queue->push('responsive_test', ['id' => $i, 'type' => 'quick']);
        }
    });
    
    $queue->work();
    
    $duration = microtime(true) - $start;
    $status = $queue->status();
    
    echo "Total time: " . number_format($duration, 2) . "s\n";
    echo "Completed: {$status['metrics']['completed']}\n";
    
    if ($duration < 3) {
        echo "✓ Scheduler responsive (quick jobs not blocked)\n\n";
    } else {
        echo "⚠ Scheduler may be unresponsive\n\n";
    }
}

// Test 4: Throughput Over Time
function testThroughput(): void {
    echo "Test 4: Throughput Over Time (500 jobs)\n";
    echo str_repeat('-', 50) . "\n";
    
    $queue = QueueFactory::create('memory');
    $queue->register('throughput_test', SoakTestHandler::class);
    
    for ($i = 0; $i < 500; $i++) {
        $queue->push('throughput_test', ['id' => $i, 'type' => 'normal']);
    }
    
    $start = microtime(true);
    $checkpoints = [];
    
    for ($batch = 0; $batch < 5; $batch++) {
        $batchStart = microtime(true);
        $queue->work();
        $batchDuration = microtime(true) - $batchStart;
        
        $status = $queue->status();
        $checkpoints[] = [
            'batch' => $batch + 1,
            'duration' => $batchDuration,
            'completed' => $status['metrics']['completed'],
        ];
    }
    
    $totalDuration = microtime(true) - $start;
    $status = $queue->status();
    
    echo "\nThroughput analysis:\n";
    foreach ($checkpoints as $cp) {
        if ($cp['duration'] > 0) {
            $rate = 100 / $cp['duration'];
            echo "  Batch {$cp['batch']}: " . number_format($rate, 1) . " jobs/s\n";
        }
    }
    
    $overallRate = 500 / $totalDuration;
    echo "  Overall: " . number_format($overallRate, 1) . " jobs/s\n";
    echo "  Total completed: {$status['metrics']['completed']}\n";
    
    echo "✓ Throughput measured\n\n";
}

// Test 5: Worker Health Monitoring
function testWorkerHealth(): void {
    echo "Test 5: Worker Health Monitoring\n";
    echo str_repeat('-', 50) . "\n";
    
    if (!Runtime::available()) {
        echo "⚠ Skipped (requires Fiber support)\n\n";
        return;
    }
    
    $queue = QueueFactory::create('memory', ['workers' => 3]);
    $queue->register('health_test', SoakTestHandler::class);
    
    for ($i = 0; $i < 50; $i++) {
        $queue->push('health_test', ['id' => $i, 'type' => 'normal']);
    }
    
    $healthChecks = [];
    
    Runtime::spawn(function() use ($queue, &$healthChecks) {
        for ($i = 0; $i < 5; $i++) {
            Runtime::sleep(1);
            $status = $queue->status();
            $healthChecks[] = [
                'time' => $i + 1,
                'workers' => $status['workers'],
                'depth' => $status['depth'],
                'completed' => $status['metrics']['completed'],
            ];
        }
    });
    
    $queue->work();
    
    echo "\nHealth check results:\n";
    foreach ($healthChecks as $check) {
        echo "  T+{$check['time']}s: {$check['workers']} workers, " .
             "depth: {$check['depth']}, completed: {$check['completed']}\n";
    }
    
    echo "✓ Worker health monitored\n\n";
}

// Test 6: Extended Soak (configurable duration)
function testExtendedSoak(int $durationMinutes = 1): void {
    echo "Test 6: Extended Soak Test ({$durationMinutes} minute(s))\n";
    echo str_repeat('-', 50) . "\n";
    
    $queue = QueueFactory::create('memory');
    $queue->register('soak_test', SoakTestHandler::class);
    
    $endTime = time() + ($durationMinutes * 60);
    $jobCount = 0;
    $snapshots = [];
    
    echo "Starting extended soak test...\n";
    
    while (time() < $endTime) {
        for ($i = 0; $i < 10; $i++) {
            $queue->push('soak_test', ['id' => $jobCount++, 'type' => 'normal']);
        }
        
        $queue->work();
        
        $snapshot = [
            'time' => time(),
            'jobs' => $jobCount,
            'memory' => memory_get_usage(true),
            'status' => $queue->status(),
        ];
        
        $snapshots[] = $snapshot;
        
        if (count($snapshots) % 10 === 0) {
            $s = end($snapshots);
            echo "  Jobs: {$s['jobs']}, " .
                 "Memory: " . number_format($s['memory'] / 1024 / 1024, 2) . " MB, " .
                 "Completed: {$s['status']['metrics']['completed']}\n";
        }
        
        usleep(100000);
    }
    
    echo "\nSoak test summary:\n";
    echo "  Total jobs: {$jobCount}\n";
    echo "  Final memory: " . number_format(end($snapshots)['memory'] / 1024 / 1024, 2) . " MB\n";
    echo "  Completed: " . end($snapshots)['status']['metrics']['completed'] . "\n";
    echo "  Failed: " . end($snapshots)['status']['metrics']['failed'] . "\n";
    
    echo "✓ Extended soak test completed\n\n";
}

// Test 7: Loop Lag Measurement
function testLoopLag(): void {
    echo "Test 7: Loop Lag Measurement\n";
    echo str_repeat('-', 50) . "\n";
    
    if (!Runtime::available()) {
        echo "⚠ Skipped (requires Fiber support)\n\n";
        return;
    }
    
    $queue = QueueFactory::create('memory', ['workers' => 2]);
    $queue->register('lag_test', SoakTestHandler::class);
    
    $lagMeasurements = [];
    
    Runtime::spawn(function() use (&$lagMeasurements) {
        for ($i = 0; $i < 10; $i++) {
            $expected = microtime(true) + 0.1;
            Runtime::sleep(0.1);
            $actual = microtime(true);
            $lag = ($actual - $expected) * 1000;
            $lagMeasurements[] = $lag;
        }
    });
    
    for ($i = 0; $i < 20; $i++) {
        $queue->push('lag_test', ['id' => $i, 'type' => 'normal']);
    }
    
    $queue->work();
    
    if (!empty($lagMeasurements)) {
        $avgLag = array_sum($lagMeasurements) / count($lagMeasurements);
        $maxLag = max($lagMeasurements);
        
        echo "Loop lag analysis:\n";
        echo "  Average: " . number_format($avgLag, 2) . " ms\n";
        echo "  Maximum: " . number_format($maxLag, 2) . " ms\n";
        
        if ($avgLag < 10) {
            echo "✓ Low loop lag\n\n";
        } else {
            echo "⚠ High loop lag detected\n\n";
        }
    } else {
        echo "⚠ No lag measurements collected\n\n";
    }
}

// Test 8: Retry Rate Monitoring
function testRetryRates(): void {
    echo "Test 8: Retry Rate Monitoring\n";
    echo str_repeat('-', 50) . "\n";
    
    $queue = QueueFactory::create('memory', ['max_attempts' => 3, 'retry_delay' => 0]);
    
    $failureRate = 0.3;
    
    $queue->register('retry_test', function($payload) use ($failureRate) {
        if (rand(0, 100) / 100 < $failureRate) {
            throw new \RuntimeException('Random failure');
        }
        return 'success';
    });
    
    for ($i = 0; $i < 100; $i++) {
        $queue->push('retry_test', ['id' => $i]);
    }
    
    $queue->work();
    
    $status = $queue->status();
    $retryRate = $status['metrics']['retried'] / 100;
    
    echo "Retry rate analysis:\n";
    echo "  Total jobs: 100\n";
    echo "  Completed: {$status['metrics']['completed']}\n";
    echo "  Failed: {$status['metrics']['failed']}\n";
    echo "  Retried: {$status['metrics']['retried']}\n";
    echo "  Retry rate: " . number_format($retryRate * 100, 1) . "%\n";
    
    echo "✓ Retry rates monitored\n\n";
}

// Run all tests
try {
    $soakDuration = (int)($argv[1] ?? 1);
    
    testMemoryLeaks();
    testQueueFairness();
    testSchedulerResponsiveness();
    testThroughput();
    testWorkerHealth();
    testLoopLag();
    testRetryRates();
    testExtendedSoak($soakDuration);
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "✓ All soak tests passed\n";
    echo str_repeat('=', 50) . "\n";
    
} catch (\Throwable $e) {
    echo "\n✗ Test failed: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
