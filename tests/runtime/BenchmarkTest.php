<?php
require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Runtime;
use Core\Core\Runtime\Channel;
use Core\Core\Runtime\Timer;

class BenchmarkTest {
    private $results = [];

    public function run() {
        if (!Runtime::available()) {
            echo "✗ Runtime not available\n";
            exit(1);
        }

        echo "=== Nexph Runtime Benchmark ===\n\n";

        $this->benchmarkCoroutineSpawn();
        $this->benchmarkContextSwitch();
        $this->benchmarkChannelThroughput();
        $this->benchmarkTimerAccuracy();
        $this->benchmarkEventLoopLatency();
        $this->benchmarkMemoryPerCoroutine();

        $this->printReport();
    }

    private function benchmarkCoroutineSpawn() {
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

        $this->results['spawn_cost'] = $perOp;
        $this->results['spawn_throughput'] = round($iterations/$elapsed);
    }

    private function benchmarkContextSwitch() {
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

        $this->results['context_switch'] = $perSwitch;
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
            $fired = false;

            Timer::after($delay, function() use (&$fired) {
                $fired = true;
            });

            Runtime::run();
            $actual = microtime(true) - $start;
            $error = abs($actual - $delay);
            $errors[] = $error;
        }

        $avgError = array_sum($errors) / count($errors);

        echo "✓\n";
        echo "  Average error: " . round($avgError * 1000, 2) . "ms\n";
        echo "  Max error: " . round(max($errors) * 1000, 2) . "ms\n";

        $this->results['timer_accuracy'] = round($avgError * 1000, 2);
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
        $p50 = $this->percentile($latencies, 50);
        $p95 = $this->percentile($latencies, 95);
        $p99 = $this->percentile($latencies, 99);

        echo "✓\n";
        echo "  Average: " . round($avg, 3) . "ms\n";
        echo "  P50: " . round($p50, 3) . "ms\n";
        echo "  P95: " . round($p95, 3) . "ms\n";
        echo "  P99: " . round($p99, 3) . "ms\n";

        $this->results['loop_latency_avg'] = round($avg, 3);
        $this->results['loop_latency_p99'] = round($p99, 3);
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

        $this->results['memory_per_coro'] = round($perCoroutine/1024, 2);
    }

    private function percentile($arr, $p) {
        sort($arr);
        $index = ceil(count($arr) * $p / 100) - 1;
        return $arr[$index];
    }

    private function printReport() {
        echo "\n=== Benchmark Report ===\n\n";

        echo "Performance Metrics:\n";
        echo "  Coroutine spawn: " . $this->results['spawn_cost'] . "ms\n";
        echo "  Context switch: " . $this->results['context_switch'] . "ms\n";
        echo "  Timer accuracy: " . $this->results['timer_accuracy'] . "ms\n";
        echo "  Loop latency (avg): " . $this->results['loop_latency_avg'] . "ms\n";
        echo "  Loop latency (p99): " . $this->results['loop_latency_p99'] . "ms\n";

        echo "\nThroughput:\n";
        echo "  Coroutine spawns: " . $this->results['spawn_throughput'] . "/s\n";
        echo "  Channel messages: " . $this->results['channel_throughput'] . "/s\n";

        echo "\nMemory:\n";
        echo "  Per coroutine: " . $this->results['memory_per_coro'] . " KB\n";
        echo "  Peak usage: " . round(memory_get_peak_usage(true)/1024/1024, 2) . " MB\n";

        echo "\n✓ Benchmark complete\n";
    }
}

$bench = new BenchmarkTest();
$bench->run();
