<?php

// Example: Mixed Authentication Routes
// Demonstrates API (JWT) and Admin (Session) in same application

use Core\Http\Router;
use Core\Http\Request;
use Core\Http\Response;
use Core\Http\Middleware;
use Core\Auth\Auth;
use Core\Auth\SessionGuard;
use Core\Auth\Csrf;

$router = new Router();

// ============================================
// PUBLIC ROUTES (No Auth)
// ============================================

$router->add('GET', '/', function(Request $req, Response $res) {
    $res->json([
        'app' => 'Nexph',
        'version' => '1.0',
        'auth' => [
            'api' => '/api/* (JWT Bearer Token)',
            'admin' => '/admin/* (Session-based)'
        ]
    ]);
});

// ============================================
// API ROUTES (JWT Authentication)
// ============================================

// API Login - Returns JWT token
$router->add('POST', '/api/auth/login', function(Request $req, Response $res) {
    $input = $req->input();
    
    $token = Auth::attempt($input['username'] ?? '', $input['password'] ?? '');
    
    if ($token) {
        $res->json([
            'success' => true,
            'token' => $token,
            'type' => 'Bearer',
            'expires_in' => 3600
        ]);
    }
    
    $res->json(['error' => 'Invalid credentials'], 401);
});

// API Token Generation - Long-lived token
$router->add('POST', '/api/auth/token', function(Request $req, Response $res) {
    $input = $req->input();
    
    $token = Auth::attempt($input['username'] ?? '', $input['password'] ?? '');
    
    if ($token) {
        $user = Auth::validate($token);
        $apiToken = Auth::apiToken($user, 86400 * 30); // 30 days
        
        $res->json([
            'success' => true,
            'token' => $apiToken,
            'type' => 'Bearer',
            'expires_in' => 86400 * 30,
            'note' => 'Store this token securely'
        ]);
    }
    
    $res->json(['error' => 'Invalid credentials'], 401);
});

// Protected API Endpoints
$router->add('GET', '/api/users/me', function(Request $req, Response $res) {
    $user = $req->user();
    $res->json(['user' => $user]);
}, [Middleware::class . '::api']);

$router->add('GET', '/api/tasks', function(Request $req, Response $res) {
    $user = $req->user();
    
    // Fetch tasks from database
    $tasks = [
        ['id' => 1, 'title' => 'API Task 1', 'user_id' => $user['id']],
        ['id' => 2, 'title' => 'API Task 2', 'user_id' => $user['id']],
    ];
    
    $res->json([
        'data' => $tasks,
        'meta' => [
            'total' => count($tasks),
            'user' => $user['username']
        ]
    ]);
}, [Middleware::class . '::api']);

