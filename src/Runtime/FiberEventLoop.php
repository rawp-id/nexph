<?php
namespace Core\Runtime;

use Fiber;

/**
 * Lightweight cooperative event loop.
 * 
 * Manages coroutine scheduling, timers, and I/O events.
 * No promises, no complex abstractions - just explicit scheduling.
 */
class FiberEventLoop {
    private array $ready = [];
    private array $sleeping = [];
    private array $timers = [];
    private bool $running = false;
    private int $nextTimerId = 1;
    
    /**
     * Schedule a coroutine for execution.
     */
    public function schedule(FiberCoroutine $coroutine): void {
        $this->ready[] = $coroutine;
    }
    
    /**
     * Schedule fiber to wake after delay.
     */
    public function sleepFiber(Fiber $fiber, float $seconds): void {
        $this->sleeping[] = ['fiber' => $fiber, 'wake' => microtime(true) + $seconds];
    }
    
    /**
     * Schedule a timer callback.
     */
    public function timer(float $seconds, callable $callback, bool $repeat = false): int {
        $id = $this->nextTimerId++;
        $this->timers[$id] = [
            'callback' => $callback,
            'interval' => $seconds,
            'next' => microtime(true) + $seconds,
            'repeat' => $repeat,
        ];
        return $id;
    }
    
    /**
     * Cancel a timer.
     */
    public function cancelTimer(int $id): void {
        unset($this->timers[$id]);
    }
    
    /**
     * Run event loop until all work complete.
     */
    public function run(): void {
        if ($this->running) {
            throw new \RuntimeException('Event loop is already running');
        }
        
        $this->running = true;
        
        while ($this->running && $this->hasWork()) {
            $this->tick();
        }
        
        $this->running = false;
    }
    
    /**
     * Stop event loop.
     */
    public function stop(): void {
        $this->running = false;
    }
    
    /**
     * Check if event loop is running.
     */
    public function isRunning(): bool {
        return $this->running;
    }
    
    /**
     * Single event loop iteration.
     */
    private function tick(): void {
        $now = microtime(true);
        
        // Process timers
        foreach ($this->timers as $id => $timer) {
            if ($timer['next'] <= $now) {
                ($timer['callback'])();
                
                if ($timer['repeat']) {
                    $this->timers[$id]['next'] = $now + $timer['interval'];
                } else {
                    unset($this->timers[$id]);
                }
            }
        }
        
        // Wake sleeping fibers
        $stillSleeping = [];
        foreach ($this->sleeping as $item) {
            if ($item['wake'] <= $now) {
                if ($item['fiber']->isTerminated()) {
                    continue;
                }
                if ($item['fiber']->isSuspended()) {
                    // Create new coroutine wrapper for already-started fiber
                    $coroutine = new FiberCoroutine($item['fiber']);
                    $coroutine->markStarted(); // Mark as already started
                    $this->ready[] = $coroutine;
                }
            } else {
                $stillSleeping[] = $item;
            }
        }
        $this->sleeping = $stillSleeping;
        
        // Execute ready coroutines
        $ready = $this->ready;
        $this->ready = [];
        
        foreach ($ready as $coroutine) {
            if ($coroutine->isFinished()) {
                continue;
            }
            
            try {
                $coroutine->resume();

                if (!$coroutine->isFinished() && $coroutine->lastSuspend() === FiberCoroutine::SUSPEND_YIELD) {
                    $this->ready[] = $coroutine;
                }
            } catch (\Throwable $e) {
                // Log error but continue loop
                error_log("Coroutine error: " . $e->getMessage());
            }
        }
        
        if (empty($this->ready)) {
            $nextWake = $this->nextWakeTime();
            $sleepTime = $nextWake !== null ? max(0, $nextWake - microtime(true)) : 0;
            if ($sleepTime > 0) {
                usleep((int)min($sleepTime * 1_000_000, 10_000));
            }
        }
    }

    private function nextWakeTime(): ?float {
        $next = null;

        foreach ($this->sleeping as $item) {
            $next = $next === null ? $item['wake'] : min($next, $item['wake']);
        }

        foreach ($this->timers as $timer) {
            $next = $next === null ? $timer['next'] : min($next, $timer['next']);
        }

        return $next;
    }
    
    /**
     * Check if loop has pending work.
     */
    private function hasWork(): bool {
        return !empty($this->ready) 
            || !empty($this->sleeping) 
            || !empty($this->timers);
    }
}
