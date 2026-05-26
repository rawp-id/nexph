<?php
namespace Core\Runtime\Loader;

class LazyModuleResolver
{
    private array $classMap = [];
    private array $fileMap = [];
    private int $hits = 0;
    private int $misses = 0;

    public function buildMap(array $modules): void
    {
        foreach ($modules as $manifest) {
            /** @var ModuleManifest $manifest */
            foreach ($manifest->lazy as $key => $target) {
                if (str_contains($target, '\\')) {
                    $this->classMap[$target] = $manifest->name;
                } else {
                    $this->fileMap[$target] = $manifest->name;
                }
            }

            // Build from autoload PSR-4
            if (isset($manifest->autoload['psr-4'])) {
                foreach ($manifest->autoload['psr-4'] as $prefix => $dir) {
                    $this->classMap[$prefix . '*'] = $manifest->name;
                }
            }
        }
    }

    public function resolveClass(string $class): ?string
    {
        if (isset($this->classMap[$class])) {
            $this->hits++;
            return $this->classMap[$class];
        }

        // Check prefix matches
        foreach ($this->classMap as $pattern => $module) {
            if (str_ends_with($pattern, '*')) {
                $prefix = rtrim($pattern, '*');
                if (str_starts_with($class, $prefix)) {
                    $this->hits++;
                    return $module;
                }
            }
        }

        $this->misses++;
        return null;
    }

    public function resolveFile(string $file): ?string
    {
        if (isset($this->fileMap[$file])) {
            $this->hits++;
            return $this->fileMap[$file];
        }

        $this->misses++;
        return null;
    }

    public function stats(): array
    {
        return [
            'class_entries' => count($this->classMap),
            'file_entries' => count($this->fileMap),
            'hits' => $this->hits,
            'misses' => $this->misses,
        ];
    }
}
