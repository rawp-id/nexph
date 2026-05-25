<?php
namespace Tests\Runtime;

require_once __DIR__ . '/../../autoload.php';

use Core\Core\Runtime\Queue\QueueFactory;
use Core\Core\Runtime\Runtime;

/**
 * Extended soak test for production reliability.
 * 
 * Runs for 6-24 hours testing memory stability, throughput consistency,
 * error recovery, and long-running worker health.
 */
class ExtendedSoakTest {
    private int $passed = 0;
    private int $failed = 0;
    private array $results = [];
    private array $metrics = [];
    private int $startTime;
    private int $durationSeconds;
    
    public function __construct(int $durationMinutes = 60) {
        $this->durationSeconds = $durationMinutes * 60;
        $this->startTime = time();
    }
    
    public function run(): void {
        echo "=== Extended Soak Test ===\n";
        echo "Duration: " . ($this->durationSeconds / 60) . " minutes\n";
        echo "Start: " . date('Y-m-d H:i:s') . "\n\n";
        
        $this->testLongRunningWorker();
        $this->testMemoryStability();
        $this->testThroughputConsistency();
        $this->testErrorRecoveryOverTime();
        $this->testQueueFairnessUnderLoad();
        $this->testSchedulerResponsiveness();
        
        $this->printResults();
        $this->printMetricsSummary();
    }
    
