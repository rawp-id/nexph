<?php
/**
 * Loader Test Suite
 * Run: php tests/Loader/LoaderTest.php
 */

require_once __DIR__ . '/../../autoload.php';
require_once __DIR__ . '/../../core/Runtime/Loader/Contracts/ModuleInterface.php';
require_once __DIR__ . '/../../core/Runtime/Loader/Contracts/ServiceProviderInterface.php';
require_once __DIR__ . '/../../core/Runtime/Loader/Contracts/RouteProviderInterface.php';
require_once __DIR__ . '/../../core/Runtime/Loader/Contracts/CommandProviderInterface.php';
require_once __DIR__ . '/../../core/Runtime/Loader/Contracts/HookProviderInterface.php';
require_once __DIR__ . '/../../core/Runtime/Loader/Contracts/ConfigProviderInterface.php';
require_once __DIR__ . '/../../core/Runtime/Loader/Contracts/PreloadableInterface.php';
require_once __DIR__ . '/../../core/Runtime/Loader/Contracts/BootableInterface.php';
require_once __DIR__ . '/../../core/Runtime/Loader/Contracts/ShutdownableInterface.php';
require_once __DIR__ . '/../../core/Runtime/Loader/Exceptions/ModuleLoadException.php';
require_once __DIR__ . '/../../core/Runtime/Loader/Exceptions/ManifestValidationException.php';
require_once __DIR__ . '/../../core/Runtime/Loader/Exceptions/ModuleNotFoundException.php';
require_once __DIR__ . '/../../core/Runtime/Loader/Exceptions/ModuleConflictException.php';
require_once __DIR__ . '/../../core/Runtime/Loader/ManifestParser.php';
require_once __DIR__ . '/../../core/Runtime/Loader/ManifestValidator.php';
require_once __DIR__ . '/../../core/Runtime/Loader/ModuleManifest.php';
require_once __DIR__ . '/../../core/Runtime/Loader/ModuleRegistry.php';
require_once __DIR__ . '/../../core/Runtime/Loader/RuntimePreloader.php';
require_once __DIR__ . '/../../core/Runtime/Loader/LazyModuleResolver.php';
require_once __DIR__ . '/../../core/Runtime/Loader/RuntimeLoader.php';

use Core\Runtime\Loader\ManifestParser;
use Core\Runtime\Loader\ManifestValidator;
use Core\Runtime\Loader\ModuleManifest;
use Core\Runtime\Loader\ModuleRegistry;
use Core\Runtime\Loader\RuntimePreloader;
use Core\Runtime\Loader\LazyModuleResolver;
use Core\Runtime\Loader\RuntimeLoader;
use Core\Runtime\Loader\Exceptions\ManifestValidationException;
use Core\Runtime\Loader\Exceptions\ModuleNotFoundException;
use Core\Runtime\Loader\Exceptions\ModuleConflictException;
use Core\Runtime\Loader\Exceptions\ModuleLoadException;

$passed = 0;
$failed = 0;
$fixtures = __DIR__ . '/fixtures';

function assert_true(bool $condition, string $label): void {
    global $passed, $failed;
    if ($condition) { $passed++; echo "  ✓ {$label}\n"; }
    else { $failed++; echo "  ✗ {$label}\n"; }
}

function assert_throws(string $class, callable $fn, string $label): void {
    global $passed, $failed;
    try { $fn(); $failed++; echo "  ✗ {$label} (no exception)\n"; }
    catch (\Throwable $e) {
        if ($e instanceof $class) { $passed++; echo "  ✓ {$label}\n"; }
        else { $failed++; echo "  ✗ {$label} (got " . get_class($e) . ")\n"; }
    }
}

// === ManifestParser ===
echo "\nManifestParser:\n";

$parser = new ManifestParser();
$data = $parser->parse($fixtures . '/valid-package/nexph.json');
assert_true($data['name'] === 'test/valid-package', 'parse valid JSON');
assert_true($data['version'] === '1.0.0', 'parse version');

assert_throws(\RuntimeException::class, function() use ($parser) {
    $parser->parse('/nonexistent/nexph.json');
}, 'throws on missing file');

assert_throws(\RuntimeException::class, function() use ($parser, $fixtures) {
    $parser->parse($fixtures . '/invalid-package/nexph.json');
}, 'throws on invalid JSON');

$result = $parser->parseFromDir($fixtures . '/valid-package');
assert_true($result !== null && $result['name'] === 'test/valid-package', 'parseFromDir valid');

$result = $parser->parseFromDir('/nonexistent');
assert_true($result === null, 'parseFromDir missing returns null');

// === ManifestValidator ===
echo "\nManifestValidator:\n";

$validator = new ManifestValidator();
$validator->validate($data, 'test');
assert_true(true, 'valid manifest passes');

