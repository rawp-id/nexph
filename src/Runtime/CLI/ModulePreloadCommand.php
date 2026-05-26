<?php
namespace Core\Runtime\CLI;

class ModulePreloadCommand extends Command
{
    protected string $name = 'module:preload';
    protected string $description = 'Preload modules and show preload stats';

    public function execute(array $args = []): int
    {
        require_once __DIR__ . '/../Loader/ManifestParser.php';
        require_once __DIR__ . '/../Loader/ManifestValidator.php';
        require_once __DIR__ . '/../Loader/ModuleManifest.php';
        require_once __DIR__ . '/../Loader/ModuleRegistry.php';
        require_once __DIR__ . '/../Loader/RuntimePreloader.php';
        require_once __DIR__ . '/../Loader/LazyModuleResolver.php';
        require_once __DIR__ . '/../Loader/RuntimeLoader.php';
        require_once __DIR__ . '/../Loader/Exceptions/ModuleLoadException.php';
        require_once __DIR__ . '/../Loader/Exceptions/ManifestValidationException.php';
        require_once __DIR__ . '/../Loader/Exceptions/ModuleNotFoundException.php';
        require_once __DIR__ . '/../Loader/Exceptions/ModuleConflictException.php';

        $parsed = $this->parseArgs($args);
        $json = isset($parsed['options']['json']);

        $root = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $paths = $this->discoverPaths($root);

        $loader = new \Core\Runtime\Loader\RuntimeLoader();
        $loader->discover($paths);

        $start = microtime(true);
        $loader->boot();
        $duration = round((microtime(true) - $start) * 1000, 2);

        $stats = $loader->stats();

        if ($json) {
            $stats['boot_duration_ms'] = $duration;
            $this->output(json_encode($stats, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        $this->output("Preload complete ({$duration}ms)");
        $this->output("");
        $this->output("Registry:  {$stats['registry']['total']} module(s)");
        $this->output("Preloaded: {$stats['preloader']['files_loaded']} file(s)");
        $this->output("Lazy map:  {$stats['lazy']['map_size']} entries");
        $this->output("Booted:    {$stats['booted']} provider(s)");

        if (($stats['preloader']['errors'] ?? 0) > 0) {
            $this->output("Errors:    {$stats['preloader']['errors']}");
        }

        return 0;
    }

    private function discoverPaths(string $root): array
    {
        $paths = [];
        if (is_dir($root . '/bags/local')) $paths[] = $root . '/bags/local';
        if (is_dir($root . '/bags/installed')) $paths[] = $root . '/bags/installed';
        if (is_dir($root . '/modules')) $paths[] = $root . '/modules';
        if (is_dir($root . '/nexph_modules')) $paths[] = $root . '/nexph_modules';
        return $paths;
    }
}
