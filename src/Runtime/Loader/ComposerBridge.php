<?php
namespace Core\Runtime\Loader;

class ComposerBridge
{
    private string $projectRoot;
    private bool $enabled = false;
    private bool $loaded = false;
    private array $packages = [];
    private array $psr4Map = [];

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = $projectRoot;
        $this->enabled = $this->detect();
    }

    private function detect(): bool
    {
        return file_exists($this->projectRoot . '/vendor/autoload.php');
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function load(): void
    {
        if ($this->loaded || !$this->enabled) {
            return;
        }

        require_once $this->projectRoot . '/vendor/autoload.php';
        $this->parseInstalledPackages();
        $this->loaded = true;
    }

    private function parseInstalledPackages(): void
    {
        $installed = $this->projectRoot . '/vendor/composer/installed.json';
        if (!file_exists($installed)) {
            return;
        }

        $data = json_decode(file_get_contents($installed), true);
        if (!is_array($data)) {
            return;
        }

        // Composer 2.x format
        $packages = $data['packages'] ?? $data;

        foreach ($packages as $pkg) {
            if (!is_array($pkg) || !isset($pkg['name'])) {
                continue;
            }

            $this->packages[$pkg['name']] = [
                'name' => $pkg['name'],
                'version' => $pkg['version'] ?? 'unknown',
                'source' => 'composer',
                'autoload' => $pkg['autoload'] ?? [],
            ];

            // Extract PSR-4 map
            if (isset($pkg['autoload']['psr-4'])) {
                foreach ($pkg['autoload']['psr-4'] as $prefix => $dir) {
                    $this->psr4Map[$prefix] = $pkg['name'];
                }
            }
        }
    }

    public function hasComposerJson(): bool
    {
        return file_exists($this->projectRoot . '/composer.json');
    }

    public function hasComposerBinary(): bool
    {
        $output = [];
        $exitCode = 0;
        exec('composer --version 2>/dev/null', $output, $exitCode);
        return $exitCode === 0;
    }

    public function install(string $package, ?string $version = null): array
    {
        if (!$this->hasComposerBinary()) {
            return ['success' => false, 'error' => 'composer binary not found'];
        }

        $cmd = 'composer require ' . escapeshellarg($package);
        if ($version !== null) {
            $cmd .= ':' . escapeshellarg($version);
        }
        $cmd .= ' --working-dir=' . escapeshellarg($this->projectRoot) . ' 2>&1';

        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        return [
            'success' => $exitCode === 0,
            'package' => $package,
            'version' => $version ?? 'latest',
            'output' => implode("\n", $output),
        ];
    }

    public function remove(string $package): array
    {
        if (!$this->hasComposerBinary()) {
            return ['success' => false, 'error' => 'composer binary not found'];
        }

        $cmd = 'composer remove ' . escapeshellarg($package)
            . ' --working-dir=' . escapeshellarg($this->projectRoot) . ' 2>&1';

        $output = [];
        $exitCode = 0;
        exec($cmd, $output, $exitCode);

        return [
            'success' => $exitCode === 0,
            'package' => $package,
            'output' => implode("\n", $output),
        ];
    }

    public function getPackages(): array
    {
        return $this->packages;
    }

    public function resolveClass(string $class): ?string
    {
        foreach ($this->psr4Map as $prefix => $package) {
            if (str_starts_with($class, $prefix)) {
                return $package;
            }
        }
        return null;
    }

    public function stats(): array
    {
        return [
            'enabled' => $this->enabled,
            'loaded' => $this->loaded,
            'packages' => count($this->packages),
            'psr4_prefixes' => count($this->psr4Map),
        ];
    }

    public function diagnostics(): array
    {
        return [
            'vendor_autoload' => file_exists($this->projectRoot . '/vendor/autoload.php'),
            'composer_json' => $this->hasComposerJson(),
            'composer_binary' => $this->hasComposerBinary(),
            'installed_json' => file_exists($this->projectRoot . '/vendor/composer/installed.json'),
        ];
    }
}