assert_throws(ManifestValidationException::class, function() use ($validator) {
    $validator->validate([], 'test');
}, 'empty manifest fails');

assert_throws(ManifestValidationException::class, function() use ($validator) {
    $validator->validate(['name' => 'bad name!', 'version' => '1.0.0'], 'test');
}, 'invalid name fails');

assert_throws(ManifestValidationException::class, function() use ($validator) {
    $validator->validate(['name' => 'test/pkg', 'version' => 'abc'], 'test');
}, 'invalid version fails');

assert_throws(ManifestValidationException::class, function() use ($validator) {
    $validator->validate(['name' => 'test/pkg', 'version' => '1.0.0', 'type' => 'invalid'], 'test');
}, 'invalid type fails');

// === ModuleManifest ===
echo "\nModuleManifest:\n";

$manifest = ModuleManifest::fromArray($data, $fixtures . '/valid-package');
assert_true($manifest->name === 'test/valid-package', 'fromArray name');
assert_true($manifest->version === '1.0.0', 'fromArray version');
assert_true($manifest->path === $fixtures . '/valid-package', 'fromArray path');
assert_true($manifest->preload === ['src/Helper.php'], 'fromArray preload');

// === ModuleRegistry ===
echo "\nModuleRegistry:\n";

$registry = new ModuleRegistry();
$registry->register($manifest);
assert_true($registry->has('test/valid-package'), 'has registered module');
assert_true(!$registry->has('nonexistent'), 'has returns false for missing');
assert_true($registry->count() === 1, 'count is 1');
assert_true($registry->get('test/valid-package')->name === 'test/valid-package', 'get returns manifest');

assert_throws(ModuleConflictException::class, function() use ($registry, $manifest) {
    $registry->register($manifest);
}, 'duplicate registration throws');

assert_throws(ModuleNotFoundException::class, function() use ($registry) {
    $registry->get('nonexistent');
}, 'get missing throws');

$registry->disable('test/valid-package');
assert_true(!$registry->isEnabled('test/valid-package'), 'disable works');
assert_true(count($registry->enabled()) === 0, 'enabled excludes disabled');

$registry->enable('test/valid-package');
assert_true($registry->isEnabled('test/valid-package'), 'enable works');

$stats = $registry->stats();
assert_true($stats['total'] === 1 && $stats['enabled'] === 1, 'stats correct');

// === RuntimePreloader ===
echo "\nRuntimePreloader:\n";

$preloader = new RuntimePreloader();
$preloader->preload($manifest);
$stats = $preloader->stats();
assert_true($stats['count'] === 1, 'preloaded 1 file');
assert_true($stats['errors'] === 0, 'no preload errors');
assert_true(class_exists('Test\ValidPackage\Helper'), 'preloaded class available');

// Duplicate preload
$preloader->preload($manifest);
$stats = $preloader->stats();
assert_true($stats['count'] === 1, 'deduplicate preload');

// Path traversal
$traversalData = $parser->parse($fixtures . '/traversal-package/nexph.json');
$traversalManifest = ModuleManifest::fromArray($traversalData, $fixtures . '/traversal-package');
$preloader->preload($traversalManifest);
$stats = $preloader->stats();
assert_true($stats['errors'] >= 1, 'path traversal blocked');

// === LazyModuleResolver ===
echo "\nLazyModuleResolver:\n";

$lazy = new LazyModuleResolver();
$lazy->buildMap(['test/valid-package' => $manifest]);
assert_true($lazy->resolveClass('Test\ValidPackage\LazyService') === 'test/valid-package', 'resolve exact class');
assert_true($lazy->resolveClass('Test\ValidPackage\Anything') === 'test/valid-package', 'resolve PSR-4 prefix');
assert_true($lazy->resolveClass('Unknown\Class') === null, 'resolve unknown returns null');

$stats = $lazy->stats();
assert_true($stats['hits'] === 2, 'lazy hits counted');
assert_true($stats['misses'] === 1, 'lazy misses counted');

// === RuntimeLoader (discovery) ===
echo "\nRuntimeLoader:\n";

$loader = new RuntimeLoader();
$loader->discover([$fixtures]);
$reg = $loader->getRegistry();
assert_true($reg->has('test/valid-package'), 'discovered valid package');
assert_true($reg->has('test/traversal'), 'discovered traversal package');
assert_true(!$reg->has('test/invalid'), 'skipped invalid package');

// Boot
$loader->boot();
assert_true($loader->isLoaded(), 'loader booted');
$loaderStats = $loader->stats();
assert_true($loaderStats['booted'] >= 2, 'booted modules');
assert_true($loaderStats['preloader']['count'] >= 1, 'preloader ran');

// === Summary ===
echo "\n" . str_repeat('─', 40) . "\n";
echo "Results: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
