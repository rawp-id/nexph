<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Compiler\ComponentResolver;
use Nexph\Runtime\ComponentRegistry;

class ComponentResolverTest extends TestCase
{
    private ComponentResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new ComponentResolver();

        $reflection = new \ReflectionClass(ComponentRegistry::class);
        $components = $reflection->getProperty('components');
        $components->setValue(null, []);

        $aliases = $reflection->getProperty('aliases');
        $aliases->setValue(null, []);
    }

    public function testResolvePascalCaseComponents(): void
    {
        $html = '<Button label="Click" /><Card><p>content</p></Card>';

        $components = $this->resolver->resolve($html);

        $this->assertContains('Button', $components);
        $this->assertContains('Card', $components);
    }

    public function testResolveKebabCaseComponents(): void
    {
        ComponentRegistry::register('MyButton', 'App\\Components\\MyButton');

        $html = '<my-button label="Click" />';

        $components = $this->resolver->resolve($html);

        $this->assertContains('my-button', $components);
    }

    public function testNoDuplicateComponents(): void
    {
        $html = '<Button /><Button /><Button />';

        $components = $this->resolver->resolve($html);

        $this->assertCount(1, array_filter($components, fn($c) => $c === 'Button'));
    }

    public function testIsComponentPascalCase(): void
    {
        $this->assertTrue($this->resolver->isComponent('Button'));
        $this->assertTrue($this->resolver->isComponent('MyComponent'));
        $this->assertTrue($this->resolver->isComponent('TodoList'));
    }

    public function testIsComponentNotNativeTag(): void
    {
        $this->assertFalse($this->resolver->isComponent('div'));
        $this->assertFalse($this->resolver->isComponent('span'));
        $this->assertFalse($this->resolver->isComponent('button'));
    }

    public function testIsComponentKebabCaseRegistered(): void
    {
        ComponentRegistry::register('MyButton', 'App\\Components\\MyButton');

        $this->assertTrue($this->resolver->isComponent('my-button'));
    }

    public function testIsComponentKebabCaseUnregistered(): void
    {
        $this->assertFalse($this->resolver->isComponent('not-registered'));
    }

    public function testResolveEmptyHtml(): void
    {
        $components = $this->resolver->resolve('<div><p>No components</p></div>');

        $this->assertEmpty($components);
    }
}
