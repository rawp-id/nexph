<?php
namespace Core\Runtime;

use PDO;

class ConnectionPool {
    private static array $pools = [];
    private static array $config = [];
    private static int $maxConnections = 10;
    private static int $idleTimeout = 300;

    public static function configure(array $config): void {
        self::$config = $config;
        self::$maxConnections = $config['max_connections'] ?? 10;
        self::$idleTimeout = $config['idle_timeout'] ?? 300;
    }

    public static function get(string $name = 'default'): PDO {
        self::cleanup($name);

        if (!isset(self::$pools[$name])) {
            self::$pools[$name] = [];
        }

        // Find available connection
        foreach (self::$pools[$name] as $i => &$entry) {
            if (!$entry['in_use']) {
                $entry['in_use'] = true;
                $entry['last_used'] = time();
                return $entry['connection'];
            }
        }

        // Create new if under limit
        if (count(self::$pools[$name]) < self::$maxConnections) {
            $pdo = self::createConnection($name);
            self::$pools[$name][] = [
                'connection' => $pdo,
                'in_use' => true,
                'created' => time(),
                'last_used' => time(),
            ];
            return $pdo;
        }

        throw new \RuntimeException("Connection pool exhausted for '{$name}'");
    }

    public static function release(PDO $connection, string $name = 'default'): void {
        if (!isset(self::$pools[$name])) return;

        foreach (self::$pools[$name] as &$entry) {
            if ($entry['connection'] === $connection) {
                $entry['in_use'] = false;
                $entry['last_used'] = time();
                return;
            }
        }
    }

    private static function createConnection(string $name): PDO {
        $cfg = self::$config['connections'][$name] ?? self::$config;
        $driver = $cfg['driver'] ?? 'sqlite';

        if ($driver === 'sqlite') {
            $dsn = "sqlite:{$cfg['database']}";
            $pdo = new PDO($dsn, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => true,
            ]);
        } else {
            $dsn = "{$driver}:host={$cfg['host']};dbname={$cfg['database']};charset=utf8mb4";
            $pdo = new PDO($dsn, $cfg['username'] ?? null, $cfg['password'] ?? null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return $pdo;
    }

    private static function cleanup(string $name): void {
        if (!isset(self::$pools[$name])) return;

        $now = time();
        self::$pools[$name] = array_filter(self::$pools[$name], function ($entry) use ($now) {
            if ($entry['in_use']) return true;
            return ($now - $entry['last_used']) < self::$idleTimeout;
        });
    }

    public static function stats(string $name = 'default'): array {
        if (!isset(self::$pools[$name])) {
            return ['total' => 0, 'in_use' => 0, 'idle' => 0];
        }
        $total = count(self::$pools[$name]);
        $inUse = count(array_filter(self::$pools[$name], fn($e) => $e['in_use']));
        return [
            'total' => $total,
            'in_use' => $inUse,
            'idle' => $total - $inUse,
            'max' => self::$maxConnections,
        ];
    }

    public static function closeAll(): void {
        self::$pools = [];
    }
}
