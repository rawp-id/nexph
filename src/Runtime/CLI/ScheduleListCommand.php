<?php
namespace Core\Runtime\CLI;

use Core\Runtime\Scheduler\Schedule;

/**
 * Schedule list command.
 */
class ScheduleListCommand extends Command {
    protected string $name = 'schedule:list';
    protected string $description = 'List all scheduled tasks';
    
    public function execute(array $args = []): int {
        $parsed = $this->parseArgs($args);
        $options = $parsed['options'];
        
        $json = isset($options['json']);
        
        try {
            $schedule = new Schedule();
            
            $scheduleFile = __DIR__ . '/../../app/schedule.php';
            
            if (file_exists($scheduleFile)) {
                require $scheduleFile;
            }
            
            $tasks = $schedule->getTasks();
            
            if (empty($tasks)) {
                $this->output("No scheduled tasks found.");
                return 0;
            }
            
            if ($json) {
                echo json_encode(array_map(fn($t) => $t->toArray(), $tasks), JSON_PRETTY_PRINT) . "\n";
            } else {
                $this->displayTasks($tasks);
            }
            
            return 0;
            
        } catch (\Throwable $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }
    
    private function displayTasks(array $tasks): void {
        $this->output(str_repeat('=', 80));
        $this->output("SCHEDULED TASKS");
        $this->output(str_repeat('=', 80));
        $this->output("");
        
        foreach ($tasks as $task) {
            $status = $task->enabled ? '✓' : '✗';
            $name = $task->name ?: "Task #{$task->id}";
            
            $this->output("{$status} {$name}");
            
            if ($task->description) {
                $this->output("   Description: {$task->description}");
            }
            
            if ($task->cronExpression) {
                $this->output("   Schedule:    {$task->cronExpression}");
            } elseif ($task->interval) {
                $this->output("   Interval:    Every {$task->interval}s");
            }
            
            if ($task->lastRun) {
                $lastRun = date('Y-m-d H:i:s', $task->lastRun);
                $this->output("   Last Run:    {$lastRun}");
            }
            
            $this->output("   Run Count:   {$task->runCount}");
            
            if ($task->errorCount > 0) {
                $this->output("   Errors:      {$task->errorCount}");
                if ($task->lastError) {
                    $this->output("   Last Error:  {$task->lastError}");
                }
            }
            
            $this->output("");
        }
    }
}
