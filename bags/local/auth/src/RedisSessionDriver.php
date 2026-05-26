<?php
namespace Core\Auth;

class RedisSessionDriver implements SessionDriver {
    public static function schema(): array {
        return [
            'host' => 'string|required',
            'port' => 'int|required',
            'password' => 'string|nullable',
            'database' => 'int',
            'prefix' => 'string',
        ];
    }
    private $redis;
    private int $lifetime;
    private string $prefix;

    public function __construct(array $config) {
        if (!extension_loaded('redis')) {
            throw new \RuntimeException('Redis extension not loaded');
        }
        $this->redis = new \Redis();
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? 6379;
        $password = $config['password'] ?? null;
        $database = $config['database'] ?? 0;
        $this->prefix = $config['prefix'] ?? 'sess:';
        $this->lifetime = $config['lifetime'] ?? 7200;
        if (!$this->redis->connect($host, $port)) {
            throw new \RuntimeException("Cannot connect to Redis at {$host}:{$port}");
        }
        if ($password) {
            $this->redis->auth($password);
        }
        $this->redis->select($database);
    }

    public function read(string $id): array {
        $key = $this->prefix . $id;
        $data = $this->redis->get($key);
        if ($data === false) {
            return [];
        }
        return json_decode($data, true) ?? [];
    }

    public function write(string $id, array $data): bool {
        $key = $this->prefix . $id;
        $jsonData = json_encode($data);
        return $this->redis->setex($key, $this->lifetime, $jsonData);
    }

    public function destroy(string $id): bool {
        $key = $this->prefix . $id;
        $this->redis->del($key);
        return true;
    }

    public function exists(string $id): bool {
        $key = $this->prefix . $id;
        return $this->redis->exists($key) > 0;
    }

    public function gc(int $maxLifetime): void {
    }

    public function __destruct() {
        if ($this->redis) {
            $this->redis->close();
        }
    }
}
