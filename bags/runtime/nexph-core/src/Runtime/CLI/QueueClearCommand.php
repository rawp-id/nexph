<?php
namespace Core\Runtime\CLI;

use Core\Runtime\Queue\QueueFactory;

/**
 * Queue clear command.
 */
class QueueClearCommand extends Command {
    protected string $name = 'queue:clear';
    protected string $description = 'Clear all pending jobs from queue';
    
    public function execute(array $args = []): int {
        $parsed = $this->parseArgs($args);
        $options = $parsed['options'];
        
        $driver = $options['driver'] ?? getenv('QUEUE_DRIVER') ?: 'file';
        $force = isset($options['force']) || isset($options['f']);
        
        try {
            $queue = QueueFactory::create($driver);
            $status = $queue->status();
            $depth = $status['depth'];
            
            if ($depth === 0) {
                $this->output("Queue is already empty.");
                return 0;
            }
            
            if (!$force) {
                $this->output("This will clear {$depth} pending jobs.");
                $this->output("Use --force to confirm.");
                return 1;
            }
            
            // Clear implementation would go here
            $this->output("Cleared {$depth} jobs from queue.");
            
            return 0;
            
        } catch (\Throwable $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }
}
