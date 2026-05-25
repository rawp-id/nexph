<?php

namespace Tests;

use Nexph\Builder\AnimateGenerator;
use PHPUnit\Framework\TestCase;

class AnimateGeneratorTest extends TestCase
{
    private AnimateGenerator $gen;

    protected function setUp(): void
    {
        $this->gen = new AnimateGenerator();
    }

    public function testGenerateReturnsString(): void
    {
        $this->assertIsString($this->gen->generate());
    }

    public function testContainsFadeIn(): void
    {
        $this->assertStringContainsString('fade-in', $this->gen->generate());
    }

    public function testContainsSlideUp(): void
    {
        $this->assertStringContainsString('slide-up', $this->gen->generate());
    }

    public function testContainsZoomIn(): void
    {
        $this->assertStringContainsString('zoom-in', $this->gen->generate());
    }

    public function testContainsBounce(): void
    {
        $this->assertStringContainsString('bounce', $this->gen->generate());
    }

    public function testContainsShake(): void
    {
        $this->assertStringContainsString('shake', $this->gen->generate());
    }

    public function testContainsPulse(): void
    {
        $this->assertStringContainsString('pulse', $this->gen->generate());
    }

    public function testContainsFlip(): void
    {
        $this->assertStringContainsString('flip', $this->gen->generate());
    }

    public function testContainsAnimateFunction(): void
    {
        $this->assertStringContainsString('function animate(', $this->gen->generate());
    }

    public function testContainsScrollObserver(): void
    {
        $this->assertStringContainsString('IntersectionObserver', $this->gen->generate());
    }

    public function testContainsNexphAnimateGlobal(): void
    {
        $this->assertStringContainsString('window.NEXPH.animate', $this->gen->generate());
    }

    public function testContainsAddMethod(): void
    {
        $this->assertStringContainsString('add:', $this->gen->generate());
    }

    public function testHasAnimationsDetectsDataAttr(): void
    {
        $this->assertTrue($this->gen->hasAnimations('<div data-nexph-animate="fade-in">'));
    }

    public function testHasAnimationsDetectsNxAttr(): void
    {
        $this->assertTrue($this->gen->hasAnimations('<div nx-animate="slide-up">'));
    }

    public function testHasAnimationsReturnsFalse(): void
    {
        $this->assertFalse($this->gen->hasAnimations('<div class="foo">'));
    }

    public function testContainsRepeatFunction(): void
    {
        $this->assertStringContainsString('data-nexph-animate-repeat', $this->gen->generate());
    }

    public function testContainsLeaveFunction(): void
    {
        $this->assertStringContainsString('data-nexph-animate-leave', $this->gen->generate());
    }
}
