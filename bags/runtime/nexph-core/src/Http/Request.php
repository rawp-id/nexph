<?php
namespace Core\Http;

class Request {
    public function method(): string {
        return strtoupper($_SERVER['REQUEST_METHOD']);
    }

    public function uri(): string {
        return parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    public function input(): array {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];
        return array_merge($_GET, $_POST, $data);
    }

    public function header(string $name): ?string {
        $name = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return $_SERVER[$name] ?? null;
    }

    public function user(): ?array {
        return $_REQUEST['user'] ?? null;
    }

    public function isHtmx(): bool {
        return $this->header('HX-Request') !== null;
    }

    public function ip(): string {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function query(?string $key = null): mixed {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? null;
    }
}
