<?php
/**
 * Test suite for Runtime Queue system.
 */

require_once __DIR__ . '/../autoload.php';

use Core\Runtime\Queue\QueueFactory;
use Core\Runtime\Queue\Job;
use Core\Runtime\Queue\JobStatus;
use Core\Runtime\Queue\Drivers\MemoryDriver;

class QueueTest {
    private int $passed = 0;
    private int $failed = 0;
    
    public function run(): void {
        echo "=== Runtime Queue Test Suite ===\n\n";
        
        $this->testBasicPushPop();
        $this->testJobExecution();
        $this->testDelayedJobs();
        $this->testRetryLogic();
        $this->testDeadLetterQueue();
        $this->testMultipleWorkers();
        $this->testMetrics();
        $this->testDrivers();
        
        echo "\n=== Test Results ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo ($this->failed === 0 ? "✓ All tests passed!\n" : "✗ Some tests failed\n");
    }
    
    private function testBasicPushPop(): void {
        echo "Test: Basic push/pop... ";
        
        $driver = new MemoryDriver();
        $job = new Job([
            'id' => 'test-1',
            'name' => 'test-job',
            'payload' => ['data' => 'value'],
            'status' => JobStatus::PENDING,
            'attempts' => 0,
            'max_attempts' => 3,
            'timeout' => 300,
            'created_at' => time(),
            'available_at' => time(),
        ]);
        
        $driver->push($job);
        $popped = $driver->pop();
        
        if ($popped && $popped->id === 'test-1' && $popped->name === 'test-job') {
            echo "✓ PASS\n";
            $this->passed++;
        } else {
            echo "✗ FAIL\n";
            $this->failed++;
        }
    }
    
    private function testJobExecution(): void {
        echo "Test: Job execution... ";
        
        $queue = QueueFactory::createWithDriver('memory', ['workers' => 1, 'metrics_interval' => 0]);
        $executed = false;
        
        $queue->register('test', function($payload, $job) use (&$executed) {
            $executed = true;
            return ['result' => 'success'];
        });
        
        $queue->push('test', ['data' => 'value']);
        $queue->work();
        
        if ($executed) {
            echo "✓ PASS\n";
            $this->passed++;
        } else {
            echo "✗ FAIL\n";
            $this->failed++;
        }
    }
    
    private function testDelayedJobs(): void {
        echo "Test: Delayed jobs... ";
        
        $driver = new MemoryDriver();
        $futureTime = time() + 3600;
        
        $job = new Job([
            'id' => 'delayed-1',
            'name' => 'delayed-job',
            'payload' => [],
            'status' => JobStatus::PENDING,
            'attempts' => 0,
            'max_attempts' => 3,
            'timeout' => 300,
            'created_at' => time(),
            'available_at' => $futureTime,
        ]);
        
        $driver->push($job);
        $popped = $driver->pop();
        
        if ($popped === null) {
            echo "✓ PASS\n";
            $this->passed++;
        } else {
            echo "✗ FAIL (job available too early)\n";
            $this->failed++;
        }
    }
    
    private function testRetryLogic(): void {
        echo "Test: Retry logic... ";
        
        $queue = QueueFactory::createWithDriver('memory', [
            'workers' => 1,
            'max_attempts' => 3,
            'retry_delay' => 0,
            'metrics_interval' => 0,
        ]);
        $attempts = 0;
        
        $queue->register('failing', function($payload, $job) use (&$attempts) {
            $attempts++;
            if ($attempts < 3) {
                throw new \Exception('Temporary failure');
            }
            return ['success' => true];
        });
        
        $queue->push('failing', []);
        $queue->work();
        
        if ($attempts === 3) {
            echo "✓ PASS\n";
            $this->passed++;
        } else {
            echo "✗ FAIL (expected 3 attempts, got {$attempts})\n";
            $this->failed++;
        }
    }
    
    private function testDeadLetterQueue(): void {
        echo "Test: Dead letter queue... ";
        
        $driver = new MemoryDriver();
        $queue = new \Runtime\Queue\Queue($driver, [
            'workers' => 1,
            'max_attempts' => 2,
            'retry_delay' => 0,
            'metrics_interval' => 0,
        ]);
        
        $queue->register('always-fail', function($payload, $job) {
            throw new \Exception('Always fails');
        });
        
        $queue->push('always-fail', []);
        $queue->work();
        
        $deadLetters = $driver->getDeadLetters();
        
        if (count($deadLetters) > 0) {
            $job = $deadLetters[0];
            if ($job->status === JobStatus::FAILED && $job->attempts === 2) {
                echo "✓ PASS\n";
                $this->passed++;
            } else {
                echo "✗ FAIL (status: {$job->status}, attempts: {$job->attempts})\n";
                $this->failed++;
            }
        } else {
            echo "✗ FAIL (no dead letters found)\n";
            $this->failed++;
        }
    }
    
    private function testMultipleWorkers(): void {
        echo "Test: Multiple workers... ";
        
        $queue = QueueFactory::createWithDriver('memory', ['workers' => 3, 'metrics_interval' => 0]);
        $processed = [];
        
        $queue->register('concurrent', function($payload, $job) use (&$processed) {
            $processed[] = $payload['id'];
            return ['id' => $payload['id']];
        });
        
        for ($i = 1; $i <= 10; $i++) {
            $queue->push('concurrent', ['id' => $i]);
        }
        
        $queue->work();
        
        if (count($processed) === 10) {
            echo "✓ PASS\n";
            $this->passed++;
        } else {
            echo "✗ FAIL (processed " . count($processed) . " jobs)\n";
            $this->failed++;
        }
    }
    
    private function testMetrics(): void {
        echo "Test: Metrics tracking... ";
        
        $queue = QueueFactory::createWithDriver('memory', ['workers' => 1, 'metrics_interval' => 0]);
        
        $queue->register('metric-test', function($payload, $job) {
            return ['done' => true];
        });
        
        $queue->push('metric-test', []);
        $queue->push('metric-test', []);
        $queue->push('metric-test', []);
        
        $queue->work();
        
        $metrics = $queue->metrics()->toArray();
        
        if ($metrics['enqueued'] === 3 && $metrics['completed'] === 3) {
            echo "✓ PASS\n";
            $this->passed++;
        } else {
            echo "✗ FAIL\n";
            $this->failed++;
        }
    }
    
    private function testDrivers(): void {
        echo "Test: Driver implementations... ";
        
        $drivers = [
            'memory' => new MemoryDriver(),
        ];
        
        $allPassed = true;
        
        foreach ($drivers as $name => $driver) {
            $job = new Job([
                'id' => "driver-test-{$name}",
                'name' => 'test',
                'payload' => ['driver' => $name],
                'status' => JobStatus::PENDING,
                'attempts' => 0,
                'max_attempts' => 3,
                'timeout' => 300,
                'created_at' => time(),
                'available_at' => time(),
            ]);
            
            $driver->push($job);
            $popped = $driver->pop();
            
            if (!$popped || $popped->id !== $job->id) {
                $allPassed = false;
                break;
            }
            
            $driver->clear();
        }
        
        if ($allPassed) {
            echo "✓ PASS\n";
            $this->passed++;
        } else {
            echo "✗ FAIL\n";
            $this->failed++;
        }
    }
}

// Run tests
$test = new QueueTest();
$test->run();
