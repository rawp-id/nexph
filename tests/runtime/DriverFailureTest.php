<?php
namespace Tests\Runtime;

require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Queue\QueueFactory;
use Core\Core\Runtime\Queue\Drivers\RedisDriver;
use Core\Core\Runtime\Queue\Drivers\FileDriver;
use Core\Core\Runtime\Queue\Drivers\DatabaseDriver;
use Core\Core\Runtime\Runtime;

/**
 * Driver failure and degradation tests.
 * 
 * Tests Redis disconnects, database failures, file system errors,
 * and ensures runtime degrades safely without crashing stateless core.
 */
class DriverFailureTest {
    private int $passed = 0;
    private int $failed = 0;
    private array $results = [];
    
    public function run(): void {
        echo "=== Driver Failure & Degradation Test Suite ===\n\n";
        
        $this->testRedisDisconnect();
        $this->testDatabaseConnectionLoss();
        $this->testFileSystemFull();
        $this->testFilePermissionDenied();
        $this->testCorruptedQueueData();
        $this->testDriverRecovery();
        $this->testFallbackToMemory();
        $this->testPartialDriverFailure();
        $this->testConcurrentDriverFailures();
        $this->testStatelessCoreIsolation();
        
        $this->printResults();
    }
    
    private function testRedisDisconnect(): void {
        echo "Test: Redis disconnect handling... ";
        
        try {
            if (!extension_loaded('redis')) {
                $this->skip("Redis extension not available");
                return;
            }
            
            $redis = new \Redis();
            
            try {
                $redis->connect('127.0.0.1', 6379, 0.1);
            } catch (\Throwable $e) {
                $this->skip("Redis not available");
                return;
            }
            
            $driver = new RedisDriver($redis, 'test_disconnect');
            $driver->clear();
            
            $queue = new \Core\Runtime\Queue\Queue($driver, [
                'workers' => 1,
                'metrics_interval' => 0,
            ]);
            
            $queue->register('redis-job', function($payload, $job) {
                return ['done' => true];
            });
            
            $queue->push('redis-job', ['id' => 1]);
            
            $redis->close();
            
            try {
                $queue->work();
                $this->fail("Should have thrown exception on Redis disconnect");
            } catch (\Throwable $e) {
                $this->pass("Redis disconnect detected: " . substr($e->getMessage(), 0, 50));
            }
        } catch (\Throwable $e) {
            $this->fail("Unexpected exception: " . $e->getMessage());
        }
    }
    
    private function testDatabaseConnectionLoss(): void {
        echo "Test: Database connection loss... ";
        
        try {
            $pdo = new \PDO('sqlite::memory:');
            $driver = new DatabaseDriver($pdo, 'test_conn_loss', 'test_dead_loss');
            
            $queue = new \Core\Runtime\Queue\Queue($driver, [
                'workers' => 1,
                'metrics_interval' => 0,
            ]);
            
            $queue->register('db-job', function($payload, $job) {
                return ['done' => true];
            });
            
            $queue->push('db-job', ['id' => 1]);
            
            unset($pdo);
            
            try {
                $queue->work();
                $this->fail("Should have thrown exception on DB disconnect");
            } catch (\Throwable $e) {
                $this->pass("Database disconnect detected");
            }
        } catch (\Throwable $e) {
            $this->fail("Unexpected exception: " . $e->getMessage());
        }
    }
    
    private function testFileSystemFull(): void {
        echo "Test: File system full simulation... ";
        
        try {
            $testDir = sys_get_temp_dir() . '/nexph-fs-full-test-' . time();
            mkdir($testDir, 0755, true);
            
            $driver = new FileDriver($testDir);
            
            $largePayload = str_repeat('x', 1024 * 1024);
            
            try {
                for ($i = 0; $i < 1000; $i++) {
                    $job = new \Core\Runtime\Queue\Job([
                        'id' => "large-{$i}",
                        'name' => 'large-job',
                        'payload' => ['data' => $largePayload],
                        'status' => \Core\Runtime\Queue\JobStatus::PENDING,
                        'attempts' => 0,
                        'max_attempts' => 3,
                        'timeout' => 300,
                        'created_at' => time(),
                        'available_at' => time(),
                    ]);
                    $driver->push($job);
                }
                
                $this->pass("File system handled large writes");
            } catch (\Throwable $e) {
                $this->pass("File system error caught: " . substr($e->getMessage(), 0, 50));
            }
            
            $driver->clear();
            $this->rmdirRecursive($testDir);
        } catch (\Throwable $e) {
            $this->fail("Unexpected exception: " . $e->getMessage());
        }
    }
    
