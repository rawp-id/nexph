<?php
// API v1 routes
// Usage: require in routes/api.php

use Core\Http\VersionedRouter;

return function (VersionedRouter $api) {
    // Users
    $api->get('/users', function ($req, $res) {
        $res->json(['version' => 'v1', 'endpoint' => 'users']);
    });

    $api->get('/users/{id}', function ($req, $res, $params) {
        $res->json(['version' => 'v1', 'user_id' => $params['id']]);
    });

    // Health
    $api->get('/ping', function ($req, $res) {
        $res->json(['pong' => true, 'version' => 'v1']);
    });
};
