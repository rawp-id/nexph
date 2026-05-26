<?php
namespace Core\Runtime\Package;

class PackageDownloader
{
    private string $cacheDir;

    public function __construct(string $cacheDir)
    {
        $this->cacheDir = $cacheDir;
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
    }

    public function download(string $url, string $package, string $version): string
    {
        $filename = str_replace('/', '-', $package) . '-' . $version . '.tar.gz';
        $target = $this->cacheDir . '/' . $filename;

        if (file_exists($target)) {
            return $target;
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 30,
                'user_agent' => 'Nexph Package Manager/0.1.0',
            ],
        ]);

        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            throw new PackageDownloadException($package, "Failed to download from: {$url}");
        }

        file_put_contents($target, $content);
        return $target;
    }

    public function getCachePath(string $package, string $version): ?string
    {
        $filename = str_replace('/', '-', $package) . '-' . $version . '.tar.gz';
        $path = $this->cacheDir . '/' . $filename;
        return file_exists($path) ? $path : null;
    }

    public function clearCache(): int
    {
        $count = 0;
        $files = glob($this->cacheDir . '/*.tar.gz');
        foreach ($files as $file) {
            unlink($file);
            $count++;
        }
        return $count;
    }
}
