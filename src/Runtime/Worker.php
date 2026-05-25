<?php
namespace Core\Runtime;

/**
 * Long-running worker process manager.
 * 
 * Manages worker lifecycle, graceful shutdown, and signal handling.
 * Only available in CLI mode with pcntl support.
 */
class Worker {
    private static bool $shouldStop = false;
    private static array $handlers = [];
    
    /**
     * Start worker with callback.
     */
    public static function start(callable $callback, array $options = []): void {
        if (!Runtime::available()) {
            throw new \RuntimeException('Worker requires CLI mode and Fiber support');
        }
        
        $capabilities = Runtime::capabilities();
        
        // Setup signal handlers if pcntl available
        if ($capabilities['pcntl']) {
            self::setupSignals();
        }
        
        $maxIterations = $options['max_iterations'] ?? 0;
        $sleepBetween = $options['sleep'] ?? 1.0;
        $iteration = 0;
        
        echo "[Worker] Starting...\n";
        
        while (!self::$shouldStop) {
            try {
                $callback();
                $iteration++;
                
                if ($maxIterations > 0 && $iteration >= $maxIterations) {
                    echo "[Worker] Max iterations reached\n";
                    break;
                }
                
                if ($sleepBetween > 0) {
                    Runtime::sleep($sleepBetween);
                }
            } catch (\Throwable $e) {
                error_log("[Worker] Error: " . $e->getMessage());
                Runtime::sleep(5.0); // backoff on error
            }
        }
        
        echo "[Worker] Stopped\n";
    }
    
    /**
     * Stop worker gracefully.
     */
    public static function stop(): void {
        self::$shouldStop = true;
    }
    
    /**
     * Setup signal handlers for graceful shutdown.
     */
    private static function setupSignals(): void {
        if (!function_exists('pcntl_signal')) {
            return;
        }
        
        pcntl_async_signals(true);
        
        $handler = function(int $signal) {
            echo "\n[Worker] Received signal {$signal}, stopping gracefully...\n";
            self::stop();
        };
        
        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
    }
    
    /**
     * Fork worker process (requires pcntl).
     */
    public static function fork(callable $callback): int {
        $capabilities = Runtime::capabilities();
        
        if (!$capabilities['pcntl']) {
            throw new \RuntimeException('Worker fork requires pcntl extension');
        }
        
        $pid = pcntl_fork();
        
        if ($pid === -1) {
            throw new \RuntimeException('Failed to fork worker process');
        }
        
        if ($pid === 0) {
            // Child process
            $callback();
            exit(0);
        }
        
        // Parent process
        return $pid;
    }
}
