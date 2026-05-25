<?php
namespace Core\Runtime\CLI;

use Core\Runtime\Queue\QueueFactory;
use Core\Runtime\Observability\RuntimeMetrics;
use Core\Runtime\Observability\HealthMonitor;
use Core\Runtime\Observability\Dashboard;
use Core\Runtime\Observability\Logger;

/**
 * Queue worker command.
 */
class QueueWorkCommand extends Command {
    protected string $name = 'queue:work';
    protected string $description = 'Start queue worker process';
    
    public function execute(array $args = []): int {
        $parsed = $this->parseArgs($args);
        $options = $parsed['options'];
        
        $driver = $options['driver'] ?? getenv('QUEUE_DRIVER') ?: 'file';
        $workers = (int)($options['workers'] ?? getenv('QUEUE_WORKERS') ?: 1);
        $maxIterations = (int)($options['max-iterations'] ?? 0);
        $sleep = (float)($options['sleep'] ?? 1.0);
        $dashboard = isset($options['dashboard']) || isset($options['d']);
        $verbose = isset($options['verbose']) || isset($options['v']);
        $quiet = isset($options['quiet']) || isset($options['q']);
        
        if (!$quiet) {
            $this->output("Starting queue worker...");
            $this->output("Driver: {$driver}");
            $this->output("Workers: {$workers}");
            if ($verbose) {
                $this->output("Mode: verbose");
            }
        }
        
        try {
            $queue = QueueFactory::create($driver, [
                'workers' => $workers,
                'poll_interval' => $sleep,
                'verbose' => $verbose,
                'quiet' => $quiet,
            ]);
            
            $metrics = new RuntimeMetrics();
            $health = new HealthMonitor();
            $dashboardObj = new Dashboard($metrics, $health);
            $logger = new Logger();
            
            $this->registerHandlers($queue, $quiet);
            
            if ($dashboard) {
                $this->runWithDashboard($queue, $dashboardObj, $maxIterations);
            } else {
                $this->runSimple($queue, $maxIterations);
            }
            
            return 0;
            
        } catch (\Throwable $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }
    
    private function registerHandlers($queue, bool $quiet = false): void {
        $handlersDir = __DIR__ . '/../../app/Jobs';
        
        if (!is_dir($handlersDir)) {
            return;
        }
        
        foreach (glob($handlersDir . '/*.php') as $file) {
            require_once $file;
            
            $className = basename($file, '.php');
            $fullClassName = "App\\Jobs\\{$className}";
            
            if (class_exists($fullClassName)) {
                $jobName = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
                $queue->register($jobName, $fullClassName);
                if (!$quiet) {
                    $this->output("Registered: {$jobName}");
                }
            }
        }
    }
    
    private function runSimple($queue, int $maxIterations): void {
        $this->output("Starting worker (blocking mode)...");
        
        // work() is now blocking - it will run until stopped
        $queue->work();
    }
    
    private function runWithDashboard($queue, Dashboard $dashboard, int $maxIterations): void {
        $this->output("Starting worker with dashboard (blocking mode)...");
        
        // Spawn dashboard updater in background
        if (class_exists('\\Fiber')) {
            \Runtime\Runtime::spawn(function() use ($dashboard) {
                while (true) {
                    echo "\033[2J\033[H";
                    echo $dashboard->render();
                    \Runtime\Runtime::sleep(5);
                }
            });
        }
        
        // work() is now blocking - it will run until stopped
        $queue->work();
    }
}
