#!/usr/bin/env php
<?php
// Nexph HTTP Server
// Usage: php serve.php [--mode=http] [--host=0.0.0.0] [--port=8080] [--workers=auto] [--supervisor=on] [--object-tracking=off] [--pool-safety=off] [--max-deferred=100000] [--memory-pressure=0.85] [--memory-hard-pressure=0.95] [--graceful-timeout=30] [--max-connections=auto] [--backlog=auto] [--max-requests=auto] [--rate-limit=auto] [--websocket=off] [--ws-path=/ws] [--sse=off] [--sse-path=/events] [--sse-heartbeat=15] [--sse-timeout=300] [--debug]

require_once __DIR__ . '/autoload.php';

use Core\Server\HttpServer;
use Core\Server\Router;
use Core\Server\AsyncIO;
use Core\Server\AsyncDatabase;
use Core\Server\StaticFiles;
use Core\Server\Middleware\Cors;
use Core\Server\Middleware\RateLimit;
use Core\Server\Middleware\Security;
use Core\Server\Middleware\Logger;
use Core\Support\Config;
use Core\Runtime\Loader\RuntimeLoader;

// Parse CLI args
$options = getopt('', [
    'mode::',
    'host::',
    'port::',
    'workers::',
    'worker-id::',
    'worker-count::',
    'stats-dir::',
    'supervisor::',
    'object-tracking::',
    'pool-safety::',
    'max-deferred::',
    'memory-pressure::',
    'memory-hard-pressure::',
    'graceful-timeout::',
    'max-connections::',
    'backlog::',
    'max-requests::',
    'rate-limit::',
    'websocket::',
    'ws-path::',
    'ws-presence::',
    'ws-default-room::',
    'ws-broadcast-batch::',
    'ws-backpressure::',
    'ws-backpressure-soft-limit::',
    'ws-max-frame-size::',
    'ws-max-read-buffer::',
    'ws-bus::',
    'ws-redis-url::',
    'ws-redis-channel::',
    'websocket-timeout::',
    'websocket-ping-interval::',
    'websocket-pong-timeout::',
    'sse::',
    'sse-path::',
    'sse-heartbeat::',
    'sse-timeout::',
    'debug',
]);
$mode = normalizeMode((string) ($options['mode'] ?? 'http'));
$httpEnabled = $mode === 'http' || $mode === 'all';
$host = $options['host'] ?? '0.0.0.0';
$port = (int) ($options['port'] ?? ($mode === 'ws' ? 8081 : ($mode === 'sse' ? 8082 : 8080)));
$cpuCount = detectCpuCount();
$workers = isset($options['workers']) ? max(1, (int) $options['workers']) : $cpuCount;
if (!function_exists('pcntl_fork')) {
    $workers = 1;
}
$supervisedChild = isset($options['worker-id']);
$workerId = $supervisedChild ? max(1, (int) $options['worker-id']) : 1;
$workerCount = isset($options['worker-count']) ? max(1, (int) $options['worker-count']) : $workers;
$supervisor = optionEnabled($options['supervisor'] ?? ($workers > 1 ? 'on' : 'off'));
$gracefulTimeout = max(1, (int) ($options['graceful-timeout'] ?? 30));
$statsDir = (string) ($options['stats-dir'] ?? (sys_get_temp_dir() . '/nexph-' . $mode . '-' . $port . '-' . bin2hex(random_bytes(4))));
if ($supervisor && !$supervisedChild && $workers > 1 && function_exists('pcntl_fork')) {
    runSupervisor($workers, $argv, $statsDir, $gracefulTimeout);
    exit(0);
}
$fdLimit = detectFileDescriptorLimit();
$maxConnections = isset($options['max-connections'])
    ? max(1, (int) $options['max-connections'])
    : autoMaxConnections($fdLimit, $workers);
$backlog = isset($options['backlog'])
    ? max(128, (int) $options['backlog'])
    : autoBacklog($maxConnections, $workers);
