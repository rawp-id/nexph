<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\InitCommand;

class InitCommandTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/nexph_init_' . uniqid();
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->tmpDir);
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

    private function runInit(array $args = []): int
    {
        ob_start();
        $code = (new InitCommand())->run(array_merge(['init', $this->tmpDir], $args));
        ob_end_clean();
        return $code;
    }

    public function testInitReturnsZero(): void
    {
        $this->assertEquals(0, $this->runInit());
    }

    public function testInitCreatesDirectory(): void
    {
        $this->runInit();
        $this->assertDirectoryExists($this->tmpDir);
    }

    public function testInitCreatesSrcDir(): void
    {
        $this->runInit();
        $this->assertDirectoryExists("{$this->tmpDir}/src");
    }

    public function testInitCreatesExamplesDir(): void
    {
        $this->runInit();
        $this->assertDirectoryExists("{$this->tmpDir}/examples");
    }

    public function testInitCreatesDistDir(): void
    {
        $this->runInit();
        $this->assertDirectoryExists("{$this->tmpDir}/dist");
    }

    public function testInitCreatesAppPhp(): void
    {
        $this->runInit();
        $this->assertFileExists("{$this->tmpDir}/examples/App.php");
    }

    public function testInitCreatesCounterPhp(): void
    {
        $this->runInit();
        $this->assertFileExists("{$this->tmpDir}/examples/Counter.php");
    }

    public function testInitCreatesConfig(): void
    {
        $this->runInit();
        $this->assertFileExists("{$this->tmpDir}/nexph.config.php");
    }

    public function testInitCreatesGitignore(): void
    {
        $this->runInit();
        $this->assertFileExists("{$this->tmpDir}/.gitignore");
    }

    public function testInitCreatesTodoByDefault(): void
    {
        $this->runInit();
        $this->assertFileExists("{$this->tmpDir}/examples/TodoApp.php");
    }

    public function testInitMinimalSkipsTodo(): void
    {
        $this->runInit(['--minimal']);
        $this->assertFileDoesNotExist("{$this->tmpDir}/examples/TodoApp.php");
    }

    public function testInitAppPhpContainsComponent(): void
    {
        $this->runInit();
        $content = file_get_contents("{$this->tmpDir}/examples/App.php");
        $this->assertStringContainsString('extends Component', $content);
    }

    public function testInitConfigReturnsArray(): void
    {
        $this->runInit();
        $config = require "{$this->tmpDir}/nexph.config.php";
        $this->assertIsArray($config);
    }

    public function testInitConfigHasEntry(): void
    {
        $this->runInit();
        $config = require "{$this->tmpDir}/nexph.config.php";
        $this->assertArrayHasKey('entry', $config);
    }

    public function testInitGitignoreContainsVendor(): void
    {
        $this->runInit();
        $content = file_get_contents("{$this->tmpDir}/.gitignore");
        $this->assertStringContainsString('vendor/', $content);
    }

    public function testInitFailsIfDirExistsWithoutForce(): void
    {
        mkdir($this->tmpDir, 0755, true);
        ob_start();
        $code = (new InitCommand())->run(['init', $this->tmpDir]);
        ob_end_clean();
        $this->assertEquals(1, $code);
    }

    public function testInitForceOverwritesExisting(): void
    {
        mkdir($this->tmpDir, 0755, true);
        $this->assertEquals(0, $this->runInit(['--force']));
    }
}
