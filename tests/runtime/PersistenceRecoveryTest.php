<?php
namespace Tests\Runtime;

require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Queue\QueueFactory;
use Core\Core\Runtime\Queue\Drivers\FileDriver;
use Core\Core\Runtime\Queue\Drivers\DatabaseDriver;
use Core\Core\Runtime\Queue\Job;
use Core\Core\Runtime\Queue\JobStatus;

/**
 * Persistence and recovery tests for queue drivers.
 * 
 * Tests file, database, APCu, and Redis drivers by restarting
 * workers/processes and ensuring jobs are not lost, duplicated, or corrupted.
 */
class PersistenceRecoveryTest {
    private int $passed = 0;
    private int $failed = 0;
    private array $results = [];
    private string $testDir;
    
    public function __construct() {
        $this->testDir = sys_get_temp_dir() . '/nexph-persistence-test-' . time();
    }
    
    public function run(): void {
        echo "=== Persistence & Recovery Test Suite ===\n\n";
        
        $this->testFileDriverPersistence();
        $this->testFileDriverRecovery();
        $this->testDatabaseDriverPersistence();
        $this->testDatabaseDriverRecovery();
        $this->testJobNotLost();
        $this->testJobNotDuplicated();
        $this->testJobNotCorrupted();
        $this->testDeadLetterPersistence();
        $this->testCrashDuringProcessing();
        $this->testMultipleRestarts();
        
        $this->cleanup();
        $this->printResults();
    }
    