$defaultRateLimit = in_array($mode, ['ws', 'sse'], true) ? 'off' : 'auto';
$rateLimit = match ($options['rate-limit'] ?? $defaultRateLimit) {
    'off', '0', 'false', 'none' => 0,
    'auto' => autoRateLimit($maxConnections, $workers),
    default => max(0, (int) $options['rate-limit']),
};
$maxRequests = match ($options['max-requests'] ?? 'auto') {
    'off', '0', 'false', 'none' => PHP_INT_MAX,
    'auto' => autoMaxRequests(),
    default => max(1, (int) $options['max-requests']),
};
$webSocketEnabled = optionEnabled($options['websocket'] ?? ($mode === 'ws' || $mode === 'all' ? 'on' : 'off'));
$webSocketPath = normalizePath((string) ($options['ws-path'] ?? '/ws'));
$webSocketPresence = optionEnabled($options['ws-presence'] ?? 'on');
$webSocketDefaultRoom = normalizeRoomName((string) ($options['ws-default-room'] ?? 'global'));
$webSocketBroadcastBatch = max(1, (int) ($options['ws-broadcast-batch'] ?? 250));
$webSocketBackpressurePolicy = normalizeBackpressurePolicy((string) ($options['ws-backpressure'] ?? 'close'));
$webSocketBackpressureSoftLimit = max(1024, (int) ($options['ws-backpressure-soft-limit'] ?? 524288));
$webSocketMaxFrameSize = max(125, (int) ($options['ws-max-frame-size'] ?? 1048576));
$webSocketMaxReadBuffer = max($webSocketMaxFrameSize + 14, (int) ($options['ws-max-read-buffer'] ?? 2097152));
$webSocketBus = normalizeBus((string) ($options['ws-bus'] ?? 'file'));
$webSocketRedisUrl = (string) ($options['ws-redis-url'] ?? 'redis://127.0.0.1:6379/0');
$webSocketRedisChannel = (string) ($options['ws-redis-channel'] ?? 'nexph:websocket');
$webSocketTimeout = max(1, (int) ($options['websocket-timeout'] ?? 300));
$webSocketPingInterval = max(0, (int) ($options['websocket-ping-interval'] ?? 30));
$webSocketPongTimeout = max(1, (int) ($options['websocket-pong-timeout'] ?? 90));
$sseEnabled = optionEnabled($options['sse'] ?? ($mode === 'sse' || $mode === 'all' ? 'on' : 'off'));
$ssePath = normalizePath((string) ($options['sse-path'] ?? '/events'), '/events');
$sseHeartbeat = max(0, (int) ($options['sse-heartbeat'] ?? 15));
$sseTimeout = max(1, (int) ($options['sse-timeout'] ?? 300));
$maxDeferred = max(1, (int) ($options['max-deferred'] ?? 100000));
$memoryPressure = min(0.99, max(0.10, (float) ($options['memory-pressure'] ?? 0.85)));
$memoryHardPressure = min(0.999, max($memoryPressure, (float) ($options['memory-hard-pressure'] ?? 0.95)));
$objectTracking = optionEnabled($options['object-tracking'] ?? 'off');
$poolSafety = optionEnabled($options['pool-safety'] ?? 'off');
$debug = isset($options['debug']);

// Load config
Config::loadEnv(__DIR__ . '/.env');
Config::load(__DIR__ . '/config/app.php');

// Boot module loader
$runtimeLoader = null;
$modulePaths = array_filter([
    __DIR__ . '/nexph_modules',
    __DIR__ . '/packages',
], 'is_dir');
if (!empty($modulePaths)) {
    require_once __DIR__ . '/core/Runtime/Loader/Contracts/ModuleInterface.php';
    require_once __DIR__ . '/core/Runtime/Loader/Contracts/ServiceProviderInterface.php';
    require_once __DIR__ . '/core/Runtime/Loader/Contracts/RouteProviderInterface.php';
    require_once __DIR__ . '/core/Runtime/Loader/Contracts/CommandProviderInterface.php';
    require_once __DIR__ . '/core/Runtime/Loader/Contracts/HookProviderInterface.php';
    require_once __DIR__ . '/core/Runtime/Loader/Contracts/ConfigProviderInterface.php';
    require_once __DIR__ . '/core/Runtime/Loader/Contracts/PreloadableInterface.php';
    require_once __DIR__ . '/core/Runtime/Loader/Contracts/BootableInterface.php';
    require_once __DIR__ . '/core/Runtime/Loader/Contracts/ShutdownableInterface.php';
    require_once __DIR__ . '/core/Runtime/Loader/Exceptions/ModuleLoadException.php';
    require_once __DIR__ . '/core/Runtime/Loader/Exceptions/ManifestValidationException.php';
    require_once __DIR__ . '/core/Runtime/Loader/Exceptions/ModuleNotFoundException.php';
    require_once __DIR__ . '/core/Runtime/Loader/Exceptions/ModuleConflictException.php';
    require_once __DIR__ . '/core/Runtime/Loader/ManifestParser.php';
    require_once __DIR__ . '/core/Runtime/Loader/ManifestValidator.php';
    require_once __DIR__ . '/core/Runtime/Loader/ModuleManifest.php';
    require_once __DIR__ . '/core/Runtime/Loader/ModuleRegistry.php';
    require_once __DIR__ . '/core/Runtime/Loader/RuntimePreloader.php';
    require_once __DIR__ . '/core/Runtime/Loader/LazyModuleResolver.php';
    require_once __DIR__ . '/core/Runtime/Loader/RuntimeLoader.php';

    $runtimeLoader = new RuntimeLoader();
    $runtimeLoader->discover($modulePaths);
    $runtimeLoader->boot();
}

