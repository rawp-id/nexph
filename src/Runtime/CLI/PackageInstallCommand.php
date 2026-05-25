<?php
namespace Core\Runtime\CLI;

class PackageInstallCommand extends Command
{
    protected string $name = 'install';
    protected string $description = 'Install a package';

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
            $this->error("Usage: nexph install <package> [--version=x.x.x]");
            return 1;
        }

        $root = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);
        $manager = new \Core\Runtime\Package\PackageManager($root);
        $version = $parsed['options']['version'] ?? null;

        foreach ($packages as $package) {
            $this->output("Installing {$package}...");
            $result = $manager->install($package, $version);

            if (isset($result['exit_code']) && $result['exit_code'] !== 0) {
                $this->error("Failed to install {$package}");
                if (isset($result['output'])) {
                    $this->error($result['output']);
                }
                return 1;
            }

            $v = $result['version'] ?? 'latest';
            $this->output("  ✓ Installed {$result['installed']} ({$v})");
        }

        return 0;
    }
}
