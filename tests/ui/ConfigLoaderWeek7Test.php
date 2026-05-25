<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\ConfigLoader;

class ConfigLoaderWeek7Test extends TestCase
{
    private ConfigLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new ConfigLoader();
    }

    public function testAnalyzeDefaultFalse(): void
    {
        $config = $this->loader->fromArgs([]);
        $this->assertFalse($config['analyze']);
    }

    public function testAnalyzeFlagSetsTrue(): void
    {
        $config = $this->loader->fromArgs(['--analyze']);
        $this->assertTrue($config['analyze']);
    }

    public function testStoresDefaultEmpty(): void
    {
        $config = $this->loader->fromArgs([]);
        $this->assertIsArray($config['stores']);
        $this->assertEmpty($config['stores']);
    }

    public function testAnalyzeCombinesWithOtherFlags(): void
    {
        $config = $this->loader->fromArgs(['--analyze', '--minify', '--production']);
        $this->assertTrue($config['analyze']);
        $this->assertTrue($config['minify']);
    }
}
