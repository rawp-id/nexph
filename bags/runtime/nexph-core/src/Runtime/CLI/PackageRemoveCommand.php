<?php
namespace Core\Runtime\CLI;

class PackageRemoveCommand extends Command
{
    protected string $name = 'remove';
    protected string $description = 'Remove a package';

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

        if (empty($packages)) {
            $this->error("Usage: nexph remove <package>");
            return 1;
        }

        $root = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $manager = new \Core\Runtime\Package\PackageManager($root);

        foreach ($packages as $package) {
            $result = $manager->remove($package);
            if ($result['removed']) {
                $this->output("  ✓ Removed {$package}");
            } else {
                $this->error("  ✗ {$package}: {$result['reason']}");
            }
        }

        return 0;
    }
}
