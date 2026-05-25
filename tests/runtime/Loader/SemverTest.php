<?php
/**
 * Semver + Dependency Resolver Test Suite
 * Run: php tests/Loader/SemverTest.php
 */

require_once __DIR__ . '/../../autoload.php';
require_once __DIR__ . '/../../core/Runtime/Package/SemverResolver.php';
require_once __DIR__ . '/../../core/Runtime/Package/DependencyResolver.php';
require_once __DIR__ . '/../../core/Runtime/Package/PackageRegistryClient.php';
require_once __DIR__ . '/../../core/Runtime/Package/PackageLock.php';
require_once __DIR__ . '/../../core/Runtime/Package/PackageResolveException.php';

use Core\Runtime\Package\SemverResolver;
use Core\Runtime\Package\DependencyResolver;
use Core\Runtime\Package\PackageRegistryClient;
use Core\Runtime\Package\PackageLock;

$passed = 0;
$failed = 0;

function assert_true(bool $condition, string $label): void {
    global $passed, $failed;
    if ($condition) { $passed++; echo "  ✓ {$label}\n"; }
    else { $failed++; echo "  ✗ {$label}\n"; }
}

// === SemverResolver ===
echo "\nSemverResolver:\n";

$s = new SemverResolver();

// Exact
assert_true($s->satisfies('1.0.0', '1.0.0'), 'exact match');
assert_true(!$s->satisfies('1.0.1', '1.0.0'), 'exact no match');

// Wildcard
assert_true($s->satisfies('1.0.0', '*'), 'wildcard matches all');
assert_true($s->satisfies('2.5.3', '2.5.*'), 'wildcard minor');
assert_true(!$s->satisfies('2.6.0', '2.5.*'), 'wildcard minor no match');

// Caret ^
assert_true($s->satisfies('1.2.3', '^1.0.0'), 'caret within major');
assert_true($s->satisfies('1.9.9', '^1.0.0'), 'caret upper bound');
assert_true(!$s->satisfies('2.0.0', '^1.0.0'), 'caret next major excluded');
assert_true($s->satisfies('0.2.5', '^0.2.0'), 'caret zero major');
assert_true(!$s->satisfies('0.3.0', '^0.2.0'), 'caret zero major upper');

// Tilde ~
assert_true($s->satisfies('1.2.5', '~1.2.0'), 'tilde within minor');
assert_true(!$s->satisfies('1.3.0', '~1.2.0'), 'tilde next minor excluded');

// Comparison operators
assert_true($s->satisfies('2.0.0', '>=1.0.0'), '>= satisfied');
assert_true(!$s->satisfies('0.9.0', '>=1.0.0'), '>= not satisfied');
assert_true($s->satisfies('1.0.0', '<=2.0.0'), '<= satisfied');
assert_true($s->satisfies('1.5.0', '>1.0.0'), '> satisfied');
assert_true(!$s->satisfies('1.0.0', '>1.0.0'), '> not satisfied (equal)');
assert_true($s->satisfies('0.9.0', '<1.0.0'), '< satisfied');
assert_true($s->satisfies('1.0.1', '!=1.0.0'), '!= satisfied');
assert_true(!$s->satisfies('1.0.0', '!=1.0.0'), '!= not satisfied');

// Range
assert_true($s->satisfies('1.5.0', '1.0.0 - 2.0.0'), 'range within');
assert_true($s->satisfies('1.0.0', '1.0.0 - 2.0.0'), 'range lower bound');
assert_true($s->satisfies('2.0.0', '1.0.0 - 2.0.0'), 'range upper bound');
assert_true(!$s->satisfies('2.0.1', '1.0.0 - 2.0.0'), 'range above');

// Multi-constraint
assert_true($s->satisfies('1.5.0', '>=1.0.0 <2.0.0'), 'multi-constraint within');
assert_true(!$s->satisfies('2.0.0', '>=1.0.0 <2.0.0'), 'multi-constraint upper excluded');

// OR
assert_true($s->satisfies('1.0.0', '1.0.0||2.0.0'), 'OR first match');
assert_true($s->satisfies('2.0.0', '1.0.0||2.0.0'), 'OR second match');
assert_true(!$s->satisfies('3.0.0', '1.0.0||2.0.0'), 'OR no match');

// findBest
$versions = ['1.0.0', '1.1.0', '1.2.0', '2.0.0', '2.1.0-beta'];
assert_true($s->findBest($versions, '^1.0.0') === '1.2.0', 'findBest caret');
assert_true($s->findBest($versions, '>=2.0.0') === '2.0.0', 'findBest >= stable only');
assert_true($s->findBest($versions, '>=2.0.0', true) === '2.1.0-beta', 'findBest with prerelease');
assert_true($s->findBest($versions, '^3.0.0') === null, 'findBest no match');

// parse
$parsed = $s->parse('1.2.3-beta.1');
assert_true($parsed['major'] === 1 && $parsed['minor'] === 2 && $parsed['patch'] === 3, 'parse numbers');
assert_true($parsed['prerelease'] === 'beta.1', 'parse prerelease');

// isStable
assert_true($s->isStable('1.0.0'), 'stable version');
assert_true(!$s->isStable('1.0.0-alpha'), 'prerelease not stable');

// === DependencyResolver ===
echo "\nDependencyResolver:\n";

$tmpLock = sys_get_temp_dir() . '/nexph_test_' . uniqid() . '.lock';
$lock = new PackageLock($tmpLock);
$registry = new PackageRegistryClient('https://registry.nexph.dev', '');
$resolver = new DependencyResolver($registry, $lock);

// Resolve unknown package (fallback)
$result = $resolver->resolve('nexph/unknown', '^1.0.0');
assert_true($result['name'] === 'nexph/unknown', 'resolve fallback name');
assert_true($result['version'] === '1.0.0', 'resolve fallback strips caret');
assert_true($result['source'] === 'local', 'resolve fallback source');

// Resolve from lock
$lock->addPackage(['name' => 'nexph/cached', 'version' => '1.5.0', 'source' => 'registry', 'checksum' => 'abc', 'requires' => []]);
$result = $resolver->resolve('nexph/cached', '^1.0.0');
assert_true($result['version'] === '1.5.0', 'resolve from lock');
assert_true($result['source'] === 'lock', 'resolve lock source');

// Resolve tree
$tree = $resolver->resolveTree([
    ['name' => 'nexph/a', 'constraint' => '^1.0.0'],
    ['name' => 'nexph/b', 'constraint' => '>=2.0.0'],
]);
assert_true(isset($tree['nexph/a']) && isset($tree['nexph/b']), 'resolve tree both');

// Circular detection
$circular = $resolver->detectCircular([
    'a' => ['b' => '^1.0.0'],
    'b' => ['c' => '^1.0.0'],
    'c' => ['a' => '^1.0.0'],
]);
assert_true(count($circular) > 0, 'detect circular dependency');

$noCycle = $resolver->detectCircular([
    'a' => ['b' => '^1.0.0'],
    'b' => ['c' => '^1.0.0'],
    'c' => [],
]);
assert_true(count($noCycle) === 0, 'no circular when linear');

// Cleanup
@unlink($tmpLock);

// === Summary ===
echo "\n" . str_repeat('─', 40) . "\n";
echo "Results: {$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
