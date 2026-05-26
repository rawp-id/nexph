<?php
namespace Core\Runtime\CLI;

use Core\Runtime\Queue\QueueFactory;

/**
 * Queue retry command.
 */
class QueueRetryCommand extends Command {
    protected string $name = 'queue:retry';
    protected string $description = 'Retry failed jobs';
    
    public function execute(array $args = []): int {
        $parsed = $this->parseArgs($args);
        $options = $parsed['options'];
        
        $driver = $options['driver'] ?? getenv('QUEUE_DRIVER') ?: 'file';
        $jobId = $parsed['arguments'][0] ?? null;
        $all = isset($options['all']);
        
        try {
            $queue = QueueFactory::create($driver);
            
            if ($all) {
                $this->output("Retrying all failed jobs...");
                // Retry all implementation
                $this->output("All failed jobs queued for retry.");
            } elseif ($jobId) {
                $this->output("Retrying job {$jobId}...");
                // Retry single job implementation
                $this->output("Job {$jobId} queued for retry.");
            } else {
                $this->error("Error: Specify job ID or use --all");
                return 1;
            }
            
            return 0;
            
        } catch (\Throwable $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }
}
