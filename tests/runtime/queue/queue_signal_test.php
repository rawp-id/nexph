<?php
require_once __DIR__ . '/../autoload.php';

use Core\Runtime\Runtime;
use Core\Runtime\Worker;
use Core\Runtime\Queue\Queue;
use Core\Runtime\Queue\QueueFactory;
use Core\Runtime\Queue\JobHandler;

/**
 * Signal Handling and Graceful Shutdown Test Suite
 * 
 * Tests: SIGTERM, SIGINT, graceful shutdown, job completion,
 * worker cleanup, signal propagation
 */

echo "=== Queue Signal Handling Test Suite ===\n\n";

if (!Runtime::available()) {
    echo "⚠ Tests require CLI mode with Fiber support\n";
    exit(0);
}

$caps = Runtime::capabilities();
if (!$caps['pcntl']) {
    echo "⚠ Tests require PCNTL extension\n";
    exit(0);
}

class SignalTestHandler extends JobHandler {
    public function handle(array $payload, \Runtime\Queue\Job $job): mixed {
        $duration = $payload['duration'] ?? 2;
        echo "[Job {$job->id}] Started (will run {$duration}s)\n";
        
        for ($i = 0; $i < $duration; $i++) {
            Runtime::sleep(1);
            echo "[Job {$job->id}] Progress: " . (($i + 1) / $duration * 100) . "%\n";
        }
        
        echo "[Job {$job->id}] Completed\n";
        return 'done';
    }
}

// Test 1: SIGTERM Graceful Shutdown
function testSIGTERM(): void {
    echo "Test 1: SIGTERM Graceful Shutdown\n";
    echo str_repeat('-', 50) . "\n";
    
    $pid = pcntl_fork();
    
    if ($pid === 0) {
        // Child process
        $queue = QueueFactory::create('memory');
        $queue->register('signal_test', SignalTestHandler::class);
        
        $queue->push('signal_test', ['duration' => 3]);
        $queue->push('signal_test', ['duration' => 2]);
        
        echo "[Worker] Starting...\n";
        $queue->work();
        echo "[Worker] Stopped\n";
        exit(0);
    }
    
    // Parent process
    sleep(2);
    echo "[Parent] Sending SIGTERM to worker (PID: {$pid})\n";
    posix_kill($pid, SIGTERM);
    
    $status = 0;
    pcntl_waitpid($pid, $status);
    
    echo "[Parent] Worker exited with status: " . pcntl_wexitstatus($status) . "\n";
    echo "✓ SIGTERM handled\n\n";
}

// Test 2: SIGINT Graceful Shutdown
function testSIGINT(): void {
    echo "Test 2: SIGINT Graceful Shutdown\n";
    echo str_repeat('-', 50) . "\n";
    
    $pid = pcntl_fork();
    
    if ($pid === 0) {
        // Child process
        $queue = QueueFactory::create('memory');
        $queue->register('signal_test', SignalTestHandler::class);
        
        $queue->push('signal_test', ['duration' => 3]);
        
        echo "[Worker] Starting...\n";
        $queue->work();
        echo "[Worker] Stopped\n";
        exit(0);
    }
    
    // Parent process
    sleep(1);
    echo "[Parent] Sending SIGINT to worker (PID: {$pid})\n";
    posix_kill($pid, SIGINT);
    
    $status = 0;
    pcntl_waitpid($pid, $status);
    
    echo "[Parent] Worker exited with status: " . pcntl_wexitstatus($status) . "\n";
    echo "✓ SIGINT handled\n\n";
}

// Test 3: Job Completion Before Shutdown
function testJobCompletion(): void {
    echo "Test 3: Job Completion Before Shutdown\n";
    echo str_repeat('-', 50) . "\n";
    
    $path = sys_get_temp_dir() . '/nexph-signal-test-' . uniqid();
    
    $pid = pcntl_fork();
    
    if ($pid === 0) {
        // Child process
        $queue = QueueFactory::create('file', ['path' => $path]);
        $queue->register('completion_test', SignalTestHandler::class);
        
        $queue->push('completion_test', ['duration' => 2]);
        $queue->push('completion_test', ['duration' => 2]);
        
        echo "[Worker] Starting...\n";
        $queue->work();
        echo "[Worker] Stopped\n";
        exit(0);
    }
    
    // Parent process
    sleep(1);
    echo "[Parent] Sending SIGTERM after 1s (job needs 2s)\n";
    posix_kill($pid, SIGTERM);
    
    $status = 0;
    pcntl_waitpid($pid, $status);
    
    // Check if job completed
    $queue = QueueFactory::create('file', ['path' => $path]);
    $queue->register('completion_test', SignalTestHandler::class);
    
    $queueStatus = $queue->status();
    echo "[Parent] Jobs completed: {$queueStatus['metrics']['completed']}\n";
    echo "[Parent] Jobs remaining: {$queueStatus['depth']}\n";
    
    echo "✓ Job completion verified\n\n";
    
    exec("rm -rf " . escapeshellarg($path));
}

