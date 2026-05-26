<?php
namespace Core\Database;

use Core\Database\DB;
use Core\Support\Cache;

class Metadata {
    private static ?array $cache = null;
    private static array $loadCache = [];

    public static function load(string $name): array {
        if (isset(self::$loadCache[$name])) {
            return self::$loadCache[$name];
        }
        $name = basename($name);
        $cacheKey = "nexph:meta:{$name}";
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return self::$loadCache[$name] = $cached;
        }
        $path = __DIR__ . "/../../metadata/{$name}.json";
        if (!file_exists($path)) {
            throw new \Exception("Metadata not found: {$name}");
        }
        $data = json_decode(file_get_contents($path), true);
        Cache::set($cacheKey, $data, 3600);
        return self::$loadCache[$name] = $data;
    }

    public static function all(): array {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $cached = Cache::get('nexph:meta:all');
        if ($cached !== null) {
            return self::$cache = $cached;
        }
        $files = glob(__DIR__ . '/../../metadata/*.json');
        $data = [];
        foreach ($files as $file) {
            $data[] = json_decode(file_get_contents($file), true);
        }
        Cache::set('nexph:meta:all', $data, 3600);
        return self::$cache = $data;
    }

    public static function clearCache(?string $name = null): void {
        self::$cache = null;
        self::$loadCache = [];
        if ($name) {
            Cache::delete("nexph:meta:{$name}");
        } else {
            Cache::delete('nexph:meta:all');
        }
    }
}
