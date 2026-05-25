<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\StoreGenerator;

class StoreGeneratorTest extends TestCase
{
    private StoreGenerator $gen;

    protected function setUp(): void
    {
        $this->gen = new StoreGenerator();
    }

    public function testGenerateReturnsString(): void
    {
        $js = $this->gen->generate([]);
        $this->assertIsString($js);
    }

    public function testGenerateContainsDefineStore(): void
    {
        $js = $this->gen->generate([]);
        $this->assertStringContainsString('defineStore', $js);
    }

    public function testGenerateContainsUseStore(): void
    {
        $js = $this->gen->generate([]);
        $this->assertStringContainsString('useStore', $js);
    }

    public function testGenerateContainsSubscribe(): void
    {
        $js = $this->gen->generate([]);
        $this->assertStringContainsString('subscribe', $js);
    }

    public function testGenerateContainsNexphStore(): void
    {
        $js = $this->gen->generate([]);
        $this->assertStringContainsString('window.NEXPH.store', $js);
    }

    public function testGenerateInjectsInitialStores(): void
    {
        $js = $this->gen->generate(['cart' => ['state' => ['items' => []]]]);
        $this->assertStringContainsString('cart', $js);
    }

    public function testGenerateContainsProxyForReactivity(): void
    {
        $js = $this->gen->generate([]);
        $this->assertStringContainsString('Proxy', $js);
    }

    public function testGenerateContainsStoreBindSelector(): void
    {
        $js = $this->gen->generate([]);
        $this->assertStringContainsString('data-nexph-store-bind', $js);
    }

    public function testGenerateContainsStoreModelSelector(): void
    {
        $js = $this->gen->generate([]);
        $this->assertStringContainsString('data-nexph-store-model', $js);
    }

    public function testGenerateContainsStoreActionSelector(): void
    {
        $js = $this->gen->generate([]);
        $this->assertStringContainsString('data-nexph-store-action', $js);
    }

    public function testExtractStoreNamesFromBind(): void
    {
        $html   = '<span data-nexph-store-bind="cart.total"></span>';
        $names  = $this->gen->extractStoreNames($html);
        $this->assertContains('cart', $names);
    }

    public function testExtractStoreNamesFromModel(): void
    {
        $html  = '<input data-nexph-store-model="auth.username" />';
        $names = $this->gen->extractStoreNames($html);
        $this->assertContains('auth', $names);
    }

    public function testExtractStoreNamesFromAction(): void
    {
        $html  = '<button data-nexph-store-action="cart.clear">Clear</button>';
        $names = $this->gen->extractStoreNames($html);
        $this->assertContains('cart', $names);
    }

    public function testExtractStoreNamesDeduplicates(): void
    {
        $html  = '<span data-nexph-store-bind="cart.a"></span>'
               . '<span data-nexph-store-bind="cart.b"></span>';
        $names = $this->gen->extractStoreNames($html);
        $this->assertCount(1, array_filter($names, fn($n) => $n === 'cart'));
    }

    public function testExtractStoreNamesEmptyWhenNone(): void
    {
        $names = $this->gen->extractStoreNames('<div>no store</div>');
        $this->assertEmpty($names);
    }
}
