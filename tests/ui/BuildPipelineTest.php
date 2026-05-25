<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\BuildPipeline;

class BuildPipelineTest extends TestCase
{
    private BuildPipeline $pipeline;
    private string $tmpDir;
    private string $entryFile;

    protected function setUp(): void
    {
        $this->pipeline  = new BuildPipeline();
        $this->tmpDir    = sys_get_temp_dir() . '/nexph_pipeline_' . uniqid();
        $this->entryFile = sys_get_temp_dir() . '/nexph_entry_' . uniqid() . '.php';

        mkdir($this->tmpDir, 0755, true);

        file_put_contents($this->entryFile, <<<'PHP'
<?php
class Counter extends Component
{
    public int $count = 0;

    public function increment()
    {
        $this->count++;
    }

    public function render(): string
    {
        return <<<HTML
<div>
    <span>{$this->count}</span>
    <button nx-click="increment">+</button>
</div>
HTML;
    }
}
PHP);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
        if (file_exists($this->entryFile)) {
            unlink($this->entryFile);
        }
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        foreach (glob($dir . '/*') as $item) {
            is_dir($item) ? $this->removeDir($item) : unlink($item);
        }
        rmdir($dir);
    }

    private function config(array $overrides = []): array
    {
        return array_merge([
            'output'  => $this->tmpDir,
            'minify'  => false,
        ], $overrides);
    }

    public function testRunReturnsSuccessTrue(): void
    {
        $result = $this->pipeline->run($this->entryFile, $this->config());

        $this->assertTrue($result['success']);
    }

    public function testRunCreatesIndexHtml(): void
    {
        $this->pipeline->run($this->entryFile, $this->config());

        $this->assertFileExists($this->tmpDir . '/index.html');
    }

    public function testRunCreatesJsFile(): void
    {
        $result = $this->pipeline->run($this->entryFile, $this->config());

        $this->assertFileExists($result['files']['js']);
    }

    public function testRunCreatesCssFile(): void
    {
        $result = $this->pipeline->run($this->entryFile, $this->config());

        $this->assertFileExists($result['files']['css']);
    }

    public function testRunCreatesManifestJson(): void
    {
        $this->pipeline->run($this->entryFile, $this->config());

        $this->assertFileExists($this->tmpDir . '/manifest.json');
    }

    public function testIndexHtmlContainsComponentOutput(): void
    {
        $this->pipeline->run($this->entryFile, $this->config());

        $html = file_get_contents($this->tmpDir . '/index.html');

        $this->assertStringContainsString('data-nexph-component="Counter"', $html);
    }

    public function testIndexHtmlLinksCssFile(): void
    {
        $result = $this->pipeline->run($this->entryFile, $this->config());

        $html    = file_get_contents($this->tmpDir . '/index.html');
        $cssFile = basename($result['files']['css']);

        $this->assertStringContainsString($cssFile, $html);
    }

    public function testIndexHtmlLinksJsFile(): void
    {
        $result = $this->pipeline->run($this->entryFile, $this->config());

        $html   = file_get_contents($this->tmpDir . '/index.html');
        $jsFile = basename($result['files']['js']);

        $this->assertStringContainsString($jsFile, $html);
    }

    public function testJsFileContainsComponentName(): void
    {
        $result = $this->pipeline->run($this->entryFile, $this->config());

        $js = file_get_contents($result['files']['js']);

        $this->assertStringContainsString('Counter', $js);
    }

    public function testManifestContainsComponentName(): void
    {
        $result = $this->pipeline->run($this->entryFile, $this->config());

        $this->assertContains('Counter', $result['manifest']['components']);
    }

    public function testManifestSizeIsPositive(): void
    {
        $result = $this->pipeline->run($this->entryFile, $this->config());

        $this->assertGreaterThan(0, $result['manifest']['size']['total']);
    }

    public function testJsFilenameIsHashed(): void
    {
        $result = $this->pipeline->run($this->entryFile, $this->config());

        $jsFile = basename($result['files']['js']);

        $this->assertMatchesRegularExpression('/^app\.[a-f0-9]{8}\.js$/', $jsFile);
    }

    public function testCssFilenameIsHashed(): void
    {
        $result = $this->pipeline->run($this->entryFile, $this->config());

        $cssFile = basename($result['files']['css']);

        $this->assertMatchesRegularExpression('/^app\.[a-f0-9]{8}\.css$/', $cssFile);
    }

    public function testMinifyProducesSmalllerJs(): void
    {
        $normal   = $this->pipeline->run($this->entryFile, $this->config(['minify' => false]));
        $minified = $this->pipeline->run($this->entryFile, $this->config(['minify' => true, 'output' => $this->tmpDir]));

        $normalSize   = filesize($normal['files']['js']);
        $minifiedSize = filesize($minified['files']['js']);

        $this->assertLessThan($normalSize, $minifiedSize);
    }
}
