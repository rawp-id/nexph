<?php
namespace Core\Runtime\CLI;

use Core\Runtime\Scheduler\Schedule;

/**
 * Schedule run command.
 */
class ScheduleRunCommand extends Command {
    protected string $name = 'schedule:run';
    protected string $description = 'Run scheduled tasks';
    
    public function execute(array $args = []): int {
        $parsed = $this->parseArgs($args);
        $options = $parsed['options'];
        
        $daemon = isset($options['daemon']) || isset($options['d']);
        
        try {
            $schedule = new Schedule();
            
            $this->loadScheduledTasks($schedule);
            
            if ($daemon) {
                $this->output("Starting scheduler daemon...");
                $this->runDaemon($schedule);
            } else {
                $this->output("Running scheduled tasks once...");
                $this->runOnce($schedule);
            }
            
            return 0;
            
        } catch (\Throwable $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }
    
    private function loadScheduledTasks(Schedule $schedule): void {
        $scheduleFile = __DIR__ . '/../../app/schedule.php';
        
        if (!file_exists($scheduleFile)) {
            $this->output("No schedule file found at {$scheduleFile}");
            return;
        }
        
        require $scheduleFile;
        
        $tasks = $schedule->getTasks();
        $this->output("Loaded " . count($tasks) . " scheduled tasks");
    }
    
    private function runOnce(Schedule $schedule): void {
        $now = time();
        $executed = 0;
        
        foreach ($schedule->getTasks() as $task) {
            if ($task->shouldRun($now)) {
                $this->output("Executing: {$task->name}");
                ($task->callback)();
                $task->markRan($now);
                $executed++;
            }
        }
        
        $this->output("Executed {$executed} tasks");
    }
    
    private function runDaemon(Schedule $schedule): void {
        $this->setupSignalHandlers();
        
        $schedule->run();
    }
    
    private function setupSignalHandlers(): void {
        if (!function_exists('pcntl_signal')) {
            return;
        }
        
        pcntl_async_signals(true);
        
        $handler = function(int $signal) {
            $this->output("\nReceived signal, stopping...");
            exit(0);
        };
        
        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
    }
}
