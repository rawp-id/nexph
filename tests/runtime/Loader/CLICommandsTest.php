<?php
/**
 * CLI Commands Test Suite
 * Run: php tests/runtime/Loader/CLICommandsTest.php
 */

require_once __DIR__ . '/../../../src/Runtime/CLI/Command.php';
require_once __DIR__ . '/../../../src/Runtime/CLI/PackageUpdateCommand.php';
require_once __DIR__ . '/../../../src/Runtime/CLI/PackageRestoreCommand.php';
require_once __DIR__ . '/../../../src/Runtime/CLI/PackageSearchCommand.php';
require_once __DIR__ . '/../../../src/Runtime/CLI/PackagePublishCommand.php';
require_once __DIR__ . '/../../../src/Runtime/CLI/PackageInfoCommand.php';
require_once __DIR__ . '/../../../src/Runtime/CLI/PackageInitCommand.php';
require_once __DIR__ . '/../../../src/Runtime/CLI/PackageValidateCommand.php';
require_once __DIR__ . '/../../../src/Runtime/CLI/ModuleDiscoverCommand.php';
require_once __DIR__ . '/../../../src/Runtime/CLI/ModulePreloadCommand.php';
require_once __DIR__ . '/../../../src/Runtime/CLI/ModuleListCommand.php';
require_once __DIR__ . '/../../../src/Runtime/CLI/ModuleValidateCommand.php';
require_once __DIR__ . '/../../../src/Runtime/CLI/ModuleInfoCommand.php';
require_once __DIR__ . '/../../../src/Runtime/CLI/ComposerBridgeCommand.php';
require_once __DIR__ . '/../../../src/Runtime/CLI/PackageInstallCommand.php';
require_once __DIR__ . '/../../../src/Runtime/CLI/PackageRemoveCommand.php';

$passed = 0;
$failed = 0;

function assert_true(bool $condition, string $label): void {
    global $passed, $failed;
    if ($condition) { $passed++; echo "  ✓ {$label}\n"; }
    else { $failed++; echo "  ✗ {$label}\n"; }
}

function capture(callable $fn): string {
    ob_start();
    $fn();
    return ob_get_clean();
}

// === PackageUpdateCommand ===
echo "\nPackageUpdateCommand:\n";

$cmd = new \Core\Runtime\CLI\PackageUpdateCommand();
assert_true($cmd->getName() === 'update', 'name is update');
assert_true($cmd->getDescription() !== '', 'has description');

// === PackageRestoreCommand ===
echo "\nPackageRestoreCommand:\n";

$cmd = new \Core\Runtime\CLI\PackageRestoreCommand();
assert_true($cmd->getName() === 'restore', 'name is restore');
assert_true($cmd->getDescription() !== '', 'has description');

// No lockfile → error
$output = capture(function() use ($cmd) {
    $code = $cmd->execute(['--quiet']);
});
// Should fail gracefully without lockfile

// === PackageSearchCommand ===
echo "\nPackageSearchCommand:\n";

$cmd = new \Core\Runtime\CLI\PackageSearchCommand();
assert_true($cmd->getName() === 'search', 'name is search');

// No query → error
$output = capture(function() use ($cmd) {
    $code = $cmd->execute([]);
});
// Should show usage

// === PackagePublishCommand ===
echo "\nPackagePublishCommand:\n";

$cmd = new \Core\Runtime\CLI\PackagePublishCommand();
assert_true($cmd->getName() === 'publish', 'name is publish');

// === PackageInfoCommand ===
echo "\nPackageInfoCommand:\n";

$cmd = new \Core\Runtime\CLI\PackageInfoCommand();
assert_true($cmd->getName() === 'package:info', 'name is package:info');

// === PackageInitCommand ===
echo "\nPackageInitCommand:\n";

$cmd = new \Core\Runtime\CLI\PackageInitCommand();
assert_true($cmd->getName() === 'package:init', 'name is package:init');

// Test init in temp dir
$tmpDir = sys_get_temp_dir() . '/nexph_test_init_' . uniqid();
mkdir($tmpDir, 0755, true);
$output = capture(function() use ($cmd, $tmpDir) {
    $cmd->execute([$tmpDir, '--name=test/my-pkg', '--version=1.0.0']);
});
assert_true(file_exists($tmpDir . '/nexph.json'), 'init creates nexph.json');
assert_true(is_dir($tmpDir . '/src'), 'init creates src/');

$manifest = json_decode(file_get_contents($tmpDir . '/nexph.json'), true);
assert_true($manifest['name'] === 'test/my-pkg', 'init sets name');
assert_true($manifest['version'] === '1.0.0', 'init sets version');

// Init again → error (already exists)
$code = $cmd->execute([$tmpDir]);
assert_true($code === 1, 'init fails if nexph.json exists');

// Cleanup
array_map('unlink', glob($tmpDir . '/src/*'));
@rmdir($tmpDir . '/src');
@unlink($tmpDir . '/nexph.json');
@rmdir($tmpDir);

// === PackageValidateCommand ===
echo "\nPackageValidateCommand:\n";

$cmd = new \Core\Runtime\CLI\PackageValidateCommand();
assert_true($cmd->getName() === 'package:validate', 'name is package:validate');

// Validate fixtures
$fixtures = __DIR__ . '/fixtures';
$output = capture(function() use ($cmd, $fixtures) {
    $cmd->execute([$fixtures . '/valid-package']);
});
assert_true(str_contains($output, '✓'), 'valid package passes validation');

// === ModuleDiscoverCommand ===
echo "\nModuleDiscoverCommand:\n";

$cmd = new \Core\Runtime\CLI\ModuleDiscoverCommand();
assert_true($cmd->getName() === 'module:discover', 'name is module:discover');

// === ModulePreloadCommand ===
echo "\nModulePreloadCommand:\n";

$cmd = new \Core\Runtime\CLI\ModulePreloadCommand();
assert_true($cmd->getName() === 'module:preload', 'name is module:preload');

// === ComposerBridgeCommand ===
echo "\nComposerBridgeCommand:\n";

$cmd = new \Core\Runtime\CLI\ComposerBridgeCommand();
assert_true($cmd->getName() === 'composer:bridge', 'name is composer:bridge');

// JSON output
$output = capture(function() use ($cmd) {
    $cmd->execute(['--json']);
});
$data = json_decode($output, true);
assert_true(is_array($data), 'json output is valid');
assert_true(isset($data['mode']), 'json has mode field');
assert_true(isset($data['composer_json']), 'json has composer_json field');

// === Quiet mode ===
echo "\nQuiet mode:\n";

$cmd = new \Core\Runtime\CLI\PackageSearchCommand();
$output = capture(function() use ($cmd) {
    $cmd->execute(['--quiet']);
});
assert_true($output === '', 'quiet suppresses output');

// === Summary ===
echo "\n" . str_repeat('─', 40) . "\n";
echo "Results: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
