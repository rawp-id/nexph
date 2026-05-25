<?php
namespace Core\Auth;

class Csrf {
    private static string $tokenKey = '_csrf_token';
    private static int $tokenLifetime = 3600;

    public static function generate(): string {
        Session::start();
        
        $token = bin2hex(random_bytes(32));
        $tokens = Session::get(self::$tokenKey, []);
        
        $tokens[$token] = time();
        
        $tokens = array_filter($tokens, function($timestamp) {
            return (time() - $timestamp) < self::$tokenLifetime;
        });
        
        Session::set(self::$tokenKey, $tokens);
        
        return $token;
    }

    public static function validate(string $token): bool {
        Session::start();
        
        $tokens = Session::get(self::$tokenKey, []);
        
        if (!isset($tokens[$token])) {
            return false;
        }
        
        if ((time() - $tokens[$token]) > self::$tokenLifetime) {
            unset($tokens[$token]);
            Session::set(self::$tokenKey, $tokens);
            return false;
        }
        
        unset($tokens[$token]);
        Session::set(self::$tokenKey, $tokens);
        
        return true;
    }

    public static function token(): string {
        return self::generate();
    }

    public static function field(): string {
        $token = self::generate();
        return "<input type='hidden' name='_csrf_token' value='{$token}'>";
    }

    public static function meta(): string {
        $token = self::generate();
        return "<meta name='csrf-token' content='{$token}'>";
    }
}
