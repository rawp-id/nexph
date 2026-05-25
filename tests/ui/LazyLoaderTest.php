<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\LazyLoader;

class LazyLoaderTest extends TestCase
{
    private LazyLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new LazyLoader();
    }

    public function testGenerateReturnsString(): void
    {
        $this->assertIsString($this->loader->generate());
    }

    public function testContainsDataNexphLazy(): void
    {
        $this->assertStringContainsString('data-nexph-lazy', $this->loader->generate());
    }

    public function testContainsIntersectionObserver(): void
    {
        $this->assertStringContainsString('IntersectionObserver', $this->loader->generate());
    }

    public function testContainsFallbackLoad(): void
    {
        $this->assertStringContainsString('loadComponent', $this->loader->generate());
    }

    public function testContainsLoadingStatus(): void
    {
        $this->assertStringContainsString("'loading'", $this->loader->generate());
    }

    public function testContainsLoadedStatus(): void
    {
        $this->assertStringContainsString("'loaded'", $this->loader->generate());
    }

    public function testContainsErrorStatus(): void
    {
        $this->assertStringContainsString("'error'", $this->loader->generate());
    }

    public function testContainsNxLazyPendingClass(): void
    {
        $this->assertStringContainsString('nx-lazy-pending', $this->loader->generate());
    }

    public function testContainsNxLazyLoadedClass(): void
    {
        $this->assertStringContainsString('nx-lazy-loaded', $this->loader->generate());
    }

    public function testContainsNexphLazyGlobal(): void
    {
        $this->assertStringContainsString('window.NEXPH.lazy', $this->loader->generate());
    }

    public function testContainsScriptCreation(): void
    {
        $this->assertStringContainsString("createElement('script')", $this->loader->generate());
    }

    public function testContainsConsoleError(): void
    {
        $this->assertStringContainsString('console.error', $this->loader->generate());
    }
}
