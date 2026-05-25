<?php
namespace Core\Runtime\Loader;

class ModuleDependencyResolver
{
    public function resolve(array $modules): array
    {
        $sorted = [];
        $visited = [];
        $visiting = [];

        $visit = function (string $name) use (&$visit, &$sorted, &$visited, &$visiting, $modules): void {
            if (isset($visited[$name])) {
                return;
            }

            if (isset($visiting[$name])) {
                throw new Exceptions\ModuleConflictException($name, $name, 'circular dependency detected');
            }

            $visiting[$name] = true;

            if (isset($modules[$name])) {
                $manifest = $modules[$name];
                foreach ($manifest->requires as $dep => $constraint) {
                    if (isset($modules[$dep])) {
                        $visit($dep);
                    }
                }

                // Check conflicts
                foreach ($manifest->conflicts as $conflict => $constraint) {
                    if (isset($modules[$conflict])) {
                        throw new Exceptions\ModuleConflictException(
                            $name, $conflict, "declared conflict"
                        );
                    }
                }
            }

            unset($visiting[$name]);
            $visited[$name] = true;
            if (isset($modules[$name])) {
                $sorted[$name] = $modules[$name];
            }
        };

        foreach (array_keys($modules) as $name) {
            $visit($name);
        }

        return $sorted;
    }

    public function checkMissing(array $modules): array
    {
        $missing = [];
        foreach ($modules as $name => $manifest) {
            foreach ($manifest->requires as $dep => $constraint) {
                if (!isset($modules[$dep])) {
                    $missing[] = ['module' => $name, 'requires' => $dep, 'constraint' => $constraint];
                }
            }
        }
        return $missing;
    }
}
