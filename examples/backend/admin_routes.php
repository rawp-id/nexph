<?php

// Example: Admin Dashboard with Session Auth
// File: routes/admin.php

use Core\Http\Router;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Middleware;
use Core\Auth\SessionGuard;
use Core\Auth\Csrf;

$router = new Router();

// Login page (guest only)
$router->add('GET', '/admin/login', function(Request $req, Response $res) {
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    {Csrf::meta()}
    <script>
        document.body.addEventListener('htmx:configRequest', (e) => {
            e.detail.headers['X-CSRF-Token'] = 
                document.querySelector('meta[name="csrf-token"]').content;
        });
    </script>
</head>
<body>
    <h1>Login</h1>
    <form hx-post="/admin/login" hx-target="#message">
        {Csrf::field()}
        <input type="text" name="username" placeholder="Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
    <div id="message"></div>
</body>
</html>
HTML;
    $res->html($html);
}, [Middleware::class . '::guest']);

// Login handler
$router->add('POST', '/admin/login', function(Request $req, Response $res) {
    $input = $req->input();
    
    if (SessionGuard::attempt($input['username'] ?? '', $input['password'] ?? '')) {
        if ($req->isHtmx()) {
            $res->htmxRedirect('/admin');
        }
        $res->redirect('/admin');
    }
    
    if ($req->isHtmx()) {
        $res->html('<p style="color:red">Invalid credentials</p>');
    }
    
    $res->json(['error' => 'Invalid credentials'], 401);
}, [Middleware::class . '::guest', Middleware::class . '::csrf']);

// Dashboard (protected)
$router->add('GET', '/admin', function(Request $req, Response $res) {
    $user = SessionGuard::user();
    
    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    {Csrf::meta()}
    <script>
        document.body.addEventListener('htmx:configRequest', (e) => {
            e.detail.headers['X-CSRF-Token'] = 
                document.querySelector('meta[name="csrf-token"]').content;
        });
    </script>
</head>
<body>
    <h1>Dashboard</h1>
    <p>Welcome, {$user['username']}!</p>
    <div id="content">
        <button hx-get="/admin/profile" hx-target="#content">View Profile</button>
        <button hx-post="/admin/logout" hx-target="body">Logout</button>
    </div>
</body>
</html>
HTML;
    
    if ($req->isHtmx()) {
        $res->html("<p>Welcome, {$user['username']}!</p>");
    }
    
    $res->html($html);
}, [Middleware::class . '::session']);

// Profile (HTMX partial)
$router->add('GET', '/admin/profile', function(Request $req, Response $res) {
    $user = SessionGuard::user();
    
    $html = <<<HTML
<div>
    <h2>Profile</h2>
    <p>Username: {$user['username']}</p>
    <p>Role: {$user['role']}</p>
    <p>ID: {$user['id']}</p>
</div>
HTML;
    
    $res->html($html);
}, [Middleware::class . '::session']);

// Update profile (with CSRF)
$router->add('POST', '/admin/profile', function(Request $req, Response $res) {
    $input = $req->input();
    $user = SessionGuard::user();
    
    // Update logic here
    
    $res->html('<p style="color:green">Profile updated!</p>');
}, [Middleware::class . '::session', Middleware::class . '::csrf']);

// Logout
$router->add('POST', '/admin/logout', function(Request $req, Response $res) {
    SessionGuard::logout();
    
    if ($req->isHtmx()) {
        $res->htmxRedirect('/admin/login');
    }
    
    $res->redirect('/admin/login');
}, [Middleware::class . '::session', Middleware::class . '::csrf']);

return $router;
