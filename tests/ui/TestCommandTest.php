<?php

namespace Tests;

use Nexph\Builder\TestCommand;
use PHPUnit\Framework\TestCase;

class TestCommandTest extends TestCase
{
    private TestCommand $cmd;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->cmd    = new TestCommand();
        $this->tmpDir = sys_get_temp_dir() . '/nexph_tc_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
        ob_start();
    }

    protected function tearDown(): void
    {
        ob_end_clean();
        array_map('unlink', glob($this->tmpDir . '/*.php'));
        @rmdir($this->tmpDir);
    }

    public function testRunReturnsZeroWhenNoDirExists(): void
    {
        $code = $this->cmd->run(['test', '/nonexistent_dir_nexph']);
        $this->assertSame(0, $code);
    }

    public function testRunReturnsZeroWhenNoTestFiles(): void
    {
        $code = $this->cmd->run(['test', $this->tmpDir]);
        $this->assertSame(0, $code);
    }

    public function testRunPassingTest(): void
    {
        $entry = $this->createEntryFile();
        $this->createTestFile($this->tmpDir . '/App.test.php', $entry, [
            ['build_success' => 'build_success'],
        ]);
        $code = $this->cmd->run(['test', $this->tmpDir]);
        $this->assertSame(0, $code);
    }

    public function testRunFailingTest(): void
    {
        $entry = $this->createEntryFile();
        $this->createTestFile($this->tmpDir . '/App.test.php', $entry, [
            ['html_contains' => '__NONEXISTENT_STRING__'],
        ]);
        $code = $this->cmd->run(['test', $this->tmpDir]);
        $this->assertSame(1, $code);
    }

    public function testHtmlContainsAssertion(): void
    {
        $entry = $this->createEntryFile();
        $this->createTestFile($this->tmpDir . '/App.test.php', $entry, [
            ['html_contains' => 'data-nexph-component'],
        ]);
        $code = $this->cmd->run(['test', $this->tmpDir]);
        $this->assertSame(0, $code);
    }

    public function testComponentExistsAssertion(): void
    {
        $entry = $this->createEntryFile();
        $this->createTestFile($this->tmpDir . '/App.test.php', $entry, [
            ['component_exists' => 'TestApp'],
        ]);
        $code = $this->cmd->run(['test', $this->tmpDir]);
        $this->assertSame(0, $code);
    }

    public function testHasStateAssertion(): void
    {
        $entry = $this->createEntryFile();
        $this->createTestFile($this->tmpDir . '/App.test.php', $entry, [
            ['has_state' => 'count'],
        ]);
        $code = $this->cmd->run(['test', $this->tmpDir]);
        $this->assertSame(0, $code);
    }

    public function testHasMethodAssertion(): void
    {
        $entry = $this->createEntryFile();
        $this->createTestFile($this->tmpDir . '/App.test.php', $entry, [
            ['has_method' => 'increment'],
        ]);
        $code = $this->cmd->run(['test', $this->tmpDir]);
        $this->assertSame(0, $code);
    }

    public function testFilterSkipsNonMatchingFiles(): void
    {
        $entry = $this->createEntryFile();
        $this->createTestFile($this->tmpDir . '/App.test.php', $entry, [
            ['html_contains' => '__NONEXISTENT__'],
        ]);
        // filter to 'Counter' — no match, so 0 tests run → pass
        $code = $this->cmd->run(['test', $this->tmpDir, '--filter=Counter']);
        $this->assertSame(0, $code);
    }

    public function testVerboseFlagAccepted(): void
    {
        $entry = $this->createEntryFile();
        $this->createTestFile($this->tmpDir . '/App.test.php', $entry, [
            ['build_success' => 'build_success'],
        ]);
        $code = $this->cmd->run(['test', $this->tmpDir, '--verbose']);
        $this->assertSame(0, $code);
    }

    public function testMissingEntryFileReturnsFailure(): void
    {
        $this->createTestFile($this->tmpDir . '/App.test.php', '/nonexistent/App.php', [
            ['build_success' => 'build_success'],
        ]);
        $code = $this->cmd->run(['test', $this->tmpDir]);
        $this->assertSame(1, $code);
    }

    private function createEntryFile(): string
    {
        $path = $this->tmpDir . '/TestApp.php';
        file_put_contents($path, <<<'PHP'
<?php
require_once __DIR__ . '/../../vendor/autoload.php';
use Nexph\Component;
class TestApp extends Component
{
    public int $count = 0;
    public function increment(): void { $this->count++; }
    public function render(): string
    {
        return <<<HTML
<div>
    <span nx-bind="count">{$this->count}</span>
    <button nx-click="increment">+</button>
</div>
HTML;
    }
}
PHP);
        return $path;
    }

    private function createTestFile(string $path, string $entry, array $assertions): void
    {
        $assertStr = '';
        foreach ($assertions as $a) {
            foreach ($a as $type => $val) {
                if ($type === 'build_success') {
                    $assertStr .= "    'build_success',\n";
                } else {
                    $assertStr .= "    '{$type}' => '{$val}',\n";
                }
            }
        }
        file_put_contents($path, <<<PHP
<?php
nexph_test('basic test', '{$entry}', [
{$assertStr}]);
PHP);
    }
}
