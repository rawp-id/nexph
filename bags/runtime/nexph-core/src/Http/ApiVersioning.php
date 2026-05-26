<?php
namespace Core\Http;

class ApiVersioning {
    private static array $versions = [];
    private static string $default = 'v1';
    private static string $strategy = 'path'; // path, header, query

    public static function configure(array $config): void {
        self::$default = $config['default'] ?? 'v1';
        self::$strategy = $config['strategy'] ?? 'path';
    }

    public static function register(string $version, callable $routes): void {
        self::$versions[$version] = $routes;
    }

    public static function resolve(Request $request): string {
        return match (self::$strategy) {
            'header' => self::fromHeader($request),
            'query' => self::fromQuery($request),
            default => self::fromPath($request),
        };
    }

    private static function fromPath(Request $request): string {
        $uri = $request->uri();
        if (preg_match('#^/api/(v\d+)/#', $uri, $m)) {
            return $m[1];
        }
        return self::$default;
    }

    private static function fromHeader(Request $request): string {
        $header = $_SERVER['HTTP_API_VERSION'] ?? $_SERVER['HTTP_ACCEPT_VERSION'] ?? null;
        if ($header && preg_match('/v?\d+/', $header, $m)) {
            return 'v' . ltrim($m[0], 'v');
        }
        return self::$default;
    }

    private static function fromQuery(Request $request): string {
        $version = $_GET['api_version'] ?? $_GET['v'] ?? null;
        if ($version && preg_match('/v?\d+/', $version, $m)) {
            return 'v' . ltrim($m[0], 'v');
        }
        return self::$default;
    }

    public static function mount(Router $router, string $basePath = '/api'): void {
        foreach (self::$versions as $version => $routes) {
            $prefix = "{$basePath}/{$version}";
            $routes($router, $prefix);
        }
    }

    public static function middleware(): callable {
        return function (Request $request, Response $response, array $params) {
            $version = self::resolve($request);
            $request->setVersion($version);
            $response->header('X-API-Version', $version);
        };
    }

    public static function getVersions(): array {
        return array_keys(self::$versions);
    }

    public static function hasVersion(string $version): bool {
        return isset(self::$versions[$version]);
    }
}

// Versioned router helper
class VersionedRouter {
    private Router $router;
    private string $prefix;
    private string $version;

    public function __construct(Router $router, string $prefix, string $version) {
        $this->router = $router;
        $this->prefix = $prefix;
        $this->version = $version;
    }

    public function get(string $path, callable|array $handler, array $middleware = []): self {
        $this->router->add('GET', $this->prefix . $path, $handler, $middleware);
        return $this;
    }

    public function post(string $path, callable|array $handler, array $middleware = []): self {
        $this->router->add('POST', $this->prefix . $path, $handler, $middleware);
        return $this;
    }

    public function put(string $path, callable|array $handler, array $middleware = []): self {
        $this->router->add('PUT', $this->prefix . $path, $handler, $middleware);
        return $this;
    }

    public function patch(string $path, callable|array $handler, array $middleware = []): self {
        $this->router->add('PATCH', $this->prefix . $path, $handler, $middleware);
        return $this;
    }

    public function delete(string $path, callable|array $handler, array $middleware = []): self {
        $this->router->add('DELETE', $this->prefix . $path, $handler, $middleware);
        return $this;
    }

    public function group(string $prefix, callable $callback): self {
        $nested = new self($this->router, $this->prefix . $prefix, $this->version);
        $callback($nested);
        return $this;
    }
}
