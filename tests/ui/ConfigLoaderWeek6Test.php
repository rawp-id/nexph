<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\ConfigLoader;

class ConfigLoaderWeek6Test extends TestCase
{
    private ConfigLoader $loader;

    protected function setUp(): void
    {
        $this->loader = new ConfigLoader();
    }

    public function testCompressDefaultFalse(): void
    {
        $config = $this->loader->fromArgs([]);
        $this->assertFalse($config['compress']);
    }

    public function testCompressFlagSetsTrue(): void
    {
        $config = $this->loader->fromArgs(['--compress']);
        $this->assertTrue($config['compress']);
    }

    public function testPwaDefaultFalse(): void
    {
        $config = $this->loader->fromArgs([]);
        $this->assertFalse($config['pwa']);
    }

    public function testPwaFlagSetsTrue(): void
    {
        $config = $this->loader->fromArgs(['--pwa']);
        $this->assertTrue($config['pwa']);
    }

    public function testPwaNameOption(): void
    {
        $config = $this->loader->fromArgs(['--pwa-name=My App']);
        $this->assertEquals('My App', $config['pwaOptions']['name']);
    }

    public function testPwaShortOption(): void
    {
        $config = $this->loader->fromArgs(['--pwa-short=MA']);
        $this->assertEquals('MA', $config['pwaOptions']['short_name']);
    }

    public function testPwaThemeOption(): void
    {
        $config = $this->loader->fromArgs(['--pwa-theme=#ff0000']);
        $this->assertEquals('#ff0000', $config['pwaOptions']['theme_color']);
    }

    public function testRouteModeDefaultHash(): void
    {
        $config = $this->loader->fromArgs([]);
        $this->assertEquals('hash', $config['routeMode']);
    }

    public function testRouteModeHistory(): void
    {
        $config = $this->loader->fromArgs(['--route-mode=history']);
        $this->assertEquals('history', $config['routeMode']);
    }

    public function testPwaOptionsHaveDefaults(): void
    {
        $config = $this->loader->fromArgs([]);
        $this->assertArrayHasKey('pwaOptions', $config);
        $this->assertArrayHasKey('name', $config['pwaOptions']);
        $this->assertArrayHasKey('theme_color', $config['pwaOptions']);
    }

    public function testMultipleFlagsCombine(): void
    {
        $config = $this->loader->fromArgs(['--compress', '--pwa', '--minify', '--route-mode=history']);
        $this->assertTrue($config['compress']);
        $this->assertTrue($config['pwa']);
        $this->assertTrue($config['minify']);
        $this->assertEquals('history', $config['routeMode']);
    }
}
