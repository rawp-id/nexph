<?php
namespace Core\Runtime\Loader;

class RuntimePreloader
{
    private array $loaded = [];
    private array $errors = [];
    private float $duration = 0.0;

    public function preload(ModuleManifest $manifest): void
    {
        $start = microtime(true);

        foreach ($manifest->preload as $file) {
            $fullPath = $manifest->path . '/' . ltrim($file, '/');

            // Block path traversal
            $realBase = realpath($manifest->path);
            $realFile = realpath($fullPath);

            if ($realFile === false) {
                $this->errors[] = "File not found: {$fullPath} (module: {$manifest->name})";
                continue;
            }

            if (!str_starts_with($realFile, $realBase)) {
                $this->errors[] = "Path traversal blocked: {$file} (module: {$manifest->name})";
                continue;
            }

            if (isset($this->loaded[$realFile])) {
                continue;
            }

            require_once $realFile;
            $this->loaded[$realFile] = $manifest->name;
        }

        $this->duration += microtime(true) - $start;
    }

    public function stats(): array
    {
        return [
            'count' => count($this->loaded),
            'errors' => count($this->errors),
            'duration_ms' => round($this->duration * 1000, 3),
        ];
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