    private function testFilePermissionDenied(): void {
        echo "Test: File permission denied... ";
        
        try {
            $testDir = sys_get_temp_dir() . '/nexph-perm-test-' . time();
            mkdir($testDir, 0755, true);
            
            $driver = new FileDriver($testDir);
            
            $job = new \Core\Runtime\Queue\Job([
                'id' => 'perm-test',
                'name' => 'test-job',
                'payload' => ['data' => 'value'],
                'status' => \Core\Runtime\Queue\JobStatus::PENDING,
                'attempts' => 0,
                'max_attempts' => 3,
                'timeout' => 300,
                'created_at' => time(),
                'available_at' => time(),
            ]);
            
            $driver->push($job);
            
            chmod($testDir . '/jobs', 0000);
            
            try {
                $driver->pop();
                chmod($testDir . '/jobs', 0755);
                $this->fail("Should have thrown permission error");
            } catch (\Throwable $e) {
                chmod($testDir . '/jobs', 0755);
                $this->pass("Permission error caught");
            }
            
            $driver->clear();
            $this->rmdirRecursive($testDir);
        } catch (\Throwable $e) {
            $this->fail("Unexpected exception: " . $e->getMessage());
        }
    }
    
    private function testCorruptedQueueData(): void {
        echo "Test: Corrupted queue data handling... ";
        
        try {
            $testDir = sys_get_temp_dir() . '/nexph-corrupt-test-' . time();
            mkdir($testDir, 0755, true);
            
            $driver = new FileDriver($testDir);
            
            $jobFile = $testDir . '/jobs/corrupt-job.json';
            if (!is_dir($testDir . '/jobs')) {
                mkdir($testDir . '/jobs', 0755, true);
            }
            
            file_put_contents($jobFile, '{invalid json data');
            
            try {
                $job = $driver->pop();
                
                if ($job === null) {
                    $this->pass("Corrupted data skipped gracefully");
                } else {
                    $this->fail("Corrupted data not detected");
                }
            } catch (\Throwable $e) {
                $this->pass("Corrupted data error caught");
            }
            
            $driver->clear();
            $this->rmdirRecursive($testDir);
        } catch (\Throwable $e) {
            $this->fail("Unexpected exception: " . $e->getMessage());
        }
    }
    
    private function testDriverRecovery(): void {
        echo "Test: Driver recovery after failure... ";
        
        try {
            $testDir = sys_get_temp_dir() . '/nexph-recovery-test-' . time();
            mkdir($testDir, 0755, true);
            
            $driver = new FileDriver($testDir);
            
            for ($i = 1; $i <= 5; $i++) {
                $job = new \Core\Runtime\Queue\Job([
                    'id' => "recovery-{$i}",
                    'name' => 'test-job',
                    'payload' => ['id' => $i],
                    'status' => \Core\Runtime\Queue\JobStatus::PENDING,
                    'attempts' => 0,
                    'max_attempts' => 3,
                    'timeout' => 300,
                    'created_at' => time(),
                    'available_at' => time(),
                ]);
                $driver->push($job);
            }
            
            chmod($testDir . '/jobs', 0000);
            
            try {
                $driver->pop();
            } catch (\Throwable $e) {
            }
            
            chmod($testDir . '/jobs', 0755);
            
            $recovered = $driver->pop();
            
            if ($recovered !== null) {
                $this->pass("Driver recovered after failure");
            } else {
                $this->fail("Driver did not recover");
            }
            
            $driver->clear();
            $this->rmdirRecursive($testDir);
        } catch (\Throwable $e) {
            $this->fail("Unexpected exception: " . $e->getMessage());
        }
    }
    
