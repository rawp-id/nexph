<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Compiler\PropsExtractor;

class PropsExtractorTest extends TestCase
{
    private PropsExtractor $extractor;
    
    protected function setUp(): void
    {
        $this->extractor = new PropsExtractor();
    }
    
    public function testExtractSimpleProps()
    {
        $tag = '<Button label="Click me" variant="primary">';
        $props = $this->extractor->extractFromTag($tag);
        
        $this->assertArrayHasKey('label', $props);
        $this->assertArrayHasKey('variant', $props);
        $this->assertEquals('Click me', $props['label']);
        $this->assertEquals('primary', $props['variant']);
    }
    
    public function testExtractBindingProps()
    {
        $tag = '<Button :count="counter" label="Click">';
        $props = $this->extractor->extractFromTag($tag);
        
        $this->assertArrayHasKey('count', $props);
        $this->assertArrayHasKey('label', $props);
        $this->assertIsArray($props['count']);
        $this->assertEquals('binding', $props['count']['type']);
        $this->assertEquals('counter', $props['count']['value']);
    }
    
    public function testConvertBoolType()
    {
        $this->assertTrue($this->extractor->convertType('true', 'bool'));
        $this->assertTrue($this->extractor->convertType('1', 'bool'));
        $this->assertFalse($this->extractor->convertType('false', 'bool'));
        $this->assertFalse($this->extractor->convertType('0', 'bool'));
    }
    
    public function testConvertIntType()
    {
        $this->assertSame(42, $this->extractor->convertType('42', 'int'));
        $this->assertSame(0, $this->extractor->convertType('0', 'int'));
        $this->assertSame(-10, $this->extractor->convertType('-10', 'int'));
    }
    
    public function testConvertFloatType()
    {
        $this->assertSame(3.14, $this->extractor->convertType('3.14', 'float'));
        $this->assertSame(0.0, $this->extractor->convertType('0', 'float'));
    }
    
    public function testConvertArrayType()
    {
        $result = $this->extractor->convertType('["a","b","c"]', 'array');
        $this->assertIsArray($result);
        $this->assertEquals(['a', 'b', 'c'], $result);
    }
    
    public function testValidateRequiredProps()
    {
        $props = ['label' => 'Click'];
        $propTypes = ['label' => 'required|string', 'variant' => 'string'];
        
        $errors = $this->extractor->validateProps($props, $propTypes);
        $this->assertEmpty($errors);
    }
    
    public function testValidateMissingRequiredProps()
    {
        $props = [];
        $propTypes = ['label' => 'required|string'];
        
        $errors = $this->extractor->validateProps($props, $propTypes);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('label', $errors[0]);
    }
}
