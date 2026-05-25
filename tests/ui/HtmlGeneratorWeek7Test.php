<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Compiler\HtmlGenerator;

class HtmlGeneratorWeek7Test extends TestCase
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

    public function testNxLazyConverted(): void
    {
        $ast = $this->makeAst('App', '<div nx-lazy="chunks/Chart.js"></div>');
        $out = $this->gen->generate($ast);
        $this->assertStringContainsString('data-nexph-lazy="chunks/Chart.js"', $out);
        $this->assertStringNotContainsString('nx-lazy=', $out);
    }

    public function testNxValidateConverted(): void
    {
        $ast = $this->makeAst('Form', '<input nx-validate="required|email" />');
        $out = $this->gen->generate($ast);
        $this->assertStringContainsString('data-nexph-validate="required|email"', $out);
        $this->assertStringNotContainsString('nx-validate=', $out);
    }

    public function testNxValidateFormConverted(): void
    {
        $ast = $this->makeAst('Form', '<form nx-validate-form></form>');
        $out = $this->gen->generate($ast);
        $this->assertStringContainsString('data-nexph-validate-form', $out);
        $this->assertStringNotContainsString('nx-validate-form', $out);
    }

    public function testNxErrorConverted(): void
    {
        $ast = $this->makeAst('Form', '<span nx-error="email"></span>');
        $out = $this->gen->generate($ast);
        $this->assertStringContainsString('data-nexph-error="email"', $out);
        $this->assertStringNotContainsString('nx-error=', $out);
    }

    public function testNxStoreBindConverted(): void
    {
        $ast = $this->makeAst('App', '<span nx-store-bind="cart.total"></span>');
        $out = $this->gen->generate($ast);
        $this->assertStringContainsString('data-nexph-store-bind="cart.total"', $out);
        $this->assertStringNotContainsString('nx-store-bind=', $out);
    }

    public function testNxStoreModelConverted(): void
    {
        $ast = $this->makeAst('App', '<input nx-store-model="auth.username" />');
        $out = $this->gen->generate($ast);
        $this->assertStringContainsString('data-nexph-store-model="auth.username"', $out);
        $this->assertStringNotContainsString('nx-store-model=', $out);
    }

    public function testNxStoreActionConverted(): void
    {
        $ast = $this->makeAst('App', '<button nx-store-action="cart.clear">Clear</button>');
        $out = $this->gen->generate($ast);
        $this->assertStringContainsString('data-nexph-store-action="cart.clear"', $out);
        $this->assertStringNotContainsString('nx-store-action=', $out);
    }

    public function testMultipleWeek7DirectivesCoexist(): void
    {
        $html = '<form nx-validate-form>'
              . '<input nx-validate="required|email" nx-store-model="auth.email" />'
              . '<span nx-error="email"></span>'
              . '</form>';
        $ast = $this->makeAst('Login', $html);
        $out = $this->gen->generate($ast);
        $this->assertStringContainsString('data-nexph-validate-form', $out);
        $this->assertStringContainsString('data-nexph-validate="required|email"', $out);
        $this->assertStringContainsString('data-nexph-store-model="auth.email"', $out);
        $this->assertStringContainsString('data-nexph-error="email"', $out);
    }
}
