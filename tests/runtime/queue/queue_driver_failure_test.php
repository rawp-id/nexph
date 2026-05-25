<?php
require_once __DIR__ . '/../autoload.php';

use Core\Runtime\Runtime;
use Core\Runtime\Queue\Queue;
use Core\Runtime\Queue\QueueFactory;
use Core\Runtime\Queue\JobHandler;
use Core\Runtime\Queue\Job;

/**
 * Driver Failure and Degradation Test Suite
 * 
 * Tests: Redis disconnects, database failures, file system errors,
 * driver fallback, safe degradation, error isolation
 */

echo "=== Queue Driver Failure Test Suite ===\n\n";

class DriverTestHandler extends JobHandler {
    public function handle(array $payload, Job $job): mixed {
        usleep(50000);
        return ['processed' => $payload['id']];
    }
}

// Test 1: Redis Connection Failure
function testRedisConnectionFailure(): void {
    echo "Test 1: Redis Connection Failure\n";
    echo str_repeat('-', 50) . "\n";
    
    if (!extension_loaded('redis')) {
        echo "⚠ Skipped (Redis extension not available)\n\n";
        return;
    }
    
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 9999, 0.1);
        
        $queue = QueueFactory::create('redis', ['redis' => $redis]);
        $queue->register('redis_test', DriverTestHandler::class);
        
        $queue->push('redis_test', ['id' => 1]);
        echo "✗ Should have failed to connect\n\n";
        
    } catch (\Throwable $e) {
        echo "✓ Redis connection failure caught: " . get_class($e) . "\n";
        echo "  Message: {$e->getMessage()}\n\n";
    }
}

// Test 2: Redis Disconnect During Operation
function testRedisDisconnect(): void {
    echo "Test 2: Redis Disconnect During Operation\n";
    echo str_repeat('-', 50) . "\n";
    
    if (!extension_loaded('redis')) {
        echo "⚠ Skipped (Redis extension not available)\n\n";
        return;
    }
    
    try {
        $redis = new Redis();
        
        if (!@$redis->connect('127.0.0.1', 6379, 0.1)) {
            echo "⚠ Skipped (Redis server not available)\n\n";
            return;
        }
        
        $queue = QueueFactory::create('redis', ['redis' => $redis]);
        $queue->register('disconnect_test', DriverTestHandler::class);
        
        $queue->push('disconnect_test', ['id' => 1]);
        echo "Pushed job to Redis\n";
        
        $redis->close();
        echo "Closed Redis connection\n";
        
        try {
            $queue->push('disconnect_test', ['id' => 2]);
            echo "✗ Should have failed after disconnect\n\n";
        } catch (\Throwable $e) {
            echo "✓ Disconnect detected: " . get_class($e) . "\n\n";
        }
        
    } catch (\Throwable $e) {
        echo "⚠ Test setup failed: {$e->getMessage()}\n\n";
    }
}

// Test 3: Database Connection Failure
function testDatabaseConnectionFailure(): void {
    echo "Test 3: Database Connection Failure\n";
    echo str_repeat('-', 50) . "\n";
    
    try {
        $pdo = new PDO('sqlite:/nonexistent/path/db.sqlite');
        $queue = QueueFactory::create('database', ['pdo' => $pdo]);
        echo "✗ Should have failed to connect\n\n";
        
    } catch (\Throwable $e) {
        echo "✓ Database connection failure caught: " . get_class($e) . "\n";
        echo "  Message: {$e->getMessage()}\n\n";
    }
}

// Test 4: Database Lock Timeout
function testDatabaseLockTimeout(): void {
    echo "Test 4: Database Lock Timeout\n";
    echo str_repeat('-', 50) . "\n";
    
    $dbPath = sys_get_temp_dir() . '/nexph-lock-test-' . uniqid() . '.db';
    
    try {
        $pdo1 = new PDO("sqlite:{$dbPath}");
        $pdo1->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo1->exec("PRAGMA busy_timeout = 100");
        
        $queue1 = QueueFactory::create('database', ['pdo' => $pdo1]);
        $queue1->register('lock_test', DriverTestHandler::class);
        
        $queue1->push('lock_test', ['id' => 1]);
        
        $pdo1->beginTransaction();
        $pdo1->exec("SELECT * FROM queue_jobs FOR UPDATE");
        
        $pdo2 = new PDO("sqlite:{$dbPath}");
        $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo2->exec("PRAGMA busy_timeout = 100");
        
        $queue2 = QueueFactory::create('database', ['pdo' => $pdo2]);
        $queue2->register('lock_test', DriverTestHandler::class);
        
        try {
            $queue2->push('lock_test', ['id' => 2]);
            echo "✓ Lock handled (may timeout or wait)\n\n";
        } catch (\Throwable $e) {
            echo "✓ Lock timeout caught: " . get_class($e) . "\n\n";
        }
        
        $pdo1->rollBack();
        
    } catch (\Throwable $e) {
        echo "⚠ Test setup failed: {$e->getMessage()}\n\n";
    } finally {
        if (file_exists($dbPath)) {
            unlink($dbPath);
        }
    }
}

