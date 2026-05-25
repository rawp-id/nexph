<?php
namespace Core\Runtime\Package;

class PackageExtractor
{
    public function extract(string $archivePath, string $targetDir): bool
    {
        if (!file_exists($archivePath)) {
            return false;
        }

        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        // Try PharData for tar.gz
        if (str_ends_with($archivePath, '.tar.gz') || str_ends_with($archivePath, '.tgz')) {
            return $this->extractTarGz($archivePath, $targetDir);
        }

        // Try ZipArchive for .zip
        if (str_ends_with($archivePath, '.zip')) {
            return $this->extractZip($archivePath, $targetDir);
        }

        return false;
    }

    private function extractTarGz(string $path, string $targetDir): bool
    {
        try {
            $phar = new \PharData($path);
            $phar->extractTo($targetDir, null, true);
            return true;
        } catch (\Throwable $e) {
            // Fallback to shell
            $cmd = sprintf(
                'tar -xzf %s -C %s 2>&1',
                escapeshellarg($path),
                escapeshellarg($targetDir)
            );
            exec($cmd, $output, $exitCode);
            return $exitCode === 0;
        }
    }

    private function extractZip(string $path, string $targetDir): bool
    {
        if (!class_exists(\ZipArchive::class)) {
            $cmd = sprintf('unzip -o %s -d %s 2>&1', escapeshellarg($path), escapeshellarg($targetDir));
            exec($cmd, $output, $exitCode);
            return $exitCode === 0;
        }

        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return false;
        }

        $zip->extractTo($targetDir);
        $zip->close();
        return true;
    }
}
