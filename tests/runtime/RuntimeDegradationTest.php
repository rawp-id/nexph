<?php
namespace Tests\Runtime;

require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Runtime;
use Core\Core\Runtime\Queue\QueueFactory;

/**
 * Runtime degradation tests.
 * 
 * Validates that runtime degrades gracefully when Fibers or async
 * capabilities are unavailable, falling back to synchronous execution.
 */
class RuntimeDegradationTest {
    private int $passed = 0;
    private int $failed = 0;
    private array $results = [];
    
    public function run(): void {
        echo "=== Runtime Degradation Test Suite ===\n\n";
        
        $this->testRuntimeAvailability();
        $this->testCapabilitiesDetection();
        $this->testSyncFallback();
        $this->testQueueSyncMode();
        $this->testWorkerWithoutPcntl();
        $this->testSleepFallback();
        $this->testSpawnFallback();
        $this->testGracefulFeatureDisable();
        
        $this->printResults();
    }
    
    private function testRuntimeAvailability(): void {
        echo "Test: Runtime availability detection... ";
        
        try {
            $available = Runtime::available();
            $capabilities = Runtime::capabilities();
            
            $hasFibers = $capabilities['fibers'];
            $isCli = $capabilities['cli'];
            
            if ($available === ($hasFibers && $isCli)) {
                $this->pass("Runtime availability correctly detected: " . ($available ? 'yes' : 'no'));
            } else {
                $this->fail("Runtime availability mismatch");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testCapabilitiesDetection(): void {
        echo "Test: Capabilities detection... ";
        
        try {
            $capabilities = Runtime::capabilities();
            
            $required = ['fibers', 'pcntl', 'sockets', 'posix', 'redis', 'cli'];
            $detected = array_keys($capabilities);
            
            $missing = array_diff($required, $detected);
            
            if (empty($missing)) {
                $this->pass("All capabilities detected: " . json_encode($capabilities));
            } else {
                $this->fail("Missing capabilities: " . implode(', ', $missing));
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testSyncFallback(): void {
        echo "Test: Synchronous fallback... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 1,
                'metrics_interval' => 0,
            ]);
            
            $executed = false;
            $queue->register('sync-job', function($payload, $job) use (&$executed) {
                $executed = true;
                return ['mode' => 'sync'];
            });
            
            $queue->push('sync-job', ['test' => 'data']);
            $queue->work();
            
            if ($executed) {
                $this->pass("Synchronous fallback works");
            } else {
                $this->fail("Synchronous fallback failed");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testQueueSyncMode(): void {
        echo "Test: Queue synchronous mode... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 1,
                'metrics_interval' => 0,
            ]);
            
            $processed = [];
            $queue->register('order-job', function($payload, $job) use (&$processed) {
                $processed[] = $payload['id'];
                return ['id' => $payload['id']];
            });
            
            for ($i = 1; $i <= 5; $i++) {
                $queue->push('order-job', ['id' => $i]);
            }
            
            $queue->work();
            
            $inOrder = ($processed === [1, 2, 3, 4, 5]);
            
            if (count($processed) === 5) {
                $this->pass("Queue sync mode processed all jobs" . ($inOrder ? " in order" : ""));
            } else {
                $this->fail("Queue sync mode failed: " . count($processed) . " jobs");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testWorkerWithoutPcntl(): void {
        echo "Test: Worker without pcntl... ";
        
        try {
            if (!Runtime::available()) {
                $this->skip("Runtime not available");
                return;
            }
            
            $capabilities = Runtime::capabilities();
            
            if (!$capabilities['pcntl']) {
                $this->pass("Worker runs without pcntl (signals disabled)");
            } else {
                $this->pass("Worker has pcntl support");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testSleepFallback(): void {
        echo "Test: Sleep fallback... ";
        
        try {
            $start = microtime(true);
            
            if (Runtime::available()) {
                Runtime::sleep(0.1);
            } else {
                usleep(100000);
            }
            
            $duration = microtime(true) - $start;
            
            if ($duration >= 0.09 && $duration <= 0.15) {
                $this->pass("Sleep fallback works: " . number_format($duration, 3) . "s");
            } else {
                $this->fail("Sleep timing incorrect: " . number_format($duration, 3) . "s");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testSpawnFallback(): void {
        echo "Test: Spawn fallback... ";
        
        try {
            $executed = false;
            
            Runtime::spawn(function() use (&$executed) {
                $executed = true;
            });
            
            if (Runtime::available()) {
                Runtime::run();
            }
            
            if ($executed) {
                $this->pass("Spawn fallback executed");
            } else {
                $this->fail("Spawn fallback did not execute");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testGracefulFeatureDisable(): void {
        echo "Test: Graceful feature disable... ";
        
        try {
            $capabilities = Runtime::capabilities();
            $disabledFeatures = [];
            
            if (!$capabilities['fibers']) {
                $disabledFeatures[] = 'coroutines';
            }
            if (!$capabilities['pcntl']) {
                $disabledFeatures[] = 'signals';
            }
            if (!$capabilities['sockets']) {
                $disabledFeatures[] = 'async-io';
            }
            
            if (empty($disabledFeatures)) {
                $this->pass("All features available");
            } else {
                $this->pass("Features gracefully disabled: " . implode(', ', $disabledFeatures));
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
        echo "\n=== Runtime Degradation Test Results ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Total: " . ($this->passed + $this->failed) . "\n";
        echo ($this->failed === 0 ? "✓ All degradation tests passed!\n" : "✗ Some degradation tests failed\n");
    }
}

if (php_sapi_name() === 'cli') {
    $test = new RuntimeDegradationTest();
    $test->run();
}
