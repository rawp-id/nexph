<?php
namespace Core\Http;

use Core\Database\Metadata;
use Core\Support\Cache;

class ApiPolicy {
    private array $defaults;
    private array $tables;

    public const ENDPOINTS = ['list', 'detail', 'create', 'update', 'delete'];

    public const ENDPOINT_MAP = [
        'list'   => ['method' => 'GET',    'path' => '/api/{table}'],
        'detail' => ['method' => 'GET',    'path' => '/api/{table}/{id}'],
        'create' => ['method' => 'POST',   'path' => '/api/{table}'],
        'update' => ['method' => 'PUT',    'path' => '/api/{table}/{id}'],
        'delete' => ['method' => 'DELETE', 'path' => '/api/{table}/{id}'],
    ];

    public function __construct(array $config = []) {
        $this->defaults = array_merge([
            'enabled' => true,
            'auth' => true,
            'roles' => [],
            'max_limit' => 100,
            'endpoints' => [],
        ], $config['defaults'] ?? []);
        $this->tables = $config['tables'] ?? [];

        // migrate legacy per-method format
        $this->defaults = self::migrateConfig($this->defaults);
        foreach ($this->tables as $table => $cfg) {
            $this->tables[$table] = self::migrateConfig($cfg);
        }
    }

    private static function migrateConfig(array $cfg): array {
        if (isset($cfg['methods']) && !isset($cfg['endpoints'])) {
            $methods = array_map('strtoupper', $cfg['methods'] ?? []);
            $authMethods = $cfg['auth_methods'] ?? [];
            $rolesMethods = $cfg['roles_methods'] ?? [];
            $defaultAuth = (bool)($cfg['auth'] ?? false);
            $defaultRoles = (array)($cfg['roles'] ?? []);
            $map = ['GET' => ['list', 'detail'], 'POST' => ['create'], 'PUT' => ['update'], 'DELETE' => ['delete']];
            $endpoints = [];
            foreach ($map as $method => $names) {
                $enabled = in_array($method, $methods, true);
                $auth = array_key_exists($method, $authMethods) ? (bool)$authMethods[$method] : $defaultAuth;
                $roles = array_key_exists($method, $rolesMethods) ? array_values(array_filter((array)$rolesMethods[$method])) : $defaultRoles;
                foreach ($names as $name) {
                    $endpoints[$name] = ['enabled' => $enabled, 'auth' => $auth, 'roles' => $roles];
                }
            }
            $cfg['endpoints'] = $endpoints;
            unset($cfg['methods'], $cfg['auth_methods'], $cfg['roles_methods']);
        }
        return $cfg;
    }

