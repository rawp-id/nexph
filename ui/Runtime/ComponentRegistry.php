<?php

namespace Nexph\Runtime;

class ComponentRegistry
{
    private static array $components = [];
    private static array $aliases = [];
    
    public static function register(string $name, string $class): void
    {
        self::$components[$name] = $class;
        
        $kebabCase = self::toKebabCase($name);
        if ($kebabCase !== $name) {
            self::$aliases[$kebabCase] = $name;
        }
    }
    
    public static function resolve(string $name): ?string
    {
        if (isset(self::$aliases[$name])) {
            $name = self::$aliases[$name];
        }
        
        return self::$components[$name] ?? null;
    }
    
    public static function has(string $name): bool
    {
        return isset(self::$components[$name]) || isset(self::$aliases[$name]);
    }
    
    public static function all(): array
    {
        return self::$components;
    }
    
    public static function autoRegister(string $namespace): void
    {
        $files = glob($namespace . '/*.php');
        
        foreach ($files as $file) {
            $className = basename($file, '.php');
            $fullClass = $namespace . '\\' . $className;
            
            if (class_exists($fullClass)) {
                self::register($className, $fullClass);
            }
        }
    }
    
    private static function toKebabCase(string $string): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $string));
    }
}
