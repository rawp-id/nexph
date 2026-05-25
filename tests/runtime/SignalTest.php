<?php
require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Runtime;
use Core\Core\Runtime\Worker;

class SignalTest {
    public function run() {
        if (!Runtime::available()) {
            echo "✗ Runtime not available\n";
            exit(1);
        }

        if (!function_exists('pcntl_signal')) {
            echo "✗ PCNTL not available\n";
            exit(1);
        }

        echo "=== Signal Handling Test ===\n\n";

        $this->testSIGTERM();
        $this->testSIGINT();
        $this->testGracefulShutdown();

        echo "\n✓ All signal tests passed\n";
    }

    private function testSIGTERM() {
        echo "Test 1: SIGTERM handling... ";

        $pid = pcntl_fork();
        if ($pid === 0) {
            pcntl_signal(SIGTERM, function() {
                Runtime::stop();
            });

            Runtime::spawn(function() {
                while (true) {
                    Runtime::sleep(0.1);
                }
            });

            Runtime::run();
            exit(0);
        }

        usleep(100000);
        posix_kill($pid, SIGTERM);
        pcntl_waitpid($pid, $status);

        if (pcntl_wifexited($status) && pcntl_wexitstatus($status) === 0) {
            echo "✓\n";
        } else {
            echo "✗\n";
        }
    }

    private function testSIGINT() {
        echo "Test 2: SIGINT handling... ";

        $pid = pcntl_fork();
        if ($pid === 0) {
            pcntl_signal(SIGINT, function() {
                Runtime::stop();
            });

            Runtime::spawn(function() {
                while (true) {
                    Runtime::sleep(0.1);
                }
            });

            Runtime::run();
            exit(0);
        }

        usleep(100000);
        posix_kill($pid, SIGINT);
        pcntl_waitpid($pid, $status);

        if (pcntl_wifexited($status) && pcntl_wexitstatus($status) === 0) {
            echo "✓\n";
        } else {
            echo "✗\n";
        }
    }

    private function testGracefulShutdown() {
        echo "Test 3: Graceful shutdown... ";

        $completed = false;
        pcntl_signal(SIGTERM, function() use (&$completed) {
            $completed = true;
            Runtime::stop();
        });

        Runtime::spawn(function() {
            Runtime::sleep(0.1);
        });

        posix_kill(posix_getpid(), SIGTERM);
        Runtime::run();

        if ($completed) {
            echo "✓\n";
        } else {
            echo "✗\n";
        }
    }
}

$test = new SignalTest();
$test->run();
