<?php
namespace Core\Runtime\Package;

class DependencyResolver
{
    private SemverResolver $semver;
    private PackageRegistryClient $registry;
    private PackageLock $lock;

    public function __construct(PackageRegistryClient $registry, PackageLock $lock)
    {
        $this->semver = new SemverResolver();
        $this->registry = $registry;
        $this->lock = $lock;
    }

    public function resolve(string $package, ?string $constraint = null): array
    {
        $constraint = $constraint ?? '*';

        // Prefer locked version
        $locked = $this->lock->getPackage($package);
        if ($locked !== null && $this->semver->satisfies($locked['version'], $constraint)) {
            return [
                'name' => $package,
                'version' => $locked['version'],
                'source' => 'lock',
                'checksum' => $locked['checksum'] ?? null,
                'requires' => $locked['requires'] ?? [],
                'installed_path' => $locked['installed_path'] ?? null,
            ];
        }

        // Lookup from registry
        $versions = $this->registry->getVersions($package);
        if (!empty($versions)) {
            $available = array_keys($versions);
            $best = $this->semver->findBest($available, $constraint);

            if ($best !== null) {
                $meta = $versions[$best];
                return [
                    'name' => $package,
                    'version' => $best,
                    'source' => 'registry',
                    'dist_url' => $meta['dist']['url'] ?? null,
                    'checksum' => $meta['dist']['checksum'] ?? null,
                    'requires' => $meta['requires'] ?? [],
                    'installed_path' => null,
                ];
            }
        }

        // Fallback: use constraint as exact version
        return [
            'name' => $package,
            'version' => $constraint !== '*' ? ltrim($constraint, '^~>=<! ') : '0.0.0',
            'source' => 'local',
            'checksum' => null,
            'requires' => [],
            'installed_path' => null,
        ];
    }

    public function resolveTree(array $requirements): array
    {
        $resolved = [];
        $queue = $requirements;
        $seen = [];

        while (!empty($queue)) {
            $item = array_shift($queue);
            $name = $item['name'];
            $constraint = $item['constraint'] ?? '*';

            if (isset($seen[$name])) {
                // Check conflict
                if (!$this->semver->satisfies($resolved[$name]['version'], $constraint)) {
                    throw new PackageResolveException($name, "version conflict: need {$constraint}, resolved {$resolved[$name]['version']}");
                }
                continue;
            }

            $seen[$name] = true;
            $result = $this->resolve($name, $constraint);
            $resolved[$name] = $result;

            // Queue transitive deps
            foreach ($result['requires'] as $dep => $depConstraint) {
                if (!isset($seen[$dep])) {
                    $queue[] = ['name' => $dep, 'constraint' => $depConstraint];
                }
            }
        }

        return $resolved;
    }

    public function detectCircular(array $requirements): array
    {
        $graph = [];
        foreach ($requirements as $name => $deps) {
            $graph[$name] = array_keys($deps);
        }

        $circular = [];
        $visited = [];
        $stack = [];

        $visit = function (string $node) use (&$visit, &$visited, &$stack, &$circular, $graph): void {
            if (in_array($node, $stack, true)) {
                $circular[] = array_merge(array_slice($stack, array_search($node, $stack)), [$node]);
                return;
            }
            if (isset($visited[$node])) {
                return;
            }

            $stack[] = $node;
            foreach ($graph[$node] ?? [] as $dep) {
                $visit($dep);
            }
            array_pop($stack);
            $visited[$node] = true;
        };

        foreach (array_keys($graph) as $node) {
            $visit($node);
        }

        return $circular;
    }
}
