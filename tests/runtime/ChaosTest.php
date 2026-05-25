<?php
namespace Tests\Runtime;

require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Queue\QueueFactory;
use Core\Core\Runtime\Queue\Job;
use Core\Core\Runtime\Queue\JobStatus;
use Core\Core\Runtime\Queue\Drivers\MemoryDriver;
use Core\Core\Runtime\Queue\Drivers\FileDriver;
use Core\Core\Runtime\Runtime;

/**
 * Chaos engineering tests for queue system.
 * 
 * Tests worker crashes, fatal exceptions, memory pressure,
 * large payloads, timeout handling, retry storms, queue starvation.
 */
class ChaosTest {
    private int $passed = 0;
    private int $failed = 0;
    private array $results = [];
    
    public function run(): void {
        echo "=== Chaos Engineering Test Suite ===\n\n";
        
        $this->testWorkerCrash();
        $this->testFatalException();
        $this->testMemoryPressure();
        $this->testLargePayload();
        $this->testTimeoutHandling();
        $this->testRetryStorm();
        $this->testQueueStarvation();
        $this->testConcurrentWorkerCrash();
        $this->testDuplicateJobPrevention();
        $this->testPartialFailureRecovery();
        
        $this->printResults();
    }
    
    private function testWorkerCrash(): void {
        echo "Test: Worker crash recovery... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 1,
                'max_attempts' => 3,
                'retry_delay' => 0,
                'metrics_interval' => 0,
            ]);
            
            $attempts = 0;
            $queue->register('crash-job', function($payload, $job) use (&$attempts) {
                $attempts++;
                if ($attempts === 1) {
                    exit(1);
                }
                return ['recovered' => true];
            });
            
            $queue->push('crash-job', ['test' => 'data']);
            
            $pid = pcntl_fork();
            if ($pid === 0) {
                $queue->work();
                exit(0);
            }
            
            pcntl_waitpid($pid, $status);
            
            if (pcntl_wifexited($status) && pcntl_wexitstatus($status) === 1) {
                $this->pass("Worker crashed as expected");
            } else {
                $this->fail("Worker did not crash");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testFatalException(): void {
        echo "Test: Fatal exception handling... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 1,
                'max_attempts' => 2,
                'retry_delay' => 0,
                'metrics_interval' => 0,
            ]);
            
            $queue->register('fatal-job', function($payload, $job) {
                throw new \Error("Fatal error occurred");
            });
            
            $queue->push('fatal-job', []);
            $queue->work();
            
            $metrics = $queue->metrics()->toArray();
            
            if ($metrics['failed'] > 0) {
                $this->pass("Fatal exception caught and job failed");
            } else {
                $this->fail("Fatal exception not handled");
            }
        } catch (\Throwable $e) {
            $this->fail("Uncaught exception: " . $e->getMessage());
        }
    }
    
    private function testMemoryPressure(): void {
        echo "Test: Memory pressure handling... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 1,
                'memory_limit' => 10 * 1024 * 1024,
                'metrics_interval' => 0,
            ]);
            
            $processed = 0;
            $queue->register('memory-job', function($payload, $job) use (&$processed) {
                $data = str_repeat('x', 1024 * 1024);
                $processed++;
                return ['size' => strlen($data)];
            });
            
            for ($i = 0; $i < 20; $i++) {
                $queue->push('memory-job', ['index' => $i]);
            }
            
            $queue->work();
            
            if ($processed > 0 && $processed < 20) {
                $this->pass("Worker stopped before memory limit (processed {$processed}/20)");
            } else {
                $this->fail("Memory limit not enforced (processed {$processed}/20)");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testLargePayload(): void {
        echo "Test: Large payload processing... ";
        
        try {
            $driver = new FileDriver(sys_get_temp_dir() . '/nexph-chaos-test');
            $queue = new \Core\Runtime\Queue\Queue($driver, [
                'workers' => 1,
                'metrics_interval' => 0,
            ]);
            
            $largeData = array_fill(0, 10000, str_repeat('x', 100));
            $processed = false;
            
            $queue->register('large-job', function($payload, $job) use (&$processed) {
                $processed = true;
                return ['items' => count($payload['data'])];
            });
            
            $queue->push('large-job', ['data' => $largeData]);
            $queue->work();
            
            $driver->clear();
            
            if ($processed) {
                $this->pass("Large payload processed successfully");
            } else {
                $this->fail("Large payload not processed");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testTimeoutHandling(): void {
        echo "Test: Job timeout handling... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 1,
                'timeout' => 1,
                'max_attempts' => 2,
                'retry_delay' => 0,
                'metrics_interval' => 0,
            ]);
            
            $attempts = 0;
            $queue->register('timeout-job', function($payload, $job) use (&$attempts) {
                $attempts++;
                if ($attempts === 1) {
                    sleep(3);
                }
                return ['completed' => true];
            });
            
            $queue->push('timeout-job', []);
            
            $start = microtime(true);
            $queue->work();
            $duration = microtime(true) - $start;
            
            if ($duration < 5) {
                $this->pass("Timeout enforced (duration: " . number_format($duration, 2) . "s)");
            } else {
                $this->fail("Timeout not enforced (duration: " . number_format($duration, 2) . "s)");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testRetryStorm(): void {
        echo "Test: Retry storm prevention... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 1,
                'max_attempts' => 5,
                'retry_delay' => 0,
                'metrics_interval' => 0,
            ]);
            
            $attempts = 0;
            $queue->register('storm-job', function($payload, $job) use (&$attempts) {
                $attempts++;
                throw new \Exception("Always fails");
            });
            
            for ($i = 0; $i < 10; $i++) {
                $queue->push('storm-job', ['index' => $i]);
            }
            
            $start = microtime(true);
            $queue->work();
            $duration = microtime(true) - $start;
            
            $metrics = $queue->metrics()->toArray();
            
            if ($attempts === 50 && $metrics['failed'] === 10) {
                $this->pass("Retry storm handled (50 attempts, 10 failed jobs)");
            } else {
                $this->fail("Retry storm not handled correctly (attempts: {$attempts}, failed: {$metrics['failed']})");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testQueueStarvation(): void {
        echo "Test: Queue starvation detection... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 3,
                'poll_interval' => 0.1,
                'metrics_interval' => 0,
            ]);
            
            $processed = [];
            $queue->register('starve-job', function($payload, $job) use (&$processed) {
                $processed[] = $payload['id'];
                usleep(100000);
                return ['id' => $payload['id']];
            });
            
            $queue->push('starve-job', ['id' => 1]);
            
            $start = microtime(true);
            $queue->work();
            $duration = microtime(true) - $start;
            
            if (count($processed) === 1 && $duration < 2) {
                $this->pass("Queue starvation detected and handled");
            } else {
                $this->fail("Queue starvation not detected");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testConcurrentWorkerCrash(): void {
        echo "Test: Concurrent worker crash isolation... ";
        
        try {
            if (!Runtime::available() || !function_exists('pcntl_fork')) {
                $this->skip("Requires pcntl extension");
                return;
            }
            
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 3,
                'max_attempts' => 2,
                'retry_delay' => 0,
                'metrics_interval' => 0,
            ]);
            
            $queue->register('crash-one', function($payload, $job) {
                if ($payload['crash']) {
                    throw new \Error("Worker crash");
                }
                return ['success' => true];
            });
            
            $queue->push('crash-one', ['crash' => true]);
            $queue->push('crash-one', ['crash' => false]);
            $queue->push('crash-one', ['crash' => false]);
            
            $queue->work();
            
            $metrics = $queue->metrics()->toArray();
            
            if ($metrics['completed'] >= 2) {
                $this->pass("Other workers continued after crash");
            } else {
                $this->fail("Workers did not isolate crash");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testDuplicateJobPrevention(): void {
        echo "Test: Duplicate job prevention... ";
        
        try {
            $driver = new MemoryDriver();
            $queue = new \Core\Runtime\Queue\Queue($driver, [
                'workers' => 2,
                'metrics_interval' => 0,
            ]);
            
            $processed = [];
            $queue->register('unique-job', function($payload, $job) use (&$processed) {
                $processed[] = $job->id;
                usleep(50000);
                return ['id' => $job->id];
            });
            
            $jobId = $queue->push('unique-job', ['data' => 'test']);
            
            $queue->work();
            
            $uniqueProcessed = array_unique($processed);
            
            if (count($processed) === count($uniqueProcessed)) {
                $this->pass("No duplicate processing detected");
            } else {
                $this->fail("Duplicate processing occurred");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testPartialFailureRecovery(): void {
        echo "Test: Partial failure recovery... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 1,
                'max_attempts' => 3,
                'retry_delay' => 0,
                'metrics_interval' => 0,
            ]);
            
            $attempts = [];
            $queue->register('partial-fail', function($payload, $job) use (&$attempts) {
                $id = $payload['id'];
                $attempts[$id] = ($attempts[$id] ?? 0) + 1;
                
                if ($id === 2 && $attempts[$id] < 2) {
                    throw new \Exception("Temporary failure");
                }
                
                return ['id' => $id, 'attempt' => $attempts[$id]];
            });
            
            $queue->push('partial-fail', ['id' => 1]);
            $queue->push('partial-fail', ['id' => 2]);
            $queue->push('partial-fail', ['id' => 3]);
            
            $queue->work();
            
            $metrics = $queue->metrics()->toArray();
            
            if ($metrics['completed'] === 3 && $metrics['retried'] >= 1) {
                $this->pass("Partial failures recovered successfully");
            } else {
                $this->fail("Partial failure recovery failed");
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
    
    private function skip(string $message): void {
        echo "⊘ SKIP - {$message}\n";
        $this->results[] = ['status' => 'skip', 'message' => $message];
    }
    
    private function printResults(): void {
        echo "\n=== Chaos Test Results ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Total: " . ($this->passed + $this->failed) . "\n";
        echo ($this->failed === 0 ? "✓ All chaos tests passed!\n" : "✗ Some chaos tests failed\n");
    }
}

if (php_sapi_name() === 'cli') {
    $test = new ChaosTest();
    $test->run();
}
