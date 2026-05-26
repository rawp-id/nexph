<?php
namespace Core\Runtime\CLI;

class PackagePublishCommand extends Command
{
    protected string $name = 'publish';
    protected string $description = 'Publish a package to the registry';

    public function execute(array $args = []): int
    {
        require_once __DIR__ . '/../Package/PackagePublisher.php';
        require_once __DIR__ . '/../Package/PackageRegistryClient.php';
        require_once __DIR__ . '/../Package/PackageVerifier.php';

        $parsed = $this->parseArgs($args);
        $dir = $parsed['arguments'][0] ?? '.';
        $json = isset($parsed['options']['json']);

        $root = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $packageDir = realpath($dir) ?: $root;

        $cacheDir = $root . '/bags/cache/registry';
        $registry = new \Core\Runtime\Package\PackageRegistryClient('https://registry.nexph.dev', $cacheDir);
        $publisher = new \Core\Runtime\Package\PackagePublisher($registry);

        $result = $publisher->publish($packageDir);

        if ($json) {
            $this->output(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return $result['published'] ? 0 : 1;
        }

        if ($result['published']) {
            $this->output("  ✓ Published {$result['name']} ({$result['version']})");
            return 0;
        }

        if (isset($result['dry_run']) && $result['dry_run']) {
            $this->output("  ⚠ Dry run: {$result['name']} ({$result['version']})");
            $this->output("    {$result['message']}");
            return 0;
        }

        $this->error("  ✗ Publish failed: " . ($result['error'] ?? 'unknown error'));
        return 1;
    }
}
