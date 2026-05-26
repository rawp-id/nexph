<?php
namespace Core\Runtime\Loader;

class ModuleManifest
{
    public function __construct(
        public readonly string $name,
        public readonly string $version,
        public readonly string $path,
        public readonly string $type = 'library',
        public readonly string $description = '',
        public readonly array $autoload = [],
        public readonly array $preload = [],
        public readonly array $lazy = [],
        public readonly array $providers = [],
        public readonly array $routes = [],
        public readonly array $commands = [],
        public readonly array $hooks = [],
        public readonly array $config = [],
        public readonly array $requires = [],
        public readonly array $conflicts = [],
    ) {}

    public static function fromArray(array $data, string $basePath): self
    {
        return new self(
            name: $data['name'],
            version: $data['version'],
            path: $basePath,
            type: $data['type'] ?? 'library',
            description: $data['description'] ?? '',
            autoload: $data['autoload'] ?? [],
            preload: $data['preload'] ?? [],
            lazy: $data['lazy'] ?? [],
            providers: $data['providers'] ?? [],
            routes: $data['routes'] ?? [],
            commands: $data['commands'] ?? [],
            hooks: $data['hooks'] ?? [],
            config: $data['config'] ?? [],
            requires: $data['requires'] ?? [],
            conflicts: $data['conflicts'] ?? [],
        );
    }
}
