<?php
namespace Core\Cache;

interface CacheDriver {
    public function get(string $key): mixed;
    public function set(string $key, mixed $value, int $ttl = 3600): bool;
    public function delete(string $key): bool;
    public function has(string $key): bool;
    public function flush(): bool;
    public function many(array $keys): array;
    public function setMany(array $values, int $ttl = 3600): bool;
}

class CacheManager {
    private static ?CacheDriver $driver = null;
    private static array $config = [];
    private static string $prefix = 'nexph:';

    public static function configure(array $config): void {
        self::$config = $config;
        self::$prefix = $config['prefix'] ?? 'nexph:';
    }

    public static function driver(?string $name = null): CacheDriver {
        $name = $name ?? self::$config['driver'] ?? 'file';

        if (self::$driver && !$name) {
            return self::$driver;
        }

        return self::$driver = match ($name) {
            'redis' => new Driver\RedisDriver(self::$config['redis'] ?? []),
            'memcached' => new Driver\MemcachedDriver(self::$config['memcached'] ?? []),
            'memory' => new Driver\MemoryDriver(),
            default => new Driver\FileDriver(self::$config['file'] ?? []),
        };
    }

    public static function get(string $key, mixed $default = null): mixed {
        $value = self::driver()->get(self::$prefix . $key);
        return $value ?? $default;
    }

    public static function set(string $key, mixed $value, int $ttl = 3600): bool {
        return self::driver()->set(self::$prefix . $key, $value, $ttl);
    }

    public static function delete(string $key): bool {
        return self::driver()->delete(self::$prefix . $key);
    }

    public static function has(string $key): bool {
        return self::driver()->has(self::$prefix . $key);
    }

    public static function flush(): bool {
        return self::driver()->flush();
    }

    public static function remember(string $key, int $ttl, callable $callback): mixed {
        $value = self::get($key);
        if ($value !== null) {
            return $value;
        }
        $value = $callback();
        self::set($key, $value, $ttl);
        return $value;
    }

    public static function forget(string $key): bool {
        return self::delete($key);
    }

    // Tags support
    public static function tags(array $tags): TaggedCache {
        return new TaggedCache(self::driver(), $tags, self::$prefix);
    }
}

class TaggedCache {
    private CacheDriver $driver;
    private array $tags;
    private string $prefix;

    public function __construct(CacheDriver $driver, array $tags, string $prefix) {
        $this->driver = $driver;
        $this->tags = $tags;
        $this->prefix = $prefix;
    }

    public function get(string $key, mixed $default = null): mixed {
        return $this->driver->get($this->taggedKey($key)) ?? $default;
    }

    public function set(string $key, mixed $value, int $ttl = 3600): bool {
        $taggedKey = $this->taggedKey($key);
        $this->driver->set($taggedKey, $value, $ttl);

        // Track key in each tag
        foreach ($this->tags as $tag) {
            $tagKey = $this->prefix . 'tag:' . $tag;
            $keys = $this->driver->get($tagKey) ?? [];
            $keys[$taggedKey] = true;
            $this->driver->set($tagKey, $keys, 0);
        }

        return true;
    }

    public function flush(): bool {
        foreach ($this->tags as $tag) {
            $tagKey = $this->prefix . 'tag:' . $tag;
            $keys = $this->driver->get($tagKey) ?? [];
            foreach (array_keys($keys) as $key) {
                $this->driver->delete($key);
            }
            $this->driver->delete($tagKey);
        }
        return true;
    }

    private function taggedKey(string $key): string {
        $tagHash = md5(implode('|', $this->tags));
        return $this->prefix . "tagged:{$tagHash}:{$key}";
    }
}
