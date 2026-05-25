<?php
namespace Core\Auth;

use Core\Database\DB;

class SessionGuard {
    public static function attempt(string $username, string $password): bool {
        $users = DB::query("SELECT id, username, role, created_at, updated_at, password FROM users WHERE username = ?", [$username]);
        
        if (empty($users)) {
            return false;
        }
        
        $user = $users[0];
        
        if (!password_verify($password, $user['password'])) {
            return false;
        }
        
        Session::start();
        Session::regenerate();
        
        unset($user['password']);
        Session::set('user', $user);
        Session::set('authenticated', true);
        Session::set('login_time', time());
        
        return true;
    }

    public static function login(array $user): void {
        Session::start();
        Session::regenerate();
        
        unset($user['password']);
        Session::set('user', $user);
        Session::set('authenticated', true);
        Session::set('login_time', time());
    }

    public static function logout(): void {
        Session::start();
        Session::destroy();
    }

    public static function check(): bool {
        Session::start();
        return Session::get('authenticated', false) === true;
    }

    public static function user(): ?array {
        Session::start();
        return Session::get('user');
    }

    public static function id(): ?int {
        $user = self::user();
        return $user['id'] ?? null;
    }

    public static function guest(): bool {
        return !self::check();
    }
}
