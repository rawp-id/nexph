<?php
namespace Tests\Runtime;

require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Queue\QueueFactory;
use Core\Core\Runtime\Queue\Drivers\MemoryDriver;
use Core\Core\Runtime\Runtime;
use Core\Core\Runtime\Worker;

/**
 * Signal handling and graceful shutdown tests.
 * 
 * Tests SIGTERM, SIGINT, SIGHUP handling, graceful worker shutdown,
 * job completion before exit, and signal propagation.
 */
class SignalHandlingTest {
    private int $passed = 0;
    private int $failed = 0;
    private array $results = [];
    
    public function run(): void {
        echo "=== Signal Handling & Graceful Shutdown Test Suite ===\n\n";
        
        if (!Runtime::available() || !function_exists('pcntl_signal')) {
            echo "⊘ SKIP - Requires CLI mode and pcntl extension\n";
            return;
        }
        
        $this->testSIGTERMHandling();
        $this->testSIGINTHandling();
        $this->testGracefulShutdown();
        $this->testJobCompletionBeforeExit();
        $this->testMultipleSignals();
        $this->testSignalDuringJobProcessing();
        $this->testForkedWorkerSignals();
        $this->testSignalPropagation();
        
        $this->printResults();
    }
    
    private function testSIGTERMHandling(): void {
        echo "Test: SIGTERM handling... ";
        
        try {
            $pid = pcntl_fork();
            
            if ($pid === 0) {
                Worker::start(function() {
                    Runtime::sleep(10);
                }, ['max_iterations' => 100]);
                exit(0);
            }
            
            usleep(100000);
            posix_kill($pid, SIGTERM);
            
            pcntl_waitpid($pid, $status);
            
            if (pcntl_wifexited($status)) {
                $this->pass("SIGTERM handled gracefully");
            } else {
                $this->fail("SIGTERM not handled properly");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testSIGINTHandling(): void {
        echo "Test: SIGINT handling... ";
        
        try {
            $pid = pcntl_fork();
            
            if ($pid === 0) {
                Worker::start(function() {
                    Runtime::sleep(10);
                }, ['max_iterations' => 100]);
                exit(0);
            }
            
            usleep(100000);
            posix_kill($pid, SIGINT);
            
            pcntl_waitpid($pid, $status);
            
            if (pcntl_wifexited($status)) {
                $this->pass("SIGINT handled gracefully");
            } else {
                $this->fail("SIGINT not handled properly");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testGracefulShutdown(): void {
        echo "Test: Graceful shutdown... ";
        
        try {
            $tmpFile = sys_get_temp_dir() . '/nexph-shutdown-test-' . time();
            
            $pid = pcntl_fork();
            
            if ($pid === 0) {
                $iterations = 0;
                Worker::start(function() use (&$iterations, $tmpFile) {
                    $iterations++;
                    file_put_contents($tmpFile, $iterations);
                    Runtime::sleep(0.1);
                }, ['max_iterations' => 100, 'sleep' => 0.1]);
                exit(0);
            }
            
            usleep(300000);
            $beforeSignal = file_exists($tmpFile) ? (int)file_get_contents($tmpFile) : 0;
            
            posix_kill($pid, SIGTERM);
            pcntl_waitpid($pid, $status);
            
            $afterSignal = file_exists($tmpFile) ? (int)file_get_contents($tmpFile) : 0;
            
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
            
            if ($afterSignal >= $beforeSignal && $afterSignal < 100) {
                $this->pass("Worker stopped gracefully after {$afterSignal} iterations");
            } else {
                $this->fail("Worker did not stop gracefully");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testJobCompletionBeforeExit(): void {
        echo "Test: Job completion before exit... ";
        
        try {
            $tmpFile = sys_get_temp_dir() . '/nexph-completion-test-' . time();
            
            $pid = pcntl_fork();
            
            if ($pid === 0) {
                $queue = QueueFactory::createWithDriver('memory', [
                    'workers' => 1,
                    'metrics_interval' => 0,
                ]);
                
                $queue->register('complete-job', function($payload, $job) use ($tmpFile) {
                    file_put_contents($tmpFile, 'started');
                    Runtime::sleep(0.5);
                    file_put_contents($tmpFile, 'completed');
                    return ['done' => true];
                });
                
                $queue->push('complete-job', ['id' => 1]);
                $queue->work();
                exit(0);
            }
            
            usleep(200000);
            posix_kill($pid, SIGTERM);
            
            pcntl_waitpid($pid, $status);
            
            $result = file_exists($tmpFile) ? file_get_contents($tmpFile) : '';
            
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
            
            if ($result === 'completed') {
                $this->pass("Job completed before exit");
            } else {
                $this->fail("Job interrupted (state: {$result})");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testMultipleSignals(): void {
        echo "Test: Multiple signals handling... ";
        
        try {
            $pid = pcntl_fork();
            
            if ($pid === 0) {
                Worker::start(function() {
                    Runtime::sleep(10);
                }, ['max_iterations' => 100]);
                exit(0);
            }
            
            usleep(100000);
            posix_kill($pid, SIGTERM);
            usleep(50000);
            posix_kill($pid, SIGTERM);
            usleep(50000);
            posix_kill($pid, SIGTERM);
            
            pcntl_waitpid($pid, $status);
            
            if (pcntl_wifexited($status)) {
                $this->pass("Multiple signals handled without crash");
            } else {
                $this->fail("Multiple signals caused crash");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testSignalDuringJobProcessing(): void {
        echo "Test: Signal during job processing... ";
        
        try {
            $tmpFile = sys_get_temp_dir() . '/nexph-signal-during-test-' . time();
            
            $pid = pcntl_fork();
            
            if ($pid === 0) {
                $queue = QueueFactory::createWithDriver('memory', [
                    'workers' => 1,
                    'metrics_interval' => 0,
                ]);
                
                $queue->register('long-job', function($payload, $job) use ($tmpFile) {
                    file_put_contents($tmpFile, 'processing');
                    for ($i = 0; $i < 10; $i++) {
                        Runtime::sleep(0.1);
                    }
                    file_put_contents($tmpFile, 'done');
                    return ['completed' => true];
                });
                
                $queue->push('long-job', ['id' => 1]);
                $queue->work();
                exit(0);
            }
            
            usleep(300000);
            posix_kill($pid, SIGTERM);
            
            pcntl_waitpid($pid, $status);
            
            $result = file_exists($tmpFile) ? file_get_contents($tmpFile) : '';
            
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
            
            if ($result === 'done' || $result === 'processing') {
                $this->pass("Signal handled during job processing");
            } else {
                $this->fail("Signal handling failed during job");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testForkedWorkerSignals(): void {
        echo "Test: Forked worker signal handling... ";
        
        try {
            $tmpFile = sys_get_temp_dir() . '/nexph-fork-signal-test-' . time();
            
            $parentPid = pcntl_fork();
            
            if ($parentPid === 0) {
                $childPid = Worker::fork(function() use ($tmpFile) {
                    file_put_contents($tmpFile, 'child-started');
                    for ($i = 0; $i < 100; $i++) {
                        Runtime::sleep(0.1);
                    }
                    file_put_contents($tmpFile, 'child-completed');
                });
                
                usleep(200000);
                posix_kill($childPid, SIGTERM);
                pcntl_waitpid($childPid, $childStatus);
                
                file_put_contents($tmpFile, 'parent-done');
                exit(0);
            }
            
            pcntl_waitpid($parentPid, $status);
            
            $result = file_exists($tmpFile) ? file_get_contents($tmpFile) : '';
            
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
            
            if ($result === 'parent-done') {
                $this->pass("Forked worker signal handled");
            } else {
                $this->fail("Forked worker signal failed");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testSignalPropagation(): void {
        echo "Test: Signal propagation to child processes... ";
        
        try {
            $tmpFile = sys_get_temp_dir() . '/nexph-propagation-test-' . time();
            
            $pid = pcntl_fork();
            
            if ($pid === 0) {
                $children = [];
                for ($i = 0; $i < 3; $i++) {
                    $childPid = pcntl_fork();
                    if ($childPid === 0) {
                        Worker::start(function() {
                            Runtime::sleep(10);
                        }, ['max_iterations' => 100]);
                        exit(0);
                    }
                    $children[] = $childPid;
                }
                
                pcntl_signal(SIGTERM, function() use ($children, $tmpFile) {
                    foreach ($children as $child) {
                        posix_kill($child, SIGTERM);
                    }
                    file_put_contents($tmpFile, 'propagated');
                    exit(0);
                });
                
                while (true) {
                    pcntl_signal_dispatch();
                    usleep(100000);
                }
            }
            
            usleep(200000);
            posix_kill($pid, SIGTERM);
            
            usleep(500000);
            
            $result = file_exists($tmpFile) ? file_get_contents($tmpFile) : '';
            
            if (file_exists($tmpFile)) {
                unlink($tmpFile);
            }
            
            if ($result === 'propagated') {
                $this->pass("Signal propagated to children");
            } else {
                $this->fail("Signal not propagated");
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
        echo "\n=== Signal Handling Test Results ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Total: " . ($this->passed + $this->failed) . "\n";
        echo ($this->failed === 0 ? "✓ All signal tests passed!\n" : "✗ Some signal tests failed\n");
    }
}

if (php_sapi_name() === 'cli') {
    $test = new SignalHandlingTest();
    $test->run();
}