// Create server
$server = new HttpServer([
    'host' => $host,
    'port' => $port,
    'max_connections' => $maxConnections,
    'backlog' => $backlog,
    'worker_count' => $workerCount,
    'worker_id' => $workerId,
    'stats_dir' => $statsDir,
    'keep_alive_timeout' => 30,
    'max_deferred' => $maxDeferred,
    'object_tracking' => $objectTracking,
    'pool_safety' => $poolSafety,
    'memory_pressure_threshold' => $memoryPressure,
    'memory_hard_pressure_threshold' => $memoryHardPressure,
    'graceful_shutdown_timeout' => $gracefulTimeout,
    'websocket_timeout' => $webSocketTimeout,
    'websocket_ping_interval' => $webSocketPingInterval,
    'websocket_pong_timeout' => $webSocketPongTimeout,
    'max_write_buffer_size' => 1024 * 1024,
    'websocket_bus_max_bytes' => 8 * 1024 * 1024,
    'websocket_broadcast_batch_size' => $webSocketBroadcastBatch,
    'websocket_backpressure_policy' => $webSocketBackpressurePolicy,
    'websocket_backpressure_soft_limit' => $webSocketBackpressureSoftLimit,
    'websocket_max_frame_size' => $webSocketMaxFrameSize,
    'websocket_max_read_buffer_size' => $webSocketMaxReadBuffer,
    'websocket_bus' => $webSocketBus,
    'websocket_redis_url' => $webSocketRedisUrl,
    'websocket_redis_channel' => $webSocketRedisChannel,
    'sse_heartbeat_interval' => $sseHeartbeat,
    'sse_timeout' => $sseTimeout,
    'max_requests' => $maxRequests,
    'debug' => $debug,
]);

// Setup async components
AsyncIO::setLoop($server->getLoop());
if ($httpEnabled) {
    AsyncDatabase::setLoop($server->getLoop());
    if ($dbConfig = Config::get('db')) {
        AsyncDatabase::connect($dbConfig);
    } else {
        AsyncDatabase::connect(['driver' => 'sqlite', 'database' => __DIR__ . '/storage/database.sqlite']);
    }
}

// Create router
$router = new Router();

// Global middleware
$server->use(new Security());
$server->use(new Cors());
if ($rateLimit > 0) {
    $server->use(new RateLimit($rateLimit, 60));
}

if ($debug) {
    $server->use(new Logger());
}

if ($httpEnabled) {
    $staticFiles = new StaticFiles(__DIR__ . '/public');
    $server->use(function ($request, $response) use ($staticFiles) {
        return $staticFiles($request, $response);
    });
}

// API Routes
$router->group('/api', function (Router $r) use ($server, $httpEnabled, $sseEnabled, $ssePath) {
    registerRuntimeRoutes($r, $server);

    if ($httpEnabled) {
        registerHttpRoutes($r);
    }

    if ($sseEnabled) {
        registerSseRoutes($r, $server, $ssePath);
    }
});

