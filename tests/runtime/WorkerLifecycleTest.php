<?php
require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Runtime;
use Core\Core\Runtime\Worker;

class WorkerLifecycleTest {
    private $results = [];

    public function run() {
        if (!Runtime::available()) {
            echo "✗ Runtime not available\n";
            exit(1);
        }

        echo "=== Worker Lifecycle Test ===\n\n";

        $this->testWorkerStart();
        $this->testWorkerStop();
        $this->testWorkerRestart();
        $this->testMaxIterations();
        $this->testWorkerSleep();

        $this->printSummary();
    }

    private function testWorkerStart() {
        echo "Test 1: Worker start... ";
        $started = false;

        $pid = pcntl_fork();
        if ($pid === 0) {
            Worker::start(function() use (&$started) {
                $started = true;
                return false;
            });
            exit($started ? 0 : 1);
        }

        pcntl_waitpid($pid, $status);

        if (pcntl_wifexited($status) && pcntl_wexitstatus($status) === 0) {
            echo "✓\n";
            $this->results[] = 'PASS';
        } else {
            echo "✗\n";
            $this->results[] = 'FAIL';
        }
    }

    private function testWorkerStop() {
        echo "Test 2: Worker stop... ";

        $pid = pcntl_fork();
        if ($pid === 0) {
            $iterations = 0;
            Worker::start(function() use (&$iterations) {
                $iterations++;
                return $iterations < 5;
            }, ['sleep' => 0.01]);
            exit($iterations === 5 ? 0 : 1);
        }

        pcntl_waitpid($pid, $status);

        if (pcntl_wifexited($status) && pcntl_wexitstatus($status) === 0) {
            echo "✓\n";
            $this->results[] = 'PASS';
        } else {
            echo "✗\n";
            $this->results[] = 'FAIL';
        }
    }

    private function testWorkerRestart() {
        echo "Test 3: Worker restart... ";

        $pid = pcntl_fork();
        if ($pid === 0) {
            $count = 0;
            Worker::start(function() use (&$count) {
                $count++;
                return $count < 10;
            }, ['sleep' => 0.01]);
            exit($count === 10 ? 0 : 1);
        }

        pcntl_waitpid($pid, $status);

        if (pcntl_wifexited($status) && pcntl_wexitstatus($status) === 0) {
            echo "✓\n";
            $this->results[] = 'PASS';
        } else {
            echo "✗\n";
            $this->results[] = 'FAIL';
        }
    }

    private function testMaxIterations() {
        echo "Test 4: Max iterations... ";

        $pid = pcntl_fork();
        if ($pid === 0) {
            $count = 0;
            Worker::start(function() use (&$count) {
                $count++;
                return true;
            }, ['sleep' => 0.01, 'max_iterations' => 100]);
            exit($count === 100 ? 0 : 1);
        }

        pcntl_waitpid($pid, $status);

        if (pcntl_wifexited($status) && pcntl_wexitstatus($status) === 0) {
            echo "✓\n";
            $this->results[] = 'PASS';
        } else {
            echo "✗\n";
            $this->results[] = 'FAIL';
        }
    }

    private function testWorkerSleep() {
        echo "Test 5: Worker sleep timing... ";

        $pid = pcntl_fork();
        if ($pid === 0) {
            $start = microtime(true);
            $count = 0;
            Worker::start(function() use (&$count) {
                $count++;
                return $count < 5;
            }, ['sleep' => 0.1]);
            $elapsed = microtime(true) - $start;
            exit(($elapsed >= 0.4 && $elapsed < 0.6) ? 0 : 1);
        }

        pcntl_waitpid($pid, $status);

        if (pcntl_wifexited($status) && pcntl_wexitstatus($status) === 0) {
            echo "✓\n";
            $this->results[] = 'PASS';
        } else {
            echo "✗\n";
            $this->results[] = 'FAIL';
        }
    }

    private function printSummary() {
        $passed = count(array_filter($this->results, fn($r) => $r === 'PASS'));
        $total = count($this->results);

        echo "\n=== Summary ===\n";
        echo "Passed: $passed/$total\n";

        if ($passed === $total) {
            echo "\n✓ All worker lifecycle tests passed\n";
            exit(0);
        } else {
            echo "\n✗ Some tests failed\n";
            exit(1);
        }
    }
}

$test = new WorkerLifecycleTest();
$test->run();
