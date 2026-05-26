<?php
namespace Core\Server;

class Router {
    private array $routes = [];
    private array $middleware = [];
    private array $groups = [];
    private string $prefix = '';

    public function get(string $path, callable $handler): self {
        return $this->add('GET', $path, $handler);
    }

    public function post(string $path, callable $handler): self {
        return $this->add('POST', $path, $handler);
    }

    public function put(string $path, callable $handler): self {
        return $this->add('PUT', $path, $handler);
    }

    public function patch(string $path, callable $handler): self {
        return $this->add('PATCH', $path, $handler);
    }

    public function delete(string $path, callable $handler): self {
        return $this->add('DELETE', $path, $handler);
    }

    public function options(string $path, callable $handler): self {
        return $this->add('OPTIONS', $path, $handler);
    }

    public function any(string $path, callable $handler): self {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'] as $method) {
            $this->add($method, $path, $handler);
        }
        return $this;
    }

    public function add(string $method, string $path, callable $handler): self {
        $fullPath = $this->prefix . $path;
        $pattern = $this->compilePattern($fullPath);

        $this->routes[] = [
            'method' => strtoupper($method),
            'path' => $fullPath,
            'pattern' => $pattern,
            'handler' => $handler,
            'middleware' => $this->middleware,
        ];

        return $this;
    }

    public function group(string $prefix, callable $callback): self {
        $previousPrefix = $this->prefix;
        $previousMiddleware = $this->middleware;

        $this->prefix .= $prefix;
        $callback($this);

        $this->prefix = $previousPrefix;
        $this->middleware = $previousMiddleware;

        return $this;
    }

    public function middleware(callable ...$middleware): self {
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    public function match(string $method, string $path): ?array {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $path, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return [
                    'handler' => $route['handler'],
                    'middleware' => $route['middleware'],
                    'params' => $params,
                ];
            }
        }

        return null;
    }

    public function dispatch(ServerRequest $request, ServerResponse $response): \Generator {
        $route = $this->match($request->method, $request->path);

        if (!$route) {
            $response->notFound();
            return;
        }

        // Set params as request attributes
        foreach ($route['params'] as $key => $value) {
            $request->setAttribute($key, $value);
        }

        // Run route middleware
        foreach ($route['middleware'] as $middleware) {
            $result = $middleware($request, $response, $route['params']);
            if ($result instanceof \Generator) {
                yield from $result;
            }
            if ($result === false || $response->isSent()) {
                return;
            }
        }

        // Run handler
        $result = ($route['handler'])($request, $response, $route['params']);
        if ($result instanceof \Generator) {
            yield from $result;
        }
    }

    private function compilePattern(string $path): string {
        // Convert {param} to named capture groups
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $path);
        // Convert {param?} to optional groups
        $pattern = preg_replace('/\{(\w+)\?\}/', '(?P<$1>[^/]*)?', $pattern);
        return '#^' . $pattern . '$#';
    }

    public function getRoutes(): array {
        return $this->routes;
    }
}
