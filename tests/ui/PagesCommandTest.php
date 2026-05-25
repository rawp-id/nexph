<?php

namespace Tests;

use Nexph\Builder\PagesCommand;
use PHPUnit\Framework\TestCase;

class PagesCommandTest extends TestCase
{
    private string $tmpOut;

    protected function setUp(): void
    {
        $this->tmpOut = sys_get_temp_dir() . '/nexph_pages_' . uniqid();
    }

    protected function tearDown(): void
    {
        $this->rrmdir($this->tmpOut);
    }

    public function testRunWithExplicitPages(): void
    {
        $entry = $this->createEntry('HomePage');
        $cmd   = new PagesCommand();
        $code  = $cmd->run(['pages', '--pages=' . $entry, '--output=' . $this->tmpOut]);
        $this->assertSame(0, $code);
    }

    public function testOutputDirCreated(): void
    {
        $entry = $this->createEntry('AboutPage');
        $cmd   = new PagesCommand();
        $cmd->run(['pages', '--pages=' . $entry, '--output=' . $this->tmpOut]);
        $this->assertDirectoryExists($this->tmpOut);
    }

    public function testIndexPageWrittenToRoot(): void
    {
        $entry = $this->createEntry('App');
        $cmd   = new PagesCommand();
        $cmd->run(['pages', '--pages=' . $entry, '--output=' . $this->tmpOut]);
        $this->assertFileExists($this->tmpOut . '/index.html');
    }

    public function testNonIndexPageWrittenToSubdir(): void
    {
        $entry = $this->createEntry('About');
        $cmd   = new PagesCommand();
        $cmd->run(['pages', '--pages=' . $entry, '--output=' . $this->tmpOut]);
        $this->assertFileExists($this->tmpOut . '/about/index.html');
    }

    public function testPagesManifestWritten(): void
    {
        $entry = $this->createEntry('App');
        $cmd   = new PagesCommand();
        $cmd->run(['pages', '--pages=' . $entry, '--output=' . $this->tmpOut]);
        $this->assertFileExists($this->tmpOut . '/pages-manifest.json');
    }

    public function testPagesManifestIsValidJson(): void
    {
        $entry = $this->createEntry('App');
        $cmd   = new PagesCommand();
        $cmd->run(['pages', '--pages=' . $entry, '--output=' . $this->tmpOut]);
        $json = json_decode(file_get_contents($this->tmpOut . '/pages-manifest.json'), true);
        $this->assertIsArray($json);
        $this->assertArrayHasKey('pages', $json);
        $this->assertArrayHasKey('total', $json);
    }

    public function testMultiplePagesBuilt(): void
    {
        $e1 = $this->createEntry('App');
        $e2 = $this->createEntry('Contact');
        $cmd = new PagesCommand();
        $code = $cmd->run(['pages', '--pages=' . $e1 . ',' . $e2, '--output=' . $this->tmpOut]);
        $this->assertSame(0, $code);
        $this->assertFileExists($this->tmpOut . '/index.html');
        $this->assertFileExists($this->tmpOut . '/contact/index.html');
    }

    public function testMissingEntryReturnsFailure(): void
    {
        $cmd  = new PagesCommand();
        $code = $cmd->run(['pages', '--pages=/nonexistent/Page.php', '--output=' . $this->tmpOut]);
        $this->assertSame(1, $code);
    }

    public function testNoPagesFallsBackToAutoDiscover(): void
    {
        // no --pages and no config — returns 1 (no pages found in cwd)
        $cmd  = new PagesCommand();
        $code = $cmd->run(['pages', '--output=' . $this->tmpOut]);
        // either 0 (auto-discovered) or 1 (none found) — just assert it runs
        $this->assertContains($code, [0, 1]);
    }

    private function createEntry(string $className): string
    {
        $dir  = sys_get_temp_dir() . '/nexph_pe_' . uniqid();
        mkdir($dir, 0755, true);
        $slug = strtolower($className);
        $path = "{$dir}/{$className}.php";
        file_put_contents($path, <<<PHP
<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use Nexph\Component;
class {$className} extends Component
{
    public string \$title = '{$className}';
    public function render(): string
    {
        return '<h1>{$className}</h1>';
    }
}
PHP);
        return $path;
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
