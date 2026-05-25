<?php
namespace Core\Support;

class Config {
    private static array $items = [];

    public static function load(string $path): void {
        if (!file_exists($path)) return;
        $cacheKey = 'nexph:config:app';
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            self::$items = $cached;
            return;
        }
        self::$items = require $path;
        Cache::set($cacheKey, self::$items, 3600);
    }

    public static function loadEnv(string $path): void {
        if (!file_exists($path)) return;
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) continue;
            if (strpos($line, '=') === false) continue;
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (preg_match('/^"(.*)"$/', $value, $matches) || preg_match("/^'(.*)'$/", $value, $matches)) {
                $value = $matches[1];
            }
            if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                putenv(sprintf('%s=%s', $name, $value));
                $_ENV[$name] = $value;
                $_SERVER[$name] = $value;
            }
        }
    }

    public static function get(string $key, mixed $default = null): mixed {
        return self::$items[$key] ?? $default;
    }
}
