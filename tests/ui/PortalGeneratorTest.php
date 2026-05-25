<?php

namespace Tests;

use Nexph\Builder\PortalGenerator;
use PHPUnit\Framework\TestCase;

class PortalGeneratorTest extends TestCase
{
    private PortalGenerator $gen;

    protected function setUp(): void
    {
        $this->gen = new PortalGenerator();
    }

    public function testGenerateReturnsString(): void
    {
        $this->assertIsString($this->gen->generate());
    }

    public function testContainsMountFunction(): void
    {
        $this->assertStringContainsString('function mountPortal', $this->gen->generate());
    }

    public function testContainsUnmountFunction(): void
    {
        $this->assertStringContainsString('function unmountPortal', $this->gen->generate());
    }

    public function testContainsPortalAttr(): void
    {
        $this->assertStringContainsString('data-nexph-portal', $this->gen->generate());
    }

    public function testContainsNexphPortalGlobal(): void
    {
        $this->assertStringContainsString('window.NEXPH.portal', $this->gen->generate());
    }

    public function testContainsPlaceholderComment(): void
    {
        $this->assertStringContainsString('createComment', $this->gen->generate());
    }

    public function testContainsAppendChild(): void
    {
        $this->assertStringContainsString('appendChild', $this->gen->generate());
    }

    public function testHasPortalsDetectsDataAttr(): void
    {
        $this->assertTrue($this->gen->hasPortals('<div data-nexph-portal="#modals">'));
    }

    public function testHasPortalsDetectsNxAttr(): void
    {
        $this->assertTrue($this->gen->hasPortals('<div nx-portal="#modals">'));
    }

    public function testHasPortalsReturnsFalse(): void
    {
        $this->assertFalse($this->gen->hasPortals('<div class="foo">'));
    }

    public function testContainsInitAll(): void
    {
        $this->assertStringContainsString('function initAll', $this->gen->generate());
    }

    public function testContainsDomReadyCheck(): void
    {
        $this->assertStringContainsString('DOMContentLoaded', $this->gen->generate());
    }
}
