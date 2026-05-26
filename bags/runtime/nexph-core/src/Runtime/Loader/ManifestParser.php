<?php
namespace Core\Runtime\Loader;

class ManifestParser
{
    public function parse(string $path): array
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Manifest not found: {$path}");
        }

        $content = file_get_contents($path);
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \RuntimeException("Invalid JSON in manifest: {$path} - " . json_last_error_msg());
        }

        return $data;
    }

    public function parseFromDir(string $dir): ?array
    {
        $path = rtrim($dir, '/') . '/nexph.json';
        if (!file_exists($path)) {
            return null;
        }
        return $this->parse($path);
    }
}
