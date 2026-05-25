<?php
namespace Core\Http;

use Core\Http\Request;
use Core\Http\Response;
use Core\Auth\Auth;
use Core\Auth\SessionGuard;
use Core\Auth\Csrf;

class Middleware {
    public static function auth(Request $request, Response $response): void {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = null;

        if (str_starts_with($header, 'Bearer ')) {
            $token = substr($header, 7);
        } elseif (isset($_COOKIE['token'])) {
            $token = $_COOKIE['token'];
        }

        if (!$token) {
            if (str_starts_with($_SERVER['REQUEST_URI'], '/admin') && !$request->header('HX-Request') && $_SERVER['REQUEST_URI'] !== '/admin/login') {
                header('Location: /admin/login');
                exit;
            }
            $response->json(['error' => 'Unauthorized'], 401);
        }
        
        $user = Auth::validate($token);
        
        if (!$user) {
            $response->json(['error' => 'Invalid or expired token'], 401);
        }

        $_REQUEST['user'] = $user;
    }

    public static function session(Request $request, Response $response): void {
        if (!SessionGuard::check()) {
            $uri = $_SERVER['REQUEST_URI'];
            $isHtmx = $request->header('HX-Request');
            
            if (str_starts_with($uri, '/admin') && $uri !== '/admin/login') {
                if ($isHtmx) {
                    header('HX-Redirect: /admin/login');
                    exit;
                }
                header('Location: /admin/login');
                exit;
            }
            
            $response->json(['error' => 'Unauthorized'], 401);
        }
        
        $user = SessionGuard::user();
        
        if (!$user || ($user['role'] ?? 'user') !== 'admin') {
            $response->json(['error' => 'Forbidden - Admin access required'], 403);
        }
        
        $_REQUEST['user'] = $user;
    }

    public static function csrf(Request $request, Response $response): void {
        $method = $request->method();
        
        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'])) {
            return;
        }
        
        $token = null;
        $input = $request->input();
        
        if (isset($input['_csrf_token'])) {
            $token = $input['_csrf_token'];
        } elseif ($header = $request->header('X-CSRF-Token')) {
            $token = $header;
        } elseif ($header = $request->header('X-XSRF-Token')) {
            $token = $header;
        }
        
        if (!$token || !Csrf::validate($token)) {
            $response->json(['error' => 'CSRF token mismatch'], 419);
        }
    }

    public static function api(Request $request, Response $response): void {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        
        if (!str_starts_with($header, 'Bearer ')) {
            $response->json(['error' => 'Missing API token'], 401);
        }
        
        $token = substr($header, 7);
        $user = Auth::validate($token);
        
        if (!$user) {
            $response->json(['error' => 'Invalid or expired token'], 401);
        }
        
        $_REQUEST['user'] = $user;
    }

    public static function guest(Request $request, Response $response): void {
        if (SessionGuard::check()) {
            $isHtmx = $request->header('HX-Request');
            
            if ($isHtmx) {
                header('HX-Redirect: /admin');
                exit;
            }
            
            header('Location: /admin');
            exit;
        }
    }

    public static function role(string $requiredRole): \Closure {
        return function(Request $request, Response $response) use ($requiredRole) {
            $user = $request->user();
            
            if (!$user || !isset($user['role'])) {
                $response->json(['error' => 'Unauthorized'], 401);
            }
            
            $roleHierarchy = ['admin' => 3, 'moderator' => 2, 'user' => 1];
            $userLevel = $roleHierarchy[$user['role']] ?? 0;
            $requiredLevel = $roleHierarchy[$requiredRole] ?? 999;
            
            if ($userLevel < $requiredLevel) {
                $response->json(['error' => 'Forbidden'], 403);
            }
        };
    }
}
