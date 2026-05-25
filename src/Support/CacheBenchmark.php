<?php
namespace Core\Support;

class CacheBenchmark {
    public static function run(int $iterations = 1000): array {
        $results = [
            'apcu_enabled' => Cache::enabled(),
            'iterations' => $iterations,
            'metadata_without_cache' => 0,
            'metadata_with_cache' => 0,
            'config_without_cache' => 0,
            'config_with_cache' => 0,
            'improvement' => []
        ];

        $start = microtime(true);
        for ($i = 0; $i < $iterations; $i++) {
            $files = glob(__DIR__ . '/../../metadata/*.json');
            foreach ($files as $file) {
                json_decode(file_get_contents($file), true);
            }
        }
        $results['metadata_without_cache'] = (microtime(true) - $start) * 1000;

        if (Cache::enabled()) {
            Cache::clear();
            $start = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                \Core\Database\Metadata::all();
            }
            $results['metadata_with_cache'] = (microtime(true) - $start) * 1000;
            $results['improvement']['metadata'] = round($results['metadata_without_cache'] / $results['metadata_with_cache'], 2) . 'x';
        }

        $configPath = __DIR__ . '/../../config/app.php';
        if (file_exists($configPath)) {
            $start = microtime(true);
            for ($i = 0; $i < $iterations; $i++) {
                require $configPath;
            }
            $results['config_without_cache'] = (microtime(true) - $start) * 1000;

            if (Cache::enabled()) {
                Cache::clear();
                $start = microtime(true);
                for ($i = 0; $i < $iterations; $i++) {
                    $cached = Cache::get('test:config');
                    if ($cached === null) {
                        $cached = require $configPath;
                        Cache::set('test:config', $cached, 3600);
                    }
                }
                $results['config_with_cache'] = (microtime(true) - $start) * 1000;
                $results['improvement']['config'] = round($results['config_without_cache'] / $results['config_with_cache'], 2) . 'x';
            }
        }

        return $results;
    }

    public static function display(array $results): void {
        echo "=== Cache Benchmark Results ===\n";
        echo "APCu enabled: " . ($results['apcu_enabled'] ? 'yes' : 'no') . "\n";
        echo "Iterations: {$results['iterations']}\n\n";

        echo "Metadata loading:\n";
        echo "  Without cache: " . round($results['metadata_without_cache'], 2) . "ms\n";
        if ($results['apcu_enabled']) {
            echo "  With cache: " . round($results['metadata_with_cache'], 2) . "ms\n";
            echo "  Improvement: {$results['improvement']['metadata']}\n";
        }
        echo "\n";

        if ($results['config_without_cache'] > 0) {
            echo "Config loading:\n";
            echo "  Without cache: " . round($results['config_without_cache'], 2) . "ms\n";
            if ($results['apcu_enabled']) {
                echo "  With cache: " . round($results['config_with_cache'], 2) . "ms\n";
                echo "  Improvement: {$results['improvement']['config']}\n";
            }
        }
    }
}
