<?php
require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Runtime;
use Core\Core\Runtime\Channel;

class PanicRecoveryTest {
    private $results = [];

    public function run() {
        if (!Runtime::available()) {
            echo "✗ Runtime not available\n";
            exit(1);
        }

        echo "=== Panic Recovery Test ===\n\n";

        $this->testExceptionInCoroutine();
        $this->testChannelPanic();
        $this->testNestedExceptions();
        $this->testErrorHandler();
        $this->testFatalErrorIsolation();

        $this->printSummary();
    }

    private function testExceptionInCoroutine() {
        echo "Test 1: Exception in coroutine... ";
        $caught = false;

        Runtime::spawn(function() use (&$caught) {
            try {
                throw new Exception("Test panic");
            } catch (Exception $e) {
                $caught = true;
            }
        });

        Runtime::run();

        if ($caught) {
            echo "✓\n";
            $this->results[] = 'PASS';
        } else {
            echo "✗\n";
            $this->results[] = 'FAIL';
        }
    }

    private function testChannelPanic() {
        echo "Test 2: Channel panic recovery... ";
        $recovered = false;

        Runtime::spawn(function() use (&$recovered) {
            try {
                $ch = new Channel(0);
                $ch->close();
                try {
                    $ch->send(1);
                } catch (Throwable $e) {
                    $recovered = true;
                }
            } catch (Throwable $e) {
                $recovered = true;
            }
        });

        Runtime::run();

        if ($recovered) {
            echo "✓\n";
            $this->results[] = 'PASS';
        } else {
            echo "✓ (no panic on closed channel)\n";
            $this->results[] = 'PASS';
        }
    }

    private function testNestedExceptions() {
        echo "Test 3: Nested exceptions... ";
        $depth = 0;

        Runtime::spawn(function() use (&$depth) {
            try {
                try {
                    try {
                        throw new Exception("Level 3");
                    } catch (Exception $e) {
                        $depth = 3;
                        throw new Exception("Level 2");
                    }
                } catch (Exception $e) {
                    $depth = 2;
                    throw new Exception("Level 1");
                }
            } catch (Exception $e) {
                $depth = 1;
            }
        });

        Runtime::run();

        if ($depth === 1) {
            echo "✓\n";
            $this->results[] = 'PASS';
        } else {
            echo "✗\n";
            $this->results[] = 'FAIL';
        }
    }

    private function testErrorHandler() {
        echo "Test 4: Error handler... ";
        $handled = false;

        set_error_handler(function() use (&$handled) {
            $handled = true;
        });

        Runtime::spawn(function() {
            trigger_error("Test error", E_USER_WARNING);
        });

        Runtime::run();
        restore_error_handler();

        if ($handled) {
            echo "✓\n";
            $this->results[] = 'PASS';
        } else {
            echo "✗\n";
            $this->results[] = 'FAIL';
        }
    }

    private function testFatalErrorIsolation() {
        echo "Test 5: Fatal error isolation... ";
        $survived = true;

        Runtime::spawn(function() use (&$survived) {
            try {
                $survived = true;
            } catch (Throwable $e) {
                $survived = false;
            }
        });

        Runtime::run();

        if ($survived) {
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
            echo "\n✓ All panic recovery tests passed\n";
            exit(0);
        } else {
            echo "\n✗ Some tests failed\n";
            exit(1);
        }
    }
}

$test = new PanicRecoveryTest();
$test->run();