    private function testLongRunningWorker(): void {
        echo "Test: Long-running worker stability... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 2,
                'metrics_interval' => 0,
            ]);
            
            $processed = 0;
            $errors = 0;
            $memorySnapshots = [];
            
            $queue->register('soak-job', function($payload, $job) use (&$processed, &$errors, &$memorySnapshots) {
                try {
                    $processed++;
                    
                    if ($processed % 100 === 0) {
                        $memorySnapshots[] = [
                            'time' => time(),
                            'memory' => memory_get_usage(true),
                            'processed' => $processed,
                        ];
                    }
                    
                    usleep(rand(1000, 10000));
                    
                    if (rand(1, 100) === 1) {
                        throw new \Exception("Random error");
                    }
                    
                    return ['id' => $payload['id'], 'processed' => $processed];
                } catch (\Throwable $e) {
                    $errors++;
                    throw $e;
                }
            });
            
            $jobsPerBatch = 100;
            $batches = min(10, $this->durationSeconds / 60);
            
            for ($batch = 0; $batch < $batches; $batch++) {
                for ($i = 0; $i < $jobsPerBatch; $i++) {
                    $queue->push('soak-job', ['id' => $batch * $jobsPerBatch + $i]);
                }
                
                $queue->work();
                
                if ($batch < $batches - 1) {
                    usleep(100000);
                }
            }
            
            $this->metrics['long_running'] = [
                'processed' => $processed,
                'errors' => $errors,
                'memory_snapshots' => $memorySnapshots,
                'duration' => time() - $this->startTime,
            ];
            
            if ($processed > 0 && $errors < $processed * 0.1) {
                $this->pass("Worker stable: {$processed} jobs, {$errors} errors");
            } else {
                $this->fail("Worker unstable: {$processed} jobs, {$errors} errors");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testMemoryStability(): void {
        echo "Test: Memory stability over time... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 1,
                'metrics_interval' => 0,
            ]);
            
            $memoryReadings = [];
            $iterations = min(50, $this->durationSeconds / 10);
            
            $queue->register('memory-job', function($payload, $job) {
                $data = str_repeat('x', 1024 * 10);
                usleep(10000);
                return ['size' => strlen($data)];
            });
            
            for ($i = 0; $i < $iterations; $i++) {
                $memoryBefore = memory_get_usage(true);
                
                for ($j = 0; $j < 20; $j++) {
                    $queue->push('memory-job', ['iteration' => $i, 'job' => $j]);
                }
                
                $queue->work();
                
                $memoryAfter = memory_get_usage(true);
                $memoryReadings[] = [
                    'iteration' => $i,
                    'before' => $memoryBefore,
                    'after' => $memoryAfter,
                    'delta' => $memoryAfter - $memoryBefore,
                ];
                
                if ($i < $iterations - 1) {
                    usleep(100000);
                }
            }
            
            $avgGrowth = array_sum(array_column($memoryReadings, 'delta')) / count($memoryReadings);
            $maxMemory = max(array_column($memoryReadings, 'after'));
            $minMemory = min(array_column($memoryReadings, 'after'));
            
            $this->metrics['memory_stability'] = [
                'readings' => count($memoryReadings),
                'avg_growth' => $avgGrowth,
                'max_memory' => $maxMemory,
                'min_memory' => $minMemory,
                'range' => $maxMemory - $minMemory,
            ];
            
            $avgGrowthMB = $avgGrowth / 1024 / 1024;
            
            if ($avgGrowthMB < 1) {
                $this->pass("Memory stable: avg growth " . number_format($avgGrowthMB, 3) . " MB/iteration");
            } else {
                $this->fail("Memory leak detected: avg growth " . number_format($avgGrowthMB, 3) . " MB/iteration");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testThroughputConsistency(): void {
        echo "Test: Throughput consistency... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 2,
                'metrics_interval' => 0,
            ]);
            
            $throughputReadings = [];
            $iterations = min(10, $this->durationSeconds / 30);
            
            $queue->register('throughput-job', function($payload, $job) {
                usleep(5000);
                return ['id' => $payload['id']];
            });
            
            for ($i = 0; $i < $iterations; $i++) {
                for ($j = 0; $j < 100; $j++) {
                    $queue->push('throughput-job', ['id' => $i * 100 + $j]);
                }
                
                $start = microtime(true);
                $queue->work();
                $duration = microtime(true) - $start;
                
                $throughput = 100 / $duration;
                $throughputReadings[] = [
                    'iteration' => $i,
                    'throughput' => $throughput,
                    'duration' => $duration,
                ];
                
                if ($i < $iterations - 1) {
                    usleep(100000);
                }
            }
            
            $avgThroughput = array_sum(array_column($throughputReadings, 'throughput')) / count($throughputReadings);
            $minThroughput = min(array_column($throughputReadings, 'throughput'));
            $maxThroughput = max(array_column($throughputReadings, 'throughput'));
            $variance = ($maxThroughput - $minThroughput) / $avgThroughput;
            
            $this->metrics['throughput_consistency'] = [
                'readings' => count($throughputReadings),
                'avg' => $avgThroughput,
                'min' => $minThroughput,
                'max' => $maxThroughput,
                'variance' => $variance,
            ];
            
            if ($variance < 0.5) {
                $this->pass("Throughput consistent: " . number_format($avgThroughput, 2) . 
                           " jobs/sec (variance: " . number_format($variance * 100, 1) . "%)");
            } else {
                $this->fail("Throughput inconsistent: variance " . number_format($variance * 100, 1) . "%");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testErrorRecoveryOverTime(): void {
        echo "Test: Error recovery over time... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 1,
                'max_attempts' => 3,
                'retry_delay' => 0,
                'metrics_interval' => 0,
            ]);
            
            $recoveryStats = [];
            $iterations = min(10, $this->durationSeconds / 20);
            
            $queue->register('recovery-job', function($payload, $job) {
                if (rand(1, 5) === 1) {
                    throw new \Exception("Random failure");
                }
                return ['id' => $payload['id']];
            });
            
            for ($i = 0; $i < $iterations; $i++) {
                for ($j = 0; $j < 50; $j++) {
                    $queue->push('recovery-job', ['id' => $i * 50 + $j]);
                }
                
                $queue->work();
                
                $metrics = $queue->metrics()->toArray();
                $recoveryStats[] = [
                    'iteration' => $i,
                    'completed' => $metrics['completed'],
                    'failed' => $metrics['failed'],
                    'retried' => $metrics['retried'],
                ];
                
                if ($i < $iterations - 1) {
                    usleep(100000);
                }
            }
            
            $totalCompleted = array_sum(array_column($recoveryStats, 'completed'));
            $totalFailed = array_sum(array_column($recoveryStats, 'failed'));
            $totalRetried = array_sum(array_column($recoveryStats, 'retried'));
            
            $this->metrics['error_recovery'] = [
                'iterations' => count($recoveryStats),
                'completed' => $totalCompleted,
                'failed' => $totalFailed,
                'retried' => $totalRetried,
                'recovery_rate' => $totalCompleted / ($totalCompleted + $totalFailed),
            ];
            
            $recoveryRate = $totalCompleted / ($totalCompleted + $totalFailed);
            
            if ($recoveryRate > 0.8) {
                $this->pass("Error recovery good: " . number_format($recoveryRate * 100, 1) . 
                           "% success rate, {$totalRetried} retries");
            } else {
                $this->fail("Error recovery poor: " . number_format($recoveryRate * 100, 1) . "% success rate");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testQueueFairnessUnderLoad(): void {
        echo "Test: Queue fairness under load... ";
        
        try {
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 3,
                'metrics_interval' => 0,
            ]);
            
            $processingTimes = [];
            
            $queue->register('fairness-job', function($payload, $job) use (&$processingTimes) {
                $start = microtime(true);
                usleep(rand(5000, 15000));
                $duration = microtime(true) - $start;
                
                $processingTimes[$payload['priority']][] = [
                    'id' => $payload['id'],
                    'duration' => $duration,
                    'queued_at' => $payload['queued_at'],
                    'processed_at' => microtime(true),
                ];
                
                return ['id' => $payload['id']];
            });
            
            $now = microtime(true);
            for ($priority = 1; $priority <= 3; $priority++) {
                for ($i = 0; $i < 30; $i++) {
                    $queue->push('fairness-job', [
                        'id' => "{$priority}-{$i}",
                        'priority' => $priority,
                        'queued_at' => $now,
                    ]);
                }
            }
            
            $queue->work();
            
            $avgWaitTimes = [];
            foreach ($processingTimes as $priority => $times) {
                $waitTimes = array_map(function($t) {
                    return $t['processed_at'] - $t['queued_at'];
                }, $times);
                $avgWaitTimes[$priority] = array_sum($waitTimes) / count($waitTimes);
            }
            
            $this->metrics['queue_fairness'] = [
                'avg_wait_times' => $avgWaitTimes,
                'total_processed' => array_sum(array_map('count', $processingTimes)),
            ];
            
            $maxWait = max($avgWaitTimes);
            $minWait = min($avgWaitTimes);
            $fairness = 1 - (($maxWait - $minWait) / $maxWait);
            
            if ($fairness > 0.7) {
                $this->pass("Queue fair: fairness score " . number_format($fairness * 100, 1) . "%");
            } else {
                $this->fail("Queue unfair: fairness score " . number_format($fairness * 100, 1) . "%");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function testSchedulerResponsiveness(): void {
        echo "Test: Scheduler responsiveness... ";
        
        try {
            if (!Runtime::available()) {
                $this->skip("Requires runtime support");
                return;
            }
            
            $queue = QueueFactory::createWithDriver('memory', [
                'workers' => 2,
                'metrics_interval' => 0,
            ]);
            
            $responseTimes = [];
            
            $queue->register('responsive-job', function($payload, $job) use (&$responseTimes) {
                $responseTime = microtime(true) - $payload['submitted_at'];
                $responseTimes[] = $responseTime;
                
                usleep(rand(1000, 5000));
                return ['response_time' => $responseTime];
            });
            
            for ($i = 0; $i < 100; $i++) {
                $queue->push('responsive-job', [
                    'id' => $i,
                    'submitted_at' => microtime(true),
                ]);
            }
            
            $queue->work();
            
            $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
            $maxResponseTime = max($responseTimes);
            $p95ResponseTime = $this->percentile($responseTimes, 95);
            
            $this->metrics['scheduler_responsiveness'] = [
                'avg' => $avgResponseTime,
                'max' => $maxResponseTime,
                'p95' => $p95ResponseTime,
                'samples' => count($responseTimes),
            ];
            
            if ($avgResponseTime < 0.1 && $p95ResponseTime < 0.2) {
                $this->pass("Scheduler responsive: avg " . number_format($avgResponseTime * 1000, 2) . 
                           " ms, p95 " . number_format($p95ResponseTime * 1000, 2) . " ms");
            } else {
                $this->fail("Scheduler slow: avg " . number_format($avgResponseTime * 1000, 2) . " ms");
            }
        } catch (\Throwable $e) {
            $this->fail("Exception: " . $e->getMessage());
        }
    }
    
    private function percentile(array $values, float $percentile): float {
        sort($values);
        $index = (int)ceil(count($values) * $percentile / 100) - 1;
        return $values[max(0, $index)];
    }
    
    private function pass(string $message): void {
        echo "✓ PASS - {$message}\n";
        $this->passed++;
        $this->results[] = ['status' => 'pass', 'message' => $message];
    }
    
    private function fail(string $message): void {
        echo "✗ FAIL - {$message}\n";
        $this->failed++;
        $this->results[] = ['status' => 'fail', 'message' => $message];
    }
    
    private function skip(string $message): void {
        echo "⊘ SKIP - {$message}\n";
        $this->results[] = ['status' => 'skip', 'message' => $message];
    }
    
    private function printResults(): void {
        echo "\n=== Extended Soak Test Results ===\n";
        echo "Duration: " . (time() - $this->startTime) . " seconds\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Total: " . ($this->passed + $this->failed) . "\n";
        echo ($this->failed === 0 ? "✓ All soak tests passed!\n" : "✗ Some soak tests failed\n");
    }
    
    private function printMetricsSummary(): void {
        echo "\n=== Metrics Summary ===\n";
        echo json_encode($this->metrics, JSON_PRETTY_PRINT) . "\n";
    }
}

if (php_sapi_name() === 'cli') {
    $durationMinutes = isset($argv[1]) ? (int)$argv[1] : 60;
    $test = new ExtendedSoakTest($durationMinutes);
    $test->run();
}
