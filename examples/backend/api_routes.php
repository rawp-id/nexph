<?php

// Example: API Routes with JWT Auth
// File: routes/api.php

use Core\Http\Router;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Middleware;
use Core\Auth\Auth;

$router = new Router();

// API Login (generates JWT)
$router->add('POST', '/api/login', function(Request $req, Response $res) {
    $input = $req->input();
    
    $token = Auth::attempt($input['username'] ?? '', $input['password'] ?? '');
    
    if ($token) {
        $res->json([
            'token' => $token,
            'type' => 'Bearer',
            'expires_in' => 3600
        ]);
    }
    
    $res->json(['error' => 'Invalid credentials'], 401);
});

// Generate long-lived API token
$router->add('POST', '/api/token', function(Request $req, Response $res) {
    $input = $req->input();
    
    $token = Auth::attempt($input['username'] ?? '', $input['password'] ?? '');
    
    if ($token) {
        $user = Auth::validate($token);
        $apiToken = Auth::apiToken($user, 86400 * 30); // 30 days
        
        $res->json([
            'token' => $apiToken,
            'type' => 'Bearer',
            'expires_in' => 86400 * 30
        ]);
    }
    
    $res->json(['error' => 'Invalid credentials'], 401);
});

// Protected API endpoint
$router->add('GET', '/api/tasks', function(Request $req, Response $res) {
    $user = $req->user();
    
    // Fetch tasks logic here
    $tasks = [
        ['id' => 1, 'title' => 'Task 1', 'user_id' => $user['id']],
        ['id' => 2, 'title' => 'Task 2', 'user_id' => $user['id']],
    ];
    
    $res->json([
        'data' => $tasks,
        'user' => $user['username']
    ]);
}, [Middleware::class . '::api']);

// Create task (API)
$router->add('POST', '/api/tasks', function(Request $req, Response $res) {
    $user = $req->user();
    $input = $req->input();
    
    // Create task logic here
    
    $res->json([
        'message' => 'Task created',
        'task' => [
            'id' => 3,
            'title' => $input['title'],
            'user_id' => $user['id']
        ]
    ], 201);
}, [Middleware::class . '::api']);

// Update task (API)
$router->add('PUT', '/api/tasks/{id}', function(Request $req, Response $res, array $params) {
    $user = $req->user();
    $input = $req->input();
    
    // Update task logic here
    
    $res->json([
        'message' => 'Task updated',
        'task' => [
            'id' => $params['id'],
            'title' => $input['title'],
            'user_id' => $user['id']
        ]
    ]);
}, [Middleware::class . '::api']);

// Delete task (API)
$router->add('DELETE', '/api/tasks/{id}', function(Request $req, Response $res, array $params) {
    $user = $req->user();
    
    // Delete task logic here
    
    $res->json([
        'message' => 'Task deleted',
        'id' => $params['id']
    ]);
}, [Middleware::class . '::api']);

// API user info
$router->add('GET', '/api/me', function(Request $req, Response $res) {
    $user = $req->user();
    
    $res->json([
        'user' => $user
    ]);
}, [Middleware::class . '::api']);

return $router;