    public static function fromFile(string $path): self {
        if (!file_exists($path)) {
            return new self();
        }
        $cacheKey = 'nexph:apipolicy';
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return new self($cached);
        }
        $data = str_ends_with($path, '.php') ? require $path : json_decode(file_get_contents($path), true);
        if (!is_array($data)) {
            throw new \RuntimeException('Invalid API policy config');
        }
        Cache::set($cacheKey, $data, 3600);
        return new self($data);
    }

    public static function savePhp(string $path, array $config): void {
        $export = var_export($config, true);
        file_put_contents($path, "<?php\nreturn {$export};\n", LOCK_EX);
        Cache::delete('nexph:apipolicy');
    }

    public static function saveJson(string $path, array $config): void {
        file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), LOCK_EX);
        Cache::delete('nexph:apipolicy');
    }

    public function toArray(): array {
        return ['defaults' => $this->defaults, 'tables' => $this->tables];
    }

    public function tableConfig(string $table): array {
        $cfg = array_merge($this->defaults, $this->tables[$table] ?? []);
        $defaultEndpoints = $this->defaults['endpoints'] ?? [];
        $tableEndpoints = ($this->tables[$table] ?? [])['endpoints'] ?? [];
        $merged = [];
        foreach (self::ENDPOINTS as $ep) {
            $merged[$ep] = array_merge(
                ['enabled' => true, 'auth' => (bool)($cfg['auth'] ?? false), 'roles' => (array)($cfg['roles'] ?? [])],
                $defaultEndpoints[$ep] ?? [],
                $tableEndpoints[$ep] ?? []
            );
        }
        $cfg['endpoints'] = $merged;
        return $cfg;
    }

    public function isTableEnabled(string $table): bool {
        return (bool)($this->tableConfig($table)['enabled'] ?? false);
    }

    public function endpointConfig(string $table, string $endpoint): array {
        $config = $this->tableConfig($table);
        return $config['endpoints'][$endpoint] ?? [
            'enabled' => true,
            'auth' => (bool)($config['auth'] ?? false),
            'roles' => (array)($config['roles'] ?? []),
        ];
    }

    public function isEndpointEnabled(string $table, string $endpoint): bool {
        return (bool)($this->endpointConfig($table, $endpoint)['enabled'] ?? true);
    }

    public function endpointRequiresAuth(string $table, string $endpoint): bool {
        return (bool)($this->endpointConfig($table, $endpoint)['auth'] ?? false);
    }

    public function endpointRoles(string $table, string $endpoint): array {
        return array_values(array_filter((array)($this->endpointConfig($table, $endpoint)['roles'] ?? [])));
    }

    public function middleware(string $table, string $endpoint): array {
        if (!$this->isEndpointEnabled($table, $endpoint)) {
            return [self::disabledEndpoint()];
        }
        $middleware = [];
        if ($this->endpointRequiresAuth($table, $endpoint)) {
            $middleware[] = [Middleware::class, 'api'];
        }
        foreach ($this->endpointRoles($table, $endpoint) as $role) {
            $middleware[] = Middleware::role($role);
        }
        return $middleware;
    }

    // Legacy compat
    public function isMethodEnabled(string $table, string $method): bool {
        $map = ['GET' => 'list', 'POST' => 'create', 'PUT' => 'update', 'DELETE' => 'delete'];
        return $this->isEndpointEnabled($table, $map[strtoupper($method)] ?? 'list');
    }

    public function methodRequiresAuth(string $table, string $method): bool {
        $map = ['GET' => 'list', 'POST' => 'create', 'PUT' => 'update', 'DELETE' => 'delete'];
        return $this->endpointRequiresAuth($table, $map[strtoupper($method)] ?? 'list');
    }

    public function methodRoles(string $table, string $method): array {
        $map = ['GET' => 'list', 'POST' => 'create', 'PUT' => 'update', 'DELETE' => 'delete'];
        return $this->endpointRoles($table, $map[strtoupper($method)] ?? 'list');
    }

    public function maxLimit(string $table): int {
        return max(1, min(1000, (int)($this->tableConfig($table)['max_limit'] ?? 100)));
    }

    public function visibleMetadata(array $metadata): array {
        return array_values(array_filter($metadata, fn($meta) => $this->isTableEnabled($meta['table'] ?? '')));
    }

    public function manifest(array $metadata): array {
        $resources = [];
        foreach ($this->visibleMetadata($metadata) as $meta) {
            $table = $meta['table'];
            $config = $this->tableConfig($table);
            $endpoints = [];
            foreach (self::ENDPOINTS as $ep) {
                $info = self::ENDPOINT_MAP[$ep];
                $epCfg = $config['endpoints'][$ep] ?? [];
                $endpoints[$ep] = [
                    'method' => $info['method'],
                    'path' => str_replace('{table}', $table, $info['path']),
                    'enabled' => (bool)($epCfg['enabled'] ?? true),
                    'auth' => (bool)($epCfg['auth'] ?? false),
                    'roles' => (array)($epCfg['roles'] ?? []),
                ];
            }
            $resources[] = [
                'table' => $table,
                'path' => "/api/{$table}",
                'auth' => (bool)($config['auth'] ?? false),
                'roles' => $config['roles'] ?? [],
                'endpoints' => $endpoints,
                'fields' => array_map(fn($f) => [
                    'name' => $f['name'],
                    'type' => $f['type'] ?? 'TEXT',
                    'nullable' => (bool)($f['nullable'] ?? false),
                    'required' => self::isRequiredField($f),
                    'guarded' => (bool)($f['guarded'] ?? false),
                ], $meta['fields'] ?? []),
            ];
        }
        return ['resources' => $resources];
    }

    public static function validatePayload(array $input, array $meta, bool $partial = false): array {
        $errors = [];
        $payload = [];
        foreach ($meta['fields'] ?? [] as $field) {
            $name = $field['name'] ?? '';
            if ($name === '' || in_array($name, ['created_at', 'updated_at'], true) || !empty($field['guarded'])) {
                continue;
            }
            $exists = array_key_exists($name, $input);
            if (!$exists) {
                if (!$partial && self::isRequiredField($field)) {
                    $errors[$name][] = 'required';
                }
                continue;
            }
            $value = $input[$name];
            if ($value === null || $value === '') {
                if (self::isRequiredField($field)) {
                    $errors[$name][] = 'required';
                    continue;
                }
                $payload[$name] = null;
                continue;
            }
            $type = strtoupper($field['type'] ?? 'TEXT');
            if (!self::validType($value, $type)) {
                $errors[$name][] = "invalid {$type}";
                continue;
            }
            $payload[$name] = in_array($type, ['JSON', 'ARRAY'], true) ? json_encode($value) : $value;
        }
        return [$payload, $errors];
    }

    private static function isRequiredField(array $field): bool {
        return empty($field['nullable']) && empty($field['primary']) && ($field['default'] ?? '') === '' && !($field['autoincrement'] ?? false);
    }

    private static function validType(mixed $value, string $type): bool {
        return match ($type) {
            'INTEGER', 'BIGINT', 'SMALLINT', 'TINYINT' => filter_var($value, FILTER_VALIDATE_INT) !== false,
            'REAL', 'FLOAT', 'DOUBLE', 'DECIMAL' => is_numeric($value),
            'BOOLEAN', 'BOOL' => is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true),
            'DATE' => is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value),
            'DATETIME', 'TIMESTAMP' => is_string($value) && strtotime($value) !== false,
            'JSON', 'ARRAY' => is_array($value) || is_object($value),
            default => is_scalar($value),
        };
    }

    private static function disabledEndpoint(): \Closure {
        return function(Request $request, Response $response): void {
            $response->json(['error' => 'Endpoint disabled'], 404);
        };
    }
}
