<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\BuildCommand;

class BuildCommandTest extends TestCase
{
    private string $tmpDir;
    private string $entryFile;

    protected function setUp(): void
    {
        $this->tmpDir    = sys_get_temp_dir() . '/nexph_cmd_' . uniqid();
        $this->entryFile = sys_get_temp_dir() . '/nexph_cmd_entry_' . uniqid() . '.php';

        mkdir($this->tmpDir, 0755, true);

        file_put_contents($this->entryFile, <<<'PHP'
<?php
class Counter extends Component
{
    public int $count = 0;

    public function render(): string
    {
        return '<div>{$this->count}</div>';
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

    private function runCommand(array $args): int
    {
        $command = new BuildCommand();
        return $command->run(array_merge(['nexph'], $args));
    }

    public function testRunWithNoArgsReturnsError(): void
    {
        $exitCode = $this->runCommand([]);

        $this->assertEquals(1, $exitCode);
    }

    public function testRunWithMissingEntryReturnsError(): void
    {
        $exitCode = $this->runCommand(['/nonexistent/App.php', '--output=' . $this->tmpDir]);

        $this->assertEquals(1, $exitCode);
    }

    public function testRunWithValidEntryReturnsSuccess(): void
    {
        $exitCode = $this->runCommand([$this->entryFile, '--output=' . $this->tmpDir]);

        $this->assertEquals(0, $exitCode);
    }

    public function testRunCreatesOutputFiles(): void
    {
        $this->runCommand([$this->entryFile, '--output=' . $this->tmpDir]);

        $this->assertFileExists($this->tmpDir . '/index.html');
        $this->assertFileExists($this->tmpDir . '/manifest.json');
    }

    public function testRunWithMinifyFlag(): void
    {
        $exitCode = $this->runCommand([$this->entryFile, '--output=' . $this->tmpDir, '--minify']);

        $this->assertEquals(0, $exitCode);
    }

    public function testRunWithProductionFlag(): void
    {
        $exitCode = $this->runCommand([$this->entryFile, '--output=' . $this->tmpDir, '--production']);

        $this->assertEquals(0, $exitCode);
    }

    public function testRunOutputsToStdout(): void
    {
        ob_start();
        $this->runCommand([$this->entryFile, '--output=' . $this->tmpDir]);
        $output = ob_get_clean();

        $this->assertStringContainsString('Built in', $output);
    }

    public function testRunOutputsFileSizes(): void
    {
        ob_start();
        $this->runCommand([$this->entryFile, '--output=' . $this->tmpDir]);
        $output = ob_get_clean();

        $this->assertStringContainsString('HTML', $output);
        $this->assertStringContainsString('JS', $output);
        $this->assertStringContainsString('CSS', $output);
    }
}
