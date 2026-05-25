<?php
namespace Core\Queue;

abstract class JobHandler {
    public const NAME = 'default';

    abstract public function handle(array $payload, ?callable $progress = null): void;

    protected function progress(?callable $callback, int $percent): void {
        if ($callback) {
            $callback(min(100, max(0, $percent)));
        }
    }
}

class DefaultJobHandler extends JobHandler {
    public const NAME = 'default';

    public function handle(array $payload, ?callable $progress = null): void {
        $steps = $payload['steps'] ?? 10;
        for ($i = 1; $i <= $steps; $i++) {
            usleep(100000);
            $this->progress($progress, ($i / $steps) * 100);
        }
    }
}
