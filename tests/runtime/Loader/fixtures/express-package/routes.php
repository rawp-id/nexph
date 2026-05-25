<?php
return function ($router) {
    $router->get('/express', fn($req, $res) => $res->json(['style' => 'express']));
    $router->post('/express', fn($req, $res) => $res->json(['created' => true]));
};
