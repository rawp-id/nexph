<?php

namespace Tests;

use Nexph\Compiler\HtmlGenerator;
use PHPUnit\Framework\TestCase;

class HtmlGeneratorWeek8Test extends TestCase
{
    private HtmlGenerator $gen;

    protected function setUp(): void
    {
        $this->gen = new HtmlGenerator();
    }

    private function ast(string $html, string $class = 'TestComp'): array
    {
        return [
            'class'        => $class,
            'renderMethod' => "return <<<HTML\n{$html}\nHTML;",
            'properties'   => [],
            'methods'      => [],
        ];
    }

    public function testNxAnimateConverted(): void
    {
        $out = $this->gen->generate($this->ast('<div nx-animate="fade-in">'));
        $this->assertStringContainsString('data-nexph-animate="fade-in"', $out);
        $this->assertStringNotContainsString('nx-animate=', $out);
    }

    public function testNxAnimateScrollConverted(): void
    {
        $out = $this->gen->generate($this->ast('<div nx-animate-scroll="slide-up">'));
        $this->assertStringContainsString('data-nexph-animate-scroll="slide-up"', $out);
    }

    public function testNxAnimateLeaveConverted(): void
    {
        $out = $this->gen->generate($this->ast('<div nx-animate-leave="fade-out">'));
        $this->assertStringContainsString('data-nexph-animate-leave="fade-out"', $out);
    }

    public function testNxAnimateRepeatConverted(): void
    {
        $out = $this->gen->generate($this->ast('<div nx-animate-repeat="pulse:2000">'));
        $this->assertStringContainsString('data-nexph-animate-repeat="pulse:2000"', $out);
    }

    public function testNxPortalWithTargetConverted(): void
    {
        $out = $this->gen->generate($this->ast('<div nx-portal="#modals">'));
        $this->assertStringContainsString('data-nexph-portal="#modals"', $out);
        $this->assertStringNotContainsString('nx-portal=', $out);
    }

    public function testNxPortalBareConverted(): void
    {
        $out = $this->gen->generate($this->ast('<div nx-portal>'));
        $this->assertStringContainsString('data-nexph-portal="body"', $out);
    }

    public function testNxAnimateWithOptions(): void
    {
        $out = $this->gen->generate($this->ast('<div nx-animate="zoom-in:duration=500">'));
        $this->assertStringContainsString('data-nexph-animate="zoom-in:duration=500"', $out);
    }

    public function testMultipleWeek8DirectivesCoexist(): void
    {
        $html = '<div nx-animate="fade-in" nx-portal="#modals" nx-animate-scroll="slide-up">';
        $out  = $this->gen->generate($this->ast($html));
        $this->assertStringContainsString('data-nexph-animate="fade-in"', $out);
        $this->assertStringContainsString('data-nexph-portal="#modals"', $out);
        $this->assertStringContainsString('data-nexph-animate-scroll="slide-up"', $out);
    }
}
