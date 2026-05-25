<?php
require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Runtime;
use Core\Core\Runtime\Channel;
use Core\Core\Runtime\Timer;

class DeadlockDetector {
    private $timeout = 5.0;
    private $results = [];

    public function run() {
        if (!Runtime::available()) {
            echo "✗ Runtime not available\n";
            exit(1);
        }

        echo "=== Deadlock Detection Test ===\n\n";

        $this->testSimpleDeadlock();
        $this->testCircularDeadlock();
        $this->testChannelDeadlock();
        $this->testNestedDeadlock();
        $this->testPartialDeadlock();

        $this->printSummary();
    }

    private function testSimpleDeadlock() {
        echo "Test 1: Simple Deadlock (2 channels)... ";
        $ch1 = new Channel(0);
        $ch2 = new Channel(0);
        $detected = false;

        Runtime::spawn(function() use ($ch1, $ch2) {
            $ch1->send(1);
            $ch2->receive();
        });

        Runtime::spawn(function() use ($ch1, $ch2) {
            $ch2->send(2);
            $ch1->receive();
        });

        Timer::after($this->timeout, function() use (&$detected) {
            $detected = true;
            Runtime::stop();
        });

        Runtime::run();

        if ($detected) {
            echo "✓ (deadlock detected)\n";
            $this->results['simple_deadlock'] = 'PASS';
        } else {
            echo "✗ (no deadlock)\n";
            $this->results['simple_deadlock'] = 'FAIL';
        }
    }

    private function testCircularDeadlock() {
        echo "Test 2: Circular Deadlock (3 channels)... ";
        $ch1 = new Channel(0);
        $ch2 = new Channel(0);
        $ch3 = new Channel(0);
        $detected = false;

        Runtime::spawn(function() use ($ch1, $ch2) {
            $ch1->send(1);
            $ch2->receive();
        });

        Runtime::spawn(function() use ($ch2, $ch3) {
            $ch2->send(2);
            $ch3->receive();
        });

        Runtime::spawn(function() use ($ch3, $ch1) {
            $ch3->send(3);
            $ch1->receive();
        });

        Timer::after($this->timeout, function() use (&$detected) {
            $detected = true;
            Runtime::stop();
        });

        Runtime::run();

        if ($detected) {
            echo "✓ (circular deadlock detected)\n";
            $this->results['circular_deadlock'] = 'PASS';
        } else {
            echo "✗ (no deadlock)\n";
            $this->results['circular_deadlock'] = 'FAIL';
        }
    }

    private function testChannelDeadlock() {
        echo "Test 3: Channel Deadlock (unbuffered)... ";
        $ch = new Channel(0);
        $detected = false;

        Runtime::spawn(function() use ($ch) {
            $ch->receive();
            $ch->send(1);
        });

        Runtime::spawn(function() use ($ch) {
            $ch->receive();
            $ch->send(2);
        });

        Timer::after($this->timeout, function() use (&$detected) {
            $detected = true;
            Runtime::stop();
        });

        Runtime::run();

        if ($detected) {
            echo "✓ (channel deadlock detected)\n";
            $this->results['channel_deadlock'] = 'PASS';
        } else {
            echo "✗ (no deadlock)\n";
            $this->results['channel_deadlock'] = 'FAIL';
        }
    }

    private function testNestedDeadlock() {
        echo "Test 4: Nested Deadlock... ";
        $ch1 = new Channel(0);
        $ch2 = new Channel(0);
        $detected = false;

        Runtime::spawn(function() use ($ch1, $ch2) {
            Runtime::spawn(function() use ($ch1) {
                $ch1->send(1);
            });
            $ch2->receive();
        });

        Runtime::spawn(function() use ($ch1, $ch2) {
            Runtime::spawn(function() use ($ch2) {
                $ch2->send(2);
            });
            $ch1->receive();
        });

        Timer::after($this->timeout, function() use (&$detected) {
            $detected = true;
            Runtime::stop();
        });

        Runtime::run();

        if ($detected) {
            echo "✓ (nested deadlock detected)\n";
            $this->results['nested_deadlock'] = 'PASS';
        } else {
            echo "✗ (no deadlock)\n";
            $this->results['nested_deadlock'] = 'FAIL';
        }
    }

    private function testPartialDeadlock() {
        echo "Test 5: Partial Deadlock (some progress)... ";
        $ch1 = new Channel(0);
        $ch2 = new Channel(0);
        $progress = 0;
        $detected = false;

        Runtime::spawn(function() use ($ch1, &$progress) {
            for ($i = 0; $i < 10; $i++) {
                $progress++;
                Runtime::sleep(0.01);
            }
            $ch1->send(1);
        });

        Runtime::spawn(function() use ($ch1, $ch2) {
            $ch1->receive();
            $ch2->send(2);
        });

        Runtime::spawn(function() use ($ch2) {
            $ch2->receive();
        });

        Timer::after($this->timeout, function() use (&$detected) {
            $detected = true;
            Runtime::stop();
        });

        Runtime::run();

        if ($progress > 0 && !$detected) {
            echo "✓ (no deadlock, progress made)\n";
            $this->results['partial_deadlock'] = 'PASS';
        } else {
            echo "✗ (deadlock or no progress)\n";
            $this->results['partial_deadlock'] = 'FAIL';
        }
    }

    private function printSummary() {
        $passed = count(array_filter($this->results, fn($r) => $r === 'PASS'));
        $total = count($this->results);

        echo "\n=== Summary ===\n";
        echo "Tests: $passed/$total passed\n";

        if ($passed === $total) {
            echo "\n✓ All deadlock detection tests passed\n";
            exit(0);
        } else {
            echo "\n✗ Some tests failed\n";
            exit(1);
        }
    }
}

$test = new DeadlockDetector();
$test->run();
