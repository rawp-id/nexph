<?php

namespace Tests;

use Nexph\Builder\BuildPipeline;
use PHPUnit\Framework\TestCase;

class BuildPipelineWeek8Test extends TestCase
{
    private BuildPipeline $pipeline;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->pipeline = new BuildPipeline();
        $this->tmpDir   = sys_get_temp_dir() . '/nexph_bpw8_' . uniqid();
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->tmpDir);
    }

    private function buildEntry(string $body, string $class = 'TestComp'): string
    {
        $dir  = sys_get_temp_dir() . '/nexph_entry_' . uniqid();
        mkdir($dir, 0755, true);
        $path = "{$dir}/{$class}.php";
        file_put_contents($path, <<<PHP
<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use Nexph\Component;
class {$class} extends Component
{
    public int \$count = 0;
    public function render(): string
    {
        return <<<HTML
{$body}
HTML;
    }
}
PHP);
        return $path;
    }

    private function doBuild(string $entry, array $extra = []): array
    {
        return $this->pipeline->run($entry, array_merge([
            'output'  => $this->tmpDir,
            'minify'  => false,
            'hmrPort' => 0,
        ], $extra));
    }

    public function testAnimateRuntimeInjectedWhenPresent(): void
    {
        $entry  = $this->buildEntry('<div nx-animate="fade-in"><span>hi</span></div>');
        $result = $this->doBuild($entry);
        $js     = file_get_contents($result['files']['js']);
        $this->assertStringContainsString('fade-in', $js);
        $this->assertStringContainsString('window.NEXPH.animate', $js);
    }

    public function testAnimateRuntimeNotInjectedWhenAbsent(): void
    {
        $entry  = $this->buildEntry('<div><span>hi</span></div>');
        $result = $this->doBuild($entry);
        $js     = file_get_contents($result['files']['js']);
        $this->assertStringNotContainsString('window.NEXPH.animate', $js);
    }

    public function testPortalRuntimeInjectedWhenPresent(): void
    {
        $entry  = $this->buildEntry('<div nx-portal="#modals"><span>modal</span></div>');
        $result = $this->doBuild($entry);
        $js     = file_get_contents($result['files']['js']);
        $this->assertStringContainsString('window.NEXPH.portal', $js);
    }

    public function testPortalRuntimeNotInjectedWhenAbsent(): void
    {
        $entry  = $this->buildEntry('<div><span>hi</span></div>');
        $result = $this->doBuild($entry);
        $js     = file_get_contents($result['files']['js']);
        $this->assertStringNotContainsString('window.NEXPH.portal', $js);
    }

    public function testDevToolsInjectedInDevMode(): void
    {
        $entry  = $this->buildEntry('<div><span>hi</span></div>');
        $result = $this->doBuild($entry, ['hmrPort' => 35729]);
        $html   = file_get_contents($result['files']['html']);
        $this->assertStringContainsString('NEXPH DevTools', $html);
    }

    public function testDevToolsNotInjectedInProdMode(): void
    {
        $entry  = $this->buildEntry('<div><span>hi</span></div>');
        $result = $this->doBuild($entry, ['hmrPort' => 0]);
        $html   = file_get_contents($result['files']['html']);
        $this->assertStringNotContainsString('NEXPH DevTools', $html);
    }

    public function testAnimateAndPortalCoexist(): void
    {
        $entry  = $this->buildEntry('<div nx-animate="fade-in" nx-portal="#m"><span>x</span></div>');
        $result = $this->doBuild($entry);
        $js     = file_get_contents($result['files']['js']);
        $this->assertStringContainsString('window.NEXPH.animate', $js);
        $this->assertStringContainsString('window.NEXPH.portal', $js);
    }

    public function testBuildSuccessWithWeek8Directives(): void
    {
        $entry  = $this->buildEntry('<div nx-animate-scroll="slide-up"><p>scroll</p></div>');
        $result = $this->doBuild($entry);
        $this->assertTrue($result['success']);
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..') continue;
            $p = $dir . '/' . $f;
            is_dir($p) ? $this->rrmdir($p) : unlink($p);
        }
        rmdir($dir);
    }
}