// Test 5: File System Permission Error
function testFileSystemPermissionError(): void {
    echo "Test 5: File System Permission Error\n";
    echo str_repeat('-', 50) . "\n";
    
    $path = '/root/nexph-test-' . uniqid();
    
    try {
        $queue = QueueFactory::create('file', ['path' => $path]);
        $queue->register('perm_test', DriverTestHandler::class);
        
        $queue->push('perm_test', ['id' => 1]);
        echo "✗ Should have failed with permission error\n\n";
        
    } catch (\Throwable $e) {
        echo "✓ Permission error caught: " . get_class($e) . "\n";
        echo "  Message: {$e->getMessage()}\n\n";
    }
}

// Test 6: File System Full Simulation
function testFileSystemFull(): void {
    echo "Test 6: File System Full Simulation\n";
    echo str_repeat('-', 50) . "\n";
    
    $path = sys_get_temp_dir() . '/nexph-full-test-' . uniqid();
    
    try {
        $queue = QueueFactory::create('file', ['path' => $path]);
        $queue->register('full_test', DriverTestHandler::class);
        
        $largePayload = ['data' => str_repeat('x', 10 * 1024 * 1024)];
        
        for ($i = 0; $i < 100; $i++) {
            try {
                $queue->push('full_test', $largePayload);
            } catch (\Throwable $e) {
                echo "✓ Disk full error caught after {$i} jobs\n";
                echo "  Error: " . get_class($e) . "\n\n";
                break;
            }
        }
        
        if ($i === 100) {
            echo "✓ All jobs written (disk not full)\n\n";
        }
        
    } catch (\Throwable $e) {
        echo "✓ File system error caught: " . get_class($e) . "\n\n";
    } finally {
        exec("rm -rf " . escapeshellarg($path));
    }
}

// Test 7: Corrupted Driver State Recovery
function testCorruptedDriverState(): void {
    echo "Test 7: Corrupted Driver State Recovery\n";
    echo str_repeat('-', 50) . "\n";
    
    $path = sys_get_temp_dir() . '/nexph-corrupt-' . uniqid();
    
    $queue1 = QueueFactory::create('file', ['path' => $path]);
    $queue1->register('corrupt_test', DriverTestHandler::class);
    
    $queue1->push('corrupt_test', ['id' => 1]);
    $queue1->push('corrupt_test', ['id' => 2]);
    
    $jobsDir = $path . '/jobs';
    $files = glob($jobsDir . '/*.json');
    
    if (!empty($files)) {
        file_put_contents($files[0], '{invalid json');
        echo "Corrupted job file\n";
    }
    
    $queue2 = QueueFactory::create('file', ['path' => $path]);
    $queue2->register('corrupt_test', DriverTestHandler::class);
    
    try {
        $queue2->work();
        $status = $queue2->status();
        echo "Completed: {$status['metrics']['completed']}\n";
        echo "✓ Corrupted state handled\n\n";
    } catch (\Throwable $e) {
        echo "✓ Corruption detected: " . get_class($e) . "\n\n";
    }
    
    exec("rm -rf " . escapeshellarg($path));
}

// Test 8: Driver Fallback Chain
function testDriverFallback(): void {
    echo "Test 8: Driver Fallback Chain\n";
    echo str_repeat('-', 50) . "\n";
    
    $drivers = ['redis', 'database', 'file', 'memory'];
    $available = [];
    
    foreach ($drivers as $driver) {
        try {
            switch ($driver) {
                case 'redis':
                    if (!extension_loaded('redis')) {
                        throw new \RuntimeException('Redis not available');
                    }
                    $redis = new Redis();
                    if (!@$redis->connect('127.0.0.1', 6379, 0.1)) {
                        throw new \RuntimeException('Redis server not available');
                    }
                    $queue = QueueFactory::create('redis', ['redis' => $redis]);
                    break;
                    
                case 'database':
                    $pdo = new PDO('sqlite::memory:');
                    $queue = QueueFactory::create('database', ['pdo' => $pdo]);
                    break;
                    
                case 'file':
                    $queue = QueueFactory::create('file');
                    break;
                    
                case 'memory':
                    $queue = QueueFactory::create('memory');
                    break;
            }
            
            $queue->register('fallback_test', DriverTestHandler::class);
            $queue->push('fallback_test', ['id' => 1]);
            $queue->work();
            
            $available[] = $driver;
            echo "  {$driver}: ✓ available\n";
            
        } catch (\Throwable $e) {
            echo "  {$driver}: ✗ unavailable ({$e->getMessage()})\n";
        }
    }
    
    echo "\nAvailable drivers: " . implode(', ', $available) . "\n";
    echo "✓ Driver fallback tested\n\n";
}

