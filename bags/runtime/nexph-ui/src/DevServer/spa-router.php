<?php
/**
 * NEXPH SPA Fallback Router Script
 * Used by PHP built-in server for history mode routing
 */

$docRoot = $_SERVER['DOCUMENT_ROOT'];
$uri     = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path    = $docRoot . $uri;

$assetExtensions = [
    'js', 'css', 'png', 'jpg', 'jpeg', 'svg', 'webp', 'ico',
    'json', 'map', 'gz', 'woff', 'woff2', 'ttf', 'eot'
];

$ext = strtolower(pathinfo($uri, PATHINFO_EXTENSION));

// existing file -> let built-in server handle it
if (is_file($path)) {
    return false;
}

// asset path but missing -> 404
if (in_array($ext, $assetExtensions, true)) {
    http_response_code(404);
    echo "404 Not Found: {$uri}";
    exit;
}

// SPA fallback -> index.html
$index = $docRoot . '/index.html';
if (is_file($index)) {
    header('Content-Type: text/html; charset=UTF-8');
    readfile($index);
    exit;
}

http_response_code(404);
echo "404 Not Found";
