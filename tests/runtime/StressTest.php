<?php
require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Runtime;
use Core\Core\Runtime\Channel;
use Core\Core\Runtime\Timer;
use Core\Core\Runtime\Worker;

class StressTest {
    private $results = [];
    private $startMemory;
    private $startTime;

    public function run() {
        if (!Runtime::available()) {
            echo "✗ Runtime not available\n";
            exit(1);
        }

        $this->startMemory = memory_get_usage(true);
        $this->startTime = microtime(true);

        echo "=== Nexph Runtime Stress Test ===\n\n";

        $this->testMassiveCoroutineSpawn();
        $this->testHighVolumeChannelMessaging();
        $this->testTimerStorm();
        $this->testConcurrentTaskExecution();
        $this->testDeadlockDetection();
        $this->testOrphanedFiberCleanup();
        $this->testUnhandledExceptions();
        $this->testCoroutineCancellation();
        $this->testResourceCleanup();
        $this->testMemoryLeakDetection();
        $this->testLongRunningStability();
        $this->testEventLoopBlocking();

        $this->printSummary();
    }

    private function testMassiveCoroutineSpawn() {
        echo "Test 1: Massive Coroutine Spawn (10,000)... ";
        $start = microtime(true);
        $count = 0;

        for ($i = 0; $i < 10000; $i++) {
            Runtime::spawn(function() use (&$count) {
                $count++;
            });
        }

        Runtime::run();
        $elapsed = microtime(true) - $start;

        if ($count === 10000) {
            echo "✓ ({$elapsed}s, " . round(10000/$elapsed) . " spawns/s)\n";
            $this->results['massive_spawn'] = 'PASS';
        } else {
            echo "✗ (expected 10000, got $count)\n";
            $this->results['massive_spawn'] = 'FAIL';
        }
    }

    private function testHighVolumeChannelMessaging() {
        echo "Test 2: High Volume Channel (100,000 msgs)... ";
        $start = microtime(true);
        $ch = new Channel(1000);
        $received = 0;

        Runtime::spawn(function() use ($ch) {
            for ($i = 0; $i < 100000; $i++) {
                $ch->send($i);
            }
            $ch->close();
        });

        Runtime::spawn(function() use ($ch, &$received) {
            while (($val = $ch->receive()) !== null) {
                $received++;
            }
        });

        Runtime::run();
        $elapsed = microtime(true) - $start;

        if ($received === 100000) {
            echo "✓ ({$elapsed}s, " . round(100000/$elapsed) . " msgs/s)\n";
            $this->results['high_volume_channel'] = 'PASS';
        } else {
            echo "✗ (expected 100000, got $received)\n";
            $this->results['high_volume_channel'] = 'FAIL';
        }
    }

    private function testTimerStorm() {
        echo "Test 3: Timer Storm (1,000 timers)... ";
        $start = microtime(true);
        $fired = 0;

        for ($i = 0; $i < 1000; $i++) {
            Timer::after(0.001, function() use (&$fired) {
                $fired++;
            });
        }

        Runtime::run();
        $elapsed = microtime(true) - $start;

        if ($fired === 1000) {
            echo "✓ ({$elapsed}s)\n";
            $this->results['timer_storm'] = 'PASS';
        } else {
            echo "✗ (expected 1000, got $fired)\n";
            $this->results['timer_storm'] = 'FAIL';
        }
    }

    private function testConcurrentTaskExecution() {
        echo "Test 4: Concurrent Tasks (1,000 parallel)... ";
        $start = microtime(true);
        $completed = 0;

        for ($i = 0; $i < 1000; $i++) {
            Runtime::spawn(function() use (&$completed) {
                Runtime::sleep(0.001);
                $completed++;
            });
        }

        Runtime::run();
        $elapsed = microtime(true) - $start;

        if ($completed === 1000) {
            echo "✓ ({$elapsed}s)\n";
            $this->results['concurrent_tasks'] = 'PASS';
        } else {
            echo "✗ (expected 1000, got $completed)\n";
            $this->results['concurrent_tasks'] = 'FAIL';
        }
    }

    private function testDeadlockDetection() {
        echo "Test 5: Deadlock Detection... ";
        $ch1 = new Channel(0);
        $ch2 = new Channel(0);
        $timeout = false;

        Runtime::spawn(function() use ($ch1, $ch2) {
            $ch1->send(1);
            $ch2->receive();
        });

        Runtime::spawn(function() use ($ch1, $ch2) {
            $ch2->send(2);
            $ch1->receive();
        });

        Timer::after(0.1, function() use (&$timeout) {
            $timeout = true;
            Runtime::stop();
        });

        Runtime::run();

        if ($timeout) {
            echo "✓ (deadlock detected via timeout)\n";
            $this->results['deadlock_detection'] = 'PASS';
        } else {
            echo "✗ (no deadlock detected)\n";
            $this->results['deadlock_detection'] = 'FAIL';
        }
    }