// Root
$router->get('/', function ($request, $response) use ($mode, $httpEnabled, $webSocketEnabled, $webSocketPath, $webSocketPresence, $webSocketDefaultRoom, $webSocketBackpressurePolicy, $webSocketBus, $sseEnabled, $ssePath, $sseHeartbeat, $port) {
    $endpoints = [
        'GET /api/health',
        'GET /api/metrics',
    ];

    if ($httpEnabled) {
        array_push(
            $endpoints,
            'GET /api/ping',
            'POST /api/echo',
            'GET /api/async',
            'GET /api/users',
            'GET /api/users/{id}',
            'POST /api/users',
        );
    }

    if ($webSocketEnabled) {
        $endpoints[] = 'WS ' . $webSocketPath;
    }

    if ($sseEnabled) {
        $endpoints[] = 'GET ' . $ssePath;
        $endpoints[] = 'POST /api/sse/publish';
    }

    $response->json([
        'name' => 'Nexph Server',
        'version' => '1.0.0',
        'mode' => $mode,
        'websocket' => $webSocketEnabled ? [
            'enabled' => true,
            'path' => $webSocketPath,
            'url' => "ws://localhost:{$port}{$webSocketPath}",
            'presence' => $webSocketPresence,
            'default_room' => $webSocketDefaultRoom,
            'backpressure' => $webSocketBackpressurePolicy,
            'bus' => $webSocketBus,
        ] : ['enabled' => false],
        'sse' => $sseEnabled ? [
            'enabled' => true,
            'path' => $ssePath,
            'url' => "http://localhost:{$port}{$ssePath}",
            'heartbeat' => $sseHeartbeat,
        ] : ['enabled' => false],
        'endpoints' => $endpoints,
    ]);
});

if ($sseEnabled) {
    $router->get($ssePath, function ($request, $response) use ($server) {
        $conn = $request->getConnection();
        $server->startSse($request, $response, $conn);
        $durationMs = max(0, (int) ($request->query('duration') ?? 0));
        if ($durationMs > 0) {
            $server->getLoop()->addTimer($durationMs / 1000, function () use ($server, $conn) {
                $server->closeSse($conn);
            });
        }
    });
}

// 404 fallback
$router->any('/{path}', function ($request, $response) {
    $response->notFound("Route not found: {$request->method} {$request->path}");
});

// Set request handler
$server->onRequest(function ($request, $response) use ($router) {
    return $router->dispatch($request, $response);
});

