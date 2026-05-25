<?php
namespace Core\Runtime;

use Fiber;

/**
 * Adaptive stateful runtime layer for Nexph.
 * 
 * Enables async/stateful features only when environment supports them.
 * Falls back to synchronous execution on shared hosting/FPM.
 * 
 * Philosophy: "No magic in development, magic in runtime adaptability"
 */
class Runtime {
    private static ?FiberEventLoop $loop = null;
    private static bool $initialized = false;
    private static array $capabilities = [];
    
    /**
     * Initialize runtime and detect capabilities.
     */
    public static function init(): void {
        if (self::$initialized) {
            return;
        }
        
        self::$capabilities = [
            'fibers' => class_exists('Fiber'),
            'pcntl' => extension_loaded('pcntl'),
            'sockets' => extension_loaded('sockets'),
            'posix' => extension_loaded('posix'),
            'redis' => extension_loaded('redis'),
            'cli' => PHP_SAPI === 'cli',
        ];
        
        self::$initialized = true;
    }
    
    /**
     * Check if runtime is available (CLI + Fibers).
     */
    public static function available(): bool {
        self::init();
        return self::$capabilities['fibers'] && self::$capabilities['cli'];
    }
    
    /**
     * Get runtime capabilities.
     */
    public static function capabilities(): array {
        self::init();
        return self::$capabilities;
    }
    
    /**
     * Get or create event loop.
     */
    public static function loop(): FiberEventLoop {
        if (!self::available()) {
            throw new \RuntimeException('Runtime not available. Requires CLI mode and Fiber support.');
        }
        
        if (self::$loop === null) {
            self::$loop = new FiberEventLoop();
        }
        
        return self::$loop;
    }
    
    /**
     * Check if event loop is running.
     */
    public static function isRunning(): bool {
        return self::$loop !== null && self::$loop->isRunning();
    }
    
    /**
     * Spawn a coroutine (Fiber-based).
     * Falls back to immediate execution if runtime unavailable.
     */
    public static function spawn(callable $fn, mixed ...$args): FiberCoroutine {
        if (!self::available()) {
            // Fallback: execute synchronously
            $fn(...$args);
            return new FiberCoroutine(null, true);
        }
        
        $fiber = new Fiber(function() use ($fn, $args) {
            return $fn(...$args);
        });
        
        $coroutine = new FiberCoroutine($fiber);
        self::loop()->schedule($coroutine);
        
        return $coroutine;
    }
    
    /**
     * Cooperative sleep (yields to event loop).
     * Falls back to blocking sleep if runtime unavailable.
     */
    public static function sleep(float $seconds): void {
        if (!self::available()) {
            // Fallback: blocking sleep
            usleep((int)($seconds * 1_000_000));
            return;
        }
        
        $fiber = Fiber::getCurrent();
        if ($fiber === null) {
            // Not in fiber context, use blocking sleep
            usleep((int)($seconds * 1_000_000));
            return;
        }
        
        self::loop()->sleepFiber($fiber, $seconds);
        Fiber::suspend(FiberCoroutine::SUSPEND_SLEEP);
    }
    
    /**
     * Yield control to event loop.
     */
    public static function yield(): void {
        if (!self::available()) {
            return;
        }
        
        $fiber = Fiber::getCurrent();
        if ($fiber !== null) {
            Fiber::suspend(FiberCoroutine::SUSPEND_YIELD);
        }
    }
    
    /**
     * Run event loop until all coroutines complete.
     */
    public static function run(): void {
        if (!self::available()) {
            return;
        }
        
        self::loop()->run();
    }
    
    /**
     * Stop event loop.
     */
    public static function stop(): void {
        if (self::$loop !== null) {
            self::$loop->stop();
        }
    }
}
