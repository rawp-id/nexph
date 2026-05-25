<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Compiler\StyleExtractor;

class StyleExtractorTest extends TestCase
{
    private StyleExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new StyleExtractor();
    }

    private function makeSource(string $styleBody): string
    {
        return <<<PHP
<?php
class Button extends Component
{
    public function style(): string
    {
        return <<<CSS
{$styleBody}
CSS;
    }

    public function render(): string
    {
        return '<button>Click</button>';
    }
}
PHP;
    }

    public function testExtractFromHeredoc(): void
    {
        $source = $this->makeSource('.btn { color: red; }');
        $ast    = ['fullSource' => $source];

        $css = $this->extractor->extract($ast);

        $this->assertStringContainsString('.btn', $css);
        $this->assertStringContainsString('color: red', $css);
    }

    public function testExtractReturnsEmptyWhenNoStyleMethod(): void
    {
        $source = <<<'PHP'
<?php
class Counter extends Component
{
    public function render(): string
    {
        return '<div></div>';
    }
}
PHP;
        $ast = ['fullSource' => $source];

        $css = $this->extractor->extract($ast);

        $this->assertEquals('', $css);
    }

    public function testExtractReturnsEmptyForEmptyAst(): void
    {
        $css = $this->extractor->extract([]);

        $this->assertEquals('', $css);
    }

    public function testExtractMultipleRules(): void
    {
        $source = $this->makeSource(".btn { color: red; }\n.card { padding: 20px; }");
        $ast    = ['fullSource' => $source];

        $css = $this->extractor->extract($ast);

        $this->assertStringContainsString('.btn', $css);
        $this->assertStringContainsString('.card', $css);
    }
}
