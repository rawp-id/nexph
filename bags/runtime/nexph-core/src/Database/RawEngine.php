<?php
namespace Core\Database;

/**
 * High-performance raw database operations.
 * 
 * Used by all generated systems:
 * - ApiGenerator
 * - UiGenerator
 * - Schema management
 * - Migration tools
 * 
 * No object hydration, no automatic relations, no magic.
 * Maximum performance, explicit SQL, easy debugging.
 */
class RawEngine {
    /**
     * Find single record by ID with metadata transformations.
     */
    public static function findById(string $table, mixed $id, array $meta): ?array {
        $fields = self::getSelectFields($meta);
        $selectCols = implode(', ', array_map(fn($f) => "`{$f}`", $fields));
        
        $data = DB::query("SELECT {$selectCols} FROM `{$table}` WHERE `id` = ?", [$id]);
        if (empty($data)) {
            return null;
        }
        
        return self::transform($data[0], $meta);
    }
    
    /**
     * List records with filters, pagination, and metadata transformations.
     */
    public static function list(string $table, array $meta, array $filters = [], int $limit = 20, int $offset = 0, ?string $orderBy = null, string $orderDir = 'ASC'): array {
        $fields = self::getSelectFields($meta);
        $selectCols = implode(', ', array_map(fn($f) => "`{$f}`", $fields));
        
        [$whereClause, $params] = self::buildWhereClause($filters, $meta);
        
        $sql = "SELECT {$selectCols} FROM `{$table}`{$whereClause}";
        
        if ($orderBy && self::isValidField($orderBy, $meta)) {
            $direction = strtoupper($orderDir) === 'DESC' ? 'DESC' : 'ASC';
            $sql .= " ORDER BY `{$orderBy}` {$direction}";
        }
        
        $sql .= " LIMIT {$limit} OFFSET {$offset}";
        
        $data = DB::query($sql, $params);
        
        return array_map(fn($row) => self::transform($row, $meta), $data);
    }
    
    /**
     * Create record with fillable filtering and metadata validation.
     */
    public static function create(string $table, array $input, array $meta): bool {
        $data = FieldControl::applyFillable($input, $meta);
        
        if (empty($data)) {
            return false;
        }
        
        // Handle UUID primary key
        foreach ($meta['fields'] as $field) {
            if (($field['name'] ?? '') === 'id' && !empty($field['primary']) && empty($data['id'])) {
                $fieldType = strtoupper($field['type'] ?? '');
                if ($fieldType === 'UUID') {
                    $data['id'] = self::generateUuid();
                }
            }
        }
        
        $cols = implode(', ', array_map(fn($c) => "`$c`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        return DB::execute("INSERT INTO `{$table}` ({$cols}) VALUES ({$placeholders})", array_values($data));
    }
    
    /**
     * Update record with fillable filtering.
     */
    public static function update(string $table, mixed $id, array $input, array $meta): bool {
        $data = FieldControl::applyFillable($input, $meta);
        
        if (empty($data)) {
            return false;
        }
        
        $sets = implode(', ', array_map(fn($k) => "`{$k}` = ?", array_keys($data)));
        $values = array_values($data);
        $values[] = $id;
        
        return DB::execute("UPDATE `{$table}` SET {$sets} WHERE `id` = ?", $values);
    }
    
    /**
     * Delete record by ID.
     */
    public static function delete(string $table, mixed $id): bool {
        return DB::execute("DELETE FROM `{$table}` WHERE `id` = ?", [$id]);
    }
    
    /**
     * Count records with optional filters.
     */
    public static function count(string $table, array $filters = [], array $meta = []): int {
        [$whereClause, $params] = self::buildWhereClause($filters, $meta);
        
        $result = DB::query("SELECT COUNT(*) as cnt FROM `{$table}`{$whereClause}", $params);
        return (int)($result[0]['cnt'] ?? 0);
    }
    
    /**
     * Paginate records with filters and sorting.
     */
    public static function paginate(string $table, array $meta, int $page = 1, int $perPage = 20, array $filters = [], ?string $orderBy = null, string $orderDir = 'ASC'): array {
        $offset = ($page - 1) * $perPage;
        
        $data = self::list($table, $meta, $filters, $perPage, $offset, $orderBy, $orderDir);
        $total = self::count($table, $filters, $meta);
        
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => (int)ceil($total / $perPage)
        ];
    }
    
    /**
     * Execute raw query with metadata transformations.
     */
    public static function query(string $sql, array $params, array $meta): array {
        $data = DB::query($sql, $params);
        return array_map(fn($row) => self::transform($row, $meta), $data);
    }
    
    /**
     * Get select fields excluding hidden fields.
     */
    private static function getSelectFields(array $meta): array {
        $fields = array_column($meta['fields'], 'name');
        $fields[] = 'id';
        
        $hidden = $meta['hidden'] ?? [];
        return array_values(array_unique(array_filter($fields, fn($f) => !in_array($f, $hidden))));
    }
    
    /**
     * Apply hidden and casts transformations.
     */
    private static function transform(array $data, array $meta): array {
        return FieldControl::applyCasts(FieldControl::applyHidden($data, $meta), $meta);
    }
    
    /**
     * Build WHERE clause from filters.
     */
    private static function buildWhereClause(array $filters, array $meta): array {
        if (empty($filters)) {
            return ['', []];
        }
        
        $conditions = [];
        $params = [];
        $validFields = array_column($meta['fields'] ?? [], 'name');
        $validFields[] = 'id';
        
        foreach ($filters as $field => $value) {
            if (!in_array($field, $validFields)) {
                continue;
            }
            
            if (is_array($value)) {
                // Handle IN clause
                $placeholders = implode(',', array_fill(0, count($value), '?'));
                $conditions[] = "`{$field}` IN ({$placeholders})";
                $params = array_merge($params, $value);
            } else {
                $conditions[] = "`{$field}` = ?";
                $params[] = $value;
            }
        }
        
        if (empty($conditions)) {
            return ['', []];
        }
        
        return [' WHERE ' . implode(' AND ', $conditions), $params];
    }
    
    /**
     * Check if field is valid in metadata.
     */
    private static function isValidField(string $field, array $meta): bool {
        if ($field === 'id') {
            return true;
        }
        
        $validFields = array_column($meta['fields'] ?? [], 'name');
        return in_array($field, $validFields);
    }
    
    /**
     * Generate UUID v4.
     */
    private static function generateUuid(): string {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
