<?php
function e(mixed $val): string {
    return htmlspecialchars((string)$val, ENT_QUOTES, "UTF-8");
}
function env(string $key, mixed $default = null): mixed {
    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}
if (!defined("BASE_PATH")) {
    define("BASE_PATH", __DIR__);
}
$autoloadMap = [
    "Core\\" => __DIR__ . "/bags/runtime/nexph-core/src/",
    "Nexph\\" => __DIR__ . "/bags/runtime/nexph-ui/src/",
];
spl_autoload_register(function (string $class) use ($autoloadMap): void {
    foreach ($autoloadMap as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace("\\", "/", $relativeClass) . ".php";
        if (!file_exists($file) && str_starts_with($prefix, "Core") && str_contains($relativeClass, "Middleware\\")) {
            $file = $baseDir . "Server/Middleware/Middleware.php";
        }
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});
if (php_sapi_name() !== "cli") {
    $loaderPaths = array_filter([
        __DIR__ . "/bags/runtime",
        __DIR__ . "/bags/local",
        __DIR__ . "/bags/installed",
        __DIR__ . "/modules",
    ], "is_dir");
    $runtimeLoader = __DIR__ . "/bags/runtime/nexph-core/src/Runtime/Loader/RuntimeLoader.php";
    if (!empty($loaderPaths) && file_exists($runtimeLoader)) {
        $GLOBALS["__nexph_loader"] = new Core\Runtime\Loader\RuntimeLoader();
        $GLOBALS["__nexph_loader"]->discover($loaderPaths);
        $GLOBALS["__nexph_loader"]->boot();
    }
}