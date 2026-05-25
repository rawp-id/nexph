<?php
// Health endpoints route file
// Include in routes/api.php or public/index.php

use Core\Http\Router;
use Core\Http\Request;
use Core\Http\Response;
use Core\Health\HealthCheck;

return function (Router $router) {
    // Full health check
    $router->add('GET', '/health', function (Request $req, Response $res) {
        HealthCheck::init();
        $health = HealthCheck::run();
        $status = $health['status'] === 'ok' ? 200 : 503;
        $res->json($health, $status);
    });

    // Kubernetes liveness probe
    $router->add('GET', '/health/live', function (Request $req, Response $res) {
        $res->json(HealthCheck::liveness());
    });

    // Kubernetes readiness probe
    $router->add('GET', '/health/ready', function (Request $req, Response $res) {
        $ready = HealthCheck::readiness();
        $status = $ready['status'] === 'ok' ? 200 : 503;
        $res->json($ready, $status);
    });

    // Memory stats
    $router->add('GET', '/health/memory', function (Request $req, Response $res) {
        $res->json([
            'usage' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'limit' => ini_get('memory_limit'),
        ]);
    });
};