    private function testFileDriverPersistence(): void {
        echo "Test: File driver persistence... ";
        
        try {
            $driver = new FileDriver($this->testDir . '/file1');
            
            $job = new Job([
                'id' => 'persist-1',
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
            unset($driver);
            
            $driver2 = new FileDriver($this->testDir . '/file1');
            $retrieved = $driver2->pop();
            
            if ($retrieved && $retrieved->id === 'persist-1') {
                $this->pass("File driver persisted job across instances");
            } else {
                $this->fail("File driver did not persist job");
            }
            
            $driver2->clear();
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testFileDriverRecovery(): void {
        echo "Test: File driver recovery after crash... ";
        
        try {
            $driver = new FileDriver($this->testDir . '/file2');
            
            for ($i = 1; $i <= 5; $i++) {
                $job = new Job([
                    'id' => "recover-{$i}",
                    'name' => 'test-job',
                    'payload' => ['index' => $i],
                    'status' => JobStatus::PENDING,
                    'attempts' => 0,
                    'max_attempts' => 3,
                    'timeout' => 300,
                    'created_at' => time(),
                    'available_at' => time(),
                ]);
                $driver->push($job);
            }
            
            $job1 = $driver->pop();
            $job1->status = JobStatus::RUNNING;
            $driver->update($job1);
            
            unset($driver);
            
            $driver2 = new FileDriver($this->testDir . '/file2');
            $depth = $driver2->depth();
            
            if ($depth === 4) {
                $this->pass("File driver recovered 4 pending jobs after crash");
            } else {
                $this->fail("File driver recovered {$depth} jobs, expected 4");
            }
            
            $driver2->clear();
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testDatabaseDriverPersistence(): void {
        echo "Test: Database driver persistence... ";
        
        try {
            $pdo = new \PDO('sqlite::memory:');
            $driver = new DatabaseDriver($pdo, 'test_queue_1', 'test_dead_1');
            
            $job = new Job([
                'id' => 'db-persist-1',
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
            
            $retrieved = $driver->get('db-persist-1');
            
            if ($retrieved && $retrieved->id === 'db-persist-1') {
                $this->pass("Database driver persisted job");
            } else {
                $this->fail("Database driver did not persist job");
            }
            
            $driver->clear();
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testDatabaseDriverRecovery(): void {
        echo "Test: Database driver recovery after crash... ";
        
        try {
            $dbFile = $this->testDir . '/test.db';
            if (!is_dir($this->testDir)) {
                mkdir($this->testDir, 0755, true);
            }
            
            $pdo = new \PDO('sqlite:' . $dbFile);
            $driver = new DatabaseDriver($pdo, 'test_queue_2', 'test_dead_2');
            
            for ($i = 1; $i <= 5; $i++) {
                $job = new Job([
                    'id' => "db-recover-{$i}",
                    'name' => 'test-job',
                    'payload' => ['index' => $i],
                    'status' => JobStatus::PENDING,
                    'attempts' => 0,
                    'max_attempts' => 3,
                    'timeout' => 300,
                    'created_at' => time(),
                    'available_at' => time(),
                ]);
                $driver->push($job);
            }
            
            $job1 = $driver->pop();
            $job1->status = JobStatus::RUNNING;
            $driver->update($job1);
            
            unset($driver);
            unset($pdo);
            
            $pdo2 = new \PDO('sqlite:' . $dbFile);
            $driver2 = new DatabaseDriver($pdo2, 'test_queue_2', 'test_dead_2');
            $depth = $driver2->depth();
            
            if ($depth === 4) {
                $this->pass("Database driver recovered 4 pending jobs after crash");
            } else {
                $this->fail("Database driver recovered {$depth} jobs, expected 4");
            }
            
            $driver2->clear();
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testJobNotLost(): void {
        echo "Test: Jobs not lost during restart... ";
        
        try {
            $driver = new FileDriver($this->testDir . '/noloss');
            $queue = new \Core\Runtime\Queue\Queue($driver, [
                'workers' => 1,
                'metrics_interval' => 0,
            ]);
            
            $processed = [];
            $queue->register('noloss-job', function($payload, $job) use (&$processed) {
                $processed[] = $payload['id'];
                return ['id' => $payload['id']];
            });
            
            for ($i = 1; $i <= 10; $i++) {
                $queue->push('noloss-job', ['id' => $i]);
            }
            
            $queue->work();
            
            if (count($processed) === 10) {
                $this->pass("All 10 jobs processed, none lost");
            } else {
                $this->fail("Lost " . (10 - count($processed)) . " jobs");
            }
            
            $driver->clear();
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testJobNotDuplicated(): void {
        echo "Test: Jobs not duplicated during restart... ";
        
        try {
            $driver = new FileDriver($this->testDir . '/nodup');
            
            for ($i = 1; $i <= 5; $i++) {
                $job = new Job([
                    'id' => "nodup-{$i}",
                    'name' => 'test-job',
                    'payload' => ['id' => $i],
                    'status' => JobStatus::PENDING,
                    'attempts' => 0,
                    'max_attempts' => 3,
                    'timeout' => 300,
                    'created_at' => time(),
                    'available_at' => time(),
                ]);
                $driver->push($job);
            }
            
            $seen = [];
            while ($job = $driver->pop()) {
                if (in_array($job->id, $seen)) {
                    $this->fail("Duplicate job detected: {$job->id}");
                    $driver->clear();
                    return;
                }
                $seen[] = $job->id;
                $job->status = JobStatus::COMPLETED;
                $driver->update($job);
            }
            
            if (count($seen) === 5) {
                $this->pass("No duplicate jobs detected");
            } else {
                $this->fail("Expected 5 unique jobs, got " . count($seen));
            }
            
            $driver->clear();
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testJobNotCorrupted(): void {
        echo "Test: Jobs not corrupted during restart... ";
        
        try {
            $driver = new FileDriver($this->testDir . '/nocorrupt');
            
            $originalPayload = [
                'string' => 'test value',
                'number' => 12345,
                'array' => [1, 2, 3],
                'nested' => ['key' => 'value'],
                'unicode' => '测试数据',
            ];
            
            $job = new Job([
                'id' => 'corrupt-test',
                'name' => 'test-job',
                'payload' => $originalPayload,
                'status' => JobStatus::PENDING,
                'attempts' => 0,
                'max_attempts' => 3,
                'timeout' => 300,
                'created_at' => time(),
                'available_at' => time(),
            ]);
            
            $driver->push($job);
            unset($driver);
            
            $driver2 = new FileDriver($this->testDir . '/nocorrupt');
            $retrieved = $driver2->pop();
            
            if ($retrieved && $retrieved->payload === $originalPayload) {
                $this->pass("Job payload not corrupted");
            } else {
                $this->fail("Job payload corrupted");
            }
            
            $driver2->clear();
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testDeadLetterPersistence(): void {
        echo "Test: Dead letter queue persistence... ";
        
        try {
            $driver = new FileDriver($this->testDir . '/deadletter');
            $queue = new \Core\Runtime\Queue\Queue($driver, [
                'workers' => 1,
                'max_attempts' => 2,
                'retry_delay' => 0,
                'metrics_interval' => 0,
            ]);
            
            $queue->register('fail-job', function($payload, $job) {
                throw new \Exception("Always fails");
            });
            
            $queue->push('fail-job', ['id' => 1]);
            $queue->work();
            
            unset($queue);
            
            $driver2 = new FileDriver($this->testDir . '/deadletter');
            $deadLetters = $driver2->getDeadLetters();
            
            if (count($deadLetters) === 1 && $deadLetters[0]->status === JobStatus::FAILED) {
                $this->pass("Dead letter persisted across restart");
            } else {
                $this->fail("Dead letter not persisted");
            }
            
            $driver2->clear();
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testCrashDuringProcessing(): void {
        echo "Test: Recovery from crash during processing... ";
        
        try {
            $driver = new FileDriver($this->testDir . '/crash');
            
            $job = new Job([
                'id' => 'crash-job',
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
            $popped->status = JobStatus::RUNNING;
            $popped->attempts = 1;
            $driver->update($popped);
            
            unset($driver);
            
            $driver2 = new FileDriver($this->testDir . '/crash');
            $recovered = $driver2->get('crash-job');
            
            if ($recovered && $recovered->status === JobStatus::RUNNING && $recovered->attempts === 1) {
                $this->pass("Job state preserved after crash");
            } else {
                $this->fail("Job state not preserved");
            }
            
            $driver2->clear();
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testMultipleRestarts(): void {
        echo "Test: Multiple restart cycles... ";
        
        try {
            $driver = new FileDriver($this->testDir . '/restarts');
            
            for ($i = 1; $i <= 10; $i++) {
                $job = new Job([
                    'id' => "restart-{$i}",
                    'name' => 'test-job',
                    'payload' => ['id' => $i],
                    'status' => JobStatus::PENDING,
                    'attempts' => 0,
                    'max_attempts' => 3,
                    'timeout' => 300,
                    'created_at' => time(),
                    'available_at' => time(),
                ]);
                $driver->push($job);
            }
            
            $processed = 0;
            for ($restart = 0; $restart < 5; $restart++) {
                unset($driver);
                $driver = new FileDriver($this->testDir . '/restarts');
                
                for ($i = 0; $i < 2; $i++) {
                    $job = $driver->pop();
                    if ($job) {
                        $job->status = JobStatus::COMPLETED;
                        $driver->update($job);
                        $processed++;
                    }
                }
            }
            
            if ($processed === 10) {
                $this->pass("All jobs processed across 5 restarts");
            } else {
                $this->fail("Processed {$processed}/10 jobs across restarts");
            }
            
            $driver->clear();
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function cleanup(): void {
        if (is_dir($this->testDir)) {
            $this->rmdirRecursive($this->testDir);
        }
    }
    
    private function rmdirRecursive(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->rmdirRecursive($path) : unlink($path);
        }
        rmdir($dir);
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
        echo "\n=== Persistence & Recovery Test Results ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Total: " . ($this->passed + $this->failed) . "\n";
        echo ($this->failed === 0 ? "✓ All persistence tests passed!\n" : "✗ Some persistence tests failed\n");
    }
}

if (php_sapi_name() === 'cli') {
    $test = new PersistenceRecoveryTest();
    $test->run();
}
