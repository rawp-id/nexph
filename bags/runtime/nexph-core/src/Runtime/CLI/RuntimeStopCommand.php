<?php
namespace Core\Runtime\CLI;

/**
 * Runtime stop command.
 */
class RuntimeStopCommand extends Command {
    protected string $name = 'runtime:stop';
    protected string $description = 'Stop runtime gracefully';
    
    public function execute(array $args = []): int {
        $parsed = $this->parseArgs($args);
        $options = $parsed['options'];
        
        $force = isset($options['force']) || isset($options['f']);
        
        try {
            $pidFile = __DIR__ . '/../../storage/runtime.pid';
            
            if (!file_exists($pidFile)) {
                $this->error("Error: Runtime not running (no PID file)");
                return 1;
            }
            
            $pid = (int)file_get_contents($pidFile);
            
            if (!posix_kill($pid, 0)) {
                $this->error("Error: Process {$pid} not found");
                unlink($pidFile);
                return 1;
            }
            
            $signal = $force ? SIGKILL : SIGTERM;
            $signalName = $force ? 'SIGKILL' : 'SIGTERM';
            
            $this->output("Sending {$signalName} to process {$pid}...");
            
            if (posix_kill($pid, $signal)) {
                $this->output("Stop signal sent successfully.");
                
                if (!$force) {
                    $this->output("Waiting for graceful shutdown...");
                    $timeout = 30;
                    $start = time();
                    
                    while (posix_kill($pid, 0) && (time() - $start) < $timeout) {
                        sleep(1);
                    }
                    
                    if (posix_kill($pid, 0)) {
                        $this->error("Warning: Process did not stop within {$timeout}s");
                        $this->error("Use --force to kill immediately");
                        return 1;
                    }
                }
                
                unlink($pidFile);
                $this->output("Runtime stopped.");
                return 0;
            } else {
                $this->error("Error: Failed to send signal");
                return 1;
            }
            
        } catch (\Throwable $e) {
            $this->error("Error: {$e->getMessage()}");
            return 1;
        }
    }
}
