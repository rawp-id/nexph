<?php
namespace Core\Runtime\CLI;

class PackageInfoCommand extends Command
{
    protected string $name = 'package:info';
    protected string $description = 'Show detailed info about a package';

    public function execute(array $args = []): int
    {
        require_once __DIR__ . '/../Package/PackageRegistryClient.php';
        require_once __DIR__ . '/../Package/PackageManager.php';
        require_once __DIR__ . '/../Package/PackageManifest.php';
        require_once __DIR__ . '/../Package/PackageLock.php';
        require_once __DIR__ . '/../Package/PackageResolver.php';
        require_once __DIR__ . '/../Package/PackageInstaller.php';
        require_once __DIR__ . '/../Package/PackageRemover.php';

        $parsed = $this->parseArgs($args);
        $name = $parsed['arguments'][0] ?? null;
        $json = isset($parsed['options']['json']);

        if ($name === null) {
            $this->error("Usage: nexph package:info <name>");
            return 1;
        }

        $root = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $cacheDir = $root . '/bags/cache/registry';

        // Check local lock first
        $manager = new \Core\Runtime\Package\PackageManager($root);
        $local = $manager->getLock()->getPackage($name);

        // Check registry
        $registry = new \Core\Runtime\Package\PackageRegistryClient('https://registry.nexph.dev', $cacheDir);
        $remote = $registry->lookup($name);

        if ($local === null && $remote === null) {
            $this->error("Package not found: {$name}");
            return 1;
        }

        $info = [
            'name' => $name,
            'installed_version' => $local['version'] ?? null,
            'source' => $local['source'] ?? null,
            'latest_version' => null,
            'description' => null,
            'versions' => [],
        ];

        if ($remote !== null) {
            $info['description'] = $remote['description'] ?? null;
            $info['latest_version'] = $remote['latest'] ?? null;
            $info['versions'] = array_keys($remote['versions'] ?? []);
        }

        if ($json) {
            $this->output(json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        $this->output("Name:      {$info['name']}");
        if ($info['installed_version']) {
            $this->output("Installed: {$info['installed_version']}");
        }
        if ($info['latest_version']) {
            $this->output("Latest:    {$info['latest_version']}");
        }
        if ($info['source']) {
            $this->output("Source:    {$info['source']}");
        }
        if ($info['description']) {
            $this->output("Desc:      {$info['description']}");
        }
        if (!empty($info['versions'])) {
            $this->output("Versions:  " . implode(', ', array_slice($info['versions'], 0, 10)));
            if (count($info['versions']) > 10) {
                $this->output("           ... and " . (count($info['versions']) - 10) . " more");
            }
        }

        return 0;
    }
}