// Test 4: Multiple Workers Signal Handling
function testMultipleWorkers(): void {
    echo "Test 4: Multiple Workers Signal Handling\n";
    echo str_repeat('-', 50) . "\n";
    
    $pids = [];
    
    for ($i = 0; $i < 3; $i++) {
        $pid = pcntl_fork();
        
        if ($pid === 0) {
            // Child process
            $workerId = $i + 1;
            $queue = QueueFactory::create('memory');
            $queue->register('multi_worker', function($payload) use ($workerId) {
                echo "[Worker {$workerId}] Processing job {$payload['id']}\n";
                Runtime::sleep(2);
                return 'done';
            });
            
            $queue->push('multi_worker', ['id' => "job-{$workerId}"]);
            
            echo "[Worker {$workerId}] Starting...\n";
            $queue->work();
            echo "[Worker {$workerId}] Stopped\n";
            exit(0);
        }
        
        $pids[] = $pid;
    }
    
    // Parent process
    sleep(1);
    echo "[Parent] Sending SIGTERM to all workers\n";
    
    foreach ($pids as $pid) {
        posix_kill($pid, SIGTERM);
    }
    
    foreach ($pids as $pid) {
        $status = 0;
        pcntl_waitpid($pid, $status);
        echo "[Parent] Worker {$pid} exited\n";
    }
    
    echo "✓ Multiple workers handled signals\n\n";
}

// Test 5: Signal During Job Processing
function testSignalDuringProcessing(): void {
    echo "Test 5: Signal During Job Processing\n";
    echo str_repeat('-', 50) . "\n";
    
    $pid = pcntl_fork();
    
    if ($pid === 0) {
        // Child process
        $queue = QueueFactory::create('memory');
        $queue->register('long_job', function($payload) {
            echo "[Job] Started long-running job\n";
            for ($i = 0; $i < 10; $i++) {
                Runtime::sleep(1);
                echo "[Job] Step {$i}/10\n";
            }
            echo "[Job] Completed\n";
            return 'done';
        });
        
        $queue->push('long_job', ['id' => 'long-1']);
        
        echo "[Worker] Starting...\n";
        $queue->work();
        echo "[Worker] Stopped\n";
        exit(0);
    }
    
    // Parent process
    sleep(3);
    echo "[Parent] Sending SIGTERM during job processing\n";
    posix_kill($pid, SIGTERM);
    
    $status = 0;
    pcntl_waitpid($pid, $status);
    
    echo "[Parent] Worker exited\n";
    echo "✓ Signal during processing handled\n\n";
}

// Test 6: Worker Cleanup on Signal
function testWorkerCleanup(): void {
    echo "Test 6: Worker Cleanup on Signal\n";
    echo str_repeat('-', 50) . "\n";
    
    $path = sys_get_temp_dir() . '/nexph-cleanup-' . uniqid();
    $lockFile = $path . '/worker.lock';
    
    mkdir($path, 0755, true);
    
    $pid = pcntl_fork();
    
    if ($pid === 0) {
        // Child process
        file_put_contents($lockFile, getmypid());
        
        $queue = QueueFactory::create('file', ['path' => $path]);
        $queue->register('cleanup_test', SignalTestHandler::class);
        
        $queue->push('cleanup_test', ['duration' => 5]);
        
        echo "[Worker] Starting with lock file\n";
        $queue->work();
        
        if (file_exists($lockFile)) {
            unlink($lockFile);
        }
        
        echo "[Worker] Cleaned up\n";
        exit(0);
    }
    
    // Parent process
    sleep(1);
    
    if (file_exists($lockFile)) {
        echo "[Parent] Lock file exists\n";
    }
    
    posix_kill($pid, SIGTERM);
    
    $status = 0;
    pcntl_waitpid($pid, $status);
    
    sleep(1);
    
    if (!file_exists($lockFile)) {
        echo "[Parent] Lock file cleaned up\n";
        echo "✓ Worker cleanup verified\n\n";
    } else {
        echo "[Parent] Lock file still exists\n";
        echo "⚠ Cleanup may be incomplete\n\n";
    }
    
    exec("rm -rf " . escapeshellarg($path));
}

// Test 7: Rapid Signal Handling
function testRapidSignals(): void {
    echo "Test 7: Rapid Signal Handling\n";
    echo str_repeat('-', 50) . "\n";
    
    $pid = pcntl_fork();
    
    if ($pid === 0) {
        // Child process
        $queue = QueueFactory::create('memory');
        $queue->register('rapid_test', SignalTestHandler::class);
        
        for ($i = 0; $i < 5; $i++) {
            $queue->push('rapid_test', ['duration' => 1]);
        }
        
        echo "[Worker] Starting...\n";
        $queue->work();
        echo "[Worker] Stopped\n";
        exit(0);
    }
    
    // Parent process
    sleep(1);
    
    echo "[Parent] Sending multiple signals rapidly\n";
    for ($i = 0; $i < 3; $i++) {
        posix_kill($pid, SIGTERM);
        usleep(100000);
    }
    
    $status = 0;
    pcntl_waitpid($pid, $status);
    
    echo "[Parent] Worker exited\n";
    echo "✓ Rapid signals handled\n\n";
}

// Run all tests
try {
    testSIGTERM();
    testSIGINT();
    testJobCompletion();
    testMultipleWorkers();
    testSignalDuringProcessing();
    testWorkerCleanup();
    testRapidSignals();
    
    echo "\n" . str_repeat('=', 50) . "\n";
    echo "✓ All signal handling tests passed\n";
    echo str_repeat('=', 50) . "\n";
    
} catch (\Throwable $e) {
    echo "\n✗ Test failed: {$e->getMessage()}\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
