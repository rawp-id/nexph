<?php
namespace Core\Runtime\Loader;

use Core\Runtime\Loader\Exceptions\ModuleNotFoundException;
use Core\Runtime\Loader\Exceptions\ModuleConflictException;

class ModuleRegistry
{
    private array $modules = [];
    private array $disabled = [];

    public function register(ModuleManifest $manifest): void
    {
        $name = $manifest->name;

        if (isset($this->modules[$name])) {
            throw new ModuleConflictException($name, $name, 'duplicate registration');
        }

        $this->modules[$name] = $manifest;
    }

    public function get(string $name): ModuleManifest
    {
        if (!isset($this->modules[$name])) {
            throw new ModuleNotFoundException($name);
        }
        return $this->modules[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->modules[$name]);
    }

    public function all(): array
    {
        return $this->modules;
    }

    public function enabled(): array
    {
        return array_diff_key($this->modules, array_flip($this->disabled));
    }

    public function disable(string $name): void
    {
        $this->disabled[] = $name;
    }

    public function enable(string $name): void
    {
        $this->disabled = array_filter($this->disabled, fn($n) => $n !== $name);
    }

    public function isEnabled(string $name): bool
    {
        return isset($this->modules[$name]) && !in_array($name, $this->disabled, true);
    }

    public function count(): int
    {
        return count($this->modules);
    }

    public function sorted(): array
    {
        $modules = $this->enabled();
        $sorted = [];
        $resolved = [];

        $resolve = function (string $name) use (&$resolve, &$sorted, &$resolved, $modules): void {
            if (isset($resolved[$name])) {
                return;
            }
            $resolved[$name] = true;

            if (!isset($modules[$name])) {
                return;
            }

            $manifest = $modules[$name];
            foreach ($manifest->requires as $dep => $version) {
                if (isset($modules[$dep])) {
                    $resolve($dep);
                }
            }

            $sorted[$name] = $manifest;
        };

        foreach (array_keys($modules) as $name) {
            $resolve($name);
        }

        return $sorted;
    }

    public function stats(): array
    {
        return [
            'total' => count($this->modules),
            'enabled' => count($this->enabled()),
            'disabled' => count($this->disabled),
        ];
    }
}
