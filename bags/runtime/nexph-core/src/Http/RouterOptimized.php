<?php
/**
 * Router with compiled route caching
 */
namespace Core\Http;

class RouterOptimized {
    private array $routes = [];
    private static ?array $compiledRoutes = null;
    private static string $cacheFile = '';

    public function __construct() {
        self::$cacheFile = sys_get_temp_dir() . '/nexph_routes_' . md5(__DIR__) . '.php';
    }

    public function add(string $method, string $path, callable|array $handler, array $middleware = []): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $path,
            'handler' => $handler,
            'middleware' => $middleware
        ];
    }

    public function dispatch(Request $request, Response $response): void {
        $method = $request->method();
        $uri = $request->uri();
        
        $compiled = $this->compileRoutes();
        
        foreach ($compiled as $idx => $route) {
            if ($route['method'] === $method && preg_match($route['pattern'], $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // Execute Middleware
                foreach ($route['middleware'] as $mw) {
                    $mw($request, $response, $params);
                }

                $this->execute($route['handler'], $request, $response, $params);
                return;
            }
        }
        $response->json(['error' => 'Not Found'], 404);
    }

    private function compileRoutes(): array {
        // Memory cache
        if (self::$compiledRoutes !== null) {
            return self::$compiledRoutes;
        }
        
        // File cache (only in production)
        if (($_ENV['APP_ENV'] ?? 'production') === 'production' && file_exists(self::$cacheFile)) {
            return self::$compiledRoutes = require self::$cacheFile;
        }
        
        $compiled = [];
        foreach ($this->routes as $route) {
            $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = "#^" . $pattern . "$#";
            
            $compiled[] = [
                'method' => $route['method'],
                'pattern' => $pattern,
                'handler' => $route['handler'],
                'middleware' => $route['middleware']
            ];
        }
        
        // Save to file cache in production
        if (($_ENV['APP_ENV'] ?? 'production') === 'production') {
            $export = var_export($compiled, true);
            file_put_contents(self::$cacheFile, "<?php return {$export};");
        }
        
        return self::$compiledRoutes = $compiled;
    }

    private function execute(callable|array $handler, Request $request, Response $response, array $params = []): void {
        if (is_callable($handler)) {
            $handler($request, $response, $params);
            return;
        }
        [$class, $method] = $handler;
        (new $class)->$method($request, $response, $params);
    }

    public static function clearCache(): void {
        self::$compiledRoutes = null;
        if (file_exists(self::$cacheFile)) {
            unlink(self::$cacheFile);
        }
    }
}
