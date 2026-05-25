<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Runtime\ComponentRegistry;

class ComponentRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        // Clear registry before each test
        $reflection = new \ReflectionClass(ComponentRegistry::class);
        $components = $reflection->getProperty('components');
        $components->setValue(null, []);

        $aliases = $reflection->getProperty('aliases');
        $aliases->setValue(null, []);
    }
    
    public function testRegisterComponent()
    {
        ComponentRegistry::register('Button', 'App\\Components\\Button');
        
        $this->assertTrue(ComponentRegistry::has('Button'));
        $this->assertEquals('App\\Components\\Button', ComponentRegistry::resolve('Button'));
    }
    
    public function testKebabCaseAlias()
    {
        ComponentRegistry::register('MyButton', 'App\\Components\\MyButton');
        
        $this->assertTrue(ComponentRegistry::has('my-button'));
        $this->assertEquals('App\\Components\\MyButton', ComponentRegistry::resolve('my-button'));
    }
    
    public function testResolveNonExistent()
    {
        $this->assertNull(ComponentRegistry::resolve('NonExistent'));
        $this->assertFalse(ComponentRegistry::has('NonExistent'));
    }
    
    public function testGetAll()
    {
        ComponentRegistry::register('Button', 'App\\Components\\Button');
        ComponentRegistry::register('Card', 'App\\Components\\Card');
        
        $all = ComponentRegistry::all();
        
        $this->assertCount(2, $all);
        $this->assertArrayHasKey('Button', $all);
        $this->assertArrayHasKey('Card', $all);
    }
}
