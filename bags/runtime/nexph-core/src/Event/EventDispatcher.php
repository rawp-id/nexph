<?php
namespace Core\Event;

class EventDispatcher {
    private static array $listeners = [];
    private static array $wildcards = [];
    private static bool $async = false;
    private static array $queue = [];

    public static function listen(string $event, callable $listener, int $priority = 0): void {
        if (str_contains($event, '*')) {
            self::$wildcards[$event][] = ['callback' => $listener, 'priority' => $priority];
        } else {
            self::$listeners[$event][] = ['callback' => $listener, 'priority' => $priority];
        }
    }

    public static function dispatch(string $event, array $payload = []): array {
        $responses = [];
        $listeners = self::getListeners($event);

        usort($listeners, fn($a, $b) => $b['priority'] <=> $a['priority']);

        foreach ($listeners as $listener) {
            if (self::$async) {
                self::$queue[] = ['event' => $event, 'listener' => $listener, 'payload' => $payload];
            } else {
                $result = ($listener['callback'])($event, $payload);
                if ($result !== null) {
                    $responses[] = $result;
                }
                if ($result === false) break; // Stop propagation
            }
        }

        return $responses;
    }

    public static function dispatchAsync(string $event, array $payload = []): void {
        self::$queue[] = ['event' => $event, 'payload' => $payload];
    }

    public static function flush(): array {
        $responses = [];
        while ($item = array_shift(self::$queue)) {
            if (isset($item['listener'])) {
                $responses[] = ($item['listener']['callback'])($item['event'], $item['payload']);
            } else {
                $responses = array_merge($responses, self::dispatch($item['event'], $item['payload']));
            }
        }
        return $responses;
    }

    private static function getListeners(string $event): array {
        $listeners = self::$listeners[$event] ?? [];

        foreach (self::$wildcards as $pattern => $wildcardListeners) {
            $regex = str_replace(['*', '.'], ['[^.]+', '\.'], $pattern);
            if (preg_match("/^{$regex}$/", $event)) {
                $listeners = array_merge($listeners, $wildcardListeners);
            }
        }

        return $listeners;
    }

    public static function forget(string $event): void {
        unset(self::$listeners[$event]);
    }

    public static function forgetAll(): void {
        self::$listeners = [];
        self::$wildcards = [];
        self::$queue = [];
    }

    public static function hasListeners(string $event): bool {
        return !empty(self::getListeners($event));
    }

    public static function getQueueSize(): int {
        return count(self::$queue);
    }
}
