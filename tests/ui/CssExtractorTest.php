<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Compiler\CssExtractor;

class CssExtractorTest extends TestCase
{
    private CssExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new CssExtractor();
    }

    public function testExtractReturnsString(): void
    {
        $ast = ['class' => 'Counter', 'properties' => [], 'methods' => []];

        $css = $this->extractor->extract($ast);

        $this->assertIsString($css);
        $this->assertNotEmpty($css);
    }

    public function testExtractContainsCardStyles(): void
    {
        $ast = ['class' => 'Counter'];

        $css = $this->extractor->extract($ast);

        $this->assertStringContainsString('.card', $css);
    }

    public function testExtractContainsBodyStyles(): void
    {
        $ast = ['class' => 'Counter'];

        $css = $this->extractor->extract($ast);

        $this->assertStringContainsString('body', $css);
    }

    public function testExtractContainsButtonStyles(): void
    {
        $ast = ['class' => 'Counter'];

        $css = $this->extractor->extract($ast);

        $this->assertStringContainsString('button', $css);
    }

    public function testExtractIsValidCssSyntax(): void
    {
        $ast = ['class' => 'Counter'];

        $css = $this->extractor->extract($ast);

        // Every opening brace has a matching closing brace
        $this->assertEquals(
            substr_count($css, '{'),
            substr_count($css, '}')
        );
    }
}
