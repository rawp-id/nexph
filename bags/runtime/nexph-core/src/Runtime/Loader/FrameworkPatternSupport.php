<?php
namespace Core\Runtime\Loader;

class FrameworkPatternSupport
{
    public static function expressRoutes(ModuleManifest $manifest, $router): void
    {
        foreach ($manifest->routes as $routeFile) {
            $path = $manifest->path . '/' . ltrim($routeFile, '/');
            if (file_exists($path)) {
                $routeFn = require $path;
                if (is_callable($routeFn)) {
                    $routeFn($router);
                }
            }
        }
    }

    public static function middlewarePackage(ModuleManifest $manifest, $server): void
    {
        foreach ($manifest->hooks as $hook) {
            if (isset($hook['middleware']) && class_exists($hook['middleware'])) {
                $server->use(new $hook['middleware']());
            }
        }
    }

    public static function cliPackage(ModuleManifest $manifest, $registry): void
    {
        foreach ($manifest->commands as $name => $class) {
            if (class_exists($class)) {
                $registry->register($name, new $class());
            }
        }
    }

    public static function configPackage(ModuleManifest $manifest): array
    {
        $configs = [];
        foreach ($manifest->config as $key => $file) {
            $path = $manifest->path . '/' . ltrim($file, '/');
            if (file_exists($path)) {
                $configs[$key] = require $path;
            }
        }
        return $configs;
    }

    public static function hookPackage(ModuleManifest $manifest): array
    {
        return $manifest->hooks;
    }
}
