# Nexph HTTP Server

Native PHP HTTP server dengan event loop, coroutines, dan async I/O.

## Quick Start

```bash
# Start server
php serve.php

# With options
php serve.php --host=0.0.0.0 --port=8080 --debug

# Multi-worker (requires pcntl)
php serve.php --workers=4
```

## Architecture

```
core/Server/
├── EventLoop.php       # Non-blocking event loop (stream_select)
├── Coroutine.php       # Generator-based coroutines
├── HttpParser.php      # HTTP/1.1 request/response parser
├── Connection.php      # Client connection handler
├── ServerRequest.php   # Request object
├── ServerResponse.php  # Response object (in ServerRequest.php)
├── HttpServer.php      # Main server class
├── Router.php          # Async-compatible router
├── AsyncIO.php         # Async file, HTTP, database operations
├── StaticFiles.php     # Static file serving + WebSocket
└── Middleware/
    └── Middleware.php  # Cors, RateLimit, Security, Logger, etc.
```

## Features

### Event Loop
- Non-blocking I/O via `stream_select()`
- Timer support (one-shot and periodic)
- Signal handling (SIGINT, SIGTERM)
- Deferred execution

### Coroutines
- Generator-based async/await pattern
- `yield from` for async operations
- `Coroutine::sleep()` for non-blocking delays
- `Coroutine::all()` for parallel execution

### HTTP Server
- Keep-alive connections
- Request size limits
- Connection timeouts
- Memory leak detection
- Graceful shutdown

### Middleware
- `Cors` — Cross-origin resource sharing
- `RateLimit` — In-memory rate limiting
- `Security` — Security headers
- `Logger` — Request logging
- `BodyParser` — Request body size limit
- `Timeout` — Request timeout

### Async I/O
- `AsyncIO::readFile()` — Non-blocking file read
- `AsyncIO::writeFile()` — Non-blocking file write
- `AsyncIO::httpRequest()` — Async HTTP client
- `AsyncIO::sleep()` — Non-blocking delay
- `AsyncDatabase::query()` — Async database queries

### Static Files
- ETag support
- Last-Modified caching
- MIME type detection
- Directory index (index.html)

### WebSocket
- Handshake support
- Frame encode/decode
- Ready for real-time features

## Usage Examples

### Basic Route
```php
$router->get('/hello', function ($request, $response) {
    $response->json(['message' => 'Hello World']);
});
```

### Async Route
```php
$router->get('/async', function ($request, $response) {
    return (function () use ($response) {
        yield from AsyncIO::sleep(1.0);
        $response->json(['delayed' => true]);
    })();
});
```

### Database Query
```php
$router->get('/users', function ($request, $response) {
    return (function () use ($response) {
        $users = yield from AsyncDatabase::query("SELECT * FROM users");
        $response->json(['data' => $users]);
    })();
});
```

### Parallel Requests
```php
$router->get('/parallel', function ($request, $response) {
    return (function () use ($response) {
        $results = yield Coroutine::all([
            AsyncIO::httpRequest('GET', 'https://api1.example.com'),
            AsyncIO::httpRequest('GET', 'https://api2.example.com'),
        ]);
        $response->json(['results' => $results]);
    })();
});
```

### Middleware
```php
// Global
$server->use(new Cors());
$server->use(new RateLimit(100, 60));

// Route-specific
$router->middleware(function ($req, $res) {
    if (!$req->header('authorization')) {
        $res->json(['error' => 'Unauthorized'], 401);
    }
})->group('/api', function ($r) {
    $r->get('/protected', $handler);
});
```

### Static Files
```php
$static = new StaticFiles(__DIR__ . '/public');
$server->use(fn($req, $res) => $static($req, $res));
```

## Configuration

```php
$server = new HttpServer([
    'host' => '0.0.0.0',
    'port' => 8080,
    'max_connections' => 1000,
    'keep_alive_timeout' => 30,
    'max_requests' => 100,        // Per connection
    'max_request_size' => 10 * 1024 * 1024,
    'debug' => true,
]);
```

## API Endpoints (Default)

| Method | Path | Description |
|--------|------|-------------|
| GET | `/` | Server info |
| GET | `/api/health` | Health check + stats |
| GET | `/api/ping` | Ping/pong |
| POST | `/api/echo` | Echo request |
| GET | `/api/async` | Async example |
| GET | `/api/users` | List users |
| GET | `/api/users/{id}` | Get user |
| POST | `/api/users` | Create user |

## Performance

- Single process handles thousands of concurrent connections
- Non-blocking I/O prevents thread blocking
- Keep-alive reduces connection overhead
- Memory monitoring prevents leaks

## Comparison

| Feature | Nexph Server | Apache/Nginx + PHP-FPM |
|---------|--------------|------------------------|
| Connections | Persistent | Per-request |
| Memory | Shared | Per-process |
| Async | Native | Not supported |
| WebSocket | Supported | Requires proxy |
| Setup | Single file | Multiple services |

## Limitations

- Single-threaded (use `--workers` for multi-process)
- No true parallel execution (cooperative multitasking)
- File I/O is simulated async (deferred, not truly non-blocking)
- For heavy CPU tasks, use worker queue

## Production

For production, use with:
- systemd/supervisord for process management
- Nginx as reverse proxy (SSL termination, load balancing)
- Multiple workers for CPU utilization

```nginx
upstream nexph {
    server 127.0.0.1:8080;
    server 127.0.0.1:8081;
    server 127.0.0.1:8082;
    server 127.0.0.1:8083;
}

server {
    listen 443 ssl;
    location / {
        proxy_pass http://nexph;
        proxy_http_version 1.1;
        proxy_set_header Connection "";
    }
}
```
