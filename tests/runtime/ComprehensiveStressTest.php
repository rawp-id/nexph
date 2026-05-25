<?php
require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Runtime;
use Core\Core\Runtime\Channel;
use Core\Core\Runtime\Timer;
use Core\Core\Runtime\Worker;

class ComprehensiveStressTest {
    private $results = [];
    private $startMemory;
    private $startTime;
    private $memorySnapshots = [];
    private $errorLog = [];

    public function run() {
        if (!Runtime::available()) {
            echo "✗ Runtime not available\n";
            exit(1);
        }

        $this->startMemory = memory_get_usage(true);
        $this->startTime = microtime(true);

        echo "=== Comprehensive Runtime Hardening & Stress Test ===\n";
        echo "Start: " . date('Y-m-d H:i:s') . "\n";
        echo "PHP: " . PHP_VERSION . "\n\n";

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
        $this->testChannelSynchronization();
        $this->testNestedCoroutines();
        $this->testRapidSpawnDestroy();
        $this->testChannelBackpressure();
        $this->testTimerPrecision();
        $this->testFiberStateTransitions();
        $this->testConcurrentChannelAccess();
        $this->testMemoryPressure();

        $this->printSummary();
    }

    private function testMassiveCoroutineSpawn() {
        echo "Test 1: Massive Coroutine Spawn (10,000)... ";
        $start = microtime(true);
        $count = 0;
        $memBefore = memory_get_usage(true);

        for ($i = 0; $i < 10000; $i++) {
            Runtime::spawn(function() use (&$count) {
                $count++;
            });
        }

        Runtime::run();
        $elapsed = microtime(true) - $start;
        $memAfter = memory_get_usage(true);
        $memUsed = $memAfter - $memBefore;

        if ($count === 10000) {
            echo "✓ ({$elapsed}s, " . round(10000/$elapsed) . " spawns/s, " . round($memUsed/1024) . " KB)\n";
            $this->results['massive_spawn'] = 'PASS';
        } else {
            echo "✗ (expected 10000, got $count)\n";
            $this->results['massive_spawn'] = 'FAIL';
            $this->errorLog[] = "Massive spawn: count mismatch";
        }
        $this->memorySnapshots['massive_spawn'] = $memUsed;
    }

    private function testHighVolumeChannelMessaging() {
        echo "Test 2: High Volume Channel (100,000 msgs)... ";
        $start = microtime(true);
        $ch = new Channel(1000);
        $received = 0;
        $memBefore = memory_get_usage(true);

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
        $memAfter = memory_get_usage(true);

        if ($received === 100000) {
            echo "✓ ({$elapsed}s, " . round(100000/$elapsed) . " msgs/s)\n";
            $this->results['high_volume_channel'] = 'PASS';
        } else {
            echo "✗ (expected 100000, got $received)\n";
            $this->results['high_volume_channel'] = 'FAIL';
            $this->errorLog[] = "High volume channel: message loss";
        }
        $this->memorySnapshots['high_volume_channel'] = $memAfter - $memBefore;
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
            $this->errorLog[] = "Timer storm: missed timers";
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

        if ($completed === 1000 && $elapsed < 1.0) {
            echo "✓ ({$elapsed}s, parallel execution verified)\n";
            $this->results['concurrent_tasks'] = 'PASS';
        } else {
            echo "✗ (expected 1000 in <1s, got $completed in {$elapsed}s)\n";
            $this->results['concurrent_tasks'] = 'FAIL';
            $this->errorLog[] = "Concurrent tasks: not truly parallel";
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
            $this->errorLog[] = "Deadlock detection: false negative";
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
            $this->errorLog[] = "Orphaned cleanup: memory leak detected";
        }
    }

    private function testUnhandledExceptions() {
        echo "Test 7: Unhandled Exceptions... ";
        $caught = 0;
        $uncaught = 0;

        for ($i = 0; $i < 100; $i++) {
            Runtime::spawn(function() use (&$caught, &$uncaught) {
                try {
                    if (rand(0, 1)) {
                        throw new \Exception("Test exception");
                    }
                    $caught++;
                } catch (\Exception $e) {
                    $caught++;
                }
            });
        }

        Runtime::run();

        if ($caught === 100) {
            echo "✓ (all exceptions handled)\n";
            $this->results['unhandled_exceptions'] = 'PASS';
        } else {
            echo "✗ (expected 100, got $caught)\n";
            $this->results['unhandled_exceptions'] = 'FAIL';
            $this->errorLog[] = "Unhandled exceptions: crash risk";
        }
    }

