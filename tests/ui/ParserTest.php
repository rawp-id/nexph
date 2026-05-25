<?php

namespace Nexph\Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Compiler\Parser;

class ParserTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    public function testExtractClassName(): void
    {
        $source = <<<'PHP'
<?php
class Counter extends Component
{
}
PHP;

        $ast = $this->parser->parse($source);
        
        $this->assertEquals('Counter', $ast['class']);
    }

    public function testExtractProperties(): void
    {
        $source = <<<'PHP'
<?php
class Counter extends Component
{
    public int $count = 0;
    public string $name = 'test';
}
PHP;

        $ast = $this->parser->parse($source);
        
        $this->assertArrayHasKey('count', $ast['properties']);
        $this->assertEquals('int', $ast['properties']['count']['type']);
        $this->assertEquals('0', $ast['properties']['count']['default']);
        
        $this->assertArrayHasKey('name', $ast['properties']);
        $this->assertEquals('string', $ast['properties']['name']['type']);
    }

    public function testExtractMethods(): void
    {
        $source = <<<'PHP'
<?php
class Counter extends Component
{
    public function increment()
    {
        $this->count++;
    }
}
PHP;

        $ast = $this->parser->parse($source);
        
        $this->assertArrayHasKey('increment', $ast['methods']);
        $this->assertStringContainsString('$this->count++', $ast['methods']['increment']);
    }
}
