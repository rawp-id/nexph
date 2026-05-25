<?php
namespace Core\Auth;

class SessionManager {
    private static array $drivers = [];
    private static array $schemas = [];

    public static function registerDriver(string $name, string $class, array $schema): void {
        self::$drivers[$name] = $class;
        self::$schemas[$name] = $schema;
    }

    public static function getDriver(string $name, array $config): SessionDriver {
        if (!isset(self::$drivers[$name])) {
            throw new \InvalidArgumentException("Session driver '{$name}' not found");
        }
        $class = self::$drivers[$name];
        return new $class($config);
    }

    public static function getAvailableDrivers(): array {
        return array_keys(self::$drivers);
    }

    public static function getDriverSchema(string $name): array {
        return self::$schemas[$name] ?? [];
    }

    public static function getAllSchemas(): array {
        return self::$schemas;
    }

    public static function validateConfig(string $driver, array $config): array {
        $schema = self::getDriverSchema($driver);
        $errors = [];
        foreach ($schema as $field => $rules) {
            $required = str_contains($rules, 'required');
            if ($required && !isset($config[$field])) {
                $errors[$field] = "Field '{$field}' is required";
            }
        }
        return $errors;
    }
}