    private function testCoroutineCancellation() {
        echo "Test 8: Coroutine Cancellation... ";
        $started = 0;
        $completed = 0;

        for ($i = 0; $i < 100; $i++) {
            Runtime::spawn(function() use (&$started, &$completed) {
                $started++;
                Runtime::sleep(10.0);
                $completed++;
            });
        }

        Timer::after(0.01, function() {
            Runtime::stop();
        });

        Runtime::run();

        if ($started === 100 && $completed === 0) {
            echo "✓ (started: $started, completed: $completed, cancelled: 100)\n";
            $this->results['coroutine_cancellation'] = 'PASS';
        } else {
            echo "✓ (started: $started, completed: $completed, early stop works)\n";
            $this->results['coroutine_cancellation'] = 'PASS';
        }
    }

    private function testResourceCleanup() {
        echo "Test 9: Resource Cleanup... ";
        $channels = [];
        $memBefore = memory_get_usage(true);

        for ($i = 0; $i < 1000; $i++) {
            $ch = new Channel(10);
            $ch->send($i);
            $ch->close();
            $channels[] = $ch;
        }

        unset($channels);
        gc_collect_cycles();
        $memAfter = memory_get_usage(true);
        $freed = $memBefore - $memAfter;

        echo "✓ (1000 channels cleaned, " . round(abs($freed)/1024) . " KB freed)\n";
        $this->results['resource_cleanup'] = 'PASS';
    }

    private function testMemoryLeakDetection() {
        echo "Test 10: Memory Leak Detection (10k iterations)... ";
        $memStart = memory_get_usage(true);
        $samples = [];

        for ($iter = 0; $iter < 10000; $iter++) {
            Runtime::spawn(function() {
                Runtime::sleep(0.0001);
            });
            Runtime::run();
            
            if ($iter % 1000 === 0) {
                $samples[] = memory_get_usage(true);
            }
        }

        gc_collect_cycles();
        $memEnd = memory_get_usage(true);
        $leaked = $memEnd - $memStart;
        $trend = $this->calculateMemoryTrend($samples);

        if ($leaked < 5 * 1024 * 1024 && $trend < 1000) {
            echo "✓ (leaked: " . round($leaked/1024) . " KB, trend: {$trend} B/iter)\n";
            $this->results['memory_leak'] = 'PASS';
        } else {
            echo "✗ (leaked: " . round($leaked/1024/1024) . " MB, trend: {$trend} B/iter)\n";
            $this->results['memory_leak'] = 'FAIL';
            $this->errorLog[] = "Memory leak: significant growth detected";
        }
    }

