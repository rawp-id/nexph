<?php

namespace Nexph\DevServer;

class SpaRouter
{
    private const ASSET_EXTENSIONS = [
        'js', 'css', 'png', 'jpg', 'jpeg', 'svg', 'webp', 'ico',
        'json', 'map', 'gz', 'woff', 'woff2', 'ttf', 'eot'
    ];

    public static function isAssetPath(string $path): bool
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, self::ASSET_EXTENSIONS, true);
    }

    public static function route(string $docRoot): void
    {
        $uri  = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = $docRoot . $uri;

        // existing file -> serve it
        if (is_file($path)) {
            return; // let PHP built-in server handle it
        }

        // asset path but file missing -> 404
        if (self::isAssetPath($uri)) {
            http_response_code(404);
            echo "404 Not Found: {$uri}";
            exit;
        }

        // SPA fallback -> serve index.html
        $index = $docRoot . '/index.html';
        if (is_file($index)) {
            header('Content-Type: text/html; charset=UTF-8');
            readfile($index);
            exit;
        }

        http_response_code(404);
        echo "404 Not Found";
        exit;
    }
}
