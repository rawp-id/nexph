<?php
namespace Core\Runtime\Loader;

class SecurityValidator
{
    public function validatePreloadPaths(ModuleManifest $manifest): array
    {
        $errors = [];
        $base = realpath($manifest->path);
        if ($base === false) {
            return [['file' => $manifest->path, 'error' => 'package path not found']];
        }

        foreach ($manifest->preload as $file) {
            $full = realpath($manifest->path . '/' . ltrim($file, '/'));
            if ($full === false) {
                $errors[] = ['file' => $file, 'error' => 'not found'];
                continue;
            }
            if (!str_starts_with($full, $base)) {
                $errors[] = ['file' => $file, 'error' => 'path traversal'];
            }
        }
        return $errors;
    }

    public function validateProviderNamespace(ModuleManifest $manifest): array
    {
        $errors = [];
        $allowedPrefixes = [];
        if (isset($manifest->autoload['psr-4'])) {
            $allowedPrefixes = array_keys($manifest->autoload['psr-4']);
        }

        foreach ($manifest->providers as $class) {
            $valid = empty($allowedPrefixes);
            foreach ($allowedPrefixes as $prefix) {
                if (str_starts_with($class, $prefix)) {
                    $valid = true;
                    break;
                }
            }
            if (!$valid) {
                $errors[] = ['provider' => $class, 'error' => 'namespace outside package autoload'];
            }
        }
        return $errors;
    }

    public function validateRouteFiles(ModuleManifest $manifest): array
    {
        $errors = [];
        $base = realpath($manifest->path);
        if ($base === false) return $errors;

        foreach ($manifest->routes as $file) {
            $full = realpath($manifest->path . '/' . ltrim($file, '/'));
            if ($full === false) {
                $errors[] = ['file' => $file, 'error' => 'not found'];
                continue;
            }
            if (!str_starts_with($full, $base)) {
                $errors[] = ['file' => $file, 'error' => 'path traversal'];
            }
        }
        return $errors;
    }

    public function validateCommandFiles(ModuleManifest $manifest): array
    {
        $errors = [];
        $base = realpath($manifest->path);
        if ($base === false) return $errors;

        foreach ($manifest->commands as $name => $class) {
            if (is_string($class) && str_contains($class, '/')) {
                $full = realpath($manifest->path . '/' . ltrim($class, '/'));
                if ($full !== false && !str_starts_with($full, $base)) {
                    $errors[] = ['command' => $name, 'error' => 'path traversal'];
                }
            }
        }
        return $errors;
    }

    public function fullValidation(ModuleManifest $manifest): array
    {
        return [
            'preload' => $this->validatePreloadPaths($manifest),
            'providers' => $this->validateProviderNamespace($manifest),
            'routes' => $this->validateRouteFiles($manifest),
            'commands' => $this->validateCommandFiles($manifest),
        ];
    }
}