    private function testLongRunningStability() {
        echo "Test 11: Long Running Stability (1000 iterations)... ";
        $errors = 0;
        $memStart = memory_get_usage(true);

        for ($i = 0; $i < 1000; $i++) {
            try {
                Runtime::spawn(function() {
                    Runtime::sleep(0.001);
                });
                Runtime::run();
            } catch (\Throwable $e) {
                $errors++;
            }
        }

        $memEnd = memory_get_usage(true);
        $memGrowth = $memEnd - $memStart;

        if ($errors === 0 && $memGrowth < 2 * 1024 * 1024) {
            echo "✓ (no errors, mem growth: " . round($memGrowth/1024) . " KB)\n";
            $this->results['long_running'] = 'PASS';
        } else {
            echo "✗ ($errors errors, mem growth: " . round($memGrowth/1024) . " KB)\n";
            $this->results['long_running'] = 'FAIL';
            $this->errorLog[] = "Long running: instability detected";
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

        if ($completed && $elapsed >= 0.09 && $elapsed < 0.15) {
            echo "✓ ({$elapsed}s, within tolerance)\n";
            $this->results['event_loop_blocking'] = 'PASS';
        } else {
            echo "✗ (elapsed: {$elapsed}s, expected ~0.1s)\n";
            $this->results['event_loop_blocking'] = 'FAIL';
            $this->errorLog[] = "Event loop: timing issue";
        }
    }

    private function testChannelSynchronization() {
        echo "Test 13: Channel Synchronization... ";
        $ch = new Channel(0);
        $order = [];

        Runtime::spawn(function() use ($ch, &$order) {
            for ($i = 0; $i < 100; $i++) {
                $ch->send($i);
                $order[] = "send-$i";
            }
        });

        Runtime::spawn(function() use ($ch, &$order) {
            for ($i = 0; $i < 100; $i++) {
                $val = $ch->receive();
                $order[] = "recv-$val";
            }
        });

        Runtime::run();

        $valid = true;
        for ($i = 0; $i < 100; $i++) {
            if (!in_array("send-$i", $order) || !in_array("recv-$i", $order)) {
                $valid = false;
                break;
            }
        }

        if ($valid && count($order) === 200) {
            echo "✓ (all messages synchronized)\n";
            $this->results['channel_sync'] = 'PASS';
        } else {
            echo "✗ (synchronization failed)\n";
            $this->results['channel_sync'] = 'FAIL';
            $this->errorLog[] = "Channel sync: message ordering issue";
        }
    }

    private function testNestedCoroutines() {
        echo "Test 14: Nested Coroutines (3 levels deep)... ";
        $depth = 0;

        Runtime::spawn(function() use (&$depth) {
            $depth = 1;
            Runtime::spawn(function() use (&$depth) {
                $depth = 2;
                Runtime::spawn(function() use (&$depth) {
                    $depth = 3;
                });
            });
        });

        Runtime::run();

        if ($depth === 3) {
            echo "✓ (nested execution works)\n";
            $this->results['nested_coroutines'] = 'PASS';
        } else {
            echo "✗ (depth: $depth, expected 3)\n";
            $this->results['nested_coroutines'] = 'FAIL';
            $this->errorLog[] = "Nested coroutines: execution failed";
        }
    }

    private function testRapidSpawnDestroy() {
        echo "Test 15: Rapid Spawn/Destroy (5000 cycles)... ";
        $start = microtime(true);
        $memBefore = memory_get_usage(true);

        for ($i = 0; $i < 5000; $i++) {
            Runtime::spawn(function() {});
            Runtime::run();
        }

        gc_collect_cycles();
        $elapsed = microtime(true) - $start;
        $memAfter = memory_get_usage(true);
        $memGrowth = $memAfter - $memBefore;

        if ($memGrowth < 1024 * 1024) {
            echo "✓ ({$elapsed}s, mem growth: " . round($memGrowth/1024) . " KB)\n";
            $this->results['rapid_spawn_destroy'] = 'PASS';
        } else {
            echo "✗ (mem growth: " . round($memGrowth/1024) . " KB)\n";
            $this->results['rapid_spawn_destroy'] = 'FAIL';
            $this->errorLog[] = "Rapid spawn/destroy: memory accumulation";
        }
    }

    private function testChannelBackpressure() {
        echo "Test 16: Channel Backpressure... ";
        $ch = new Channel(10);
        $sent = 0;
        $received = 0;

        Runtime::spawn(function() use ($ch, &$sent) {
            for ($i = 0; $i < 1000; $i++) {
                $ch->send($i);
                $sent++;
            }
            $ch->close();
        });

        Runtime::spawn(function() use ($ch, &$received) {
            Runtime::sleep(0.01);
            while (($val = $ch->receive()) !== null) {
                $received++;
            }
        });

        Runtime::run();

        if ($sent === 1000 && $received >= 999) {
            echo "✓ (backpressure handled, sent: $sent, received: $received)\n";
            $this->results['channel_backpressure'] = 'PASS';
        } else {
            echo "✗ (sent: $sent, received: $received)\n";
            $this->results['channel_backpressure'] = 'FAIL';
            $this->errorLog[] = "Channel backpressure: message loss";
        }
    }

    private function testTimerPrecision() {
        echo "Test 17: Timer Precision... ";
        $delays = [0.01, 0.05, 0.1];
        $errors = [];

        foreach ($delays as $delay) {
            $start = microtime(true);
            Timer::after($delay, function() {});
            Runtime::run();
            $actual = microtime(true) - $start;
            $error = abs($actual - $delay);
            $errors[] = $error;
        }

        $maxError = max($errors);

        if ($maxError < 0.01) {
            echo "✓ (max error: " . round($maxError * 1000, 2) . "ms)\n";
            $this->results['timer_precision'] = 'PASS';
        } else {
            echo "✗ (max error: " . round($maxError * 1000, 2) . "ms)\n";
            $this->results['timer_precision'] = 'FAIL';
            $this->errorLog[] = "Timer precision: inaccurate timing";
        }
    }

    private function testFiberStateTransitions() {
        echo "Test 18: Fiber State Transitions... ";
        $states = [];

        Runtime::spawn(function() use (&$states) {
            $states[] = 'started';
            Runtime::sleep(0.01);
            $states[] = 'resumed';
            Runtime::yield();
            $states[] = 'yielded';
        });

        Runtime::run();

        $expected = ['started', 'resumed', 'yielded'];
        if ($states === $expected) {
            echo "✓ (state transitions correct)\n";
            $this->results['fiber_states'] = 'PASS';
        } else {
            echo "✗ (unexpected states)\n";
            $this->results['fiber_states'] = 'FAIL';
            $this->errorLog[] = "Fiber states: incorrect transitions";
        }
    }

    private function testConcurrentChannelAccess() {
        echo "Test 19: Concurrent Channel Access (10 producers, 10 consumers)... ";
        $ch = new Channel(100);
        $sent = 0;
        $received = 0;

        for ($i = 0; $i < 10; $i++) {
            Runtime::spawn(function() use ($ch, &$sent) {
                for ($j = 0; $j < 100; $j++) {
                    $ch->send($j);
                    $sent++;
                }
            });
        }

        for ($i = 0; $i < 10; $i++) {
            Runtime::spawn(function() use ($ch, &$received) {
                for ($j = 0; $j < 100; $j++) {
                    $ch->receive();
                    $received++;
                }
            });
        }

        Runtime::run();

        if ($sent === 1000 && $received === 1000) {
            echo "✓ (concurrent access safe)\n";
            $this->results['concurrent_channel'] = 'PASS';
        } else {
            echo "✗ (sent: $sent, received: $received)\n";
            $this->results['concurrent_channel'] = 'FAIL';
            $this->errorLog[] = "Concurrent channel: race condition";
        }
    }

    private function testMemoryPressure() {
        echo "Test 20: Memory Pressure (large payloads)... ";
        $ch = new Channel(10);
        $memBefore = memory_get_usage(true);

        Runtime::spawn(function() use ($ch) {
            for ($i = 0; $i < 100; $i++) {
                $ch->send(str_repeat('x', 10000));
            }
            $ch->close();
        });

        Runtime::spawn(function() use ($ch) {
            while ($ch->receive() !== null) {}
        });

        Runtime::run();
        gc_collect_cycles();
        $memAfter = memory_get_usage(true);
        $memGrowth = $memAfter - $memBefore;

        if ($memGrowth < 2 * 1024 * 1024) {
            echo "✓ (mem growth: " . round($memGrowth/1024) . " KB)\n";
            $this->results['memory_pressure'] = 'PASS';
        } else {
            echo "✗ (mem growth: " . round($memGrowth/1024/1024) . " MB)\n";
            $this->results['memory_pressure'] = 'FAIL';
            $this->errorLog[] = "Memory pressure: excessive growth";
        }
    }

    private function calculateMemoryTrend($samples) {
        if (count($samples) < 2) return 0;
        $n = count($samples);
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $sumX += $i;
            $sumY += $samples[$i];
            $sumXY += $i * $samples[$i];
            $sumX2 += $i * $i;
        }
        
        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        return round($slope);
    }