if ($webSocketEnabled) {
    $server->onWebSocket($webSocketPath, function ($conn, string $message, HttpServer $server) use ($webSocketPath, $webSocketDefaultRoom) {
        $incoming = json_decode($message, true);
        $isJson = is_array($incoming);
        $type = $isJson ? (string) ($incoming['type'] ?? 'message') : 'message';
        $room = normalizeRoomName($isJson ? (string) ($incoming['room'] ?? $webSocketDefaultRoom) : $webSocketDefaultRoom);

        if ($type === 'join') {
            $server->joinWebSocketRoom($conn, $room);
            $server->sendWebSocket($conn, json_encode(['type' => 'joined', 'room' => $room, 'time' => microtime(true)]));
            return;
        }

        if ($type === 'leave') {
            $server->leaveWebSocketRoom($conn, $room);
            $server->sendWebSocket($conn, json_encode(['type' => 'left', 'room' => $room, 'time' => microtime(true)]));
            return;
        }

        $data = $isJson && array_key_exists('data', $incoming) ? $incoming['data'] : $message;
        $payload = json_encode([
            'type' => 'message',
            'from' => $conn->getId(),
            'room' => $room,
            'data' => $data,
            'time' => microtime(true),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($payload !== false) {
            $server->sendWebSocket($conn, $payload);
            $server->broadcastWebSocketRoom($webSocketPath, $room, $payload, $conn);
        }
    }, function ($conn, HttpServer $server) use ($webSocketPath, $webSocketPresence, $webSocketDefaultRoom) {
        $server->joinWebSocketRoom($conn, $webSocketDefaultRoom);
        $server->sendWebSocket($conn, json_encode(['type' => 'hello', 'id' => $conn->getId(), 'room' => $webSocketDefaultRoom]));
        if (!$webSocketPresence) {
            return;
        }
        $payload = json_encode(['type' => 'join', 'id' => $conn->getId(), 'room' => $webSocketDefaultRoom, 'time' => microtime(true)]);
        if ($payload !== false) {
            $server->broadcastWebSocketRoom($webSocketPath, $webSocketDefaultRoom, $payload, $conn);
        }
    }, function ($conn, HttpServer $server) use ($webSocketPath, $webSocketPresence, $webSocketDefaultRoom) {
        if (!$webSocketPresence) {
            return;
        }
        $payload = json_encode(['type' => 'leave', 'id' => $conn->getId(), 'room' => $webSocketDefaultRoom, 'time' => microtime(true)]);
        if ($payload !== false) {
            $server->broadcastWebSocketRoom($webSocketPath, $webSocketDefaultRoom, $payload, $conn);
        }
    });
}

// Multi-worker support
if (!$supervisedChild && $workers > 1 && function_exists('pcntl_fork')) {
    echo "Starting {$workers} workers...\n";

    for ($i = 1; $i < $workers; $i++) {
        $pid = pcntl_fork();
        if ($pid === 0) {
            $workerId = $i + 1;
            break;
        }
    }
}

// Start server
$server->setWorkerInfo($workerId, $workerCount);
$server->start();

function runSupervisor(int $workers, array $argv, string $statsDir, int $gracefulTimeout): void {
    $children = [];
    $stopping = false;
    $deadline = 0.0;

    $spawn = function (int $workerId) use (&$children, $argv, $workers, $statsDir): void {
        $pid = pcntl_fork();
        if ($pid === -1) {
            return;
        }
        if ($pid === 0) {
            $args = buildWorkerArgs($argv, $workerId, $workers, $statsDir);
            pcntl_exec(PHP_BINARY, $args);
            exit(1);
        }
        $children[$pid] = $workerId;
    };

    for ($i = 1; $i <= $workers; $i++) {
        $spawn($i);
    }

    pcntl_async_signals(true);
    pcntl_signal(SIGTERM, function () use (&$stopping, &$deadline, &$children, $gracefulTimeout): void {
        $stopping = true;
        $deadline = microtime(true) + $gracefulTimeout;
        foreach (array_keys($children) as $pid) {
            @posix_kill($pid, defined('SIGUSR1') ? SIGUSR1 : SIGTERM);
        }
    });
    pcntl_signal(SIGINT, function () use (&$stopping, &$deadline, &$children, $gracefulTimeout): void {
        $stopping = true;
        $deadline = microtime(true) + $gracefulTimeout;
        foreach (array_keys($children) as $pid) {
            @posix_kill($pid, defined('SIGUSR1') ? SIGUSR1 : SIGTERM);
        }
    });
    if (defined('SIGUSR2')) {
        pcntl_signal(SIGUSR2, function () use (&$children, $spawn): void {
            $old = $children;
            foreach ($old as $workerId) {
                $spawn($workerId);
            }
            foreach (array_keys($old) as $pid) {
                @posix_kill($pid, defined('SIGUSR1') ? SIGUSR1 : SIGTERM);
            }
        });
    }

    echo "Supervisor started {$workers} workers\n";
    while ($children !== []) {
        while (($pid = pcntl_waitpid(-1, $status, WNOHANG)) > 0) {
            $workerId = $children[$pid] ?? null;
            unset($children[$pid]);
            if (!$stopping && $workerId !== null) {
                $spawn($workerId);
            }
        }

        if ($stopping && $deadline > 0 && microtime(true) >= $deadline) {
            foreach (array_keys($children) as $pid) {
                @posix_kill($pid, SIGTERM);
            }
            $deadline = 0.0;
        }

        usleep(100000);
    }
}

function buildWorkerArgs(array $argv, int $workerId, int $workerCount, string $statsDir): array {
    $args = [$argv[0] ?? __FILE__];
    $skipNext = false;
    $strip = [
        'workers',
        'worker-id',
        'worker-count',
        'stats-dir',
        'supervisor',
    ];

    for ($i = 1, $count = count($argv); $i < $count; $i++) {
        if ($skipNext) {
            $skipNext = false;
            continue;
        }

        $arg = (string) $argv[$i];
        if (!str_starts_with($arg, '--')) {
            $args[] = $arg;
            continue;
        }

        $name = substr($arg, 2);
        $hasValue = str_contains($name, '=');
        $name = $hasValue ? strstr($name, '=', true) : $name;
        if (in_array($name, $strip, true)) {
            if (!$hasValue && isset($argv[$i + 1]) && !str_starts_with((string) $argv[$i + 1], '--')) {
                $skipNext = true;
            }
            continue;
        }

        $args[] = $arg;
    }

    $args[] = '--workers=1';
    $args[] = '--supervisor=off';
    $args[] = '--worker-id=' . $workerId;
    $args[] = '--worker-count=' . $workerCount;
    $args[] = '--stats-dir=' . $statsDir;
    return $args;
}

function detectCpuCount(): int {
    $count = (int) trim((string) @shell_exec('nproc 2>/dev/null'));
    if ($count > 0) {
        return $count;
    }

    $count = (int) trim((string) @shell_exec('getconf _NPROCESSORS_ONLN 2>/dev/null'));
    return $count > 0 ? $count : 1;
}

function detectFileDescriptorLimit(): int {
    $limit = trim((string) @shell_exec('sh -c "ulimit -n" 2>/dev/null'));
    if ($limit === 'unlimited') {
        return 1048576;
    }

    $value = (int) $limit;
    return $value > 0 ? $value : 1024;
}

function detectSomaxconn(): int {
    $path = '/proc/sys/net/core/somaxconn';
    if (is_readable($path)) {
        $value = (int) trim((string) file_get_contents($path));
        if ($value > 0) {
            return $value;
        }
    }

    return 4096;
}

function autoMaxConnections(int $fdLimit, int $workers): int {
    $usable = max(128, $fdLimit - 256);
    $perWorker = (int) floor(($usable * 0.70) / max(1, $workers));
    return max(1000, min(5000, $perWorker));
}

function autoBacklog(int $maxConnections, int $workers): int {
    $desired = max(4096, min(10000, $maxConnections * max(1, $workers)));
    return min($desired, detectSomaxconn());
}

function autoRateLimit(int $maxConnections, int $workers): int {
    return max(1000, $maxConnections * max(1, $workers) * 100);
}

function autoMaxRequests(): int {
    return 10000;
}

function registerRuntimeRoutes(Router $r, HttpServer $server): void {
    $r->get('/health', function ($request, $response) use ($server) {
        $response->json([
            'status' => 'ok',
            'timestamp' => date('c'),
            'stats' => $server->getStats(),
            'database' => AsyncDatabase::stats(),
        ]);
    });

    $r->get('/metrics', function ($request, $response) use ($server) {
        $db = AsyncDatabase::stats();
        $metrics = $server->getMetricsText()
            . '# HELP nexph_database_queries_total Total database queries.' . "\n"
            . '# TYPE nexph_database_queries_total counter' . "\n"
            . 'nexph_database_queries_total ' . (int) $db['queries'] . "\n"
            . '# HELP nexph_database_errors_total Total database errors.' . "\n"
            . '# TYPE nexph_database_errors_total counter' . "\n"
            . 'nexph_database_errors_total ' . (int) $db['errors'] . "\n"
            . '# HELP nexph_database_query_avg_ms Average database query duration.' . "\n"
            . '# TYPE nexph_database_query_avg_ms gauge' . "\n"
            . 'nexph_database_query_avg_ms ' . sprintf('%.6F', (float) $db['avg_ms']) . "\n"
            . '# HELP nexph_database_pool_idle Idle database connections.' . "\n"
            . '# TYPE nexph_database_pool_idle gauge' . "\n"
            . 'nexph_database_pool_idle ' . (int) $db['pool']['idle'] . "\n";

        $response
            ->header('Content-Type', 'text/plain; version=0.0.4; charset=utf-8')
            ->body($metrics);
    });
}

function registerHttpRoutes(Router $r): void {
    $r->get('/ping', function ($request, $response) {
        $response->json(['pong' => true, 'time' => microtime(true)]);
    });

    $r->post('/echo', function ($request, $response) {
        $response->json([
            'method' => $request->method,
            'path' => $request->path,
            'query' => $request->query,
            'body' => $request->parsedBody,
            'headers' => $request->headers,
        ]);
    });

    $r->get('/async', function ($request, $response) {
        return (function () use ($response) {
            yield from AsyncIO::sleep(0.1);

            $response->json([
                'message' => 'Async response',
                'time' => microtime(true),
            ]);
        })();
    });

    $usersCache = ['body' => null, 'expires' => 0.0];
    $r->get('/users', function ($request, $response) use (&$usersCache) {
        return (function () use ($request, $response, &$usersCache) {
            try {
                $limit = (int) ($request->query('limit') ?? 10);
                $offset = (int) ($request->query('offset') ?? 0);
                $now = microtime(true);

                $cachedBody = is_array($usersCache) ? ($usersCache['body'] ?? null) : null;
                $cachedExpires = is_array($usersCache) ? (float) ($usersCache['expires'] ?? 0.0) : 0.0;

                if ($limit === 10 && $offset === 0 && $cachedBody !== null && $cachedExpires > $now) {
                    $response->header('Content-Type', 'application/json')->body($cachedBody);
                    return;
                }

                $users = yield from AsyncDatabase::query(
                    "SELECT id, username, role, created_at, updated_at FROM users LIMIT ? OFFSET ?",
                    [$limit, $offset]
                );

                if (isset($users['error'])) {
                    $response->json(['error' => $users['error']], 500);
                    return;
                }

                if ($limit === 10 && $offset === 0) {
                    $body = json_encode(['data' => $users], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                    if ($body !== false) {
                        $usersCache = ['body' => $body, 'expires' => $now + 1.0];
                        $response->header('Content-Type', 'application/json')->body($body);
                        return;
                    }
                }

                $response->json(['data' => $users]);
            } catch (\Throwable $e) {
                $response->json(['error' => $e->getMessage()], 500);
            }
        })();
    });

    $r->get('/users/{id}', function ($request, $response, $params) {
        return (function () use ($response, $params) {
            $user = yield from AsyncDatabase::query(
                "SELECT * FROM users WHERE id = ?",
                [$params['id']]
            );

            if (empty($user) || isset($user['error'])) {
                $response->notFound('User not found');
                return;
            }

            $response->json(['data' => $user[0]]);
        })();
    });

    $r->post('/users', function ($request, $response) {
        return (function () use ($request, $response) {
            $data = $request->parsedBody;

            if (empty($data['email'])) {
                $response->json(['error' => 'Email required'], 400);
                return;
            }

            $id = yield from AsyncDatabase::insert('users', [
                'email' => $data['email'],
                'name' => $data['name'] ?? '',
                'created_at' => date('Y-m-d H:i:s'),
            ]);

            $response->json(['id' => $id, 'message' => 'Created'], 201);
        })();
    });
}

function registerSseRoutes(Router $r, HttpServer $server, string $ssePath): void {
    $r->post('/sse/publish', function ($request, $response) use ($server, $ssePath) {
        $body = $request->json();
        $data = $body['data'] ?? $request->input('data', '');
        $event = (string) ($body['event'] ?? $request->input('event', 'message'));
        $channel = normalizeRoomName((string) ($body['channel'] ?? $request->input('channel', 'global')));
        $sent = $server->broadcastSse($ssePath, $data, $event, true, $channel);
        $response->json([
            'sent' => $sent,
            'queued' => true,
            'event' => $event,
            'channel' => $channel,
            'time' => microtime(true),
        ]);
    });
}

function optionEnabled(string|bool|int|null $value): bool {
    if (is_bool($value)) {
        return $value;
    }

    return !in_array(strtolower((string) $value), ['off', '0', 'false', 'no', 'none'], true);
}

function normalizePath(string $path, string $default = '/ws'): string {
    $path = trim($path);
    if ($path === '') {
        return $default;
    }

    $path = '/' . ltrim($path, '/');
    return preg_match('#^/[A-Za-z0-9/_-]+$#', $path) ? rtrim($path, '/') ?: $default : $default;
}

function normalizeRoomName(string $room): string {
    $room = trim($room);
    if ($room === '') {
        return 'global';
    }

    $room = preg_replace('/[^A-Za-z0-9:._-]/', '-', $room) ?? 'global';
    return substr($room, 0, 96) ?: 'global';
}

function normalizeBackpressurePolicy(string $policy): string {
    $policy = strtolower(trim($policy));
    return in_array($policy, ['close', 'skip'], true) ? $policy : 'close';
}

function normalizeBus(string $bus): string {
    $bus = strtolower(trim($bus));
    return in_array($bus, ['file', 'redis', 'off'], true) ? $bus : 'file';
}

function normalizeMode(string $mode): string {
    $mode = strtolower(trim($mode));
    return in_array($mode, ['http', 'ws', 'sse', 'all'], true) ? $mode : 'http';
}
