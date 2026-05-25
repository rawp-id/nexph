<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\ValidatorGenerator;

class ValidatorGeneratorTest extends TestCase
{
    private ValidatorGenerator $gen;

    protected function setUp(): void
    {
        $this->gen = new ValidatorGenerator();
    }

    public function testGenerateReturnsString(): void
    {
        $this->assertIsString($this->gen->generate());
    }

    public function testContainsRequiredRule(): void
    {
        $this->assertStringContainsString('required', $this->gen->generate());
    }

    public function testContainsEmailRule(): void
    {
        $this->assertStringContainsString('email', $this->gen->generate());
    }

    public function testContainsMinRule(): void
    {
        $this->assertStringContainsString('min:', $this->gen->generate());
    }

    public function testContainsMaxRule(): void
    {
        $this->assertStringContainsString('max:', $this->gen->generate());
    }

    public function testContainsNumericRule(): void
    {
        $this->assertStringContainsString('numeric', $this->gen->generate());
    }

    public function testContainsPatternRule(): void
    {
        $this->assertStringContainsString('pattern', $this->gen->generate());
    }

    public function testContainsUrlRule(): void
    {
        $this->assertStringContainsString('url', $this->gen->generate());
    }

    public function testContainsValidateFieldFunction(): void
    {
        $this->assertStringContainsString('validateField', $this->gen->generate());
    }

    public function testContainsValidateFormFunction(): void
    {
        $this->assertStringContainsString('validateForm', $this->gen->generate());
    }

    public function testContainsNxInvalidClass(): void
    {
        $this->assertStringContainsString('nx-invalid', $this->gen->generate());
    }

    public function testContainsNxValidClass(): void
    {
        $this->assertStringContainsString('nx-valid', $this->gen->generate());
    }

    public function testContainsDataNexphValidate(): void
    {
        $this->assertStringContainsString('data-nexph-validate', $this->gen->generate());
    }

    public function testContainsDataNexphError(): void
    {
        $this->assertStringContainsString('data-nexph-error', $this->gen->generate());
    }

    public function testContainsNexphValidateGlobal(): void
    {
        $this->assertStringContainsString('window.NEXPH.validate', $this->gen->generate());
    }

    public function testContainsSubmitInterception(): void
    {
        $this->assertStringContainsString('data-nexph-validate-form', $this->gen->generate());
    }

    public function testContainsConfirmedRule(): void
    {
        $this->assertStringContainsString('confirmed', $this->gen->generate());
    }
}
