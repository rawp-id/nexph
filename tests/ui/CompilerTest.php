<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Compiler\Compiler;

class CompilerTest extends TestCase
{
    private Compiler $compiler;

    protected function setUp(): void
    {
        $this->compiler = new Compiler();
    }

    private function makeSource(string $class, string $props = '', string $render = ''): string
    {
        $renderBody = $render ?: "return '<div>Hello</div>';";
        return <<<PHP
<?php
class {$class} extends Component
{
    {$props}
    public function render(): string
    {
        {$renderBody}
    }
}
PHP;
    }

    public function testCompileReturnsAllKeys(): void
    {
        $output = $this->compiler->compile($this->makeSource('Counter'));

        $this->assertArrayHasKey('html', $output);
        $this->assertArrayHasKey('css', $output);
        $this->assertArrayHasKey('js', $output);
        $this->assertArrayHasKey('manifest', $output);
    }

    public function testCompileHtmlContainsComponent(): void
    {
        $output = $this->compiler->compile($this->makeSource('Counter'));

        $this->assertStringContainsString('data-nexph-component="Counter"', $output['html']);
    }

    public function testCompileJsContainsComponent(): void
    {
        $output = $this->compiler->compile($this->makeSource('Counter'));

        $this->assertStringContainsString('Counter', $output['js']);
    }

    public function testCompileCssIsNotEmpty(): void
    {
        $output = $this->compiler->compile($this->makeSource('Counter'));

        $this->assertNotEmpty($output['css']);
    }

    public function testCompileManifestContainsComponentName(): void
    {
        $output = $this->compiler->compile($this->makeSource('Counter'));

        $this->assertEquals('Counter', $output['manifest']['component']);
    }

    public function testCompileManifestHasEventsAndState(): void
    {
        $output = $this->compiler->compile($this->makeSource('Counter'));

        $this->assertArrayHasKey('events', $output['manifest']);
        $this->assertArrayHasKey('state', $output['manifest']);
    }

    public function testCompileWithProperties(): void
    {
        $source = $this->makeSource(
            'Counter',
            'public int $count = 0;',
            "return <<<HTML\n<div>{\$this->count}</div>\nHTML;"
        );

        $output = $this->compiler->compile($source);

        $this->assertStringContainsString('"count":0', $output['js']);
    }

    public function testCompileWithMethod(): void
    {
        $source = <<<'PHP'
<?php
class Counter extends Component
{
    public int $count = 0;

    public function increment()
    {
        $this->count++;
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
PHP;

        $output = $this->compiler->compile($source);

        $this->assertStringContainsString('increment', $output['js']);
        $this->assertStringContainsString('state.count++', $output['js']);
    }

    public function testCompileBindingInHtml(): void
    {
        $source = $this->makeSource(
            'Greeter',
            'public string $name = \'World\';',
            "return <<<HTML\n<p>{\$this->name}</p>\nHTML;"
        );

        $output = $this->compiler->compile($source);

        $this->assertStringContainsString('data-nexph-bind="name"', $output['html']);
    }

    public function testCompileNxClickInHtml(): void
    {
        $source = $this->makeSource(
            'Button',
            '',
            "return <<<HTML\n<button nx-click=\"handleClick\">Go</button>\nHTML;"
        );

        $output = $this->compiler->compile($source);

        $this->assertStringContainsString('data-nexph-click="handleClick"', $output['html']);
    }
}
