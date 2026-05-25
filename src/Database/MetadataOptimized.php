<?php
/**
 * Optimized Metadata with APCu caching
 */
namespace Core\Database;

use Core\Database\DB;

class MetadataOptimized {
    private static ?array $cache = null;
    private static array $loadCache = [];
    private static bool $useApcu = false;

    public static function init(): void {
        self::$useApcu = function_exists('apcu_enabled') && apcu_enabled();
    }

    public static function load(string $name): array {
        // Memory cache first
        if (isset(self::$loadCache[$name])) {
            return self::$loadCache[$name];
        }

        // APCu cache second
        if (self::$useApcu) {
            $cached = apcu_fetch("nexph_meta_{$name}", $success);
            if ($success) {
                return self::$loadCache[$name] = $cached;
            }
        }

        // Filesystem last
        $name = basename($name);
        $path = __DIR__ . "/../../metadata/{$name}.json";
        if (!file_exists($path)) {
            throw new \Exception("Metadata not found: {$name}");
        }

        $data = json_decode(file_get_contents($path), true);
        
        // Store in both caches
        self::$loadCache[$name] = $data;
        if (self::$useApcu) {
            apcu_store("nexph_meta_{$name}", $data, 3600);
        }

        return $data;
    }

    public static function all(): array {
        // Memory cache first
        if (self::$cache !== null) {
            return self::$cache;
        }

        // APCu cache second
        if (self::$useApcu) {
            $cached = apcu_fetch('nexph_meta_all', $success);
            if ($success) {
                return self::$cache = $cached;
            }
        }

        // Filesystem last
        $files = glob(__DIR__ . '/../../metadata/*.json');
        $data = array_map(fn($f) => json_decode(file_get_contents($f), true), $files);
        
        // Store in both caches
        self::$cache = $data;
        if (self::$useApcu) {
            apcu_store('nexph_meta_all', $data, 3600);
        }

        return $data;
    }

    public static function clearCache(): void {
        self::$cache = null;
        self::$loadCache = [];
        if (self::$useApcu) {
            apcu_clear_cache();
        }
    }
}

MetadataOptimized::init();
