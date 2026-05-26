<?php
namespace Core\Runtime\Package;

class PackageManifest
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
        $json = json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        file_put_contents($this->path, $json);
    }

    public function getDependencies(): array
    {
        return $this->data['requires'] ?? [];
    }

    public function getDependency(string $name): ?string
    {
        return $this->data['requires'][$name] ?? null;
    }

    public function addDependency(string $name, string $version): void
    {
        $this->data['requires'][$name] = $version;
        $this->save();
    }

    public function removeDependency(string $name): void
    {
        unset($this->data['requires'][$name]);
        $this->save();
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function exists(): bool
    {
        return file_exists($this->path);
    }
}
