<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\DependencyResolver;

class DependencyResolverTest extends TestCase
{
    private DependencyResolver $resolver;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->resolver = new DependencyResolver();
        $this->tmpDir   = sys_get_temp_dir() . '/nexph_deps_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . '/*.php') as $file) {
            unlink($file);
        }
        rmdir($this->tmpDir);
    }

    private function writeComponent(string $name, string $body = ''): string
    {
        $file = $this->tmpDir . '/' . $name . '.php';
        file_put_contents($file, <<<PHP
<?php
class {$name} extends Component
{
    public function render(): string
    {
        return '{$body}';
    }
}
PHP);
        return $file;
    }

    public function testResolveSingleFile(): void
    {
        $file = $this->writeComponent('Counter');

        $resolved = $this->resolver->resolve($file);

        $this->assertCount(1, $resolved);
        $this->assertEquals(realpath($file), $resolved[0]);
    }

    public function testResolveNonExistentFileReturnsEmpty(): void
    {
        $resolved = $this->resolver->resolve('/nonexistent/path/App.php');

        $this->assertEmpty($resolved);
    }

    public function testResolveWithDependency(): void
    {
        $this->writeComponent('Button');

        $appFile = $this->tmpDir . '/App.php';
        file_put_contents($appFile, <<<'PHP'
<?php
class App extends Component
{
    public function render(): string
    {
        return '<Button label="Click" />';
    }
}
PHP);

        $resolved = $this->resolver->resolve($appFile);

        $this->assertCount(2, $resolved);
        // Button should come before App (dependency first)
        $names = array_map(fn($f) => basename($f, '.php'), $resolved);
        $this->assertContains('Button', $names);
        $this->assertContains('App', $names);
        $this->assertLessThan(
            array_search('App', $names),
            array_search('Button', $names)
        );
    }

    public function testResolveNoDuplicates(): void
    {
        $file = $this->writeComponent('Counter');

        $resolved = $this->resolver->resolve($file);

        $this->assertEquals(count($resolved), count(array_unique($resolved)));
    }

    public function testResolveHandlesCircularDependency(): void
    {
        // A references B, B references A — should not infinite loop
        $fileA = $this->tmpDir . '/CompA.php';
        $fileB = $this->tmpDir . '/CompB.php';

        file_put_contents($fileA, <<<'PHP'
<?php
class CompA extends Component
{
    public function render(): string { return '<CompB />'; }
}
PHP);
        file_put_contents($fileB, <<<'PHP'
<?php
class CompB extends Component
{
    public function render(): string { return '<CompA />'; }
}
PHP);

        // Should complete without infinite loop
        $resolved = $this->resolver->resolve($fileA);

        $this->assertIsArray($resolved);
    }
}
