<?php
namespace Core\Runtime\CLI;

class PackageUpdateCommand extends Command
{
    protected string $name = 'update';
    protected string $description = 'Update packages to latest compatible versions';

    public function execute(array $args = []): int
    {
        require_once __DIR__ . '/../Package/PackageManager.php';
        require_once __DIR__ . '/../Package/PackageManifest.php';
        require_once __DIR__ . '/../Package/PackageLock.php';
        require_once __DIR__ . '/../Package/PackageResolver.php';
        require_once __DIR__ . '/../Package/PackageInstaller.php';
        require_once __DIR__ . '/../Package/PackageRemover.php';

        $parsed = $this->parseArgs($args);
        $packages = $parsed['arguments'];
        $json = isset($parsed['options']['json']);

        $root = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $manager = new \Core\Runtime\Package\PackageManager($root);

        $package = $packages[0] ?? null;
        $result = $manager->update($package);

        if ($json) {
            $this->output(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        if (empty($result['updated'])) {
            $this->output('All packages are up to date.');
            return 0;
        }

        foreach ($result['updated'] as $name => $version) {
            $this->output("  ✓ Updated {$name} → {$version}");
        }

        $this->output("\nUpdated " . count($result['updated']) . " package(s).");
        return 0;
    }
}
