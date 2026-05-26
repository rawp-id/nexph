<?php
namespace Core\Auth;

use Core\Database\DB;

class Auth {
    private static ?string $secret = null;

    private static function getSecret(): string {
        if (self::$secret === null) {
            if (!isset($_ENV['APP_KEY']) || empty($_ENV['APP_KEY'])) {
                throw new \RuntimeException('APP_KEY not configured. Run: php keygen.php');
            }
            self::$secret = $_ENV['APP_KEY'];
        }
        return self::$secret;
    }

    public static function generateToken(array $payload, int $ttl = 3600): string {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload['exp'] = time() + $ttl;
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));
        
        $signature = hash_hmac('sha256', "{$base64Header}.{$base64Payload}", self::getSecret(), true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return "{$base64Header}.{$base64Payload}.{$base64Signature}";
    }

    public static function validate(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        
        [$header, $payload, $signature] = $parts;
        $validSignature = hash_hmac('sha256', "{$header}.{$payload}", self::getSecret(), true);
        $base64ValidSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($validSignature));
        
        if (!hash_equals($signature, $base64ValidSignature)) return null;
        
        $data = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
        if (($data['exp'] ?? 0) < time()) return null;
        
        return $data;
    }

    public static function attempt(string $username, string $password): ?string {
        $users = DB::query("SELECT id, username, role, created_at, updated_at, password FROM users WHERE username = ?", [$username]);
        if (!$users) return null;
        
        $user = $users[0];
        if (password_verify($password, $user['password'])) {
            unset($user['password']);
            return self::generateToken($user);
        }
        return null;
    }

    public static function register(string $username, string $password, string $role = 'user'): bool {
        if ($username === '' || strlen($password) < 8) {
            return false;
        }
        $exists = DB::query("SELECT id FROM users WHERE username = ? LIMIT 1", [$username]);
        if ($exists) {
            return false;
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $id = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        DB::query("INSERT INTO users (id, username, password, role) VALUES (?, ?, ?, ?)", [$id, $username, $hash, $role]);
        return true;
    }

    public static function apiToken(array $user, int $ttl = 86400): string {
        return self::generateToken($user, $ttl);
    }
}
