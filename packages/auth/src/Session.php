<?php
namespace Core\Auth;

class Session {
    private static ?SessionDriver $driver = null;
    private static bool $started = false;
    private static array $config = [];

    public static function configure(array $config): void {
        self::$config = array_merge([
            'driver' => 'file',
            'lifetime' => 7200,
            'path' => '/storage/sessions',
            'cookie_name' => 'nexph_session',
            'cookie_secure' => true,
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax',
            'regenerate_interval' => 300,
        ], $config);
        SessionManager::registerDriver('file', FileSessionDriver::class, FileSessionDriver::schema());
        SessionManager::registerDriver('database', DatabaseSessionDriver::class, DatabaseSessionDriver::schema());
        SessionManager::registerDriver('redis', RedisSessionDriver::class, RedisSessionDriver::schema());
    }

    public static function start(): void {
        if (self::$started) return;

        if (!self::$driver) {
            $driverName = self::$config['driver'] ?? 'file';
            $driverConfig = array_merge(
                self::$config,
                self::$config['drivers'][$driverName] ?? []
            );
            self::$driver = SessionManager::getDriver($driverName, $driverConfig);
        }

        $sessionId = $_COOKIE[self::$config['cookie_name']] ?? null;
        
        if ($sessionId && self::$driver->exists($sessionId)) {
            $data = self::$driver->read($sessionId);
            
            $validSession = true;
            $currentIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $currentUa = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            if (isset($data['_ip']) && $data['_ip'] !== $currentIp) {
                $validSession = false;
            }
            if (isset($data['_ua']) && $data['_ua'] !== $currentUa) {
                $validSession = false;
            }

            if ($validSession) {
                $_SESSION = $data;
                
                if (self::shouldRegenerate()) {
                    self::regenerate();
                }
            } else {
                self::$driver->destroy($sessionId);
                $sessionId = self::generateId();
                $_SESSION = [];
                self::setCookie($sessionId);
            }
        } else {
            $sessionId = self::generateId();
            $_SESSION = [];
            self::setCookie($sessionId);
        }

        $_SESSION['_id'] = $sessionId;
        $_SESSION['_ip'] = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $_SESSION['_ua'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $_SESSION['_last_activity'] = time();
        self::$started = true;
    }

    public static function regenerate(): void {
        $oldId = $_SESSION['_id'] ?? null;
        $newId = self::generateId();
        
        $data = $_SESSION;
        $data['_id'] = $newId;
        $data['_last_activity'] = time();
        $data['_regenerated_at'] = time();
        
        self::$driver->write($newId, $data);
        
        if ($oldId) {
            self::$driver->destroy($oldId);
        }
        
        $_SESSION = $data;
        self::setCookie($newId);
    }

    public static function destroy(): void {
        $sessionId = $_SESSION['_id'] ?? null;
        
        if ($sessionId) {
            self::$driver->destroy($sessionId);
        }
        
        $_SESSION = [];
        
        setcookie(
            self::$config['cookie_name'],
            '',
            time() - 3600,
            '/',
            '',
            self::$config['cookie_secure'],
            self::$config['cookie_httponly']
        );
        
        self::$started = false;
    }

    public static function get(string $key, mixed $default = null): mixed {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void {
        $_SESSION[$key] = $value;
        self::save();
    }

    public static function has(string $key): bool {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void {
        unset($_SESSION[$key]);
        self::save();
    }

    public static function flash(string $key, mixed $value): void {
        $_SESSION['_flash'][$key] = $value;
        self::save();
    }

    public static function getFlash(string $key, mixed $default = null): mixed {
        $value = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        self::save();
        return $value;
    }

    public static function save(): void {
        if (!self::$started) return;
        
        $sessionId = $_SESSION['_id'] ?? null;
        if ($sessionId) {
            self::$driver->write($sessionId, $_SESSION);
        }
    }

    public static function gc(): void {
        self::$driver->gc(self::$config['lifetime']);
    }

    private static function generateId(): string {
        return bin2hex(random_bytes(32));
    }

    private static function setCookie(string $sessionId): void {
        setcookie(
            self::$config['cookie_name'],
            $sessionId,
            [
                'expires' => time() + self::$config['lifetime'],
                'path' => '/',
                'domain' => '',
                'secure' => self::$config['cookie_secure'],
                'httponly' => self::$config['cookie_httponly'],
                'samesite' => self::$config['cookie_samesite'],
            ]
        );
    }

    private static function shouldRegenerate(): bool {
        $regeneratedAt = $_SESSION['_regenerated_at'] ?? 0;
        return (time() - $regeneratedAt) > self::$config['regenerate_interval'];
    }
}
