<?php
function e(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8');
}

function env(string $key, mixed $default = null): mixed {
    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', __DIR__);
}

spl_autoload_register(function ($class) {
    // Core\* → src/
    $prefix = 'Core\\';
    $base_dir = __DIR__ . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) === 0) {
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (!file_exists($file) && str_contains($relative_class, 'Middleware\\')) {
            $file = $base_dir . 'Server/Middleware/Middleware.php';
        }

        if (file_exists($file)) {
            require $file;
            return;
        }
    }

    // Nexph\* → ui/
    $prefix = 'Nexph\\';
    $base_dir = __DIR__ . '/ui/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) === 0) {
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

// Boot loader for stateless FPM
if (php_sapi_name() !== 'cli') {
    $loaderPaths = array_filter([
        __DIR__ . '/nexph_modules',
        __DIR__ . '/packages',
        __DIR__ . '/modules',
    ], 'is_dir');

    if (!empty($loaderPaths) && file_exists(__DIR__ . '/src/Runtime/Loader/RuntimeLoader.php')) {
        $GLOBALS['__nexph_loader'] = new Core\Runtime\Loader\RuntimeLoader();
        $GLOBALS['__nexph_loader']->discover($loaderPaths);
        $GLOBALS['__nexph_loader']->boot();
    }
}