$router->add('POST', '/api/tasks', function(Request $req, Response $res) {
    $user = $req->user();
    $input = $req->input();
    
    // Validate and create task
    if (empty($input['title'])) {
        $res->json(['error' => 'Title is required'], 422);
    }
    
    $task = [
        'id' => rand(1000, 9999),
        'title' => $input['title'],
        'user_id' => $user['id'],
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $res->json([
        'success' => true,
        'data' => $task
    ], 201);
}, [Middleware::class . '::api']);

// ============================================
// ADMIN ROUTES (Session Authentication)
// ============================================

// Admin Login Page
$router->add('GET', '/admin/login', function(Request $req, Response $res) {
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Nexph</title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    {Csrf::meta()}
    <script>
        document.body.addEventListener('htmx:configRequest', (e) => {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (token) {
                e.detail.headers['X-CSRF-Token'] = token;
            }
        });
    </script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-96">
        <h1 class="text-2xl font-bold mb-6 text-center">Admin Login</h1>
        <form hx-post="/admin/login" hx-target="#message" class="space-y-4">
            {Csrf::field()}
            <div>
                <label class="block text-sm font-medium mb-1">Username</label>
                <input type="text" name="username" required 
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Password</label>
                <input type="password" name="password" required 
                    class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            <button type="submit" 
                class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">
                Login
            </button>
        </form>
        <div id="message" class="mt-4"></div>
        <div class="mt-6 text-sm text-gray-600 text-center">
            <p>API Access: Use <code class="bg-gray-100 px-2 py-1 rounded">POST /api/auth/login</code></p>
        </div>
    </div>
</body>
</html>
HTML;
    $res->html($html);
}, [Middleware::class . '::guest']);

// Admin Login Handler
$router->add('POST', '/admin/login', function(Request $req, Response $res) {
    $input = $req->input();
    
    if (SessionGuard::attempt($input['username'] ?? '', $input['password'] ?? '')) {
        if ($req->isHtmx()) {
            $res->htmxRedirect('/admin/dashboard');
        }
        $res->redirect('/admin/dashboard');
    }
    
    if ($req->isHtmx()) {
        $res->html('<p class="text-red-600 text-center">Invalid credentials</p>');
    }
    
    $res->json(['error' => 'Invalid credentials'], 401);
}, [Middleware::class . '::guest', Middleware::class . '::csrf']);

// Admin Dashboard
$router->add('GET', '/admin/dashboard', function(Request $req, Response $res) {
    $user = SessionGuard::user();
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Nexph Admin</title>
    <script src="https://unpkg.com/htmx.org@1.9.10"></script>
    <script src="https://cdn.tailwindcss.com"></script>
    {Csrf::meta()}
    <script>
        document.body.addEventListener('htmx:configRequest', (e) => {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            if (token) {
                e.detail.headers['X-CSRF-Token'] = token;
            }
        });
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold">Nexph Admin</h1>
            <div class="flex items-center gap-4">
                <span class="text-gray-600">Welcome, {$user['username']}</span>
                <button hx-post="/admin/logout" 
                    class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
                    Logout
                </button>
            </div>
        </div>
    </nav>
    
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold mb-2">Session Auth</h3>
                <p class="text-gray-600">Secure session-based authentication with CSRF protection</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold mb-2">HTMX Powered</h3>
                <p class="text-gray-600">Dynamic updates without full page reloads</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <h3 class="text-lg font-semibold mb-2">API Separate</h3>
                <p class="text-gray-600">JWT tokens for API, sessions for admin</p>
            </div>
        </div>
        
        <div id="content" class="bg-white p-6 rounded-lg shadow">
            <div class="flex gap-4 mb-6">
                <button hx-get="/admin/profile" hx-target="#content" 
                    class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                    View Profile
                </button>
                <button hx-get="/admin/tasks" hx-target="#content" 
                    class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
                    View Tasks
                </button>
                <button hx-get="/admin/settings" hx-target="#content" 
                    class="bg-purple-600 text-white px-4 py-2 rounded hover:bg-purple-700 transition">
                    Settings
                </button>
            </div>
            <p class="text-gray-600">Select an option above to load content dynamically.</p>
        </div>
    </div>
</body>
</html>
HTML;
    
    $res->html($html);
}, [Middleware::class . '::session']);

// Admin Profile (HTMX Partial)
$router->add('GET', '/admin/profile', function(Request $req, Response $res) {
    $user = SessionGuard::user();
    
    $html = <<<HTML
<div>
    <h2 class="text-2xl font-bold mb-4">Profile</h2>
    <div class="space-y-3">
        <div class="flex justify-between border-b pb-2">
            <span class="font-semibold">Username:</span>
            <span>{$user['username']}</span>
        </div>
        <div class="flex justify-between border-b pb-2">
            <span class="font-semibold">Role:</span>
            <span class="capitalize">{$user['role']}</span>
        </div>
        <div class="flex justify-between border-b pb-2">
            <span class="font-semibold">User ID:</span>
            <span>{$user['id']}</span>
        </div>
        <div class="flex justify-between border-b pb-2">
            <span class="font-semibold">Auth Type:</span>
            <span class="text-green-600">Session-based</span>
        </div>
    </div>
    <form hx-post="/admin/profile/update" hx-target="#message" class="mt-6 space-y-4">
        {Csrf::field()}
        <div>
            <label class="block text-sm font-medium mb-1">New Password</label>
            <input type="password" name="password" 
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <button type="submit" 
            class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
            Update Profile
        </button>
    </form>
    <div id="message" class="mt-4"></div>
</div>
HTML;
    
    $res->html($html);
}, [Middleware::class . '::session']);

// Admin Tasks (HTMX Partial)
$router->add('GET', '/admin/tasks', function(Request $req, Response $res) {
    $user = SessionGuard::user();
    
    $tasks = [
        ['id' => 1, 'title' => 'Setup session auth', 'status' => 'completed'],
        ['id' => 2, 'title' => 'Implement CSRF protection', 'status' => 'completed'],
        ['id' => 3, 'title' => 'Add HTMX integration', 'status' => 'completed'],
    ];
    
    $taskRows = '';
    foreach ($tasks as $task) {
        $statusColor = $task['status'] === 'completed' ? 'text-green-600' : 'text-yellow-600';
        $taskRows .= <<<HTML
        <tr class="border-b">
            <td class="py-2">{$task['id']}</td>
            <td class="py-2">{$task['title']}</td>
            <td class="py-2 {$statusColor} capitalize">{$task['status']}</td>
            <td class="py-2">
                <button hx-delete="/admin/tasks/{$task['id']}" hx-confirm="Delete this task?" 
                    class="text-red-600 hover:text-red-800">Delete</button>
            </td>
        </tr>
HTML;
    }
    
    $html = <<<HTML
<div>
    <h2 class="text-2xl font-bold mb-4">Tasks</h2>
    <table class="w-full">
        <thead>
            <tr class="border-b-2">
                <th class="text-left py-2">ID</th>
                <th class="text-left py-2">Title</th>
                <th class="text-left py-2">Status</th>
                <th class="text-left py-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            {$taskRows}
        </tbody>
    </table>
    <form hx-post="/admin/tasks" hx-target="#message" class="mt-6 space-y-4">
        {Csrf::field()}
        <div>
            <label class="block text-sm font-medium mb-1">New Task</label>
            <input type="text" name="title" required 
                class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500">
        </div>
        <button type="submit" 
            class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
            Add Task
        </button>
    </form>
    <div id="message" class="mt-4"></div>
</div>
HTML;
    
    $res->html($html);
}, [Middleware::class . '::session']);

// Create Task (Admin)
$router->add('POST', '/admin/tasks', function(Request $req, Response $res) {
    $input = $req->input();
    
    // Create task logic here
    
    $res->html('<p class="text-green-600">Task created successfully!</p>');
}, [Middleware::class . '::session', Middleware::class . '::csrf']);

// Delete Task (Admin)
$router->add('DELETE', '/admin/tasks/{id}', function(Request $req, Response $res, array $params) {
    // Delete task logic here
    
    $res->html('<p class="text-green-600">Task deleted!</p>');
}, [Middleware::class . '::session', Middleware::class . '::csrf']);

// Admin Logout
$router->add('POST', '/admin/logout', function(Request $req, Response $res) {
    SessionGuard::logout();
    
    if ($req->isHtmx()) {
        $res->htmxRedirect('/admin/login');
    }
    
    $res->redirect('/admin/login');
}, [Middleware::class . '::session', Middleware::class . '::csrf']);

// ============================================
// DOCUMENTATION ROUTE
// ============================================

$router->add('GET', '/docs/auth', function(Request $req, Response $res) {
    $html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication Documentation - Nexph</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto bg-white p-8 rounded-lg shadow-lg">
        <h1 class="text-3xl font-bold mb-6">Nexph Authentication</h1>
        
        <section class="mb-8">
            <h2 class="text-2xl font-semibold mb-4">Two Authentication Systems</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="border p-4 rounded">
                    <h3 class="text-xl font-semibold mb-2 text-blue-600">Session Auth (Admin)</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700">
                        <li>HttpOnly cookies</li>
                        <li>CSRF protection</li>
                        <li>Session regeneration</li>
                        <li>HTMX compatible</li>
                        <li>Routes: /admin/*</li>
                    </ul>
                </div>
                <div class="border p-4 rounded">
                    <h3 class="text-xl font-semibold mb-2 text-green-600">JWT Auth (API)</h3>
                    <ul class="list-disc list-inside space-y-2 text-gray-700">
                        <li>Bearer tokens</li>
                        <li>Stateless</li>
                        <li>Long-lived tokens</li>
                        <li>RESTful API</li>
                        <li>Routes: /api/*</li>
                    </ul>
                </div>
            </div>
        </section>
        
        <section class="mb-8">
            <h2 class="text-2xl font-semibold mb-4">API Examples</h2>
            <div class="bg-gray-900 text-gray-100 p-4 rounded overflow-x-auto">
                <pre><code># Login and get JWT token
curl -X POST http://localhost:8000/api/auth/login \\
  -H "Content-Type: application/json" \\
  -d '{"username":"admin","password":"password"}'

# Use token for API requests
curl -X GET http://localhost:8000/api/tasks \\
  -H "Authorization: Bearer YOUR_TOKEN_HERE"

# Create task via API
curl -X POST http://localhost:8000/api/tasks \\
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \\
  -H "Content-Type: application/json" \\
  -d '{"title":"New API Task"}'</code></pre>
            </div>
        </section>
        
        <section class="mb-8">
            <h2 class="text-2xl font-semibold mb-4">Admin Dashboard</h2>
            <p class="text-gray-700 mb-4">
                The admin dashboard uses session-based authentication with CSRF protection.
                All forms automatically include CSRF tokens, and HTMX requests are secured.
            </p>
            <a href="/admin/login" class="bg-blue-600 text-white px-6 py-3 rounded inline-block hover:bg-blue-700 transition">
                Go to Admin Login
            </a>
        </section>
        
        <section>
            <h2 class="text-2xl font-semibold mb-4">Security Features</h2>
            <ul class="list-disc list-inside space-y-2 text-gray-700">
                <li>Session regeneration on login and every 5 minutes</li>
                <li>CSRF token rotation (single-use tokens)</li>
                <li>HttpOnly cookies prevent XSS attacks</li>
                <li>SameSite=Lax prevents CSRF attacks</li>
                <li>256-bit session IDs (cryptographically secure)</li>
                <li>Separate authentication for API and admin</li>
                <li>Password hashing with bcrypt</li>
                <li>Activity-based session expiration</li>
            </ul>
        </section>
    </div>
</body>
</html>
HTML;
    
    $res->html($html);
});

return $router;
