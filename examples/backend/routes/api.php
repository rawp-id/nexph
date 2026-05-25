<?php

use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Middleware;
use Core\Support\Cache;
use Core\Support\CacheWarmer;

$router->add('GET', '/api', function (Request $req, Response $res) use ($apiPolicy, $allMeta) {
    $res->json($apiPolicy->manifest($allMeta));
}, [[Middleware::class, 'api']]);

$router->add('GET', '/api/cache/status', function (Request $req, Response $res) {
    $enabled = Cache::enabled();
    $stats = ['enabled' => $enabled];
    
    if ($enabled && function_exists('apcu_cache_info')) {
        $info = @apcu_cache_info();
        if ($info !== false) {
            $stats['memory_size'] = round($info['mem_size'] / 1024 / 1024, 2);
            $stats['entries'] = $info['num_entries'];
            $stats['hits'] = $info['num_hits'];
            $stats['misses'] = $info['num_misses'];
            if ($info['num_hits'] + $info['num_misses'] > 0) {
                $stats['hit_rate'] = round($info['num_hits'] / ($info['num_hits'] + $info['num_misses']) * 100, 2);
            }
        }
    }
    
    $res->json($stats);
}, [[Middleware::class, 'api']]);

$router->add('POST', '/api/cache/warm', function (Request $req, Response $res) {
    $stats = CacheWarmer::warm();
    $res->json($stats);
}, [[Middleware::class, 'api']]);

$router->add('POST', '/api/cache/clear', function (Request $req, Response $res) {
    $result = CacheWarmer::clear();
    $res->json(['success' => $result]);
}, [[Middleware::class, 'api']]);
