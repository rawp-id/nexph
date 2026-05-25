<?php
namespace Core\Runtime\Package;

class PackageRemover
{
    private string $projectRoot;
    private string $modulesDir;

    public function __construct(string $projectRoot)
    {
        $this->projectRoot = $projectRoot;
        $this->modulesDir = $projectRoot . '/nexph_modules';
    }

    public function remove(string $package): array
    {
        $targetDir = $this->modulesDir . '/' . $package;

        if (!is_dir($targetDir)) {
            return ['removed' => false, 'reason' => 'not installed'];
        }

        $this->deleteDir($targetDir);

        return ['removed' => true, 'package' => $package];
    }

    private function deleteDir(string $dir): void
    {
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($dir);
    }
}
