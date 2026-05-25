<?php
namespace Core\Runtime\Package;

class PackageInstaller
{
    private string $projectRoot;
    private string $modulesDir;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = $projectRoot;
        $this->modulesDir = $projectRoot . '/nexph_modules';
    }

    public function installNative(array $package): array
    {
        $targetDir = $this->modulesDir . '/' . $package['name'];

        if (!is_dir($this->modulesDir)) {
            mkdir($this->modulesDir, 0755, true);
        }

        // TODO: download from registry or copy from path source
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $package['installed_path'] = $targetDir;

        return [
            'installed' => $package['name'],
            'version' => $package['version'],
            'path' => $targetDir,
        ];
    }

    public function installComposer(string $package, ?string $version = null): array
    {
        $cmd = 'composer require ' . escapeshellarg($package);
        if ($version !== null) {
            $cmd .= ':' . escapeshellarg($version);
        }
        $cmd .= ' --working-dir=' . escapeshellarg($this->projectRoot);

        $output = [];
        $exitCode = 0;
        exec($cmd . ' 2>&1', $output, $exitCode);

        return [
            'installed' => $package,
            'version' => $version ?? 'latest',
            'source' => 'composer',
            'exit_code' => $exitCode,
            'output' => implode("\n", $output),
        ];
    }
}