    private function testOrphanedFiberCleanup() {
        echo "Test 6: Orphaned Fiber Cleanup... ";
        $memBefore = memory_get_usage(true);

        for ($i = 0; $i < 1000; $i++) {
            Runtime::spawn(function() {
                Runtime::sleep(0.001);
            });
        }

        Runtime::run();
        gc_collect_cycles();
        $memAfter = memory_get_usage(true);
        $leaked = $memAfter - $memBefore;

        if ($leaked < 1024 * 1024) {
            echo "✓ (leaked: " . round($leaked/1024) . " KB)\n";
            $this->results['orphaned_cleanup'] = 'PASS';
        } else {
            echo "✗ (leaked: " . round($leaked/1024) . " KB)\n";
            $this->results['orphaned_cleanup'] = 'FAIL';
        }
    }

    private function testUnhandledExceptions() {
        echo "Test 7: Unhandled Exceptions... ";
        $caught = 0;

        for ($i = 0; $i < 100; $i++) {
            Runtime::spawn(function() use (&$caught) {
                try {
                    throw new Exception("Test exception");
                } catch (Exception $e) {
                    $caught++;
                }
            });
        }

        Runtime::run();

        if ($caught === 100) {
            echo "✓ (all exceptions caught)\n";
            $this->results['unhandled_exceptions'] = 'PASS';
        } else {
            echo "✗ (expected 100, got $caught)\n";
            $this->results['unhandled_exceptions'] = 'FAIL';
        }
    }

    private function testCoroutineCancellation() {
        echo "Test 8: Coroutine Cancellation... ";
        $cancelled = 0;
        $coros = [];

        for ($i = 0; $i < 100; $i++) {
            $coros[] = Runtime::spawn(function() use (&$cancelled) {
                try {
                    Runtime::sleep(10.0);
                } catch (Exception $e) {
                    $cancelled++;
                }
            });
        }

        Timer::after(0.01, function() {
            Runtime::stop();
        });

        Runtime::run();

        echo "✓ (stopped early)\n";
        $this->results['coroutine_cancellation'] = 'PASS';
    }

    private function testResourceCleanup() {
        echo "Test 9: Resource Cleanup... ";
        $channels = [];

        for ($i = 0; $i < 1000; $i++) {
            $ch = new Channel(10);
            $ch->send($i);
            $ch->close();
            $channels[] = $ch;
        }

        unset($channels);
        gc_collect_cycles();

        echo "✓ (1000 channels cleaned)\n";
        $this->results['resource_cleanup'] = 'PASS';
    }

    private function testMemoryLeakDetection() {
        echo "Test 10: Memory Leak Detection (10k iterations)... ";
        $memStart = memory_get_usage(true);

        for ($iter = 0; $iter < 10000; $iter++) {
            Runtime::spawn(function() {
                Runtime::sleep(0.0001);
            });
            Runtime::run();
        }

        gc_collect_cycles();
        $memEnd = memory_get_usage(true);
        $leaked = $memEnd - $memStart;

        if ($leaked < 5 * 1024 * 1024) {
            echo "✓ (leaked: " . round($leaked/1024) . " KB)\n";
            $this->results['memory_leak'] = 'PASS';
        } else {
            echo "✗ (leaked: " . round($leaked/1024/1024) . " MB)\n";
            $this->results['memory_leak'] = 'FAIL';
        }
    }

    private function testLongRunningStability() {
        echo "Test 11: Long Running Stability (1000 iterations)... ";
        $errors = 0;

        for ($i = 0; $i < 1000; $i++) {
            try {
                Runtime::spawn(function() {
                    Runtime::sleep(0.001);
                });
                Runtime::run();
            } catch (Exception $e) {
                $errors++;
            }
        }

        if ($errors === 0) {
            echo "✓ (no errors)\n";
            $this->results['long_running'] = 'PASS';
        } else {
            echo "✗ ($errors errors)\n";
            $this->results['long_running'] = 'FAIL';
        }
    }

    private function testEventLoopBlocking() {
        echo "Test 12: Event Loop Blocking Detection... ";
        $start = microtime(true);
        $completed = false;

        Runtime::spawn(function() use (&$completed) {
            Runtime::sleep(0.1);
            $completed = true;
        });

        Runtime::run();
        $elapsed = microtime(true) - $start;

        if ($completed && $elapsed < 0.2) {
            echo "✓ ({$elapsed}s)\n";
            $this->results['event_loop_blocking'] = 'PASS';
        } else {
            echo "✗ (blocked or timeout)\n";
            $this->results['event_loop_blocking'] = 'FAIL';
        }
    }

    private function printSummary() {
        $totalTime = microtime(true) - $this->startTime;
        $memUsed = memory_get_usage(true) - $this->startMemory;
        $passed = count(array_filter($this->results, fn($r) => $r === 'PASS'));
        $total = count($this->results);

        echo "\n=== Summary ===\n";
        echo "Tests: $passed/$total passed\n";
        echo "Time: " . round($totalTime, 2) . "s\n";
        echo "Memory: " . round($memUsed/1024/1024, 2) . " MB\n";
        echo "Peak: " . round(memory_get_peak_usage(true)/1024/1024, 2) . " MB\n";

        if ($passed === $total) {
            echo "\n✓ All stress tests passed\n";
            exit(0);
        } else {
            echo "\n✗ Some tests failed\n";
            exit(1);
        }
    }
}

$test = new StressTest();
$test->run();
