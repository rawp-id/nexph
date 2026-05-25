<?php
namespace Core\Server;

class EventLoop {
    private array $readers = [];
    private array $writers = [];
    private array $timers = [];
    private array $deferred = [];
    private array $signals = [];
    private bool $running = false;
    private int $tickInterval = 1000; // microseconds
    private int $maxDeferred = 100000;
    private int $deferredDropped = 0;
    private float $now;
    private int $tickCount = 0;
    private float $lastTickDurationMs = 0.0;

    public function __construct() {
        $this->now = microtime(true);
    }

    public function addReader($stream, callable $callback): void {
        $id = (int) $stream;
        $this->readers[$id] = ['stream' => $stream, 'callback' => $callback];
    }

    public function removeReader($stream): void {
        unset($this->readers[(int) $stream]);
    }

    public function addWriter($stream, callable $callback): void {
        $id = (int) $stream;
        $this->writers[$id] = ['stream' => $stream, 'callback' => $callback];
    }

    public function removeWriter($stream): void {
        unset($this->writers[(int) $stream]);
    }

    public function addTimer(float $interval, callable $callback, bool $periodic = false): int {
        static $id = 0;
        $this->timers[++$id] = [
            'interval' => $interval,
            'callback' => $callback,
            'periodic' => $periodic,
            'next' => microtime(true) + $interval,
        ];
        return $id;
    }

    public function cancelTimer(int $id): void {
        unset($this->timers[$id]);
    }

    public function setMaxDeferred(int $maxDeferred): void {
        $this->maxDeferred = max(1, $maxDeferred);
    }

    public function defer(callable $callback): bool {
        if (count($this->deferred) >= $this->maxDeferred) {
            $this->deferredDropped++;
            return false;
        }

        $this->deferred[] = $callback;
        return true;
    }

    public function addSignal(int $signal, callable $callback): void {
        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal($signal, function ($sig) use ($callback) {
                $this->defer(fn() => $callback($sig));
            });
            $this->signals[$signal] = $callback;
        }
    }

    public function run(): void {
        $this->running = true;

        while ($this->running) {
            $this->tick();
        }
    }

    public function stop(): void {
        $this->running = false;
    }

    public function tick(): void {
        $startedAt = microtime(true);
        $this->tickCount++;
        $this->now = microtime(true);

        $deferredCount = count($this->deferred);
        for ($i = 0; $i < $deferredCount; $i++) {
            ($this->deferred[$i])();
        }
        $this->deferred = array_slice($this->deferred, $deferredCount);

        // Process timers
        foreach ($this->timers as $id => $timer) {
            if ($this->now >= $timer['next']) {
                ($timer['callback'])();
                if ($timer['periodic']) {
                    $this->timers[$id]['next'] = $this->now + $timer['interval'];
                } else {
                    unset($this->timers[$id]);
                }
            }
        }

        // Prepare streams
        $read = array_column($this->readers, 'stream');
        $write = array_column($this->writers, 'stream');
        $except = null;

        if (empty($read) && empty($write)) {
            if (empty($this->timers) && empty($this->deferred)) {
                $this->running = false;
                return;
            }
            usleep($this->tickInterval);
            return;
        }

        // Calculate timeout
        $timeout = $this->calculateTimeout();
        $tvSec = (int) $timeout;
        $tvUsec = (int) (($timeout - $tvSec) * 1000000);

        $result = @stream_select($read, $write, $except, $tvSec, $tvUsec);

        if ($result === false) {
            $this->cleanupInvalidStreams();
            return;
        }

        // Handle readable
        foreach ($read as $stream) {
            $id = (int) $stream;
            if (isset($this->readers[$id])) {
                ($this->readers[$id]['callback'])($stream);
            }
        }

        // Handle writable
        foreach ($write as $stream) {
            $id = (int) $stream;
            if (isset($this->writers[$id])) {
                ($this->writers[$id]['callback'])($stream);
            }
        }

        $this->lastTickDurationMs = (microtime(true) - $startedAt) * 1000;
    }

    private function calculateTimeout(): float {
        if (!empty($this->deferred)) {
            return 0;
        }

        $minTimeout = 1.0;
        foreach ($this->timers as $timer) {
            $remaining = $timer['next'] - $this->now;
            if ($remaining < $minTimeout) {
                $minTimeout = max(0, $remaining);
            }
        }

        return $minTimeout;
    }

    public function isRunning(): bool {
        return $this->running;
    }

    public function now(): float {
        return $this->now;
    }

    private function cleanupInvalidStreams(): void {
        foreach ($this->readers as $id => $entry) {
            if (!is_resource($entry['stream']) || feof($entry['stream'])) {
                unset($this->readers[$id]);
            }
        }
        foreach ($this->writers as $id => $entry) {
            if (!is_resource($entry['stream'])) {
                unset($this->writers[$id]);
            }
        }
    }

    public function getReaderCount(): int {
        return count($this->readers);
    }

    public function getWriterCount(): int {
        return count($this->writers);
    }

    public function getTimerCount(): int {
        return count($this->timers);
    }

    public function getDeferredCount(): int {
        return count($this->deferred);
    }

    public function getDeferredDroppedCount(): int {
        return $this->deferredDropped;
    }

    public function getMaxDeferred(): int {
        return $this->maxDeferred;
    }

    public function getTickCount(): int {
        return $this->tickCount;
    }

    public function getLastTickDurationMs(): float {
        return $this->lastTickDurationMs;
    }
}
