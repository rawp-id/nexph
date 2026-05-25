<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Runtime\Store;

class StoreTest extends TestCase
{
    protected function setUp(): void
    {
        Store::clear();
    }

    public function testDefineAndGet(): void
    {
        Store::define('cart', ['items' => [], 'total' => 0]);
        $store = Store::get('cart');
        $this->assertIsArray($store);
        $this->assertArrayHasKey('state', $store);
    }

    public function testGetReturnsNullForUndefined(): void
    {
        $this->assertNull(Store::get('nonexistent'));
    }

    public function testHasReturnsTrueWhenDefined(): void
    {
        Store::define('auth', ['user' => null]);
        $this->assertTrue(Store::has('auth'));
    }

    public function testHasReturnsFalseWhenNotDefined(): void
    {
        $this->assertFalse(Store::has('missing'));
    }

    public function testAllReturnsAllStores(): void
    {
        Store::define('a', ['x' => 1]);
        Store::define('b', ['y' => 2]);
        $all = Store::all();
        $this->assertArrayHasKey('a', $all);
        $this->assertArrayHasKey('b', $all);
    }

    public function testClearRemovesAllStores(): void
    {
        Store::define('temp', ['v' => 1]);
        Store::clear();
        $this->assertEmpty(Store::all());
    }

    public function testStoreHasStateKey(): void
    {
        Store::define('ui', ['theme' => 'dark']);
        $store = Store::get('ui');
        $this->assertArrayHasKey('state', $store);
        $this->assertEquals('dark', $store['state']['theme']);
    }

    public function testStoreHasActionsKey(): void
    {
        Store::define('counter', ['count' => 0], ['increment' => fn() => null]);
        $store = Store::get('counter');
        $this->assertArrayHasKey('actions', $store);
    }

    public function testMultipleStoresIndependent(): void
    {
        Store::define('s1', ['v' => 1]);
        Store::define('s2', ['v' => 2]);
        $this->assertEquals(1, Store::get('s1')['state']['v']);
        $this->assertEquals(2, Store::get('s2')['state']['v']);
    }
}
