<?php
namespace Core\Runtime\Loader;

use Core\Runtime\Loader\Exceptions\ModuleLoadException;

class RuntimeLoader
{
    private ModuleRegistry $registry;
    private ManifestParser $parser;
    private ManifestValidator $validator;
    private RuntimePreloader $preloader;
    private LazyModuleResolver $lazyResolver;
    private array $booted = [];
    private bool $loaded = false;

    public function __construct()
    {
        $this->registry = new ModuleRegistry();
        $this->parser = new ManifestParser();
        $this->validator = new ManifestValidator();
        $this->preloader = new RuntimePreloader();
        $this->lazyResolver = new LazyModuleResolver();
    }

    public function discover(array $paths): void
    {
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            $dirs = glob($path . '/*', GLOB_ONLYDIR);
            foreach ($dirs as $dir) {
                $this->loadFromDir($dir);
            }

            // Support vendor-style nested dirs
            $nestedDirs = glob($path . '/*/*', GLOB_ONLYDIR);
            foreach ($nestedDirs as $dir) {
                $this->loadFromDir($dir);
            }
        }
    }

    public function loadFromDir(string $dir): void
    {
        try {
            $data = $this->parser->parseFromDir($dir);
        } catch (\Throwable $e) {
            return;
        }

        if ($data === null) {
            return;
        }

        try {
            $this->validator->validate($data, $dir . '/nexph.json');
            $manifest = ModuleManifest::fromArray($data, $dir);
            $this->registry->register($manifest);
        } catch (\Throwable $e) {
            // Skip invalid/conflicting modules during discovery
            return;
        }
    }

    public function boot(): void
    {
        if ($this->loaded) {
            return;
        }

        $modules = $this->registry->sorted();

        // Preload phase
        foreach ($modules as $manifest) {
            $this->preloader->preload($manifest);
        }

        // Build lazy map
        $this->lazyResolver->buildMap($modules);

        // Boot providers
        foreach ($modules as $name => $manifest) {
            $this->bootModule($manifest);
        }

        $this->loaded = true;
    }

    private function bootModule(ModuleManifest $manifest): void
    {
        if (isset($this->booted[$manifest->name])) {
            return;
        }

        foreach ($manifest->providers as $providerClass) {
            if (!class_exists($providerClass)) {
                $file = $manifest->path . '/' . str_replace('\\', '/', $providerClass) . '.php';
                if (file_exists($file)) {
                    require_once $file;
                }
            }

            if (class_exists($providerClass)) {
                $provider = new $providerClass();
                if (method_exists($provider, 'register')) {
                    $provider->register();
                }
                if (method_exists($provider, 'boot')) {
                    $provider->boot();
                }
            }
        }

        $this->booted[$manifest->name] = true;
    }

    public function getRegistry(): ModuleRegistry
    {
        return $this->registry;
    }

    public function getPreloader(): RuntimePreloader
    {
        return $this->preloader;
    }

    public function getLazyResolver(): LazyModuleResolver
    {
        return $this->lazyResolver;
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    public function stats(): array
    {
        return [
            'registry' => $this->registry->stats(),
            'preloader' => $this->preloader->stats(),
            'lazy' => $this->lazyResolver->stats(),
            'booted' => count($this->booted),
        ];
    }
}
