<?php
namespace Core\Runtime\CLI;

/**
 * Runtime reload command.
 */
class RuntimeReloadCommand extends Command {
    protected string $name = 'runtime:reload';
    protected string $description = 'Reload runtime configuration';
    
    public function execute(array $args = []): int {
        $parsed = $this->parseArgs($args);
        
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
            
            $this->output("Sending reload signal to process {$pid}...");
            
            if (posix_kill($pid, SIGHUP)) {
                $this->output("Reload signal sent successfully.");
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
