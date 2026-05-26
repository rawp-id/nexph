<?php
namespace Core\Runtime\CLI;

use Core\Runtime\Queue\QueueFactory;

/**
 * Queue stats command.
 */
class QueueStatsCommand extends Command {
    protected string $name = 'queue:stats';
    protected string $description = 'Display queue statistics';
    
    public function execute(array $args = []): int {
        $parsed = $this->parseArgs($args);
        $options = $parsed['options'];
        
        $driver = $options['driver'] ?? getenv('QUEUE_DRIVER') ?: 'file';
        $watch = isset($options['watch']) || isset($options['w']);
        $interval = (int)($options['interval'] ?? 2);
        
        try {
            $queue = QueueFactory::create($driver);
            
            if ($watch) {
                $this->watchStats($queue, $interval);
            } else {
                $this->displayStats($queue);
            }
            
            return 0;
            
        } catch (\Throwable $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }
    
    private function displayStats($queue): void {
        $status = $queue->status();
        $metrics = $status['metrics'];
        
        $this->output(str_repeat('=', 60));
        $this->output("QUEUE STATISTICS");
        $this->output(str_repeat('=', 60));
        $this->output("");
        $this->output("Status:        " . ($status['running'] ? 'Running' : 'Stopped'));
        $this->output("Workers:       {$status['workers']}");
        $this->output("Queue Depth:   {$status['depth']}");
        $this->output("");
        $this->output("Jobs:");
        $this->output("  Enqueued:    {$metrics['counters']['jobs_enqueued']}");
        $this->output("  Completed:   {$metrics['counters']['jobs_completed']}");
        $this->output("  Failed:      {$metrics['counters']['jobs_failed']}");
        $this->output("  Retried:     {$metrics['counters']['jobs_retried']}");
        $this->output("");
        $this->output("Performance:");
        $this->output("  Throughput:  " . number_format($metrics['computed']['throughput'], 2) . " jobs/s");
        $this->output("  Success:     " . number_format($metrics['computed']['success_rate'], 1) . "%");
        $this->output("  Uptime:      " . gmdate('H:i:s', (int)$metrics['uptime']));
        $this->output("");
        $this->output("Memory:");
        $this->output("  Current:     {$metrics['computed']['memory_usage_mb']} MB");
        $this->output("  Peak:        {$metrics['computed']['memory_peak_mb']} MB");
        $this->output("");
    }
    
    private function watchStats($queue, int $interval): void {
        while (true) {
            echo "\033[2J\033[H";
            $this->displayStats($queue);
            $this->output("Refreshing every {$interval}s... (Ctrl+C to stop)");
            sleep($interval);
        }
    }
}
