<?php
require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Runtime;
use Core\Core\Runtime\Channel;
use Core\Core\Runtime\Timer;

class PerformanceBenchmark {
    private $results = [];

    public function run() {
        if (!Runtime::available()) {
            echo "✗ Runtime not available\n";
            exit(1);
        }

        echo "=== Nexph Runtime Performance Benchmark ===\n";
        echo "PHP: " . PHP_VERSION . "\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

        $this->benchmarkCoroutineSpawnCost();
        $this->benchmarkContextSwitchOverhead();
        $this->benchmarkChannelThroughput();
        $this->benchmarkTimerAccuracy();
        $this->benchmarkEventLoopLatency();
        $this->benchmarkMemoryPerCoroutine();
        $this->benchmarkConcurrentExecution();
        $this->benchmarkChannelLatency();
        $this->benchmarkTimerOverhead();
        $this->benchmarkYieldCost();

        $this->generateReport();
    }

    private function benchmarkCoroutineSpawnCost() {
        echo "Benchmark 1: Coroutine Spawn Cost... ";
        $iterations = 10000;
        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            Runtime::spawn(function() {});
        }

        Runtime::run();
        $elapsed = microtime(true) - $start;
        $perOp = ($elapsed / $iterations) * 1000;

        echo "✓\n";
        echo "  Total: " . round($elapsed, 3) . "s\n";
        echo "  Per spawn: " . round($perOp, 4) . "ms\n";
        echo "  Throughput: " . round($iterations/$elapsed) . " spawns/s\n";

