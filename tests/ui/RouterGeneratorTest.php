<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\RouterGenerator;

class RouterGeneratorTest extends TestCase
{
    private RouterGenerator $router;

    protected function setUp(): void
    {
        $this->router = new RouterGenerator();
    }

    public function testGenerateReturnsString(): void
    {
        $js = $this->router->generate(['/home' => 'Home', '/about' => 'About']);
        $this->assertIsString($js);
    }

    public function testGenerateContainsRoutes(): void
    {
        $js = $this->router->generate(['/home' => 'Home', '/about' => 'About']);
        $this->assertStringContainsString('/home', $js);
        $this->assertStringContainsString('Home', $js);
        $this->assertStringContainsString('/about', $js);
        $this->assertStringContainsString('About', $js);
    }

    public function testHashModeDefault(): void
    {
        $js = $this->router->generate(['/home' => 'Home']);
        $this->assertStringContainsString('HASH_MODE = true', $js);
    }

    public function testHistoryMode(): void
    {
        $js = $this->router->generate(['/home' => 'Home'], 'history');
        $this->assertStringContainsString('HASH_MODE = false', $js);
    }

    public function testGenerateContainsNavigateFunction(): void
    {
        $js = $this->router->generate([]);
        $this->assertStringContainsString('function navigate', $js);
    }

    public function testGenerateContainsRenderFunction(): void
    {
        $js = $this->router->generate([]);
        $this->assertStringContainsString('function render', $js);
    }

    public function testGenerateContainsMatchRoute(): void
    {
        $js = $this->router->generate([]);
        $this->assertStringContainsString('function matchRoute', $js);
    }

    public function testGenerateContainsOutletSelector(): void
    {
        $js = $this->router->generate([]);
        $this->assertStringContainsString('data-nexph-outlet', $js);
    }

    public function testGenerateContainsLinkSelector(): void
    {
        $js = $this->router->generate([]);
        $this->assertStringContainsString('data-nexph-link', $js);
    }

    public function testGenerateExposesNexphRouter(): void
    {
        $js = $this->router->generate([]);
        $this->assertStringContainsString('window.NEXPH.router', $js);
    }

    public function testExtractRoutesFromHtml(): void
    {
        $html = '<div data-nexph-route="/home" data-nexph-component="Home"></div>'
              . '<div data-nexph-route="/about" data-nexph-component="About"></div>';

        $routes = $this->router->extractRoutes($html);

        $this->assertArrayHasKey('/home', $routes);
        $this->assertArrayHasKey('/about', $routes);
        $this->assertEquals('Home', $routes['/home']);
        $this->assertEquals('About', $routes['/about']);
    }

    public function testExtractRoutesEmptyWhenNoRoutes(): void
    {
        $routes = $this->router->extractRoutes('<div>no routes here</div>');
        $this->assertEmpty($routes);
    }

    public function testGenerateWithEmptyRoutesIsValid(): void
    {
        $js = $this->router->generate([]);
        $this->assertStringContainsString('var ROUTES =', $js);
    }

    public function testGenerateContainsHashChangeListener(): void
    {
        $js = $this->router->generate([], 'hash');
        $this->assertStringContainsString('hashchange', $js);
    }

    public function testGenerateContainsPopstateListener(): void
    {
        $js = $this->router->generate([], 'history');
        $this->assertStringContainsString('popstate', $js);
    }
}
