<?php
namespace Core\Runtime\CLI;

class PackageRestoreCommand extends Command
{
    protected string $name = 'restore';
    protected string $description = 'Restore packages from lockfile';

    public function execute(array $args = []): int
    {
        require_once __DIR__ . '/../Package/PackageManager.php';
        require_once __DIR__ . '/../Package/PackageManifest.php';
        require_once __DIR__ . '/../Package/PackageLock.php';
        require_once __DIR__ . '/../Package/PackageResolver.php';
        require_once __DIR__ . '/../Package/PackageInstaller.php';
        require_once __DIR__ . '/../Package/PackageRemover.php';

        $parsed = $this->parseArgs($args);
        $json = isset($parsed['options']['json']);

        $root = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $lockPath = $root . '/nexph.lock';

        if (!file_exists($lockPath)) {
            $this->error("No nexph.lock found. Nothing to restore.");
            return 1;
        }

        $manager = new \Core\Runtime\Package\PackageManager($root);
        $result = $manager->restore();

        if ($json) {
            $this->output(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        if (empty($result['restored'])) {
            $this->output('No packages to restore.');
            return 0;
        }

        foreach ($result['restored'] as $name) {
            $this->output("  ✓ Restored {$name}");
        }

        $this->output("\nRestored " . count($result['restored']) . " package(s).");
        return 0;
    }
}
