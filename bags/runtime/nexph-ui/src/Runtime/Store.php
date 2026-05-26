<?php

namespace Nexph\Runtime;

/**
 * Shared reactive store — Pinia-style, compile-time registered.
 * PHP side: define stores, serialize initial state.
 * JS side: StoreGenerator emits the runtime.
 */
class Store
{
    private static array $stores = [];

    public static function define(string $name, array $state, array $actions = []): void
    {
        self::$stores[$name] = [
            'state'   => $state,
            'actions' => $actions,
        ];
    }

    public static function get(string $name): ?array
    {
        return self::$stores[$name] ?? null;
    }

    public static function all(): array
    {
        return self::$stores;
    }

    public static function has(string $name): bool
    {
        return isset(self::$stores[$name]);
    }

    public static function clear(): void
    {
        self::$stores = [];
    }
}
