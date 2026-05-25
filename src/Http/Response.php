<?php
namespace Core\Http;

class Response {
    public function json(mixed $data, int $status = 200): void {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($status);
        echo json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
        exit;
    }

    public function redirect(string $url, int $status = 302): void {
        http_response_code($status);
        header("Location: {$url}");
        exit;
    }

    public function htmxRedirect(string $url): void {
        header("HX-Redirect: {$url}");
        exit;
    }

    public function htmxRefresh(): void {
        header("HX-Refresh: true");
        exit;
    }

    public function html(string $content, int $status = 200): void {
        header('Content-Type: text/html; charset=utf-8');
        http_response_code($status);
        echo $content;
        exit;
    }
}
