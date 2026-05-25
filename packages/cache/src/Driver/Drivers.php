<?php
namespace Core\Cache\Driver;

use Core\Cache\CacheDriver;

class FileDriver implements CacheDriver {
    private string $path;

    public function __construct(array $config = []) {
        $this->path = $config['path'] ?? sys_get_temp_dir() . '/nexph_cache';
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public function get(string $key): mixed {
        $file = $this->file($key);
        if (!file_exists($file)) return null;

        $data = unserialize(file_get_contents($file));
        if ($data['expires'] > 0 && $data['expires'] < time()) {
            unlink($file);
            return null;
        }
        return $data['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool {
        $data = [
            'value' => $value,
            'expires' => $ttl > 0 ? time() + $ttl : 0,
        ];
        return file_put_contents($this->file($key), serialize($data), LOCK_EX) !== false;
    }

    public function delete(string $key): bool {
        $file = $this->file($key);
        return file_exists($file) ? unlink($file) : true;
    }

    public function has(string $key): bool {
        return $this->get($key) !== null;
    }

    public function flush(): bool {
        $files = glob($this->path . '/*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
        return true;
    }

    public function many(array $keys): array {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    public function setMany(array $values, int $ttl = 3600): bool {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }

    private function file(string $key): string {
        return $this->path . '/' . md5($key) . '.cache';
    }
}

class MemoryDriver implements CacheDriver {
    private static array $store = [];

    public function get(string $key): mixed {
        if (!isset(self::$store[$key])) return null;

        $data = self::$store[$key];
        if ($data['expires'] > 0 && $data['expires'] < time()) {
            unset(self::$store[$key]);
            return null;
        }
        return $data['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool {
        self::$store[$key] = [
            'value' => $value,
            'expires' => $ttl > 0 ? time() + $ttl : 0,
        ];
        return true;
    }

    public function delete(string $key): bool {
        unset(self::$store[$key]);
        return true;
    }

    public function has(string $key): bool {
        return $this->get($key) !== null;
    }

    public function flush(): bool {
        self::$store = [];
        return true;
    }

    public function many(array $keys): array {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key);
        }
        return $result;
    }

    public function setMany(array $values, int $ttl = 3600): bool {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }
        return true;
    }
}

class RedisDriver implements CacheDriver {
    private \Redis $redis;

    public function __construct(array $config = []) {
        $this->redis = new \Redis();
        $this->redis->connect(
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? 6379
        );
        if ($pass = $config['password'] ?? null) {
            $this->redis->auth($pass);
        }
        if ($db = $config['database'] ?? null) {
            $this->redis->select($db);
        }
    }

    public function get(string $key): mixed {
        $value = $this->redis->get($key);
        return $value !== false ? unserialize($value) : null;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool {
        if ($ttl > 0) {
            return $this->redis->setex($key, $ttl, serialize($value));
        }
        return $this->redis->set($key, serialize($value));
    }

    public function delete(string $key): bool {
        return $this->redis->del($key) >= 0;
    }

    public function has(string $key): bool {
        return $this->redis->exists($key) > 0;
    }

    public function flush(): bool {
        return $this->redis->flushDB();
    }

    public function many(array $keys): array {
        $values = $this->redis->mget($keys);
        $result = [];
        foreach ($keys as $i => $key) {
            $result[$key] = $values[$i] !== false ? unserialize($values[$i]) : null;
        }
        return $result;
    }

    public function setMany(array $values, int $ttl = 3600): bool {
        $pipe = $this->redis->multi(\Redis::PIPELINE);
        foreach ($values as $key => $value) {
            if ($ttl > 0) {
                $pipe->setex($key, $ttl, serialize($value));
            } else {
                $pipe->set($key, serialize($value));
            }
        }
        $pipe->exec();
        return true;
    }
}

class MemcachedDriver implements CacheDriver {
    private \Memcached $mc;

    public function __construct(array $config = []) {
        $this->mc = new \Memcached();
        $this->mc->addServer(
            $config['host'] ?? '127.0.0.1',
            $config['port'] ?? 11211
        );
    }

    public function get(string $key): mixed {
        $value = $this->mc->get($key);
        return $this->mc->getResultCode() === \Memcached::RES_SUCCESS ? $value : null;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool {
        return $this->mc->set($key, $value, $ttl);
    }

    public function delete(string $key): bool {
        return $this->mc->delete($key);
    }

    public function has(string $key): bool {
        $this->mc->get($key);
        return $this->mc->getResultCode() === \Memcached::RES_SUCCESS;
    }

    public function flush(): bool {
        return $this->mc->flush();
    }

    public function many(array $keys): array {
        return $this->mc->getMulti($keys) ?: [];
    }

    public function setMany(array $values, int $ttl = 3600): bool {
        return $this->mc->setMulti($values, $ttl);
    }
}
