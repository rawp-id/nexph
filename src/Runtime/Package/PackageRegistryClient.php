<?php
namespace Core\Runtime\Package;

class PackageRegistryClient
{
    private string $baseUrl;
    private string $cacheDir;

    public function __construct(string $baseUrl = 'https://registry.nexph.dev', string $cacheDir = '')
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->cacheDir = $cacheDir;
    }

    public function lookup(string $package): ?array
    {
        $cached = $this->fromCache($package);
        if ($cached !== null) {
            return $cached;
        }

        $url = $this->baseUrl . '/packages/' . $package . '.json';
        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Nexph Package Manager/0.1.0',
                'ignore_errors' => true,
            ],
        ]);

        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (!is_array($data) || empty($data['name'])) {
            return null;
        }

        $this->toCache($package, $data);
        return $data;
    }

    public function getVersions(string $package): array
    {
        $data = $this->lookup($package);
        return $data['versions'] ?? [];
    }

    public function getDistUrl(string $package, string $version): ?string
    {
        $data = $this->lookup($package);
        if ($data === null) {
            return null;
        }

        $versions = $data['versions'] ?? [];
        return $versions[$version]['dist']['url'] ?? null;
    }

    public function search(string $query): array
    {
        $url = $this->baseUrl . '/search?q=' . urlencode($query);
        $context = stream_context_create([
            'http' => ['timeout' => 10, 'user_agent' => 'Nexph Package Manager/0.1.0'],
        ]);

        $content = @file_get_contents($url, false, $context);
        if ($content === false) {
            return [];
        }

        $data = json_decode($content, true);
        return $data['results'] ?? [];
    }

    private function fromCache(string $package): ?array
    {
        if (empty($this->cacheDir)) {
            return null;
        }

        $file = $this->cacheDir . '/' . str_replace('/', '-', $package) . '.json';
        if (!file_exists($file)) {
            return null;
        }

        // Cache TTL: 1 hour
        if (filemtime($file) < time() - 3600) {
            unlink($file);
            return null;
        }

        $data = json_decode(file_get_contents($file), true);
        return is_array($data) ? $data : null;
    }

    private function toCache(string $package, array $data): void
    {
        if (empty($this->cacheDir)) {
            return;
        }

        if (!is_dir($this->cacheDir)) {
            mkdir($this->cacheDir, 0755, true);
        }

        $file = $this->cacheDir . '/' . str_replace('/', '-', $package) . '.json';
        file_put_contents($file, json_encode($data, JSON_UNESCAPED_SLASHES));
    }
}
