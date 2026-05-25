<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\StaticExporter;

class StaticExporterTest extends TestCase
{
    private StaticExporter $exporter;
    private string $tmpDir;
    private string $entryFile;

    protected function setUp(): void
    {
        $this->exporter  = new StaticExporter();
        $this->tmpDir    = sys_get_temp_dir() . '/nexph_export_' . uniqid();
        $this->entryFile = sys_get_temp_dir() . '/nexph_export_entry_' . uniqid() . '.php';

        mkdir($this->tmpDir, 0755, true);

        file_put_contents($this->entryFile, <<<'PHP'
<?php
class Counter extends Component
{
    public int $count = 0;

    public function style(): string
    {
        return <<<CSS
.card { padding: 20px; }
CSS;
    }

    public function render(): string
    {
        return <<<HTML
<div class="card"><span>{$this->count}</span></div>
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
        return array_merge(['output' => $this->tmpDir, 'minify' => false], $overrides);
    }

    public function testExportReturnsSuccess(): void
    {
        $result = $this->exporter->export($this->entryFile, $this->config());

        $this->assertTrue($result['success']);
    }

    public function testExportCreatesIndexHtml(): void
    {
        $this->exporter->export($this->entryFile, $this->config());

        $this->assertFileExists($this->tmpDir . '/index.html');
    }

    public function testExportCreatesManifest(): void
    {
        $this->exporter->export($this->entryFile, $this->config());

        $this->assertFileExists($this->tmpDir . '/manifest.json');
    }

    public function testExportInlinesCss(): void
    {
        $this->exporter->export($this->entryFile, $this->config());

        $html = file_get_contents($this->tmpDir . '/index.html');

        $this->assertStringContainsString('<style>', $html);
        $this->assertStringContainsString('.card', $html);
    }

    public function testExportInlinesJs(): void
    {
        $this->exporter->export($this->entryFile, $this->config());

        $html = file_get_contents($this->tmpDir . '/index.html');

        $this->assertStringContainsString('<script>', $html);
        $this->assertStringContainsString('Counter', $html);
    }

    public function testExportNoExternalFiles(): void
    {
        $this->exporter->export($this->entryFile, $this->config());

        $files = glob($this->tmpDir . '/*.js');

        $this->assertEmpty($files);
    }

    public function testExportNoJsOmitsScript(): void
    {
        $this->exporter->export($this->entryFile, $this->config(['noJs' => true]));

        $html = file_get_contents($this->tmpDir . '/index.html');

        $this->assertStringNotContainsString('<script>', $html);
    }

    public function testExportMinified(): void
    {
        // Write a component with verbose CSS that minification will meaningfully shrink
        $verboseEntry = sys_get_temp_dir() . '/nexph_verbose_' . uniqid() . '.php';
        file_put_contents($verboseEntry, <<<'PHP'
<?php
class Verbose extends Component
{
    public function style(): string
    {
        return <<<CSS
/* Main card component styles */
.card  {  padding:  20px;  margin:  10px;  background:  white;  }
.card  h1  {  font-size:  24px;  color:  #333;  font-weight:  bold;  }
.card  p  {  font-size:  16px;  line-height:  1.5;  color:  #666;  }
.card  button  {  padding:  10px  20px;  background:  blue;  color:  white;  }
CSS;
    }

    public function render(): string
    {
        return '<div class="card"><h1>Title</h1><p>Body</p></div>';
    }
}
PHP);

        $normal   = $this->exporter->export($verboseEntry, ['output' => $this->tmpDir . '/normal',   'minify' => false]);
        $minified = $this->exporter->export($verboseEntry, ['output' => $this->tmpDir . '/minified', 'minify' => true]);

        $normalSize   = filesize($normal['files']['html']);
        $minifiedSize = filesize($minified['files']['html']);

        unlink($verboseEntry);
        $this->removeDir($this->tmpDir . '/normal');
        $this->removeDir($this->tmpDir . '/minified');

        $this->assertLessThan($normalSize, $minifiedSize);
    }

    public function testExportManifestHasCorrectComponent(): void
    {
        $result = $this->exporter->export($this->entryFile, $this->config());

        $this->assertContains('Counter', $result['manifest']['components']);
    }

    public function testExportSingleFileOutput(): void
    {
        $this->exporter->export($this->entryFile, $this->config());

        // Only index.html and manifest.json — no separate .js/.css
        $phpFiles = glob($this->tmpDir . '/*.js');
        $cssFiles = glob($this->tmpDir . '/*.css');

        $this->assertEmpty($phpFiles);
        $this->assertEmpty($cssFiles);
    }
}
