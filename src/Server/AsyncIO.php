<?php
namespace Core\Server;

class AsyncIO {
    private static ?EventLoop $loop = null;

    public static function setLoop(EventLoop $loop): void {
        self::$loop = $loop;
    }

    public static function readFile(string $path): \Generator {
        $deferred = new Deferred();

        if (!file_exists($path)) {
            $deferred->resolve(null);
            return yield $deferred;
        }

        // Non-blocking file read simulation
        self::$loop?->defer(function () use ($path, $deferred) {
            $content = @file_get_contents($path);
            $deferred->resolve($content !== false ? $content : null);
        });

        return yield $deferred;
    }

    public static function writeFile(string $path, string $content): \Generator {
        $deferred = new Deferred();

        self::$loop?->defer(function () use ($path, $content, $deferred) {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $result = @file_put_contents($path, $content, LOCK_EX);
            $deferred->resolve($result !== false);
        });

        return yield $deferred;
    }

    public static function httpRequest(string $method, string $url, array $options = []): \Generator {
        $deferred = new Deferred();

        self::$loop?->defer(function () use ($method, $url, $options, $deferred) {
            $context = stream_context_create([
                'http' => [
                    'method' => $method,
                    'header' => self::buildHeaders($options['headers'] ?? []),
                    'content' => $options['body'] ?? '',
                    'timeout' => $options['timeout'] ?? 30,
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => $options['verify_ssl'] ?? true,
                ],
            ]);

            $response = @file_get_contents($url, false, $context);
            $headers = $http_response_header ?? [];

            $status = 0;
            if (!empty($headers) && preg_match('/HTTP\/\d\.\d\s+(\d+)/', $headers[0], $m)) {
                $status = (int) $m[1];
            }

            $deferred->resolve([
                'status' => $status,
                'headers' => self::parseHeaders($headers),
                'body' => $response !== false ? $response : '',
            ]);
        });

        return yield $deferred;
    }

    public static function tcpConnect(string $host, int $port, float $timeout = 5.0): \Generator {
        $deferred = new Deferred();

        $socket = @stream_socket_client(
            "tcp://{$host}:{$port}",
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_ASYNC_CONNECT | STREAM_CLIENT_CONNECT
        );

        if (!$socket) {
            $deferred->resolve(null);
            return yield $deferred;
        }

        stream_set_blocking($socket, false);

        self::$loop?->addWriter($socket, function ($s) use ($deferred, $socket) {
            self::$loop?->removeWriter($socket);
            $deferred->resolve($socket);
        });

        return yield $deferred;
    }

    public static function sleep(float $seconds): \Generator {
        return yield Coroutine::sleep($seconds);
    }

    private static function buildHeaders(array $headers): string {
        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = "{$name}: {$value}";
        }
        return implode("\r\n", $lines);
    }

    private static function parseHeaders(array $raw): array {
        $headers = [];
        foreach ($raw as $line) {
            if (strpos($line, ':') !== false) {
                [$name, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($name))] = trim($value);
            }
        }
        return $headers;
    }
}

class AsyncDatabase {
    private static ?EventLoop $loop = null;
    private static array $config = [];
    private static array $pool = [];
    private static array $statementCache = [];
    private static int $maxConnections = 4;
    private static int $statementCacheSize = 128;
    private static int $busyTimeoutMs = 5000;
    private static array $stats = [
        'queries' => 0,
        'errors' => 0,
        'total_ms' => 0.0,
        'max_ms' => 0.0,
        'slow_queries' => 0,
        'pool_hits' => 0,
        'pool_misses' => 0,
        'statement_hits' => 0,
        'statement_misses' => 0,
    ];

    public static function setLoop(EventLoop $loop): void {
        self::$loop = $loop;
    }

    public static function connect(array $config): void {
        self::close();
        self::$config = $config;
        self::$maxConnections = max(1, (int) ($config['pool_size'] ?? $config['max_connections'] ?? 4));
        self::$statementCacheSize = max(0, (int) ($config['statement_cache_size'] ?? 128));
        self::$busyTimeoutMs = max(1, (int) ($config['busy_timeout_ms'] ?? 5000));
        self::release(self::createConnection());
    }

    public static function query(string $sql, array $params = []): \Generator {
        $deferred = new Deferred();

        if (!self::$config) {
            $deferred->resolve(['error' => 'Database not connected']);
            return yield $deferred;
        }

        $conn = null;
        $start = microtime(true);
        try {
            $conn = self::acquire();
            $stmt = self::prepare($conn, $sql);
            $stmt->execute($params);

            if (self::returnsRows($sql)) {
                $deferred->resolve($stmt->fetchAll());
            } else {
                $deferred->resolve($stmt->rowCount());
            }
        } catch (\Throwable $e) {
            self::$stats['errors']++;
            $deferred->resolve(['error' => $e->getMessage()]);
        } finally {
            if ($conn) {
                self::release($conn);
            }
            self::recordQuery((microtime(true) - $start) * 1000);
        }

        return yield $deferred;
    }

