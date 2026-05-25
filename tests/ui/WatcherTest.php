<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\DevServer\Watcher;

class WatcherTest extends TestCase
{
    private Watcher $watcher;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->watcher = new Watcher();
        $this->tmpDir  = sys_get_temp_dir() . '/nexph_watcher_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpDir . '/*.php') as $f) {
            unlink($f);
        }
        rmdir($this->tmpDir);
    }

    private function writeFile(string $name, string $content = '<?php'): string
    {
        $path = $this->tmpDir . '/' . $name;
        file_put_contents($path, $content);
        return $path;
    }

    public function testFirstCheckDoesNotReportChanges(): void
    {
        $this->writeFile('App.php');

        $changed = $this->watcher->check([$this->tmpDir]);

        $this->assertEmpty($changed);
    }

    public function testDetectsModifiedFile(): void
    {
        $file = $this->writeFile('App.php', '<?php // v1');

        // Seed
        $this->watcher->check([$this->tmpDir]);

        // Modify — ensure mtime changes by sleeping 1s
        sleep(1);
        file_put_contents($file, '<?php // v2');

        $changed = $this->watcher->check([$this->tmpDir]);

        $this->assertContains($file, $changed);
    }

    public function testUnchangedFileNotReported(): void
    {
        $file = $this->writeFile('App.php');

        $this->watcher->check([$this->tmpDir]);
        $changed = $this->watcher->check([$this->tmpDir]);

        $this->assertNotContains($file, $changed);
    }

    public function testSeedPreventsFirstChangeReport(): void
    {
        $file = $this->writeFile('App.php');

        $this->watcher->seed([$this->tmpDir]);
        $changed = $this->watcher->check([$this->tmpDir]);

        $this->assertEmpty($changed);
    }

    public function testWatchesDirectoryRecursively(): void
    {
        $subDir = $this->tmpDir . '/sub';
        mkdir($subDir, 0755, true);
        $file = $subDir . '/Child.php';
        file_put_contents($file, '<?php // v1');

        // Seed
        $this->watcher->check([$this->tmpDir]);

        sleep(1);
        file_put_contents($file, '<?php // v2');

        $changed = $this->watcher->check([$this->tmpDir]);

        $this->assertContains($file, $changed);

        unlink($file);
        rmdir($subDir);
    }

    public function testWatchesSingleFile(): void
    {
        $file = $this->writeFile('App.php', '<?php // v1');

        $this->watcher->check([$file]);

        sleep(1);
        file_put_contents($file, '<?php // v2');

        $changed = $this->watcher->check([$file]);

        $this->assertContains($file, $changed);
    }

    public function testIgnoresNonPhpFiles(): void
    {
        $txt = $this->tmpDir . '/notes.txt';
        file_put_contents($txt, 'hello');

        $this->watcher->check([$this->tmpDir]);

        sleep(1);
        file_put_contents($txt, 'changed');

        $changed = $this->watcher->check([$this->tmpDir]);

        $this->assertNotContains($txt, $changed);

        unlink($txt);
    }
}
