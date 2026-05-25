<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Runtime\EventBus;

class EventBusTest extends TestCase
{
    protected function setUp(): void
    {
        EventBus::clear();
    }
    
    public function testEmitAndListen()
    {
        $called = false;
        $data = null;
        
        EventBus::on('component1', 'click', function($d) use (&$called, &$data) {
            $called = true;
            $data = $d;
        });
        
        EventBus::emit('component1', 'click', ['value' => 42]);
        
        $this->assertTrue($called);
        $this->assertEquals(['value' => 42], $data);
    }
    
    public function testMultipleListeners()
    {
        $count = 0;
        
        EventBus::on('component1', 'click', function() use (&$count) {
            $count++;
        });
        
        EventBus::on('component1', 'click', function() use (&$count) {
            $count++;
        });
        
        EventBus::emit('component1', 'click');
        
        $this->assertEquals(2, $count);
    }
    
    public function testComponentScopedEvents()
    {
        $component1Called = false;
        $component2Called = false;
        
        EventBus::on('component1', 'click', function() use (&$component1Called) {
            $component1Called = true;
        });
        
        EventBus::on('component2', 'click', function() use (&$component2Called) {
            $component2Called = true;
        });
        
        EventBus::emit('component1', 'click');
        
        $this->assertTrue($component1Called);
        $this->assertFalse($component2Called);
    }
    
    public function testOff()
    {
        $called = false;
        
        EventBus::on('component1', 'click', function() use (&$called) {
            $called = true;
        });
        
        EventBus::off('component1', 'click');
        EventBus::emit('component1', 'click');
        
        $this->assertFalse($called);
    }
    
    public function testClear()
    {
        $called = false;
        
        EventBus::on('component1', 'click', function() use (&$called) {
            $called = true;
        });
        
        EventBus::clear();
        EventBus::emit('component1', 'click');
        
        $this->assertFalse($called);
    }
}