        $this->results['spawn_cost_ms'] = round($perOp, 4);
        $this->results['spawn_throughput'] = round($iterations/$elapsed);
    }

    private function benchmarkContextSwitchOverhead() {
        echo "\nBenchmark 2: Context Switch Overhead... ";
        $iterations = 10000;
        $ch = new Channel(0);
        $start = microtime(true);

        Runtime::spawn(function() use ($ch, $iterations) {
            for ($i = 0; $i < $iterations; $i++) {
                $ch->send($i);
            }
        });

        Runtime::spawn(function() use ($ch, $iterations) {
            for ($i = 0; $i < $iterations; $i++) {
                $ch->receive();
            }
        });

        Runtime::run();
        $elapsed = microtime(true) - $start;
        $perSwitch = ($elapsed / ($iterations * 2)) * 1000;

        echo "✓\n";
        echo "  Total: " . round($elapsed, 3) . "s\n";
        echo "  Per switch: " . round($perSwitch, 4) . "ms\n";
        echo "  Switches/s: " . round(($iterations*2)/$elapsed) . "\n";

        $this->results['context_switch_ms'] = round($perSwitch, 4);
        $this->results['context_switch_throughput'] = round(($iterations*2)/$elapsed);
    }

    private function benchmarkChannelThroughput() {
        echo "\nBenchmark 3: Channel Throughput... ";
        $messages = 100000;
        $ch = new Channel(1000);
        $start = microtime(true);

        Runtime::spawn(function() use ($ch, $messages) {
            for ($i = 0; $i < $messages; $i++) {
                $ch->send($i);
            }
            $ch->close();
        });

        Runtime::spawn(function() use ($ch) {
            while ($ch->receive() !== null) {}
        });

        Runtime::run();
        $elapsed = microtime(true) - $start;
        $throughput = round($messages/$elapsed);

        echo "✓\n";
        echo "  Messages: $messages\n";
        echo "  Time: " . round($elapsed, 3) . "s\n";
        echo "  Throughput: $throughput msgs/s\n";

        $this->results['channel_throughput'] = $throughput;
    }

    private function benchmarkTimerAccuracy() {
        echo "\nBenchmark 4: Timer Accuracy... ";
        $delays = [0.01, 0.05, 0.1, 0.5];
        $errors = [];

        foreach ($delays as $delay) {
            $start = microtime(true);
            Timer::after($delay, function() {});
            Runtime::run();
            $actual = microtime(true) - $start;
            $error = abs($actual - $delay);
            $errors[] = $error;
        }

        $avgError = array_sum($errors) / count($errors);

        echo "✓\n";
        echo "  Average error: " . round($avgError * 1000, 2) . "ms\n";
        echo "  Max error: " . round(max($errors) * 1000, 2) . "ms\n";

        $this->results['timer_accuracy_ms'] = round($avgError * 1000, 2);
    }

    private function benchmarkEventLoopLatency() {
        echo "\nBenchmark 5: Event Loop Latency... ";
        $iterations = 1000;
        $latencies = [];

        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            Runtime::spawn(function() {});
            Runtime::run();
            $latencies[] = (microtime(true) - $start) * 1000;
        }

        $avg = array_sum($latencies) / count($latencies);
        sort($latencies);
        $p50 = $latencies[intval(count($latencies) * 0.50)];
        $p95 = $latencies[intval(count($latencies) * 0.95)];
        $p99 = $latencies[intval(count($latencies) * 0.99)];

        echo "✓\n";
        echo "  Average: " . round($avg, 3) . "ms\n";
        echo "  P50: " . round($p50, 3) . "ms\n";
        echo "  P95: " . round($p95, 3) . "ms\n";
        echo "  P99: " . round($p99, 3) . "ms\n";

        $this->results['loop_latency_avg_ms'] = round($avg, 3);
        $this->results['loop_latency_p99_ms'] = round($p99, 3);
    }

    private function benchmarkMemoryPerCoroutine() {
        echo "\nBenchmark 6: Memory Per Coroutine... ";
        $count = 1000;
        $memBefore = memory_get_usage(true);

        for ($i = 0; $i < $count; $i++) {
            Runtime::spawn(function() {
                Runtime::sleep(0.001);
            });
        }

        $memAfter = memory_get_usage(true);
        $perCoroutine = ($memAfter - $memBefore) / $count;

        Runtime::run();

        echo "✓\n";
        echo "  Per coroutine: " . round($perCoroutine/1024, 2) . " KB\n";
        echo "  Total ($count): " . round(($memAfter-$memBefore)/1024, 2) . " KB\n";

        $this->results['memory_per_coro_kb'] = round($perCoroutine/1024, 2);
    }

    private function benchmarkConcurrentExecution() {
        echo "\nBenchmark 7: Concurrent Execution (1000 tasks)... ";
        $start = microtime(true);

        for ($i = 0; $i < 1000; $i++) {
            Runtime::spawn(function() {
                Runtime::sleep(0.01);
            });
        }

        Runtime::run();
        $elapsed = microtime(true) - $start;

        echo "✓\n";
        echo "  Time: " . round($elapsed, 3) . "s\n";
        echo "  Expected sequential: ~10s\n";
        echo "  Speedup: " . round(10/$elapsed, 1) . "x\n";

        $this->results['concurrent_speedup'] = round(10/$elapsed, 1);
    }

    private function benchmarkChannelLatency() {
        echo "\nBenchmark 8: Channel Latency (unbuffered)... ";
        $iterations = 1000;
        $ch = new Channel(0);
        $latencies = [];

        for ($i = 0; $i < $iterations; $i++) {
            $start = microtime(true);
            
            Runtime::spawn(function() use ($ch) {
                $ch->send(1);
            });

            Runtime::spawn(function() use ($ch) {
                $ch->receive();
            });

            Runtime::run();
            $latencies[] = (microtime(true) - $start) * 1000;
        }

        $avg = array_sum($latencies) / count($latencies);

        echo "✓\n";
        echo "  Average: " . round($avg, 3) . "ms\n";

        $this->results['channel_latency_ms'] = round($avg, 3);
    }

    private function benchmarkTimerOverhead() {
        echo "\nBenchmark 9: Timer Overhead... ";
        $iterations = 1000;
        $start = microtime(true);

        for ($i = 0; $i < $iterations; $i++) {
            Timer::after(0.001, function() {});
        }

        Runtime::run();
        $elapsed = microtime(true) - $start;
        $perTimer = ($elapsed / $iterations) * 1000;

        echo "✓\n";
        echo "  Per timer: " . round($perTimer, 4) . "ms\n";
        echo "  Throughput: " . round($iterations/$elapsed) . " timers/s\n";

        $this->results['timer_overhead_ms'] = round($perTimer, 4);
    }

    private function benchmarkYieldCost() {
        echo "\nBenchmark 10: Yield Cost... ";
        $iterations = 10000;
        $start = microtime(true);

        Runtime::spawn(function() use ($iterations) {
            for ($i = 0; $i < $iterations; $i++) {
                Runtime::yield();
            }
        });

        Runtime::run();
        $elapsed = microtime(true) - $start;
        $perYield = ($elapsed / $iterations) * 1000;

        echo "✓\n";
        echo "  Per yield: " . round($perYield, 4) . "ms\n";
        echo "  Throughput: " . round($iterations/$elapsed) . " yields/s\n";

        $this->results['yield_cost_ms'] = round($perYield, 4);
    }

    private function generateReport() {
        echo "\n=== Performance Report ===\n\n";

        echo "Latency Metrics:\n";
        echo "  Coroutine spawn: " . $this->results['spawn_cost_ms'] . "ms\n";
        echo "  Context switch: " . $this->results['context_switch_ms'] . "ms\n";
        echo "  Yield: " . $this->results['yield_cost_ms'] . "ms\n";
        echo "  Channel latency: " . $this->results['channel_latency_ms'] . "ms\n";
        echo "  Timer overhead: " . $this->results['timer_overhead_ms'] . "ms\n";
        echo "  Timer accuracy: " . $this->results['timer_accuracy_ms'] . "ms\n";
        echo "  Loop latency (avg): " . $this->results['loop_latency_avg_ms'] . "ms\n";
        echo "  Loop latency (p99): " . $this->results['loop_latency_p99_ms'] . "ms\n";

        echo "\nThroughput Metrics:\n";
        echo "  Coroutine spawns: " . number_format($this->results['spawn_throughput']) . "/s\n";
        echo "  Context switches: " . number_format($this->results['context_switch_throughput']) . "/s\n";
        echo "  Channel messages: " . number_format($this->results['channel_throughput']) . "/s\n";

        echo "\nMemory Metrics:\n";
        echo "  Per coroutine: " . $this->results['memory_per_coro_kb'] . " KB\n";
        echo "  Peak usage: " . round(memory_get_peak_usage(true)/1024/1024, 2) . " MB\n";

        echo "\nConcurrency:\n";
        echo "  Speedup: " . $this->results['concurrent_speedup'] . "x\n";

        $this->saveResults();
        echo "\n✓ Benchmark complete\n";
    }

    private function saveResults() {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'results' => $this->results,
        ];

        $filename = __DIR__ . '/benchmark_results_' . date('Ymd_His') . '.json';
        file_put_contents($filename, json_encode($report, JSON_PRETTY_PRINT));
        echo "\nResults saved to: $filename\n";
    }
}

$bench = new PerformanceBenchmark();
$bench->run();
