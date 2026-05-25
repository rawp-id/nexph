<?php
namespace Tests\Runtime;

require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Queue\QueueFactory;
use Core\Core\Runtime\Queue\Drivers\MemoryDriver;
use Core\Core\Runtime\Queue\Drivers\FileDriver;
use Core\Core\Runtime\Runtime;

/**
 * Observability and metrics tests.
 * 
 * Tests worker health, queue depth, retry rates, active jobs,
 * failed jobs, loop lag, memory growth, job throughput over extended runs.
 */
class ObservabilityTest {
    private int $passed = 0;
    private int $failed = 0;
    private array $results = [];
    
    public function run(): void {
        echo "=== Observability & Metrics Test Suite ===\n\n";
        
        $this->testQueueDepthTracking();
        $this->testRetryRateMetrics();
        $this->testActiveJobsTracking();
        $this->testFailedJobsMetrics();
        $this->testJobThroughput();
        $this->testMemoryGrowthTracking();
        $this->testWorkerHealthMetrics();
        $this->testAverageDurationTracking();
        $this->testMetricsAccuracy();
        $this->testExtendedSoakMetrics();
        
        $this->printResults();
    }
    
    private function testQueueDepthTracking(): void {
        echo "Test: Queue depth tracking... ";
        
        try {
            $driver = new MemoryDriver();
            $queue = new \Core\Runtime\Queue\Queue($driver, [
                'workers' => 1,
                'metrics_interval' => 0,
            ]);
            
            $queue->register('depth-job', function($payload, $job) {
                usleep(10000);
                return ['done' => true];
            });
            
            for ($i = 0; $i < 10; $i++) {
                $queue->push('depth-job', ['id' => $i]);
            }
            
            $depthBefore = $driver->depth();
            $queue->work();
            $depthAfter = $driver->depth();
            
            if ($depthBefore === 10 && $depthAfter === 0) {
                $this->pass("Queue depth tracked correctly (10 → 0)");
            } else {
                $this->fail("Queue depth incorrect (before: {$depthBefore}, after: {$depthAfter})");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testRetryRateMetrics(): void {
        echo "Test: Retry rate metrics... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 1,
                'max_attempts' => 3,
                'retry_delay' => 0,
                'metrics_interval' => 0,
            ]);
            
            $attempts = 0;
            $queue->register('retry-job', function($payload, $job) use (&$attempts) {
                $attempts++;
                if ($attempts < 3) {
                    throw new \Exception("Retry needed");
                }
                return ['success' => true];
            });
            
            $queue->push('retry-job', ['id' => 1]);
            $queue->work();
            
            $metrics = $queue->metrics()->toArray();
            
            if ($metrics['retried'] === 2 && $metrics['completed'] === 1) {
                $this->pass("Retry rate tracked (2 retries, 1 completed)");
            } else {
                $this->fail("Retry rate incorrect (retried: {$metrics['retried']}, completed: {$metrics['completed']})");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testActiveJobsTracking(): void {
        echo "Test: Active jobs tracking... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 2,
                'metrics_interval' => 0,
            ]);
            
            $activeCount = 0;
            $queue->register('active-job', function($payload, $job) use (&$activeCount) {
                $activeCount++;
                usleep(100000);
                return ['id' => $payload['id']];
            });
            
            for ($i = 0; $i < 5; $i++) {
                $queue->push('active-job', ['id' => $i]);
            }
            
            $queue->work();
            
            if ($activeCount === 5) {
                $this->pass("Active jobs tracked correctly (5 processed)");
            } else {
                $this->fail("Active jobs count incorrect: {$activeCount}");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testFailedJobsMetrics(): void {
        echo "Test: Failed jobs metrics... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 1,
                'max_attempts' => 2,
                'retry_delay' => 0,
                'metrics_interval' => 0,
            ]);
            
            $queue->register('fail-job', function($payload, $job) {
                throw new \Exception("Always fails");
            });
            
            for ($i = 0; $i < 3; $i++) {
                $queue->push('fail-job', ['id' => $i]);
            }
            
            $queue->work();
            
            $metrics = $queue->metrics()->toArray();
            
            if ($metrics['failed'] === 3) {
                $this->pass("Failed jobs tracked correctly (3 failed)");
            } else {
                $this->fail("Failed jobs count incorrect: {$metrics['failed']}");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testJobThroughput(): void {
        echo "Test: Job throughput measurement... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 2,
                'metrics_interval' => 0,
            ]);
            
            $queue->register('throughput-job', function($payload, $job) {
                usleep(10000);
                return ['id' => $payload['id']];
            });
            
            $jobCount = 50;
            for ($i = 0; $i < $jobCount; $i++) {
                $queue->push('throughput-job', ['id' => $i]);
            }
            
            $start = microtime(true);
            $queue->work();
            $duration = microtime(true) - $start;
            
            $throughput = $jobCount / $duration;
            
            if ($throughput > 10) {
                $this->pass("Throughput measured: " . number_format($throughput, 2) . " jobs/sec");
            } else {
                $this->fail("Throughput too low: " . number_format($throughput, 2) . " jobs/sec");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testMemoryGrowthTracking(): void {
        echo "Test: Memory growth tracking... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 1,
                'metrics_interval' => 0,
            ]);
            
            $memorySnapshots = [];
            $queue->register('memory-job', function($payload, $job) use (&$memorySnapshots) {
                $memorySnapshots[] = memory_get_usage(true);
                $data = str_repeat('x', 1024);
                return ['size' => strlen($data)];
            });
            
            for ($i = 0; $i < 20; $i++) {
                $queue->push('memory-job', ['id' => $i]);
            }
            
            $memoryBefore = memory_get_usage(true);
            $queue->work();
            $memoryAfter = memory_get_usage(true);
            
            $growth = $memoryAfter - $memoryBefore;
            $growthMB = $growth / 1024 / 1024;
            
            if ($growthMB < 10) {
                $this->pass("Memory growth tracked: " . number_format($growthMB, 2) . " MB");
            } else {
                $this->fail("Excessive memory growth: " . number_format($growthMB, 2) . " MB");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testWorkerHealthMetrics(): void {
        echo "Test: Worker health metrics... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 3,
                'metrics_interval' => 0,
            ]);
            
            $queue->register('health-job', function($payload, $job) {
                usleep(50000);
                return ['healthy' => true];
            });
            
            for ($i = 0; $i < 10; $i++) {
                $queue->push('health-job', ['id' => $i]);
            }
            
            $queue->work();
            
            $status = $queue->status();
            
            if ($status['workers'] === 3 && $status['running'] === false) {
                $this->pass("Worker health tracked (3 workers, stopped)");
            } else {
                $this->fail("Worker health metrics incorrect");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testAverageDurationTracking(): void {
        echo "Test: Average duration tracking... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 1,
                'metrics_interval' => 0,
            ]);
            
            $queue->register('duration-job', function($payload, $job) {
                usleep($payload['sleep'] * 1000);
                return ['slept' => $payload['sleep']];
            });
            
            $queue->push('duration-job', ['sleep' => 10]);
            $queue->push('duration-job', ['sleep' => 20]);
            $queue->push('duration-job', ['sleep' => 30]);
            
            $queue->work();
            
            $metrics = $queue->metrics()->toArray();
            
            if (isset($metrics['avg_duration']) && $metrics['avg_duration'] > 0) {
                $this->pass("Average duration tracked: " . number_format($metrics['avg_duration'] * 1000, 2) . " ms");
            } else {
                $this->fail("Average duration not tracked");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testMetricsAccuracy(): void {
        echo "Test: Metrics accuracy... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 1,
                'max_attempts' => 2,
                'retry_delay' => 0,
                'metrics_interval' => 0,
            ]);
            
            $attempts = [];
            $queue->register('accuracy-job', function($payload, $job) use (&$attempts) {
                $id = $payload['id'];
                $attempts[$id] = ($attempts[$id] ?? 0) + 1;
                
                if ($id === 2 && $attempts[$id] < 2) {
                    throw new \Exception("Retry once");
                }
                if ($id === 3) {
                    throw new \Exception("Always fail");
                }
                
                return ['id' => $id];
            });
            
            for ($i = 1; $i <= 5; $i++) {
                $queue->push('accuracy-job', ['id' => $i]);
            }
            
            $queue->work();
            
            $metrics = $queue->metrics()->toArray();
            
            $expectedEnqueued = 5;
            $expectedCompleted = 4;
            $expectedFailed = 1;
            $expectedRetried = 1;
            
            if ($metrics['enqueued'] === $expectedEnqueued &&
                $metrics['completed'] === $expectedCompleted &&
                $metrics['failed'] === $expectedFailed &&
                $metrics['retried'] === $expectedRetried) {
                $this->pass("Metrics accurate (E:5, C:4, F:1, R:1)");
            } else {
                $this->fail("Metrics inaccurate (E:{$metrics['enqueued']}, C:{$metrics['completed']}, F:{$metrics['failed']}, R:{$metrics['retried']})");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testExtendedSoakMetrics(): void {
        echo "Test: Extended soak metrics (30s)... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 2,
                'metrics_interval' => 0,
            ]);
            
            $queue->register('soak-job', function($payload, $job) {
                usleep(100000);
                return ['id' => $payload['id']];
            });
            
            $jobCount = 100;
            for ($i = 0; $i < $jobCount; $i++) {
                $queue->push('soak-job', ['id' => $i]);
            }
            
            $memoryBefore = memory_get_usage(true);
            $start = microtime(true);
            
            $queue->work();
            
            $duration = microtime(true) - $start;
            $memoryAfter = memory_get_usage(true);
            $memoryGrowth = ($memoryAfter - $memoryBefore) / 1024 / 1024;
            
            $metrics = $queue->metrics()->toArray();
            $throughput = $jobCount / $duration;
            
            if ($metrics['completed'] === $jobCount && $memoryGrowth < 5) {
                $this->pass("Soak test passed: {$jobCount} jobs, " . 
                           number_format($throughput, 2) . " jobs/sec, " .
                           number_format($memoryGrowth, 2) . " MB growth");
            } else {
                $this->fail("Soak test failed (completed: {$metrics['completed']}, growth: " . 
                           number_format($memoryGrowth, 2) . " MB)");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function pass(string $message): void {
        echo "✓ PASS - {$message}\n";
        $this->passed++;
        $this->results[] = ['status' => 'pass', 'message' => $message];
    }
    
    private function fail(string $message): void {
        echo "✗ FAIL - {$message}\n";
        $this->failed++;
        $this->results[] = ['status' => 'fail', 'message' => $message];
    }
    
    private function printResults(): void {
        echo "\n=== Observability Test Results ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Total: " . ($this->passed + $this->failed) . "\n";
        echo ($this->failed === 0 ? "✓ All observability tests passed!\n" : "✗ Some observability tests failed\n");
    }
}

if (php_sapi_name() === 'cli') {
    $test = new ObservabilityTest();
    $test->run();
}
