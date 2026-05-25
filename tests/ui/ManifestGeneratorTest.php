<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\ManifestGenerator;

class ManifestGeneratorTest extends TestCase
{
    private ManifestGenerator $generator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->generator = new ManifestGenerator();
        $this->tmpDir    = sys_get_temp_dir() . '/nexph_test_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $manifest = $this->tmpDir . '/manifest.json';
        if (file_exists($manifest)) {
            unlink($manifest);
        }
        rmdir($this->tmpDir);
    }

    public function testGenerateReturnsRequiredKeys(): void
    {
        $manifest = $this->generator->generate([]);

        $this->assertArrayHasKey('version', $manifest);
        $this->assertArrayHasKey('buildTime', $manifest);
        $this->assertArrayHasKey('entry', $manifest);
        $this->assertArrayHasKey('assets', $manifest);
        $this->assertArrayHasKey('components', $manifest);
        $this->assertArrayHasKey('size', $manifest);
    }

    public function testGenerateVersion(): void
    {
        $manifest = $this->generator->generate([]);

        $this->assertEquals('1.0.0', $manifest['version']);
    }

    public function testGenerateBuildTimeIsIso8601(): void
    {
        $manifest = $this->generator->generate([]);

        $this->assertNotEmpty($manifest['buildTime']);
        $this->assertNotFalse(strtotime($manifest['buildTime']));
    }

    public function testGenerateWithOptions(): void
    {
        $manifest = $this->generator->generate([
            'entry'      => 'src/App.php',
            'assets'     => ['app.js' => 'app.abc123.js'],
            'components' => ['Counter', 'Button'],
            'size'       => ['html' => 100, 'js' => 200, 'css' => 50, 'total' => 350],
        ]);

        $this->assertEquals('src/App.php', $manifest['entry']);
        $this->assertEquals(['app.js' => 'app.abc123.js'], $manifest['assets']);
        $this->assertContains('Counter', $manifest['components']);
        $this->assertEquals(350, $manifest['size']['total']);
    }

    public function testWriteCreatesJsonFile(): void
    {
        $manifest = $this->generator->generate(['entry' => 'src/App.php']);
        $this->generator->write($manifest, $this->tmpDir);

        $this->assertFileExists($this->tmpDir . '/manifest.json');
    }

    public function testWrittenFileIsValidJson(): void
    {
        $manifest = $this->generator->generate(['entry' => 'src/App.php']);
        $this->generator->write($manifest, $this->tmpDir);

        $content = file_get_contents($this->tmpDir . '/manifest.json');
        $decoded = json_decode($content, true);

        $this->assertNotNull($decoded);
        $this->assertEquals('1.0.0', $decoded['version']);
    }
}
