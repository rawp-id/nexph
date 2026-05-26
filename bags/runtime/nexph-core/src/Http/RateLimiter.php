<?php
namespace Core\Http;

use Core\Database\DB;
use Core\Cache\CacheManager;

class RateLimiter {
    private static string $driver = 'database';
    private static array $config = [];

    public static function configure(array $config): void {
        self::$config = $config;
        self::$driver = $config['driver'] ?? 'database';
    }

    public static function check(string $key, int $maxAttempts = 60, int $window = 60): array {
        return match (self::$driver) {
            'redis' => self::checkRedis($key, $maxAttempts, $window),
            'memory' => self::checkMemory($key, $maxAttempts, $window),
            default => self::checkDatabase($key, $maxAttempts, $window),
        };
    }

    public static function attempt(string $key, int $maxAttempts = 60, int $window = 60): bool {
        $result = self::check($key, $maxAttempts, $window);
        return $result['allowed'];
    }

    public static function hit(string $key, int $window = 60): int {
        return match (self::$driver) {
            'redis' => self::hitRedis($key, $window),
            'memory' => self::hitMemory($key, $window),
            default => self::hitDatabase($key, $window),
        };
    }

    public static function remaining(string $key, int $maxAttempts = 60, int $window = 60): int {
        $result = self::check($key, $maxAttempts, $window);
        return $result['remaining'];
    }

    public static function reset(string $key): void {
        match (self::$driver) {
            'redis' => self::resetRedis($key),
            'memory' => self::resetMemory($key),
            default => self::resetDatabase($key),
        };
    }

    // Sliding window database
    private static function checkDatabase(string $key, int $max, int $window): array {
        $now = time();
        $cutoff = $now - $window;

        DB::query("DELETE FROM rate_limits WHERE timestamp < ?", [$cutoff]);

        $result = DB::query(
            "SELECT COUNT(*) as cnt, MIN(timestamp) as oldest FROM rate_limits WHERE key = ? AND timestamp > ?",
            [$key, $cutoff]
        );

        $count = (int) ($result[0]['cnt'] ?? 0);
        $oldest = (int) ($result[0]['oldest'] ?? $now);
        $remaining = max(0, $max - $count);
        $resetAt = $oldest + $window;

        return [
            'allowed' => $count < $max,
            'remaining' => $remaining,
            'reset_at' => $resetAt,
            'retry_after' => $count >= $max ? $resetAt - $now : 0,
        ];
    }

    private static function hitDatabase(string $key, int $window): int {
        $now = time();
        DB::query("INSERT INTO rate_limits (key, timestamp) VALUES (?, ?)", [$key, $now]);

        $cutoff = $now - $window;
        $result = DB::query(
            "SELECT COUNT(*) as cnt FROM rate_limits WHERE key = ? AND timestamp > ?",
            [$key, $cutoff]
        );
        return (int) ($result[0]['cnt'] ?? 1);
    }

    private static function resetDatabase(string $key): void {
        DB::query("DELETE FROM rate_limits WHERE key = ?", [$key]);
    }

    // Redis sliding window
    private static function checkRedis(string $key, int $max, int $window): array {
        $redis = self::getRedis();
        $now = microtime(true);
        $windowStart = $now - $window;
        $rkey = "ratelimit:{$key}";

        $redis->zRemRangeByScore($rkey, '-inf', $windowStart);
        $count = $redis->zCard($rkey);
        $remaining = max(0, $max - $count);

        $oldest = $redis->zRange($rkey, 0, 0, true);
        $resetAt = $oldest ? (int) (array_key_first($oldest) + $window) : time() + $window;

        return [
            'allowed' => $count < $max,
            'remaining' => $remaining,
            'reset_at' => $resetAt,
            'retry_after' => $count >= $max ? $resetAt - time() : 0,
        ];
    }

    private static function hitRedis(string $key, int $window): int {
        $redis = self::getRedis();
        $now = microtime(true);
        $rkey = "ratelimit:{$key}";

        $redis->zAdd($rkey, $now, $now . ':' . bin2hex(random_bytes(4)));
        $redis->expire($rkey, $window + 1);

        return $redis->zCard($rkey);
    }

    private static function resetRedis(string $key): void {
        self::getRedis()->del("ratelimit:{$key}");
    }

    // In-memory (single process only)
    private static array $memory = [];

    private static function checkMemory(string $key, int $max, int $window): array {
        $now = time();
        self::$memory[$key] = array_filter(
            self::$memory[$key] ?? [],
            fn($t) => $t > ($now - $window)
        );

        $count = count(self::$memory[$key]);
        $remaining = max(0, $max - $count);
        $oldest = !empty(self::$memory[$key]) ? min(self::$memory[$key]) : $now;
        $resetAt = $oldest + $window;

        return [
            'allowed' => $count < $max,
            'remaining' => $remaining,
            'reset_at' => $resetAt,
            'retry_after' => $count >= $max ? $resetAt - $now : 0,
        ];
    }

    private static function hitMemory(string $key, int $window): int {
        $now = time();
        self::$memory[$key][] = $now;
        self::$memory[$key] = array_filter(
            self::$memory[$key],
            fn($t) => $t > ($now - $window)
        );
        return count(self::$memory[$key]);
    }

    private static function resetMemory(string $key): void {
        unset(self::$memory[$key]);
    }

    private static ?\Redis $redis = null;

    private static function getRedis(): \Redis {
        if (!self::$redis) {
            self::$redis = new \Redis();
            self::$redis->connect(
                self::$config['redis']['host'] ?? '127.0.0.1',
                self::$config['redis']['port'] ?? 6379
            );
            if ($pass = self::$config['redis']['password'] ?? null) {
                self::$redis->auth($pass);
            }
        }
        return self::$redis;
    }

    // Middleware helper
    public static function middleware(int $max = 60, int $window = 60, ?string $keyResolver = null): callable {
        return function ($request, $response, $params) use ($max, $window, $keyResolver) {
            $key = $keyResolver
                ? $keyResolver($request)
                : 'ip:' . ($request->ip() ?? $_SERVER['REMOTE_ADDR'] ?? 'unknown');

            $result = self::check($key, $max, $window);

            $response->header('X-RateLimit-Limit', $max);
            $response->header('X-RateLimit-Remaining', $result['remaining']);
            $response->header('X-RateLimit-Reset', $result['reset_at']);

            if (!$result['allowed']) {
                $response->header('Retry-After', $result['retry_after']);
                $response->json(['error' => 'Too Many Requests', 'retry_after' => $result['retry_after']], 429);
                exit;
            }

            self::hit($key, $window);
        };
    }

    // Per-user rate limiting
    public static function forUser(int $userId, int $max = 100, int $window = 60): array {
        return self::check("user:{$userId}", $max, $window);
    }

    // Per-endpoint rate limiting
    public static function forEndpoint(string $method, string $path, int $max = 30, int $window = 60): array {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        return self::check("endpoint:{$method}:{$path}:{$ip}", $max, $window);
    }
}
