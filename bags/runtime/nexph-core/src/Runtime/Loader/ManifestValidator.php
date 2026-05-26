<?php
namespace Core\Runtime\Loader;

use Core\Runtime\Loader\Exceptions\ManifestValidationException;

class ManifestValidator
{
    private const REQUIRED_FIELDS = ['name', 'version'];
    private const VALID_TYPES = ['library', 'module', 'plugin', 'app'];

    public function validate(array $data, string $path): void
    {
        $errors = [];

        foreach (self::REQUIRED_FIELDS as $field) {
            if (empty($data[$field])) {
                $errors[] = "missing required field '{$field}'";
            }
        }

        if (isset($data['name']) && !preg_match('/^[a-z0-9]([a-z0-9_-]*\/)?[a-z0-9][a-z0-9_-]*$/', $data['name'])) {
            $errors[] = "invalid package name format";
        }

        if (isset($data['version']) && !preg_match('/^\d+\.\d+\.\d+(-[a-zA-Z0-9.]+)?$/', $data['version'])) {
            $errors[] = "invalid version format (expected semver)";
        }

        if (isset($data['type']) && !in_array($data['type'], self::VALID_TYPES, true)) {
            $errors[] = "invalid type (expected: " . implode(', ', self::VALID_TYPES) . ")";
        }

        if (isset($data['autoload']) && !is_array($data['autoload'])) {
            $errors[] = "'autoload' must be an array";
        }

        if (isset($data['requires']) && !is_array($data['requires'])) {
            $errors[] = "'requires' must be an array";
        }

        if (isset($data['providers']) && !is_array($data['providers'])) {
            $errors[] = "'providers' must be an array";
        }

        if (!empty($errors)) {
            throw new ManifestValidationException($path, $errors);
        }
    }
}
