<?php
namespace Core\Runtime\CLI;

use Core\Runtime\Observability\RuntimeMetrics;
use Core\Runtime\Observability\RuntimeState;

class RuntimeMetricsCommand extends Command {
    protected string $name = 'runtime:metrics';
    protected string $description = 'Display runtime metrics';

    public function execute(array $args = []): int {
        $parsed = $this->parseArgs($args);
        $options = $parsed['options'];
        $json = isset($options['json']);
        $watch = isset($options['watch']) || isset($options['w']);
        $interval = (int)($options['interval'] ?? 2);
        $driver = $options['driver'] ?? null;
        try {
            $metrics = new RuntimeMetrics();
            if ($watch) {
                $this->watchMetrics($metrics, $interval, $json, $driver);
            } else {
                $this->displayMetrics($metrics, $json, $driver);
            }
            return 0;
        } catch (\Throwable $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }

    private function displayMetrics(RuntimeMetrics $metrics, bool $json, ?string $driver = null): void {
        $data = RuntimeState::snapshot($driver);
        if ($json) {
            echo json_encode($data, JSON_PRETTY_PRINT) . "\n";
            return;
        }
        $this->output(str_repeat('=', 60));
        $this->output('RUNTIME METRICS');
        $this->output(str_repeat('=', 60));
        $this->output('Mode: ' . $data['runtime']['mode'] . ' | Degradation: ' . $data['runtime']['degradation_state']);
        $this->output('Queue: depth=' . $data['queue']['depth'] . ' dlq=' . $data['queue']['dead_letters'] . ' workers=' . $data['queue']['workers']);
        $this->output('Uptime: ' . gmdate('H:i:s', (int)$data['uptime']));
        $this->output('Memory: ' . $data['computed']['memory_usage_mb'] . ' MB peak=' . $data['computed']['memory_peak_mb'] . ' MB');
        $this->output('CPU: ' . implode(', ', array_map(fn($v) => number_format((float)$v, 2), $data['system']['cpu_load'])));
        $this->output('Loop lag: ' . $data['gauges']['loop_lag_ms'] . ' ms | Fibers: ' . $data['gauges']['active_fibers'] . ' | Timers: ' . $data['gauges']['active_timers']);
        $this->output('Throughput: ' . number_format($data['computed']['throughput'], 2) . '/s | Failed: ' . $data['counters']['jobs_failed'] . ' | Retries: ' . $data['counters']['jobs_retried']);
    }

    private function watchMetrics(RuntimeMetrics $metrics, int $interval, bool $json, ?string $driver = null): void {
        while (true) {
            if (!$json) echo "\033[2J\033[H";
            $this->displayMetrics($metrics, $json, $driver);
            if (!$json) $this->output("Refreshing every {$interval}s... (Ctrl+C to stop)");
            sleep($interval);
        }
    }
}
