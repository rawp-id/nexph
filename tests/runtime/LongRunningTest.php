<?php
require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Runtime;
use Core\Core\Runtime\Channel;
use Core\Core\Runtime\Timer;

class LongRunningTest {
    private $duration;
    private $startTime;
    private $startMemory;
    private $stats = [];

    public function __construct($durationMinutes = 5) {
        $this->duration = $durationMinutes * 60;
    }

    public function run() {
        if (!Runtime::available()) {
            echo "✗ Runtime not available\n";
            exit(1);
        }

        $this->startTime = time();
        $this->startMemory = memory_get_usage(true);

        echo "=== Long Running Stability Test ===\n";
        echo "Duration: " . ($this->duration/60) . " minutes\n";
        echo "Start: " . date('Y-m-d H:i:s') . "\n\n";

        $this->stats['iterations'] = 0;
        $this->stats['errors'] = 0;
        $this->stats['coroutines'] = 0;
        $this->stats['messages'] = 0;

        Timer::every(10.0, function() {
            $this->printProgress();
        });

        Timer::every(1.0, function() {
            $this->runWorkload();
        });

        Timer::after($this->duration, function() {
            Runtime::stop();
        });

        Runtime::run();

        $this->printFinalReport();
    }

    private function runWorkload() {
        $this->stats['iterations']++;

        try {
            for ($i = 0; $i < 10; $i++) {
                Runtime::spawn(function() {
                    Runtime::sleep(0.01);
                });
                $this->stats['coroutines']++;
            }

            $ch = new Channel(10);
            Runtime::spawn(function() use ($ch) {
                for ($i = 0; $i < 10; $i++) {
                    $ch->send($i);
                }
                $ch->close();
            });

            Runtime::spawn(function() use ($ch) {
                while ($ch->receive() !== null) {
                    $this->stats['messages']++;
                }
            });

        } catch (Exception $e) {
            $this->stats['errors']++;
        }
    }

    private function printProgress() {
        $elapsed = time() - $this->startTime;
        $remaining = $this->duration - $elapsed;
        $memCurrent = memory_get_usage(true);
        $memDelta = $memCurrent - $this->startMemory;

        echo "[" . date('H:i:s') . "] ";
        echo "Elapsed: {$elapsed}s | ";
        echo "Remaining: {$remaining}s | ";
        echo "Iterations: " . $this->stats['iterations'] . " | ";
        echo "Errors: " . $this->stats['errors'] . " | ";
        echo "Memory: " . round($memDelta/1024/1024, 2) . " MB\n";
    }

    private function printFinalReport() {
        $totalTime = time() - $this->startTime;
        $memFinal = memory_get_usage(true);
        $memDelta = $memFinal - $this->startMemory;
        $memPeak = memory_get_peak_usage(true);

        echo "\n=== Final Report ===\n";
        echo "Total time: {$totalTime}s (" . round($totalTime/60, 1) . " min)\n";
        echo "Iterations: " . $this->stats['iterations'] . "\n";
        echo "Coroutines spawned: " . $this->stats['coroutines'] . "\n";
        echo "Messages sent: " . $this->stats['messages'] . "\n";
        echo "Errors: " . $this->stats['errors'] . "\n";
        echo "Memory delta: " . round($memDelta/1024/1024, 2) . " MB\n";
        echo "Memory peak: " . round($memPeak/1024/1024, 2) . " MB\n";
        echo "Avg iterations/s: " . round($this->stats['iterations']/$totalTime, 2) . "\n";

        if ($this->stats['errors'] === 0 && $memDelta < 50*1024*1024) {
            echo "\n✓ Long running test passed\n";
            exit(0);
        } else {
            echo "\n✗ Long running test failed\n";
            exit(1);
        }
    }
}

$minutes = isset($argv[1]) ? (int)$argv[1] : 5;
$test = new LongRunningTest($minutes);
$test->run();
