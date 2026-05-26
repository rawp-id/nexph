<?php
namespace Core\Support;

class CacheWarmer {
    public static function warm(): array {
        $stats = [
            'apcu_enabled' => Cache::enabled(),
            'warmed' => [],
            'errors' => []
        ];

        if (!Cache::enabled()) {
            return $stats;
        }

        try {
            $metaPath = __DIR__ . '/../../metadata';
            if (is_dir($metaPath)) {
                $files = glob($metaPath . '/*.json');
                foreach ($files as $file) {
                    $name = basename($file, '.json');
                    try {
                        \Core\Database\Metadata::load($name);
                        $stats['warmed'][] = "metadata:{$name}";
                    } catch (\Exception $e) {
                        $stats['errors'][] = "metadata:{$name} - {$e->getMessage()}";
                    }
                }
            }

            try {
                \Core\Database\Metadata::all();
                $stats['warmed'][] = 'metadata:all';
            } catch (\Exception $e) {
                $stats['errors'][] = "metadata:all - {$e->getMessage()}";
            }

            $configPath = __DIR__ . '/../../config/app.php';
            if (file_exists($configPath)) {
                try {
                    Config::load($configPath);
                    $stats['warmed'][] = 'config:app';
                } catch (\Exception $e) {
                    $stats['errors'][] = "config:app - {$e->getMessage()}";
                }
            }

            $apiPolicyPath = __DIR__ . '/../../config/api.json';
            if (file_exists($apiPolicyPath)) {
                try {
                    \Core\Http\ApiPolicy::fromFile($apiPolicyPath);
                    $stats['warmed'][] = 'apipolicy';
                } catch (\Exception $e) {
                    $stats['errors'][] = "apipolicy - {$e->getMessage()}";
                }
            }
        } catch (\Exception $e) {
            $stats['errors'][] = "warmup - {$e->getMessage()}";
        }

        return $stats;
    }

    public static function clear(): bool {
        \Core\Database\Metadata::clearCache();
        Cache::delete('nexph:config:app');
        Cache::delete('nexph:apipolicy');
        Cache::delete('nexph:routes');
        return Cache::clear();
    }
}
