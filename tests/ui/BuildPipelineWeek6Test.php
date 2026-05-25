<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\BuildPipeline;

class BuildPipelineWeek6Test extends TestCase
{
    private string $tmpDir;
    private string $entryFile;

    protected function setUp(): void
    {
        $this->tmpDir    = sys_get_temp_dir() . '/nexph_w6_' . uniqid();
        mkdir($this->tmpDir, 0755, true);

        $this->entryFile = $this->tmpDir . '/App.php';
        file_put_contents($this->entryFile, $this->makeSource());
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
    }

    private function makeSource(): string
    {
        return <<<'PHP'
<?php
class App extends \Nexph\Component
{
    public string $title = 'Hello';
    public function render(): string
    {
        return <<<HTML
<div><h1>{$this->title}</h1></div>
HTML;
    }
}
PHP;
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..') continue;
            $path = "{$dir}/{$f}";
            is_dir($path) ? $this->removeDir($path) : unlink($path);
        }
        rmdir($dir);
    }

    private function build(array $extra = []): array
    {
        $outDir = $this->tmpDir . '/dist';
        $config = array_merge([
            'output'    => $outDir,
            'minify'    => false,
            'sourcemap' => false,
            'tailwind'  => false,
            'hmrPort'   => 0,
            'compress'  => false,
            'pwa'       => false,
            'routeMode' => 'hash',
        ], $extra);

        return (new BuildPipeline())->run($this->entryFile, $config);
    }

    // ── compress ──────────────────────────────────────────────────────────

    public function testCompressFlagCreatesGzFiles(): void
    {
        $result = $this->build(['compress' => true]);
        $this->assertTrue($result['success']);
        $this->assertFileExists($result['files']['html'] . '.gz');
        $this->assertFileExists($result['files']['js']   . '.gz');
        $this->assertFileExists($result['files']['css']  . '.gz');
    }

    public function testGzFilesAreValidGzip(): void
    {
        $result = $this->build(['compress' => true]);
        $gz     = file_get_contents($result['files']['html'] . '.gz');
        $this->assertEquals("\x1f\x8b", substr($gz, 0, 2));
    }

    public function testNoGzFilesWithoutFlag(): void
    {
        $result = $this->build(['compress' => false]);
        $this->assertFileDoesNotExist($result['files']['html'] . '.gz');
    }

    // ── pwa ───────────────────────────────────────────────────────────────

    public function testPwaFlagCreatesManifest(): void
    {
        $result = $this->build(['pwa' => true]);
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('pwa_manifest', $result['files']);
        $this->assertFileExists($result['files']['pwa_manifest']);
    }

    public function testPwaFlagCreatesSw(): void
    {
        $result = $this->build(['pwa' => true]);
        $this->assertArrayHasKey('sw', $result['files']);
        $this->assertFileExists($result['files']['sw']);
    }

    public function testPwaManifestIsValidJson(): void
    {
        $result = $this->build(['pwa' => true]);
        $data   = json_decode(file_get_contents($result['files']['pwa_manifest']), true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('name', $data);
    }

    public function testPwaInjectsManifestLinkInHtml(): void
    {
        $result = $this->build(['pwa' => true]);
        $html   = file_get_contents($result['files']['html']);
        $this->assertStringContainsString('manifest.webmanifest', $html);
    }

    public function testPwaInjectsServiceWorkerScript(): void
    {
        $result = $this->build(['pwa' => true]);
        $html   = file_get_contents($result['files']['html']);
        $this->assertStringContainsString('serviceWorker', $html);
    }

    public function testNoPwaFilesByDefault(): void
    {
        $result = $this->build();
        $this->assertArrayNotHasKey('pwa_manifest', $result['files']);
        $this->assertArrayNotHasKey('sw', $result['files']);
    }

    public function testPwaManifestAssetsInBuildManifest(): void
    {
        $result = $this->build(['pwa' => true]);
        $this->assertArrayHasKey('manifest.webmanifest', $result['manifest']['assets']);
        $this->assertArrayHasKey('sw.js', $result['manifest']['assets']);
    }

    // ── compress + pwa combined ───────────────────────────────────────────

    public function testCompressAndPwaCanCombine(): void
    {
        $result = $this->build(['compress' => true, 'pwa' => true]);
        $this->assertTrue($result['success']);
        $this->assertFileExists($result['files']['pwa_manifest']);
        $this->assertFileExists($result['files']['html'] . '.gz');
    }
}
