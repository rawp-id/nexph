<?php

namespace Nexph\Builder;

use Nexph\Compiler\Compiler;
use Nexph\Compiler\ComponentResolver;

class DependencyResolver
{
    private Compiler $compiler;
    private ComponentResolver $resolver;
    private array $resolved = [];
    private array $visiting = [];

    public function __construct()
    {
        $this->compiler  = new Compiler();
        $this->resolver  = new ComponentResolver();
    }

    /**
     * Resolve all component files reachable from the entry file.
     * Returns an ordered list (dependencies before dependents).
     */
    public function resolve(string $entryFile): array
    {
        $this->resolved = [];
        $this->visiting = [];

        $this->visit($entryFile);

        return $this->resolved;
    }

    private function visit(string $file): void
    {
        $real = realpath($file);

        if ($real === false || in_array($real, $this->resolved)) {
            return;
        }

        if (in_array($real, $this->visiting)) {
            // Circular dependency — skip
            return;
        }

        if (!file_exists($real)) {
            return;
        }

        $this->visiting[] = $real;

        $source     = file_get_contents($real);
        $components = $this->resolver->resolve($source);
        $dir        = dirname($real);

        foreach ($components as $componentName) {
            $candidate = $dir . '/' . $componentName . '.php';
            if (file_exists($candidate)) {
                $this->visit($candidate);
            }
        }

        $this->visiting = array_filter($this->visiting, fn($v) => $v !== $real);
        $this->resolved[] = $real;
    }
}
