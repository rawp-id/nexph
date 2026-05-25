<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\SourceMapGenerator;

class SourceMapGeneratorTest extends TestCase
{
    private SourceMapGenerator $generator;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->generator = new SourceMapGenerator();
        $this->tmpDir    = sys_get_temp_dir() . '/nexph_sourcemap_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . '/*') as $f) {
            unlink($f);
        }
        rmdir($this->tmpDir);
    }

    public function testGenerateAppendsSourceMappingUrl(): void
    {
        $result = $this->generator->generate('const x = 1;', 'src/App.php');

        $this->assertStringContainsString('//# sourceMappingURL=', $result);
    }

    public function testGenerateInlineIsBase64DataUrl(): void
    {
        $result = $this->generator->generate('const x = 1;', 'src/App.php');

        $this->assertStringContainsString('data:application/json;base64,', $result);
    }

    public function testGenerateInlineMapIsValidJson(): void
    {
        $result = $this->generator->generate('const x = 1;', 'src/App.php');

        preg_match('/base64,(.+)$/', $result, $matches);
        $decoded = json_decode(base64_decode($matches[1]), true);

        $this->assertNotNull($decoded);
        $this->assertEquals(3, $decoded['version']);
    }

    public function testGenerateMapContainsSourceFile(): void
    {
        $result = $this->generator->generate('const x = 1;', 'src/App.php');

        preg_match('/base64,(.+)$/', $result, $matches);
        $decoded = json_decode(base64_decode($matches[1]), true);

        $this->assertContains('src/App.php', $decoded['sources']);
    }

    public function testGenerateMapContainsJsFilename(): void
    {
        $result = $this->generator->generate('const x = 1;', 'src/App.php', 'app.abc123.js');

        preg_match('/base64,(.+)$/', $result, $matches);
        $decoded = json_decode(base64_decode($matches[1]), true);

        $this->assertEquals('app.abc123.js', $decoded['file']);
    }

    public function testGenerateFileCreatesMapFile(): void
    {
        $mapFile = $this->tmpDir . '/app.js.map';

        $this->generator->generateFile('const x = 1;', 'src/App.php', $mapFile);

        $this->assertFileExists($mapFile);
    }

    public function testGenerateFileAppendsUrlComment(): void
    {
        $mapFile = $this->tmpDir . '/app.js.map';

        $result = $this->generator->generateFile('const x = 1;', 'src/App.php', $mapFile);

        $this->assertStringContainsString('//# sourceMappingURL=app.js.map', $result);
    }

    public function testGenerateFileMapIsValidJson(): void
    {
        $mapFile = $this->tmpDir . '/app.js.map';

        $this->generator->generateFile('const x = 1;', 'src/App.php', $mapFile);

        $decoded = json_decode(file_get_contents($mapFile), true);

        $this->assertNotNull($decoded);
        $this->assertEquals(3, $decoded['version']);
        $this->assertArrayHasKey('mappings', $decoded);
    }

    public function testGeneratePreservesOriginalJs(): void
    {
        $js     = 'const x = 1;';
        $result = $this->generator->generate($js, 'src/App.php');

        $this->assertStringStartsWith($js, $result);
    }
}
