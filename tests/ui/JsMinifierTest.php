<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\JsMinifier;

class JsMinifierTest extends TestCase
{
    private JsMinifier $minifier;

    protected function setUp(): void
    {
        $this->minifier = new JsMinifier();
    }

    public function testRemovesSingleLineComments(): void
    {
        $js = "const x = 1; // this is a comment\nconst y = 2;";

        $result = $this->minifier->minify($js);

        $this->assertStringNotContainsString('// this is a comment', $result);
        $this->assertStringContainsString('const x', $result);
        $this->assertStringContainsString('const y', $result);
    }

    public function testRemovesMultiLineComments(): void
    {
        $js = "/* block comment */ const x = 1;";

        $result = $this->minifier->minify($js);

        $this->assertStringNotContainsString('/* block comment */', $result);
        $this->assertStringContainsString('const x', $result);
    }

    public function testCollapsesWhitespace(): void
    {
        $js = "const   x   =   1;";

        $result = $this->minifier->minify($js);

        $this->assertStringNotContainsString('   ', $result);
    }

    public function testRemovesSpacesAroundBraces(): void
    {
        $js = "function foo() { return 1; }";

        $result = $this->minifier->minify($js);

        $this->assertStringContainsString('foo(){', $result);
    }

    public function testRemovesNewlines(): void
    {
        $js = "const x = 1;\nconst y = 2;\nconst z = 3;";

        $result = $this->minifier->minify($js);

        $this->assertStringNotContainsString("\n", $result);
    }

    public function testEmptyStringReturnsEmpty(): void
    {
        $this->assertEquals('', $this->minifier->minify(''));
    }
}
