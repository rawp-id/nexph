<?php
return function ($router) {
    $router->get('/test-route', function ($req, $res) {
        $res->json(['from' => 'route-package']);
    });
};
