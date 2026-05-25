<?php
namespace Core\Runtime\CLI;

class ModuleListCommand extends Command
{
    protected string $name = 'module:list';
    protected string $description = 'List all loaded modules';

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

        $loader = new \Core\Runtime\Loader\RuntimeLoader();
        $paths = $this->discoverPaths();
        $loader->discover($paths);

        $registry = $loader->getRegistry();
        $modules = $registry->all();

        if ($json) {
            $out = [];
            foreach ($modules as $m) {
                $out[] = ['name' => $m->name, 'version' => $m->version, 'type' => $m->type, 'path' => $m->path];
            }
            $this->output(json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        if (empty($modules)) {
            $this->output('No modules found.');
            return 0;
        }

        $this->output(sprintf("%-30s %-10s %-10s %s", 'NAME', 'VERSION', 'TYPE', 'PATH'));
        foreach ($modules as $m) {
            $this->output(sprintf("%-30s %-10s %-10s %s", $m->name, $m->version, $m->type, $m->path));
        }

        $this->output("\nTotal: " . count($modules) . " module(s)");
        return 0;
    }

    private function discoverPaths(): array
    {
        $root = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $paths = [];
        if (is_dir($root . '/nexph_modules')) $paths[] = $root . '/nexph_modules';
        if (is_dir($root . '/packages')) $paths[] = $root . '/packages';
        return $paths;
    }
}
