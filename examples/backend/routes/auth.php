<?php

use Core\Http\Request;
use Core\Http\Response;
use Core\Http\RateLimiter;
use Core\Auth\Auth;
use Core\Auth\SessionGuard;
use Core\Auth\Session;
use Core\Auth\Csrf;
use Core\UI\View;

$router->add('POST', '/login', function (Request $req, Response $res) {
    $ip = $req->ip();
    if (!RateLimiter::check("login:{$ip}", 5, 300)) {
        $res->json(['error' => 'Too many attempts. Try again later.'], 429);
    }
    $input = $req->input();
    if ($token = Auth::attempt($input['username'] ?? '', $input['password'] ?? '')) {
        $res->json(['token' => $token, 'type' => 'Bearer']);
    } else {
        $res->json(['error' => 'Unauthorized'], 401);
    }
});

$router->add('POST', '/register', function (Request $req, Response $res) {
    $ip = $req->ip();
    if (!RateLimiter::check("register:{$ip}", 3, 300)) {
        $res->json(['error' => 'Too many attempts. Try again later.'], 429);
    }
    $input = $req->input();
    $username = trim((string)($input['username'] ?? ''));
    $password = (string)($input['password'] ?? '');
    $role = (string)($input['role'] ?? 'user');
    if ($username === '' || strlen($password) < 8) {
        $res->json(['error' => 'Validation failed', 'fields' => ['username' => ['required'], 'password' => ['min:8']]], 422);
    }
    if (!in_array($role, ['user', 'moderator', 'admin'], true)) {
        $role = 'user';
    }
    if (!Auth::register($username, $password, $role)) {
        $res->json(['error' => 'Username already exists or invalid input'], 409);
    }
    $token = Auth::attempt($username, $password);
    $res->json(['status' => 'registered', 'token' => $token, 'type' => 'Bearer'], 201);
});

$router->add('POST', '/admin/login', function (Request $req, Response $res) {
    $ip = $req->ip();
    if (!RateLimiter::check("admin_login:{$ip}", 5, 300)) {
        $res->json(['error' => 'Too many attempts. Try again later.'], 429);
    }
    $input = $req->input();
    if (SessionGuard::attempt($input['username'] ?? '', $input['password'] ?? '')) {
        $res->json(['success' => true]);
    } else {
        $res->json(['error' => 'Invalid credentials'], 401);
    }
});

$router->add('GET', '/admin/login', function (Request $req, Response $res) {
    Session::start();
    $csrfToken = Csrf::generate();
    echo View::loginPage($csrfToken);
    exit;
});

$router->add('GET', '/logout', function (Request $req, Response $res) {
    setcookie('token', '', [
        'expires' => time() - 3600,
        'path' => '/',
        'domain' => '',
        'secure' => true,
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    header('Location: /admin/login');
    exit;
});

$router->add('POST', '/admin/logout', function (Request $req, Response $res) {
    Session::destroy();
    header('Location: /admin/login');
    exit;
});
