<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Compiler\StyleScoper;

class StyleScoperTest extends TestCase
{
    private StyleScoper $scoper;

    protected function setUp(): void
    {
        $this->scoper = new StyleScoper();
    }

    public function testScopeIdIsDeterministic(): void
    {
        $this->assertEquals(
            $this->scoper->scopeId('Button'),
            $this->scoper->scopeId('Button')
        );
    }

    public function testScopeIdStartsWithNx(): void
    {
        $id = $this->scoper->scopeId('Button');

        $this->assertStringStartsWith('nx-', $id);
    }

    public function testScopeIdDiffersPerComponent(): void
    {
        $this->assertNotEquals(
            $this->scoper->scopeId('Button'),
            $this->scoper->scopeId('Card')
        );
    }

    public function testScopeIdLength(): void
    {
        // 'nx-' (3) + 6 hex chars = 9
        $this->assertEquals(9, strlen($this->scoper->scopeId('Button')));
    }

    public function testScopeAddsAttributeToSelector(): void
    {
        $css    = '.btn { color: red; }';
        $result = $this->scoper->scope($css, 'nx-abc123');

        $this->assertStringContainsString('[data-nx-scope="nx-abc123"]', $result);
        $this->assertStringContainsString('[data-nx-scope="nx-abc123"] .btn', $result);
    }

    public function testScopePreservesDeclarations(): void
    {
        $css    = '.btn { color: red; font-size: 16px; }';
        $result = $this->scoper->scope($css, 'nx-abc123');

        $this->assertStringContainsString('color: red', $result);
        $this->assertStringContainsString('font-size: 16px', $result);
    }

    public function testScopeDoesNotScopeBodySelector(): void
    {
        $css    = 'body { margin: 0; }';
        $result = $this->scoper->scope($css, 'nx-abc123');

        $this->assertStringContainsString('body {', $result);
        $this->assertStringNotContainsString('body[data-nx-scope', $result);
    }

    public function testScopeDoesNotScopeHtmlSelector(): void
    {
        $css    = 'html { box-sizing: border-box; }';
        $result = $this->scoper->scope($css, 'nx-abc123');

        $this->assertStringNotContainsString('html[data-nx-scope', $result);
    }

    public function testScopeHandlesMultipleSelectors(): void
    {
        $css    = '.btn, .card { padding: 10px; }';
        $result = $this->scoper->scope($css, 'nx-abc123');

        $this->assertStringContainsString('[data-nx-scope="nx-abc123"] .btn', $result);
        $this->assertStringContainsString('[data-nx-scope="nx-abc123"] .card', $result);
    }

    public function testScopeHandlesMultipleRules(): void
    {
        $css = <<<CSS
.btn { color: red; }
.card { padding: 20px; }
CSS;
        $result = $this->scoper->scope($css, 'nx-abc123');

        $this->assertStringContainsString('[data-nx-scope="nx-abc123"] .btn', $result);
        $this->assertStringContainsString('[data-nx-scope="nx-abc123"] .card', $result);
    }

    public function testScopeEmptyStringReturnsEmpty(): void
    {
        $this->assertEquals('', $this->scoper->scope('', 'nx-abc123'));
    }
}
