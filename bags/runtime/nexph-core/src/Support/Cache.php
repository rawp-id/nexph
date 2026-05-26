<?php
namespace Core\Support;

class Cache {
    private static ?bool $enabled = null;

    public static function get(string $key, mixed $default = null): mixed {
        if (self::$enabled === null) {
            self::$enabled = function_exists('apcu_fetch') && apcu_enabled();
        }
        if (!self::$enabled) return $default;
        $value = apcu_fetch($key, $success);
        return $success ? $value : $default;
    }

    public static function set(string $key, mixed $value, int $ttl = 3600): bool {
        if (self::$enabled === null) {
            self::$enabled = function_exists('apcu_fetch') && apcu_enabled();
        }
        return self::$enabled ? apcu_store($key, $value, $ttl) : false;
    }

    public static function delete(string $key): bool {
        if (self::$enabled === null) {
            self::$enabled = function_exists('apcu_fetch') && apcu_enabled();
        }
        return self::$enabled ? apcu_delete($key) : false;
    }

    public static function clear(): bool {
        if (self::$enabled === null) {
            self::$enabled = function_exists('apcu_fetch') && apcu_enabled();
        }
        return self::$enabled ? apcu_clear_cache() : false;
    }

    public static function enabled(): bool {
        if (self::$enabled === null) {
            self::$enabled = function_exists('apcu_fetch') && apcu_enabled();
        }
        return self::$enabled;
    }
}
