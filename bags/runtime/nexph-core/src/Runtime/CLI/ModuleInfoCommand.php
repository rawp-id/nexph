<?php
namespace Core\Runtime\CLI;

class ModuleInfoCommand extends Command
{
    protected string $name = 'module:info';
    protected string $description = 'Show detailed info about a module';

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
        $name = $parsed['arguments'][0] ?? null;

        if ($name === null) {
            $this->error("Usage: nexph module:info <name>");
            return 1;
        }

        $loader = new \Core\Runtime\Loader\RuntimeLoader();
        $paths = $this->discoverPaths();
        $loader->discover($paths);

        $registry = $loader->getRegistry();
        if (!$registry->has($name)) {
            $this->error("Module not found: {$name}");
            return 1;
        }

        $m = $registry->get($name);
        $source = $this->detectSource($m->path);

        $this->output("Name:        {$m->name}");
        $this->output("Version:     {$m->version}");
        $this->output("Type:        {$m->type}");
        $this->output("Source:      {$source}");
        $this->output("Path:        {$m->path}");
        $this->output("Description: " . ($m->description ?: '-'));

        if (!empty($m->providers)) {
            $this->output("Providers:   " . implode(', ', $m->providers));
        }
        if (!empty($m->routes)) {
            $this->output("Routes:      " . implode(', ', $m->routes));
        }
        if (!empty($m->commands)) {
            $this->output("Commands:    " . implode(', ', array_keys($m->commands)));
        }
        if (!empty($m->requires)) {
            $this->output("Requires:");
            foreach ($m->requires as $dep => $constraint) {
                $this->output("  {$dep}: {$constraint}");
            }
        }

        return 0;
    }

    private function discoverPaths(): array
    {
        $root = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
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
