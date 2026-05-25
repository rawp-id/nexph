<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Compiler\JsGenerator;

class JsGeneratorTest extends TestCase
{
    private JsGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new JsGenerator();
    }

    public function testGenerateContainsComponentName(): void
    {
        $ast = [
            'class' => 'Counter',
            'properties' => [],
            'methods' => [],
        ];

        $js = $this->generator->generate($ast);

        $this->assertStringContainsString('Counter', $js);
        $this->assertStringContainsString('data-nexph-component="Counter"', $js);
    }

    public function testGenerateStateFromProperties(): void
    {
        $ast = [
            'class' => 'Counter',
            'properties' => [
                'count' => ['type' => 'int', 'default' => '0'],
                'label' => ['type' => 'string', 'default' => "'Hello'"],
            ],
            'methods' => [],
        ];

        $js = $this->generator->generate($ast);

        $this->assertStringContainsString('"count":0', $js);
        $this->assertStringContainsString('"label":"Hello"', $js);
    }

    public function testGenerateStateBoolTrue(): void
    {
        $ast = [
            'class' => 'Toggle',
            'properties' => [
                'active' => ['type' => 'bool', 'default' => 'true'],
            ],
            'methods' => [],
        ];

        $js = $this->generator->generate($ast);

        $this->assertStringContainsString('"active":true', $js);
    }

    public function testGenerateStateBoolFalse(): void
    {
        $ast = [
            'class' => 'Toggle',
            'properties' => [
                'disabled' => ['type' => 'bool', 'default' => 'false'],
            ],
            'methods' => [],
        ];

        $js = $this->generator->generate($ast);

        $this->assertStringContainsString('"disabled":false', $js);
    }

    public function testGenerateStateEmptyArray(): void
    {
        $ast = [
            'class' => 'List',
            'properties' => [
                'items' => ['type' => 'array', 'default' => '[]'],
            ],
            'methods' => [],
        ];

        $js = $this->generator->generate($ast);

        $this->assertStringContainsString('"items":[]', $js);
    }

    public function testGenerateMethodsJs(): void
    {
        $ast = [
            'class' => 'Counter',
            'properties' => [],
            'methods' => [
                'increment' => "\n        \$this->count++;\n    ",
            ],
        ];

        $js = $this->generator->generate($ast);

        $this->assertStringContainsString('increment', $js);
        $this->assertStringContainsString('state.count++', $js);
    }

    public function testGenerateMultipleMethods(): void
    {
        $ast = [
            'class' => 'Counter',
            'properties' => [],
            'methods' => [
                'increment' => "\n        \$this->count++;\n    ",
                'decrement' => "\n        \$this->count--;\n    ",
            ],
        ];

        $js = $this->generator->generate($ast);

        $this->assertStringContainsString('increment', $js);
        $this->assertStringContainsString('decrement', $js);
    }

    public function testGenerateContainsUpdateUI(): void
    {
        $ast = ['class' => 'Foo', 'properties' => [], 'methods' => []];

        $js = $this->generator->generate($ast);

        $this->assertStringContainsString('updateUI()', $js);
    }

    public function testGenerateContainsEventListeners(): void
    {
        $ast = ['class' => 'Foo', 'properties' => [], 'methods' => []];

        $js = $this->generator->generate($ast);

        $this->assertStringContainsString('data-nexph-click', $js);
        $this->assertStringContainsString('data-nexph-model', $js);
        $this->assertStringContainsString('data-nexph-submit', $js);
    }

    public function testGenerateContainsNexphGlobal(): void
    {
        $ast = ['class' => 'MyComp', 'properties' => [], 'methods' => []];

        $js = $this->generator->generate($ast);

        $this->assertStringContainsString('window.NEXPH', $js);
        $this->assertStringContainsString('window.NEXPH.components', $js);
    }

    public function testConvertPhpEmitToJs(): void
    {
        $ast = [
            'class' => 'Child',
            'properties' => [],
            'methods' => [
                'handleClick' => "\n        \$this->emit('clicked');\n    ",
            ],
        ];

        $js = $this->generator->generate($ast);

        $this->assertStringContainsString('emit("clicked")', $js);
    }

    public function testConvertPhpToggleToJs(): void
    {
        $ast = [
            'class' => 'Toggle',
            'properties' => [],
            'methods' => [
                'toggle' => "\n        \$this->active = !\$this->active;\n    ",
            ],
        ];

        $js = $this->generator->generate($ast);

        $this->assertStringContainsString('state.active = !state.active', $js);
    }
}
