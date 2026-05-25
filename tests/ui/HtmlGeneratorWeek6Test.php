<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Compiler\HtmlGenerator;

class HtmlGeneratorWeek6Test extends TestCase
{
    private HtmlGenerator $gen;

    protected function setUp(): void
    {
        $this->gen = new HtmlGenerator();
    }

    private function makeAst(string $class, string $html): array
    {
        return [
            'class'        => $class,
            'renderMethod' => "return <<<HTML\n{$html}\nHTML;",
        ];
    }

    public function testNxFetchConverted(): void
    {
        $ast = $this->makeAst('Fetch', '<div nx-fetch="/api/data"></div>');
        $out = $this->gen->generate($ast);
        $this->assertStringContainsString('data-nexph-fetch="/api/data"', $out);
        $this->assertStringNotContainsString('nx-fetch=', $out);
    }

    public function testNxRouteConverted(): void
    {
        $ast = $this->makeAst('Router', '<div nx-route="/home"></div>');
        $out = $this->gen->generate($ast);
        $this->assertStringContainsString('data-nexph-route="/home"', $out);
        $this->assertStringNotContainsString('nx-route=', $out);
    }

    public function testNxLinkConverted(): void
    {
        $ast = $this->makeAst('Nav', '<a nx-link="/about">About</a>');
        $out = $this->gen->generate($ast);
        $this->assertStringContainsString('data-nexph-link="/about"', $out);
        $this->assertStringNotContainsString('nx-link=', $out);
    }

    public function testNxOutletConverted(): void
    {
        $ast = $this->makeAst('App', '<div nx-outlet></div>');
        $out = $this->gen->generate($ast);
        $this->assertStringContainsString('data-nexph-outlet', $out);
        $this->assertStringNotContainsString('nx-outlet', $out);
    }

    public function testNxFetchWithTargetAttribute(): void
    {
        $ast = $this->makeAst('Fetch', '<div nx-fetch="/api/users" data-nexph-fetch-target="users"></div>');
        $out = $this->gen->generate($ast);
        $this->assertStringContainsString('data-nexph-fetch="/api/users"', $out);
        $this->assertStringContainsString('data-nexph-fetch-target="users"', $out);
    }

    public function testMultipleRoutesInTemplate(): void
    {
        $html = '<div nx-route="/home" data-nexph-component="Home"></div>'
              . '<div nx-route="/about" data-nexph-component="About"></div>';
        $ast  = $this->makeAst('App', $html);
        $out  = $this->gen->generate($ast);
        $this->assertStringContainsString('data-nexph-route="/home"', $out);
        $this->assertStringContainsString('data-nexph-route="/about"', $out);
    }

    public function testNxLinkAndNxOutletCoexist(): void
    {
        $html = '<nav><a nx-link="/home">Home</a></nav><div nx-outlet></div>';
        $ast  = $this->makeAst('Layout', $html);
        $out  = $this->gen->generate($ast);
        $this->assertStringContainsString('data-nexph-link="/home"', $out);
        $this->assertStringContainsString('data-nexph-outlet', $out);
    }
}
