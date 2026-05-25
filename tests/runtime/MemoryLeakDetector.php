<?php
require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Runtime;
use Core\Core\Runtime\Channel;
use Core\Core\Runtime\Timer;

class MemoryLeakDetector {
    private $samples = [];
    private $startTime;
    private $testDuration;

    public function __construct($durationMinutes = 60) {
        $this->testDuration = $durationMinutes * 60;
    }

    public function run() {
        if (!Runtime::available()) {
            echo "✗ Runtime not available\n";
            exit(1);
        }

        $this->startTime = time();
        echo "=== Memory Leak Detection Test ===\n";
        echo "Duration: " . ($this->testDuration/60) . " minutes\n";
        echo "Start: " . date('Y-m-d H:i:s') . "\n\n";

        Timer::every(10.0, function() {
            $this->sampleMemory();
        });

        Timer::every(1.0, function() {
            $this->runWorkload();
        });

        Timer::after($this->testDuration, function() {
            Runtime::stop();
        });

        Runtime::run();
        $this->analyzeResults();
    }

    private function runWorkload() {
        for ($i = 0; $i < 50; $i++) {
            Runtime::spawn(function() {
                Runtime::sleep(0.01);
            });
        }

        $ch = new Channel(10);
        Runtime::spawn(function() use ($ch) {
            for ($i = 0; $i < 10; $i++) {
                $ch->send($i);
            }
            $ch->close();
        });

        Runtime::spawn(function() use ($ch) {
            while ($ch->receive() !== null) {}
        });
    }

    private function sampleMemory() {
        gc_collect_cycles();
        $this->samples[] = [
            'time' => time() - $this->startTime,
            'memory' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
        ];

        $current = end($this->samples);
        echo "[" . date('H:i:s') . "] ";
        echo "Elapsed: " . $current['time'] . "s | ";
        echo "Memory: " . round($current['memory']/1024/1024, 2) . " MB | ";
        echo "Peak: " . round($current['peak']/1024/1024, 2) . " MB\n";
    }

    private function analyzeResults() {
        echo "\n=== Memory Leak Analysis ===\n";

        if (count($this->samples) < 3) {
            echo "✗ Insufficient samples\n";
            exit(1);
        }

        $first = $this->samples[0];
        $last = end($this->samples);
        $growth = $last['memory'] - $first['memory'];
        $duration = $last['time'];
        $growthRate = $duration > 0 ? $growth / $duration : 0;

        echo "Initial memory: " . round($first['memory']/1024/1024, 2) . " MB\n";
        echo "Final memory: " . round($last['memory']/1024/1024, 2) . " MB\n";
        echo "Total growth: " . round($growth/1024/1024, 2) . " MB\n";
        echo "Growth rate: " . round($growthRate/1024, 2) . " KB/s\n";
        echo "Peak memory: " . round($last['peak']/1024/1024, 2) . " MB\n";

        $trend = $this->calculateTrend();
        echo "Memory trend: " . round($trend/1024, 2) . " KB/s\n";

        $stable = $this->isMemoryStable();
        echo "Memory stable: " . ($stable ? 'YES' : 'NO') . "\n";

        if ($stable && $growthRate < 1024) {
            echo "\n✓ No memory leak detected\n";
            exit(0);
        } else {
            echo "\n✗ Potential memory leak detected\n";
            exit(1);
        }
    }

    private function calculateTrend() {
        $n = count($this->samples);
        if ($n < 2) return 0;

        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($this->samples as $i => $sample) {
            $x = $sample['time'];
            $y = $sample['memory'];
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        return $slope;
    }

    private function isMemoryStable() {
        if (count($this->samples) < 5) return false;

        $recent = array_slice($this->samples, -5);
        $memories = array_column($recent, 'memory');
        $avg = array_sum($memories) / count($memories);
        $variance = 0;

        foreach ($memories as $mem) {
            $variance += pow($mem - $avg, 2);
        }
        $variance /= count($memories);
        $stdDev = sqrt($variance);

        return $stdDev < (0.05 * $avg);
    }
}

$minutes = isset($argv[1]) ? (int)$argv[1] : 60;
$detector = new MemoryLeakDetector($minutes);
$detector->run();
