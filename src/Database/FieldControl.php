<?php
namespace Core\Database;

class FieldControl {
    public static function applyFillable(array $input, array $meta): array {
        $fillable = self::getFillable($meta);
        if (empty($fillable)) {
            return self::filterGuarded($input, $meta);
        }
        $result = [];
        foreach ($fillable as $field) {
            if (array_key_exists($field, $input)) {
                $result[$field] = $input[$field];
            }
        }
        return $result;
    }

    public static function applyHidden(array $data, array $meta): array {
        $hidden = self::getHidden($meta);
        if (empty($hidden)) {
            return $data;
        }
        foreach ($hidden as $field) {
            unset($data[$field]);
        }
        return $data;
    }

    public static function applyCasts(array $data, array $meta): array {
        $casts = self::getCasts($meta);
        if (empty($casts)) {
            return $data;
        }
        foreach ($casts as $field => $cast) {
            if (!array_key_exists($field, $data)) {
                continue;
            }
            $data[$field] = self::castValue($data[$field], $cast);
        }
        return $data;
    }

    private static function getFillable(array $meta): array {
        return array_values(array_filter((array)($meta['fillable'] ?? [])));
    }

    private static function getHidden(array $meta): array {
        return array_values(array_filter((array)($meta['hidden'] ?? [])));
    }

    private static function getCasts(array $meta): array {
        return (array)($meta['casts'] ?? []);
    }

    private static function filterGuarded(array $input, array $meta): array {
        $guarded = ['created_at', 'updated_at'];
        foreach ($meta['fields'] ?? [] as $field) {
            if (!empty($field['guarded'])) {
                $guarded[] = $field['name'];
            }
        }
        foreach ($guarded as $field) {
            unset($input[$field]);
        }
        return $input;
    }

    private static function castValue(mixed $value, string $cast): mixed {
        if ($value === null) {
            return null;
        }
        return match (strtolower($cast)) {
            'bool', 'boolean' => (bool)$value,
            'int', 'integer' => (int)$value,
            'float', 'double' => (float)$value,
            'string' => (string)$value,
            'json' => is_string($value) ? json_decode($value, true) : $value,
            'datetime' => is_numeric($value) ? date('Y-m-d H:i:s', $value) : $value,
            default => $value,
        };
    }
}
