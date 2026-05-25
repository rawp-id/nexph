<?php
namespace Core\Runtime\Package;

class PackageRepository
{
    private array $sources = [];

    public function addSource(string $name, string $type, string $url): void
    {
        $this->sources[$name] = ['type' => $type, 'url' => $url];
    }

    public function getSources(): array
    {
        return $this->sources;
    }

    public function resolveFromPath(string $path): ?array
    {
        $manifestPath = rtrim($path, '/') . '/nexph.json';
        if (!file_exists($manifestPath)) {
            return null;
        }

        $data = json_decode(file_get_contents($manifestPath), true);
        if (!is_array($data) || empty($data['name'])) {
            return null;
        }

        return [
            'name' => $data['name'],
            'version' => $data['version'] ?? '0.0.0',
            'source' => 'path',
            'path' => realpath($path),
            'checksum' => null,
            'requires' => $data['requires'] ?? [],
        ];
    }

    public function resolveFromGit(string $url, ?string $ref = null): array
    {
        return [
            'source' => 'git',
            'url' => $url,
            'ref' => $ref ?? 'main',
        ];
    }
}
