<?php
namespace Core\Runtime\Package;

class PackageLock
{
    private string $path;
    private array $data = [];

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->load();
    }

    private function load(): void
    {
        if (file_exists($this->path)) {
            $content = file_get_contents($this->path);
            $this->data = json_decode($content, true) ?? [];
        }
    }

    public function save(): void
    {
        $this->data['_generated'] = date('c');
        $json = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        file_put_contents($this->path, $json);
    }

    public function addPackage(array $package): void
    {
        $this->data['packages'][$package['name']] = [
            'version' => $package['version'],
            'source' => $package['source'] ?? 'registry',
            'checksum' => $package['checksum'] ?? null,
            'requires' => $package['requires'] ?? [],
            'installed_path' => $package['installed_path'] ?? null,
        ];
        $this->save();
    }

    public function removePackage(string $name): void
    {
        unset($this->data['packages'][$name]);
        $this->save();
    }

    public function getPackage(string $name): ?array
    {
        return $this->data['packages'][$name] ?? null;
    }

    public function all(): array
    {
        return $this->data['packages'] ?? [];
    }

    public function exists(): bool
    {
        return file_exists($this->path);
    }
}
