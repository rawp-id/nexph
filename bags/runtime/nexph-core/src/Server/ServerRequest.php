<?php
namespace Core\Server;

class ServerRequest {
    public readonly string $method;
    public readonly string $uri;
    public readonly string $path;
    public readonly string $queryString;
    public readonly array $query;
    public readonly array $headers;
    public readonly string $body;
    public readonly array $parsedBody;
    public readonly array $cookies;
    public readonly string $remoteAddr;
    public readonly int $remotePort;
    public readonly float $time;

    private Connection $connection;
    private array $attributes = [];

    public function __construct(array $parsed, Connection $conn) {
        $this->connection = $conn;
        $this->method = $parsed['method'];
        $this->uri = $parsed['uri'];
        $this->path = $parsed['path'];
        $this->queryString = $parsed['query_string'];
        $this->query = $parsed['query'];
        $this->headers = $parsed['headers'];
        $this->body = $parsed['body'];
        $this->parsedBody = $parsed['parsed_body'];
        $this->cookies = $parsed['cookies'];
        $this->remoteAddr = $conn->getRemoteAddr();
        $this->remotePort = $conn->getRemotePort();
        $this->time = microtime(true);
    }

    public function header(string $name, ?string $default = null): ?string {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function query(string $name, mixed $default = null): mixed {
        return $this->query[$name] ?? $default;
    }

    public function post(string $name, mixed $default = null): mixed {
        return $this->parsedBody[$name] ?? $default;
    }

    public function cookie(string $name, ?string $default = null): ?string {
        return $this->cookies[$name] ?? $default;
    }

    public function input(string $name, mixed $default = null): mixed {
        return $this->parsedBody[$name] ?? $this->query[$name] ?? $default;
    }

    public function all(): array {
        return array_merge($this->query, $this->parsedBody);
    }

    public function json(): array {
        return $this->parsedBody;
    }

    public function isJson(): bool {
        return str_contains($this->header('content-type', ''), 'application/json');
    }

    public function isMethod(string $method): bool {
        return strcasecmp($this->method, $method) === 0;
    }

    public function setAttribute(string $name, mixed $value): void {
        $this->attributes[$name] = $value;
    }

    public function getAttribute(string $name, mixed $default = null): mixed {
        return $this->attributes[$name] ?? $default;
    }

    public function getAttributes(): array {
        return $this->attributes;
    }

    public function getConnection(): Connection {
        return $this->connection;
    }

    public function wantsKeepAlive(): bool {
        $connection = strtolower($this->header('connection', 'keep-alive'));
        return $connection !== 'close';
    }
}

class ServerResponse implements Resettable, Cleanable {
    private int $status = 200;
    private array $headers = [];
    private string $body = '';
    private bool $sent = false;
    private array $cookies = [];

    public function status(int $code): self {
        $this->status = $code;
        return $this;
    }

    public function header(string $name, string $value): self {
        $this->headers[$name] = $value;
        return $this;
    }

    public function headers(array $headers): self {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }
        return $this;
    }

    public function cookie(string $name, string $value, array $options = []): self {
        $cookie = urlencode($name) . '=' . urlencode($value);

        if (isset($options['expires'])) {
            $cookie .= '; Expires=' . gmdate('D, d M Y H:i:s T', $options['expires']);
        }
        if (isset($options['max_age'])) {
            $cookie .= '; Max-Age=' . $options['max_age'];
        }
        if (isset($options['path'])) {
            $cookie .= '; Path=' . $options['path'];
        }
        if (isset($options['domain'])) {
            $cookie .= '; Domain=' . $options['domain'];
        }
        if (!empty($options['secure'])) {
            $cookie .= '; Secure';
        }
        if (!empty($options['httponly'])) {
            $cookie .= '; HttpOnly';
        }
        if (isset($options['samesite'])) {
            $cookie .= '; SameSite=' . $options['samesite'];
        }

        $this->cookies[] = $cookie;
        return $this;
    }

    public function body(string $body): self {
        $this->body = $body;
        return $this;
    }

    public function json(mixed $data, int $status = 200): self {
        $this->status = $status;
        $this->headers['Content-Type'] = 'application/json';
        $this->body = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return $this;
    }

    public function html(string $html, int $status = 200): self {
        $this->status = $status;
        $this->headers['Content-Type'] = 'text/html; charset=utf-8';
        $this->body = $html;
        return $this;
    }

    public function text(string $text, int $status = 200): self {
        $this->status = $status;
        $this->headers['Content-Type'] = 'text/plain; charset=utf-8';
        $this->body = $text;
        return $this;
    }

    public function redirect(string $url, int $status = 302): self {
        $this->status = $status;
        $this->headers['Location'] = $url;
        return $this;
    }

    public function notFound(string $message = 'Not Found'): self {
        return $this->json(['error' => $message], 404);
    }

    public function error(string $message = 'Internal Server Error', int $status = 500): self {
        return $this->json(['error' => $message], $status);
    }

    public function build(bool $keepAlive = true): string {
        $headers = $this->headers;
        static $date = '';
        static $dateSecond = 0;
        $now = time();
        if ($now !== $dateSecond) {
            $dateSecond = $now;
            $date = gmdate('D, d M Y H:i:s T', $now);
        }
        $headers['Date'] = $date;
        $headers['Server'] = 'Nexph/1.0';
        $headers['Connection'] = $headers['Connection'] ?? ($keepAlive ? 'keep-alive' : 'close');

        if (!empty($this->cookies)) {
            $headers['Set-Cookie'] = $this->cookies;
        }

        $this->sent = true;
        return HttpParser::buildResponse($this->status, $headers, $this->body);
    }

    public function isSent(): bool {
        return $this->sent;
    }

    public function getStatus(): int {
        return $this->status;
    }

    public function getBody(): string {
        return $this->body;
    }

    public function isClean(): bool {
        return $this->status === 200 &&
            $this->headers === [] &&
            $this->body === '' &&
            $this->sent === false &&
            $this->cookies === [];
    }

    public function reset(): void {
        $this->status = 200;
        $this->headers = [];
        $this->body = '';
        $this->sent = false;
        $this->cookies = [];
    }
}
