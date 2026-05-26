<?php
namespace Core\Server;

class HttpParser {
    public static function parseRequest(string $raw): ?array {
        $pos = strpos($raw, "\r\n\r\n");
        if ($pos === false) {
            return null; // Incomplete
        }

        $headerPart = substr($raw, 0, $pos);
        $body = substr($raw, $pos + 4);

        $lines = explode("\r\n", $headerPart);
        $firstLine = array_shift($lines);

        if (!preg_match('/^(GET|POST|PUT|PATCH|DELETE|HEAD|OPTIONS)\s+(.+)\s+HTTP\/1\.[01]$/', $firstLine, $m)) {
            return null;
        }

        $method = $m[1];
        $uri = $m[2];

        // Parse URI and query string
        $queryString = '';
        $path = $uri;
        if (($qPos = strpos($uri, '?')) !== false) {
            $path = substr($uri, 0, $qPos);
            $queryString = substr($uri, $qPos + 1);
        }

        // Parse headers
        $headers = [];
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($value);
            }
        }

        // Check content-length for body
        $contentLength = (int) ($headers['content-length'] ?? 0);
        if ($contentLength > 0 && strlen($body) < $contentLength) {
            return null; // Incomplete body
        }

        // Parse query params
        $query = [];
        if ($queryString) {
            parse_str($queryString, $query);
        }

        // Parse body
        $parsedBody = [];
        $contentType = $headers['content-type'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $parsedBody = json_decode($body, true) ?? [];
        } elseif (str_contains($contentType, 'application/x-www-form-urlencoded')) {
            parse_str($body, $parsedBody);
        }

        // Parse cookies
        $cookies = [];
        if (isset($headers['cookie'])) {
            $pairs = explode(';', $headers['cookie']);
            foreach ($pairs as $pair) {
                $pair = trim($pair);
                if (strpos($pair, '=') !== false) {
                    [$k, $v] = explode('=', $pair, 2);
                    $cookies[trim($k)] = urldecode(trim($v));
                }
            }
        }

        return [
            'method' => $method,
            'uri' => $uri,
            'path' => $path,
            'query_string' => $queryString,
            'query' => $query,
            'headers' => $headers,
            'body' => $body,
            'parsed_body' => $parsedBody,
            'cookies' => $cookies,
            'raw_length' => $pos + 4 + $contentLength,
        ];
    }

    public static function buildResponse(int $status, array $headers, string $body): string {
        static $statusTexts = [
            200 => 'OK',
            101 => 'Switching Protocols',
            201 => 'Created',
            204 => 'No Content',
            301 => 'Moved Permanently',
            302 => 'Found',
            304 => 'Not Modified',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            413 => 'Payload Too Large',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
        ];

        $statusText = $statusTexts[$status] ?? 'Unknown';
        $response = "HTTP/1.1 {$status} {$statusText}\r\n";

        $contentType = strtolower((string) ($headers['Content-Type'] ?? $headers['content-type'] ?? ''));
        $streaming = str_starts_with($contentType, 'text/event-stream');
        if (!$streaming && $status !== 101 && $status !== 204 && $status !== 304) {
            $headers['Content-Length'] = strlen($body);
        }
        $headers['Connection'] = $headers['Connection'] ?? 'keep-alive';

        foreach ($headers as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    $response .= "{$key}: {$v}\r\n";
                }
            } else {
                $response .= "{$key}: {$value}\r\n";
            }
        }

        $response .= "\r\n" . $body;
        return $response;
    }
}