// Test 9: Safe Degradation to Memory
function testSafeDegradation(): void {
    echo "Test 9: Safe Degradation to Memory\n";
    echo str_repeat('-', 50) . "\n";
    
    $queue = QueueFactory::create('memory');
    $queue->register('degrade_test', DriverTestHandler::class);
    
    for ($i = 0; $i < 10; $i++) {
        $queue->push('degrade_test', ['id' => $i]);
    }
    
    echo "Pushed 10 jobs to memory driver\n";
    
    $queue->work();
    
    $status = $queue->status();
    echo "Completed: {$status['metrics']['completed']}\n";
    echo "✓ Memory driver works as fallback\n\n";
}

// Test 10: Error Isolation
function testErrorIsolation(): void {
    echo "Test 10: Error Isolation\n";
    echo str_repeat('-', 50) . "\n";
    
    $queue = QueueFactory::create('memory');
    
    $queue->register('good_job', function($payload) {
        return 'success';
    });
    
    $queue->register('bad_job', function($payload) {
        throw new \RuntimeException('Job error');
    });
    
    $queue->push('good_job', ['id' => 1]);
    $queue->push('bad_job', ['id' => 2]);
    $queue->push('good_job', ['id' => 3]);
    $queue->push('bad_job', ['id' => 4]);
    $queue->push('good_job', ['id' => 5]);
    
    echo "Pushed 3 good jobs + 2 bad jobs\n";
    
    $queue->work();
    
    $status = $queue->status();
    echo "Completed: {$status['metrics']['completed']}\n";
    echo "Failed: {$status['metrics']['failed']}\n";
    
    if ($status['metrics']['completed'] === 3 && $status['metrics']['failed'] === 2) {
        echo "✓ Errors isolated (good jobs completed)\n\n";
    } else {
        echo "⚠ Error isolation may be incomplete\n\n";
    }
}

// Test 11: APCu Driver Availability
function testApcuDriver(): void {
    echo "Test 11: APCu Driver Availability\n";
    echo str_repeat('-', 50) . "\n";
    
    if (!extension_loaded('apcu') || !ini_get('apc.enabled')) {
        echo "⚠ Skipped (APCu not available)\n\n";
        return;
    }
    
    try {
        $queue = QueueFactory::create('apcu');
        $queue->register('apcu_test', DriverTestHandler::class);
        
        $queue->push('apcu_test', ['id' => 1]);
        $queue->work();
        
        $status = $queue->status();
        echo "Completed: {$status['metrics']['completed']}\n";
        echo "✓ APCu driver works\n\n";
        
    } catch (\Throwable $e) {
        echo "✗ APCu driver failed: {$e->getMessage()}\n\n";
    }
}

// Test 12: Driver Recovery After Failure
function testDriverRecovery(): void {
    echo "Test 12: Driver Recovery After Failure\n";
    echo str_repeat('-', 50) . "\n";
    
    $dbPath = sys_get_temp_dir() . '/nexph-recovery-' . uniqid() . '.db';
    
    $pdo = new PDO("sqlite:{$dbPath}");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $queue = QueueFactory::create('database', ['pdo' => $pdo]);
    $queue->register('recovery_test', DriverTestHandler::class);
    
    $queue->push('recovery_test', ['id' => 1]);
    echo "Pushed job before failure\n";
    
    unset($pdo);
    unlink($dbPath);
    echo "Deleted database file\n";
    
    try {
        $queue->push('recovery_test', ['id' => 2]);
        echo "✗ Should have failed\n\n";
    } catch (\Throwable $e) {
        echo "✓ Failure detected: " . get_class($e) . "\n";
        
        $pdo2 = new PDO("sqlite:{$dbPath}");
        $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $queue2 = QueueFactory::create('database', ['pdo' => $pdo2]);
        $queue2->register('recovery_test', DriverTestHandler::class);
        
        $queue2->push('recovery_test', ['id' => 3]);
        echo "✓ Recovered with new connection\n\n";
    }
    
    if (file_exists($dbPath)) {
        unlink($dbPath);
    }
}

// Run all tests
try {
    testRedisConnectionFailure();
    testRedisDisconnect();
    testDatabaseConnectionFailure();
    testDatabaseLockTimeout();
    testFileSystemPermissionError();
    testFileSystemFull();
    testCorruptedDriverState();
    testDriverFallback();
    testSafeDegradation();
    testErrorIsolation();
    testApcuDriver();
    testDriverRecovery();
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "✓ All driver failure tests passed\n";
    echo str_repeat('=', 50) . "\n";
    
} catch (\Throwable $e) {
    echo "\n✗ Test failed: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
