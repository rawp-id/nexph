<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Compiler\HtmlGenerator;

class HtmlGeneratorTest extends TestCase
{
    private HtmlGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new HtmlGenerator();
    }

    public function testGenerateEmptyComponent(): void
    {
        $ast = ['class' => 'MyComponent', 'renderMethod' => ''];

        $html = $this->generator->generate($ast);

        $this->assertStringContainsString('data-nexph-component="MyComponent"', $html);
    }

    public function testGenerateWithHeredocRender(): void
    {
        $ast = [
            'class' => 'Counter',
            'renderMethod' => <<<'PHP'
                return <<<HTML
<div><span>{$this->count}</span></div>
HTML;
PHP,
        ];

        $html = $this->generator->generate($ast);

        $this->assertStringContainsString('data-nexph-bind="count"', $html);
        $this->assertStringContainsString('data-nexph-component="Counter"', $html);
    }

    public function testProcessNxClick(): void
    {
        $ast = [
            'class' => 'Button',
            'renderMethod' => <<<'PHP'
                return <<<HTML
<button nx-click="handleClick">Click</button>
HTML;
PHP,
        ];

        $html = $this->generator->generate($ast);

        $this->assertStringContainsString('data-nexph-click="handleClick"', $html);
        $this->assertStringNotContainsString('nx-click=', $html);
    }

    public function testProcessNxModel(): void
    {
        $ast = [
            'class' => 'Form',
            'renderMethod' => <<<'PHP'
                return <<<HTML
<input nx-model="username" />
HTML;
PHP,
        ];

        $html = $this->generator->generate($ast);

        $this->assertStringContainsString('data-nexph-model="username"', $html);
        $this->assertStringNotContainsString('nx-model=', $html);
    }

    public function testProcessNxIf(): void
    {
        $ast = [
            'class' => 'Toggle',
            'renderMethod' => <<<'PHP'
                return <<<HTML
<p nx-if="visible">Hello</p>
HTML;
PHP,
        ];

        $html = $this->generator->generate($ast);

        $this->assertStringContainsString('data-nexph-if="visible"', $html);
        $this->assertStringNotContainsString('nx-if=', $html);
    }

    public function testProcessNxFor(): void
    {
        $ast = [
            'class' => 'List',
            'renderMethod' => <<<'PHP'
                return <<<HTML
<li nx-for="item in items">x</li>
HTML;
PHP,
        ];

        $html = $this->generator->generate($ast);

        $this->assertStringContainsString('data-nexph-for="item:items"', $html);
        $this->assertStringNotContainsString('nx-for=', $html);
    }

    public function testProcessNxClass(): void
    {
        $ast = [
            'class' => 'Button',
            'renderMethod' => <<<'PHP'
                return <<<HTML
<div nx-class="{'active': isActive}">text</div>
HTML;
PHP,
        ];

        $html = $this->generator->generate($ast);

        $this->assertStringContainsString('data-nexph-class=', $html);
        $this->assertStringNotContainsString('nx-class=', $html);
    }

    public function testProcessNxStyle(): void
    {
        $ast = [
            'class' => 'Styled',
            'renderMethod' => <<<'PHP'
                return <<<HTML
<div nx-style="{'color': color}">text</div>
HTML;
PHP,
        ];

        $html = $this->generator->generate($ast);

        $this->assertStringContainsString('data-nexph-style=', $html);
        $this->assertStringNotContainsString('nx-style=', $html);
    }

    public function testProcessPropBinding(): void
    {
        $ast = [
            'class' => 'Parent',
            'renderMethod' => <<<'PHP'
                return <<<HTML
<Child :message="greeting" />
HTML;
PHP,
        ];

        $html = $this->generator->generate($ast);

        $this->assertStringContainsString('data-nexph-prop-message="greeting"', $html);
    }

    public function testProcessEventBinding(): void
    {
        $ast = [
            'class' => 'Parent',
            'renderMethod' => <<<'PHP'
                return <<<HTML
<Child @click="handleClick" />
HTML;
PHP,
        ];

        $html = $this->generator->generate($ast);

        $this->assertStringContainsString('data-nexph-event-click="handleClick"', $html);
    }

    public function testProcessNxSubmitPrevent(): void
    {
        $ast = [
            'class' => 'Form',
            'renderMethod' => <<<'PHP'
                return <<<HTML
<form nx-submit.prevent="onSubmit"></form>
HTML;
PHP,
        ];

        $html = $this->generator->generate($ast);

        $this->assertStringContainsString('data-nexph-submit="onSubmit"', $html);
        $this->assertStringContainsString('data-nexph-prevent', $html);
    }

    public function testFallbackComponentWrapper(): void
    {
        $ast = ['class' => 'Fallback'];

        $html = $this->generator->generate($ast);

        $this->assertStringContainsString('data-nexph-component="Fallback"', $html);
    }
}