    public static function insert(string $table, array $data): \Generator {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";

        $deferred = new Deferred();
        if (!self::$config) {
            $deferred->resolve(['error' => 'Database not connected']);
            return yield $deferred;
        }

        $conn = null;
        $start = microtime(true);
        try {
            $conn = self::acquire();
            $stmt = self::prepare($conn, $sql);
            $stmt->execute(array_values($data));
            $deferred->resolve($conn->lastInsertId());
        } catch (\Throwable $e) {
            self::$stats['errors']++;
            $deferred->resolve(['error' => $e->getMessage()]);
        } finally {
            if ($conn) {
                self::release($conn);
            }
            self::recordQuery((microtime(true) - $start) * 1000);
        }

        return yield $deferred;
    }

    public static function update(string $table, array $data, string $where, array $whereParams = []): \Generator {
        $set = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $sql = "UPDATE {$table} SET {$set} WHERE {$where}";
        return yield from self::query($sql, array_merge(array_values($data), $whereParams));
    }

    public static function delete(string $table, string $where, array $params = []): \Generator {
        $sql = "DELETE FROM {$table} WHERE {$where}";
        return yield from self::query($sql, $params);
    }

    public static function stats(): array {
        $idle = count(self::$pool);
        return [
            'connected' => self::$config !== [],
            'driver' => self::$config['driver'] ?? null,
            'pool' => [
                'idle' => $idle,
                'max' => self::$maxConnections,
            ],
            'statements' => [
                'cached' => array_sum(array_map('count', self::$statementCache)),
                'max' => self::$statementCacheSize,
            ],
            'queries' => self::$stats['queries'],
            'errors' => self::$stats['errors'],
            'avg_ms' => self::$stats['queries'] > 0 ? self::$stats['total_ms'] / self::$stats['queries'] : 0.0,
            'max_ms' => self::$stats['max_ms'],
            'slow_queries' => self::$stats['slow_queries'],
            'pool_hits' => self::$stats['pool_hits'],
            'pool_misses' => self::$stats['pool_misses'],
            'statement_hits' => self::$stats['statement_hits'],
            'statement_misses' => self::$stats['statement_misses'],
        ];
    }

    public static function close(): void {
        self::$pool = [];
        self::$statementCache = [];
    }

    private static function acquire(): \PDO {
        $conn = array_pop(self::$pool);
        if ($conn instanceof \PDO) {
            self::$stats['pool_hits']++;
            return $conn;
        }

        self::$stats['pool_misses']++;
        return self::createConnection();
    }

    private static function release(\PDO $pdo): void {
        if (count(self::$pool) < self::$maxConnections) {
            self::$pool[] = $pdo;
        }
    }

    private static function createConnection(): \PDO {
        $config = self::$config;
        $driver = $config['driver'] ?? 'sqlite';

        if ($driver === 'sqlite') {
            $dsn = "sqlite:{$config['database']}";
            $pdo = new \PDO($dsn, null, null, self::pdoOptions($config, false));
            self::configureSqlite($pdo);
            return $pdo;
        }

        $dsn = "{$driver}:host={$config['host']};dbname={$config['database']};charset=utf8mb4";
        return new \PDO($dsn, $config['username'] ?? null, $config['password'] ?? null, self::pdoOptions($config, true));
    }

    private static function pdoOptions(array $config, bool $emulatePrepares): array {
        return [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => $config['emulate_prepares'] ?? $emulatePrepares,
            \PDO::ATTR_PERSISTENT => $config['persistent'] ?? true,
        ];
    }

    private static function configureSqlite(\PDO $pdo): void {
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA synchronous = NORMAL');
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = ' . self::$busyTimeoutMs);
    }

    private static function prepare(\PDO $pdo, string $sql): \PDOStatement {
        $id = (string) spl_object_id($pdo);
        self::$statementCache[$id] ??= [];
        $key = sha1($sql);

        if (isset(self::$statementCache[$id][$key])) {
            self::$stats['statement_hits']++;
            return self::$statementCache[$id][$key];
        }

        self::$stats['statement_misses']++;
        $stmt = $pdo->prepare($sql);
        if (self::$statementCacheSize > 0) {
            if (count(self::$statementCache[$id]) >= self::$statementCacheSize) {
                array_shift(self::$statementCache[$id]);
            }
            self::$statementCache[$id][$key] = $stmt;
        }
        return $stmt;
    }

    private static function returnsRows(string $sql): bool {
        $verb = strtoupper(strtok(ltrim($sql), " \t\r\n(") ?: '');
        return in_array($verb, ['SELECT', 'PRAGMA', 'WITH', 'EXPLAIN'], true);
    }

    private static function recordQuery(float $durationMs): void {
        self::$stats['queries']++;
        self::$stats['total_ms'] += $durationMs;
        self::$stats['max_ms'] = max(self::$stats['max_ms'], $durationMs);
        if ($durationMs > 100) {
            self::$stats['slow_queries']++;
        }
    }
}