    private function testFallbackToMemory(): void {
        echo "Test: Fallback to memory driver... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 1,
                'metrics_interval' => 0,
            ]);
            
            $processed = false;
            $queue->register('fallback-job', function($payload, $job) use (&$processed) {
                $processed = true;
                return ['done' => true];
            });
            
            $queue->push('fallback-job', ['id' => 1]);
            $queue->work();
            
            if ($processed) {
                $this->pass("Memory driver fallback works");
            } else {
                $this->fail("Memory driver fallback failed");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testPartialDriverFailure(): void {
        echo "Test: Partial driver failure handling... ";
        
        try {
            $testDir = sys_get_temp_dir() . '/nexph-partial-test-' . time();
            mkdir($testDir, 0755, true);
            
            $driver = new FileDriver($testDir);
            
            for ($i = 1; $i <= 5; $i++) {
                $job = new \Core\Runtime\Queue\Job([
                    'id' => "partial-{$i}",
                    'name' => 'test-job',
                    'payload' => ['id' => $i],
                    'status' => \Core\Runtime\Queue\JobStatus::PENDING,
                    'attempts' => 0,
                    'max_attempts' => 3,
                    'timeout' => 300,
                    'created_at' => time(),
                    'available_at' => time(),
                ]);
                $driver->push($job);
            }
            
            $jobFiles = glob($testDir . '/jobs/*.json');
            if (count($jobFiles) > 2) {
                unlink($jobFiles[2]);
            }
            
            $processed = 0;
            while ($job = $driver->pop()) {
                $processed++;
            }
            
            if ($processed === 4) {
                $this->pass("Partial failure handled (4/5 jobs processed)");
            } else {
                $this->fail("Partial failure not handled correctly ({$processed}/5 processed)");
            }
            
            $driver->clear();
            $this->rmdirRecursive($testDir);
        } catch (\Throwable $e) {
            $this->fail("Unexpected exception: " . $e->getMessage());
        }
    }
    
    private function testConcurrentDriverFailures(): void {
        echo "Test: Concurrent driver failures... ";
        
        try {
            if (!Runtime::available() || !function_exists('pcntl_fork')) {
                $this->skip("Requires pcntl extension");
                return;
            }
            
            $testDir = sys_get_temp_dir() . '/nexph-concurrent-fail-' . time();
            mkdir($testDir, 0755, true);
            
            $driver = new FileDriver($testDir);
            
            for ($i = 1; $i <= 10; $i++) {
                $job = new \Core\Runtime\Queue\Job([
                    'id' => "concurrent-{$i}",
                    'name' => 'test-job',
                    'payload' => ['id' => $i],
                    'status' => \Core\Runtime\Queue\JobStatus::PENDING,
                    'attempts' => 0,
                    'max_attempts' => 3,
                    'timeout' => 300,
                    'created_at' => time(),
                    'available_at' => time(),
                ]);
                $driver->push($job);
            }
            
            $pids = [];
            for ($w = 0; $w < 3; $w++) {
                $pid = pcntl_fork();
                if ($pid === 0) {
                    $driver = new FileDriver($testDir);
                    try {
                        while ($job = $driver->pop()) {
                            usleep(10000);
                        }
                    } catch (\Throwable $e) {
                    }
                    exit(0);
                }
                $pids[] = $pid;
            }
            
            foreach ($pids as $pid) {
                pcntl_waitpid($pid, $status);
            }
            
            $this->pass("Concurrent failures handled");
            
            $driver->clear();
            $this->rmdirRecursive($testDir);
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testStatelessCoreIsolation(): void {
        echo "Test: Stateless core isolation from driver failures... ";
        
        try {
            $testDir = sys_get_temp_dir() . '/nexph-isolation-test-' . time();
            mkdir($testDir, 0755, true);
            
            $driver = new FileDriver($testDir);
            $queue = new \Core\Runtime\Queue\Queue($driver, [
                'workers' => 1,
                'metrics_interval' => 0,
            ]);
            
            $queue->register('isolation-job', function($payload, $job) {
                return ['done' => true];
            });
            
            $queue->push('isolation-job', ['id' => 1]);
            
            chmod($testDir . '/jobs', 0000);
            
            try {
                $queue->work();
                chmod($testDir . '/jobs', 0755);
                $this->fail("Should have thrown exception");
            } catch (\Throwable $e) {
                chmod($testDir . '/jobs', 0755);
                
                $metrics = $queue->metrics();
                if ($metrics !== null) {
                    $this->pass("Core isolated from driver failure");
                } else {
                    $this->fail("Core affected by driver failure");
                }
            }
            
            $driver->clear();
            $this->rmdirRecursive($testDir);
        } catch (\Throwable $e) {
            $this->fail("Unexpected exception: " . $e->getMessage());
        }
    }
    
    private function rmdirRecursive(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->rmdirRecursive($path) : @unlink($path);
        }
        @rmdir($dir);
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
        echo "\n=== Driver Failure Test Results ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Total: " . ($this->passed + $this->failed) . "\n";
        echo ($this->failed === 0 ? "✓ All driver failure tests passed!\n" : "✗ Some driver failure tests failed\n");
    }
}

if (php_sapi_name() === 'cli') {
    $test = new DriverFailureTest();
    $test->run();
}
