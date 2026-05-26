<?php
namespace Core\Database;

use PDO;
use PDOException;
use Core\Database\QueryLogger;

class DB {
    private static array $connections = [];

    public static function connect(array $config, string $name = 'default'): PDO {
        if (isset(self::$connections[$name])) return self::$connections[$name];

        try {
            $dsn = self::getDsn($config);
            $pdo = new PDO($dsn, $config['username'] ?? null, $config['password'] ?? null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);

            if ($config['driver'] === 'sqlite') {
                $pdo->exec("PRAGMA journal_mode=WAL;");
            }

            self::$connections[$name] = $pdo;
            return $pdo;
        } catch (PDOException $e) {
            die("DB Connection Error [{$name}]: " . $e->getMessage());
        }
    }

    private static function getDsn(array $config): string {
        return match ($config['driver']) {
            'sqlite' => "sqlite:" . $config['database'],
            'mysql' => "mysql:host={$config['host']};dbname={$config['database']};charset=utf8mb4",
            'pgsql' => "pgsql:host={$config['host']};dbname={$config['database']}",
            default => throw new \Exception("Unsupported DB driver: " . $config['driver']),
        };
    }

    public static function query(string $sql, array $params = [], string $connection = 'default'): array {
        if (!isset(self::$connections[$connection])) {
            throw new \Exception("Connection [{$connection}] not initialized");
        }
        $start = microtime(true);
        $stmt = self::$connections[$connection]->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        $time = microtime(true) - $start;
        QueryLogger::log($sql, $params, $time);
        return $result;
    }

    public static function execute(string $sql, array $params = [], string $connection = 'default'): bool {
        if (!isset(self::$connections[$connection])) {
            throw new \Exception("Connection [{$connection}] not initialized");
        }
        $start = microtime(true);
        $stmt = self::$connections[$connection]->prepare($sql);
        $result = $stmt->execute($params);
        $time = microtime(true) - $start;
        QueryLogger::log($sql, $params, $time);
        return $result;
    }

    public static function lastInsertId(string $connection = 'default'): string {
        if (!isset(self::$connections[$connection])) {
            throw new \Exception("Connection [{$connection}] not initialized");
        }
        return self::$connections[$connection]->lastInsertId();
    }
}
