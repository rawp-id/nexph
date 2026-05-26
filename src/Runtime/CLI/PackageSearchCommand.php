<?php
namespace Core\Runtime\CLI;

class PackageSearchCommand extends Command
{
    protected string $name = 'search';
    protected string $description = 'Search packages in the registry';

    public function execute(array $args = []): int
    {
        require_once __DIR__ . '/../Package/PackageRegistryClient.php';

        $parsed = $this->parseArgs($args);
        $query = $parsed['arguments'][0] ?? null;
        $json = isset($parsed['options']['json']);

        if ($query === null) {
            $this->error("Usage: nexph search <query>");
            return 1;
        }

        $root = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $cacheDir = $root . '/bags/cache/registry';
        $registry = new \Core\Runtime\Package\PackageRegistryClient('https://registry.nexph.dev', $cacheDir);

        $results = $registry->search($query);

        if ($json) {
            $this->output(json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        if (empty($results)) {
            $this->output("No packages found for '{$query}'.");
            return 0;
        }

        $this->output(sprintf("%-30s %-10s %s", 'NAME', 'VERSION', 'DESCRIPTION'));
        foreach ($results as $pkg) {
            $this->output(sprintf(
                "%-30s %-10s %s",
                $pkg['name'] ?? '',
                $pkg['version'] ?? '',
                $pkg['description'] ?? ''
            ));
        }

        $this->output("\nFound " . count($results) . " package(s).");
        return 0;
    }
}