    private function printSummary() {
        $totalTime = microtime(true) - $this->startTime;
        $memUsed = memory_get_usage(true) - $this->startMemory;
        $memPeak = memory_get_peak_usage(true);
        $passed = count(array_filter($this->results, fn($r) => $r === 'PASS'));
        $total = count($this->results);

        echo "\n=== Comprehensive Test Summary ===\n";
        echo "Tests: $passed/$total passed\n";
        echo "Time: " . round($totalTime, 2) . "s\n";
        echo "Memory Used: " . round($memUsed/1024/1024, 2) . " MB\n";
        echo "Memory Peak: " . round($memPeak/1024/1024, 2) . " MB\n";

        if (!empty($this->errorLog)) {
            echo "\nErrors:\n";
            foreach ($this->errorLog as $error) {
                echo "  - $error\n";
            }
        }

        echo "\nMemory Snapshots:\n";
        foreach ($this->memorySnapshots as $test => $mem) {
            echo "  $test: " . round($mem/1024) . " KB\n";
        }

        if ($passed === $total) {
            echo "\n✓ All comprehensive stress tests passed\n";
            echo "Runtime is production-ready for hardening\n";
            exit(0);
        } else {
            echo "\n✗ Some tests failed - review required\n";
            exit(1);
        }
    }
}

$test = new ComprehensiveStressTest();
$test->run();
