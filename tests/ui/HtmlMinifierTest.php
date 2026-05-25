<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\HtmlMinifier;

class HtmlMinifierTest extends TestCase
{
    private HtmlMinifier $minifier;

    protected function setUp(): void
    {
        $this->minifier = new HtmlMinifier();
    }

    public function testRemovesHtmlComments(): void
    {
        $html = '<!-- comment --><div>Hello</div>';

        $result = $this->minifier->minify($html);

        $this->assertStringNotContainsString('<!-- comment -->', $result);
        $this->assertStringContainsString('<div>Hello</div>', $result);
    }

    public function testCollapsesWhitespaceBetweenTags(): void
    {
        $html = "<div>   \n   <span>text</span>   \n   </div>";

        $result = $this->minifier->minify($html);

        $this->assertStringNotContainsString('   ', $result);
        $this->assertStringContainsString('<div><span>text</span></div>', $result);
    }

    public function testCollapsesInternalWhitespace(): void
    {
        $html = '<div>  hello   world  </div>';

        $result = $this->minifier->minify($html);

        $this->assertStringContainsString('hello world', $result);
    }

    public function testPreservesIEConditionals(): void
    {
        $html = '<!--[if IE]><p>IE</p><![endif]--><div>ok</div>';

        $result = $this->minifier->minify($html);

        $this->assertStringContainsString('<!--[if IE]>', $result);
    }

    public function testTrimsResult(): void
    {
        $html = '   <div>hi</div>   ';

        $result = $this->minifier->minify($html);

        $this->assertEquals('<div>hi</div>', $result);
    }

    public function testEmptyStringReturnsEmpty(): void
    {
        $this->assertEquals('', $this->minifier->minify(''));
    }
}
