<?php
require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Runtime;
use Core\Core\Runtime\Worker;

class GracefulShutdownTest {
    private $shutdownReceived = false;
    private $cleanupCompleted = false;

    public function run() {
        if (!Runtime::available()) {
            echo "✗ Runtime not available\n";
            exit(1);
        }

        $capabilities = Runtime::capabilities();
        if (!$capabilities['pcntl']) {
            echo "✗ PCNTL extension required for signal handling\n";
            exit(1);
        }

        echo "=== Graceful Shutdown Test ===\n";
        echo "Testing SIGTERM and SIGINT handling\n\n";

        $this->testSIGTERM();
        $this->testSIGINT();
        $this->testCleanupOnShutdown();
        $this->testMultipleSignals();

        echo "\n✓ All graceful shutdown tests passed\n";
    }

    private function testSIGTERM() {
        echo "Test 1: SIGTERM handling... ";
        
        $pid = pcntl_fork();
        if ($pid === 0) {
            Worker::start(function() {
                Runtime::sleep(0.1);
            }, ['max_iterations' => 1000]);
            exit(0);
        }

        usleep(100000);
        posix_kill($pid, SIGTERM);
        pcntl_waitpid($pid, $status);

        if (pcntl_wifexited($status)) {
            echo "✓ (clean exit)\n";
        } else {
            echo "✗ (abnormal exit)\n";
            exit(1);
        }
    }

    private function testSIGINT() {
        echo "Test 2: SIGINT handling... ";
        
        $pid = pcntl_fork();
        if ($pid === 0) {
            Worker::start(function() {
                Runtime::sleep(0.1);
            }, ['max_iterations' => 1000]);
            exit(0);
        }

        usleep(100000);
        posix_kill($pid, SIGINT);
        pcntl_waitpid($pid, $status);

        if (pcntl_wifexited($status)) {
            echo "✓ (clean exit)\n";
        } else {
            echo "✗ (abnormal exit)\n";
            exit(1);
        }
    }

    private function testCleanupOnShutdown() {
        echo "Test 3: Cleanup on shutdown... ";
        
        $tempFile = tempnam(sys_get_temp_dir(), 'nexph_test_');
        
        $pid = pcntl_fork();
        if ($pid === 0) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, function() use ($tempFile) {
                file_put_contents($tempFile, 'cleanup_done');
                exit(0);
            });

            Worker::start(function() {
                Runtime::sleep(0.1);
            }, ['max_iterations' => 1000]);
            exit(0);
        }

        usleep(100000);
        posix_kill($pid, SIGTERM);
        pcntl_waitpid($pid, $status);

        $cleaned = file_exists($tempFile) && file_get_contents($tempFile) === 'cleanup_done';
        unlink($tempFile);

        if ($cleaned) {
            echo "✓ (cleanup executed)\n";
        } else {
            echo "✗ (cleanup failed)\n";
            exit(1);
        }
    }

    private function testMultipleSignals() {
        echo "Test 4: Multiple signals (idempotent)... ";
        
        $pid = pcntl_fork();
        if ($pid === 0) {
            Worker::start(function() {
                Runtime::sleep(0.1);
            }, ['max_iterations' => 1000]);
            exit(0);
        }

        usleep(100000);
        posix_kill($pid, SIGTERM);
        usleep(10000);
        posix_kill($pid, SIGTERM);
        usleep(10000);
        posix_kill($pid, SIGTERM);
        
        pcntl_waitpid($pid, $status);

        if (pcntl_wifexited($status)) {
            echo "✓ (handled gracefully)\n";
        } else {
            echo "✗ (crashed)\n";
            exit(1);
        }
    }
}

$test = new GracefulShutdownTest();
$test->run();
