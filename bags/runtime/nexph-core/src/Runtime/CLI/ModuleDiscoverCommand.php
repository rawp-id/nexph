<?php
namespace Core\Runtime\CLI;

class ModuleDiscoverCommand extends Command
{
    protected string $name = 'module:discover';
    protected string $description = 'Discover and list all available modules from all paths';

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

        $registry = $loader->getRegistry();
        $modules = $registry->all();

        if ($json) {
            $out = [];
            foreach ($modules as $m) {
                $out[] = [
                    'name' => $m->name,
                    'version' => $m->version,
                    'type' => $m->type,
                    'path' => $m->path,
                    'source' => $this->detectSource($m->path),
                ];
            }
            $this->output(json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        $this->output("Scanning paths:");
        foreach ($paths as $p) {
            $this->output("  • {$p}");
        }
        $this->output("");

        if (empty($modules)) {
            $this->output("No modules discovered.");
            return 0;
        }

        $this->output(sprintf("%-30s %-10s %-10s %s", 'NAME', 'VERSION', 'SOURCE', 'PATH'));
        foreach ($modules as $m) {
            $source = $this->detectSource($m->path);
            $this->output(sprintf("%-30s %-10s %-10s %s", $m->name, $m->version, $source, $m->path));
        }

        $this->output("\nDiscovered " . count($modules) . " module(s).");
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

    private function detectSource(string $path): string
    {
        if (str_contains($path, '/bags/local/')) return 'local';
        if (str_contains($path, '/bags/installed/')) return 'installed';
        if (str_contains($path, '/modules/')) return 'app';
        if (str_contains($path, '/nexph_modules/')) return 'installed';
        return 'unknown';
    }
}
