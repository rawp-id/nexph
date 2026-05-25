<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Compiler\Parser;

class ParserComputedTest extends TestCase
{
    private Parser $parser;

    protected function setUp(): void
    {
        $this->parser = new Parser();
    }

    public function testExtractComputedProperties(): void
    {
        $source = <<<'PHP'
<?php
class UserProfile extends Component
{
    public string $firstName = 'John';
    public string $lastName = 'Doe';

    public function computed(): array
    {
        return [
            'fullName' => fn() => "{$this->firstName} {$this->lastName}",
            'initials' => fn() => strtoupper($this->firstName[0] . $this->lastName[0]),
        ];
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
PHP;

        $ast = $this->parser->parse($source);

        $this->assertArrayHasKey('computed', $ast);
        $this->assertArrayHasKey('fullName', $ast['computed']);
        $this->assertArrayHasKey('initials', $ast['computed']);
    }

    public function testExtractEmptyComputed(): void
    {
        $source = <<<'PHP'
<?php
class Counter extends Component
{
    public int $count = 0;

    public function render(): string
    {
        return '<div></div>';
    }
}
PHP;

        $ast = $this->parser->parse($source);

        $this->assertArrayHasKey('computed', $ast);
        $this->assertEmpty($ast['computed']);
    }

    public function testExtractLifecycleHooks(): void
    {
        $source = <<<'PHP'
<?php
class Timer extends Component
{
    public int $seconds = 0;

    public function onMount(): void
    {
        $this->seconds = time();
    }

    public function onDestroy(): void
    {
        $this->seconds = 0;
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
PHP;

        $ast = $this->parser->parse($source);

        $this->assertArrayHasKey('lifecycle', $ast);
        $this->assertArrayHasKey('onMount', $ast['lifecycle']);
        $this->assertArrayHasKey('onDestroy', $ast['lifecycle']);
        $this->assertStringContainsString('seconds', $ast['lifecycle']['onMount']);
    }

    public function testLifecycleExcludedFromMethods(): void
    {
        $source = <<<'PHP'
<?php
class Timer extends Component
{
    public int $count = 0;

    public function onMount(): void
    {
        $this->count = 1;
    }

    public function increment()
    {
        $this->count++;
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
PHP;

        $ast = $this->parser->parse($source);

        $this->assertArrayHasKey('increment', $ast['methods']);
        $this->assertArrayNotHasKey('onMount', $ast['methods']);
    }

    public function testComputedExcludedFromMethods(): void
    {
        $source = <<<'PHP'
<?php
class Profile extends Component
{
    public string $name = 'John';

    public function computed(): array
    {
        return [
            'upper' => fn() => strtoupper($this->name),
        ];
    }

    public function greet()
    {
        $this->name = 'Hello';
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
PHP;

        $ast = $this->parser->parse($source);

        $this->assertArrayHasKey('greet', $ast['methods']);
        $this->assertArrayNotHasKey('computed', $ast['methods']);
    }
}
