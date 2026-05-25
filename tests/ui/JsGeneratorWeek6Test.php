<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Compiler\JsGenerator;

class JsGeneratorWeek6Test extends TestCase
{
    private JsGenerator $gen;

    protected function setUp(): void
    {
        $this->gen = new JsGenerator();
    }

    private function makeAst(string $class, array $props = [], array $methods = []): array
    {
        return [
            'class'      => $class,
            'properties' => $props,
            'methods'    => $methods,
            'computed'   => [],
            'lifecycle'  => [],
        ];
    }

    public function testNxFetchRuntimePresent(): void
    {
        $js = $this->gen->generate($this->makeAst('Fetch'));
        $this->assertStringContainsString('data-nexph-fetch', $js);
    }

    public function testNxFetchCallsFetchApi(): void
    {
        $js = $this->gen->generate($this->makeAst('Fetch'));
        $this->assertStringContainsString('fetch(url', $js);
    }

    public function testNxFetchSetsLoadingStatus(): void
    {
        $js = $this->gen->generate($this->makeAst('Fetch'));
        $this->assertStringContainsString("'loading'", $js);
    }

    public function testNxFetchSetsDoneStatus(): void
    {
        $js = $this->gen->generate($this->makeAst('Fetch'));
        $this->assertStringContainsString("'done'", $js);
    }

    public function testNxFetchSetsErrorStatus(): void
    {
        $js = $this->gen->generate($this->makeAst('Fetch'));
        $this->assertStringContainsString("'error'", $js);
    }

    public function testNxFetchCallsUpdateUiOnSuccess(): void
    {
        $js = $this->gen->generate($this->makeAst('Fetch'));
        // updateUI() called inside .then()
        $this->assertStringContainsString('updateUI()', $js);
    }

    public function testNxFetchUsesTargetAttribute(): void
    {
        $js = $this->gen->generate($this->makeAst('Fetch'));
        $this->assertStringContainsString('data-nexph-fetch-target', $js);
    }

    public function testNxFetchUsesMethodAttribute(): void
    {
        $js = $this->gen->generate($this->makeAst('Fetch'));
        $this->assertStringContainsString('data-nexph-fetch-method', $js);
    }

    public function testNxFetchHandlesPostBody(): void
    {
        $js = $this->gen->generate($this->makeAst('Fetch'));
        $this->assertStringContainsString('JSON.stringify', $js);
    }

    public function testNxFetchLogsError(): void
    {
        $js = $this->gen->generate($this->makeAst('Fetch'));
        $this->assertStringContainsString('console.error', $js);
    }

    public function testNxFetchQueriesAllFetchElements(): void
    {
        $js = $this->gen->generate($this->makeAst('Fetch'));
        $this->assertStringContainsString('querySelectorAll', $js);
    }
}
