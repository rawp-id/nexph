<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\CssMinifier;

class CssMinifierTest extends TestCase
{
    private CssMinifier $minifier;

    protected function setUp(): void
    {
        $this->minifier = new CssMinifier();
    }

    public function testRemovesComments(): void
    {
        $css = '/* comment */ .btn { color: red; }';

        $result = $this->minifier->minify($css);

        $this->assertStringNotContainsString('/* comment */', $result);
        $this->assertStringContainsString('.btn', $result);
    }

    public function testCollapsesWhitespace(): void
    {
        $css = ".btn  {  color:  red;  }";

        $result = $this->minifier->minify($css);

        $this->assertStringNotContainsString('  ', $result);
    }

    public function testRemovesSpacesAroundBraces(): void
    {
        $css = '.btn { color: red; }';

        $result = $this->minifier->minify($css);

        $this->assertStringContainsString('.btn{', $result);
        $this->assertStringContainsString('red}', $result);
    }

    public function testRemovesTrailingSemicolonBeforeBrace(): void
    {
        $css = '.btn { color: red; font-size: 16px; }';

        $result = $this->minifier->minify($css);

        $this->assertStringContainsString('16px}', $result);
        $this->assertStringNotContainsString(';}', $result);
    }

    public function testMultipleRules(): void
    {
        $css = <<<CSS
.card {
    padding: 20px;
    margin: 10px;
}

.btn {
    color: blue;
}
CSS;

        $result = $this->minifier->minify($css);

        $this->assertStringContainsString('.card{', $result);
        $this->assertStringContainsString('.btn{', $result);
        $this->assertStringNotContainsString("\n", $result);
    }

    public function testEmptyStringReturnsEmpty(): void
    {
        $this->assertEquals('', $this->minifier->minify(''));
    }
}
