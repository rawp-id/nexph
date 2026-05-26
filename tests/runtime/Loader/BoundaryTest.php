<?php
/**
 * Module Boundary Test Suite
 * Run: php tests/runtime/Loader/BoundaryTest.php
 */

require_once __DIR__ . '/../../../autoload.php';

use Core\Runtime\Loader\ManifestParser;
use Core\Runtime\Loader\ManifestValidator;
use Core\Runtime\Loader\ModuleManifest;
use Core\Runtime\Loader\ModuleRegistry;
use Core\Runtime\Loader\RuntimeLoader;
use Core\Runtime\Loader\Exceptions\ManifestValidationException;
use Core\Runtime\Loader\Exceptions\ModuleConflictException;

$passed = 0;
$failed = 0;

function assert_true(bool $condition, string $label): void {
    global $passed, $failed;
    if ($condition) { $passed++; echo "  ✓ {$label}\n"; }
    else { $failed++; echo "  ✗ {$label}\n"; }
}

$root = dirname(__DIR__, 3);

// === bags/local discovery ===
echo "\nBags/Local Discovery:\n";

$loader = new RuntimeLoader();
$loader->discover([$root . '/bags/local']);
$reg = $loader->getRegistry();

assert_true($reg->has('nexph/auth'), 'discovered nexph/auth');
assert_true($reg->has('nexph/cache'), 'discovered nexph/cache');
assert_true($reg->has('nexph/generator'), 'discovered nexph/generator');
assert_true($reg->has('nexph/ui'), 'discovered nexph/ui');
assert_true($reg->count() === 4, 'exactly 4 local packages');

// === src/ not auto-discovered ===
echo "\nSrc Not Discovered:\n";

$loader2 = new RuntimeLoader();
$loader2->discover([$root . '/src']);
$reg2 = $loader2->getRegistry();
assert_true($reg2->count() === 0, 'src/ has no packages (no nexph.json in src subdirs)');

// === invalid manifest ignored ===
echo "\nInvalid Manifest:\n";

$tmpDir = sys_get_temp_dir() . '/nexph_boundary_test_' . uniqid();
mkdir($tmpDir . '/bad-pkg', 0755, true);
file_put_contents($tmpDir . '/bad-pkg/nexph.json', '{ invalid }');

$loader3 = new RuntimeLoader();
$loader3->discover([$tmpDir]);
assert_true($loader3->getRegistry()->count() === 0, 'invalid JSON skipped');

// Missing name
mkdir($tmpDir . '/no-name', 0755, true);
file_put_contents($tmpDir . '/no-name/nexph.json', json_encode(['version' => '1.0.0']));

$loader4 = new RuntimeLoader();
$loader4->discover([$tmpDir]);
assert_true($loader4->getRegistry()->count() === 0, 'missing name skipped');

// === duplicate package name ===
echo "\nDuplicate Detection:\n";

$dupDir = sys_get_temp_dir() . '/nexph_dup_test_' . uniqid();
mkdir($dupDir . '/pkg-a', 0755, true);
mkdir($dupDir . '/pkg-b', 0755, true);
file_put_contents($dupDir . '/pkg-a/nexph.json', json_encode(['name' => 'test/dup', 'version' => '1.0.0']));
file_put_contents($dupDir . '/pkg-b/nexph.json', json_encode(['name' => 'test/dup', 'version' => '2.0.0']));

$loader5 = new RuntimeLoader();
$loader5->discover([$dupDir]);
assert_true($loader5->getRegistry()->count() === 1, 'duplicate name: only first registered');

// === source detection ===
echo "\nSource Detection:\n";

$authManifest = $reg->get('nexph/auth');
assert_true(str_contains($authManifest->path, '/bags/local/'), 'auth path contains bags/local');

// === validator rejects bad fields ===
echo "\nValidator:\n";

$validator = new ManifestValidator();

$caught = false;
try { $validator->validate(['name' => 'INVALID!', 'version' => '1.0.0'], 'test'); }
catch (ManifestValidationException $e) { $caught = true; }
assert_true($caught, 'rejects invalid name');

$caught = false;
try { $validator->validate(['name' => 'ok/pkg', 'version' => 'bad'], 'test'); }
catch (ManifestValidationException $e) { $caught = true; }
assert_true($caught, 'rejects invalid version');

$caught = false;
try { $validator->validate(['name' => 'ok/pkg', 'version' => '1.0.0', 'autoload' => 'string'], 'test'); }
catch (ManifestValidationException $e) { $caught = true; }
assert_true($caught, 'rejects non-array autoload');

// Cleanup
exec("rm -rf " . escapeshellarg($tmpDir) . " " . escapeshellarg($dupDir));

// === Summary ===
echo "\n" . str_repeat('─', 40) . "\n";
echo "Results: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
