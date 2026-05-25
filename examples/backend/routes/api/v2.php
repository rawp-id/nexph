<?php
// API v2 routes
// Usage: require in routes/api.php

use Core\Http\VersionedRouter;

return function (VersionedRouter $api) {
    // Users with pagination
    $api->get('/users', function ($req, $res) {
        $res->json([
            'version' => 'v2',
            'endpoint' => 'users',
            'pagination' => ['page' => 1, 'per_page' => 20],
        ]);
    });

    $api->get('/users/{id}', function ($req, $res, $params) {
        $res->json([
            'version' => 'v2',
            'data' => ['id' => $params['id']],
            'meta' => ['api_version' => 'v2'],
        ]);
    });

    // Health
    $api->get('/ping', function ($req, $res) {
        $res->json(['pong' => true, 'version' => 'v2', 'timestamp' => time()]);
    });
};
