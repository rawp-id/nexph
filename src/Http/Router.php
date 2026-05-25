<?php
namespace Core\Http;

use Core\Support\Cache;

class Router {
    private array $routes = [];
    private static ?array $compiledRoutes = null;

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
        if (self::$compiledRoutes !== null) {
            return self::$compiledRoutes;
        }
        
        $cacheKey = 'nexph:routes';
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return self::$compiledRoutes = $cached;
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
        
        Cache::set($cacheKey, $compiled, 3600);
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

    private function abort(int $code): void {
        http_response_code($code);
        echo json_encode(['error' => "Error $code"]);
        exit;
    }
}
