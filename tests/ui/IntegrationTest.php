<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Compiler\Compiler;

class IntegrationTest extends TestCase
{
    private Compiler $compiler;

    protected function setUp(): void
    {
        $this->compiler = new Compiler();
    }

    public function testTodoAppCompiles(): void
    {
        $source = file_get_contents(__DIR__ . '/../examples/TodoApp.php');
        $result = $this->compiler->compile($source);

        $this->assertStringContainsString('data-nexph-component="TodoApp"', $result['html']);
        $this->assertStringContainsString('data-nexph-submit="addTodo"', $result['html']);
        $this->assertStringContainsString('data-nexph-model="newTodo"', $result['html']);
        $this->assertStringContainsString('data-nexph-for="todo:todos"', $result['html']);
        $this->assertStringContainsString('data-nexph-if="todos"', $result['html']);
        $this->assertStringContainsString('data-nexph-show="newTodo"', $result['html']);
    }

    public function testTodoAppJsHasState(): void
    {
        $source = file_get_contents(__DIR__ . '/../examples/TodoApp.php');
        $result = $this->compiler->compile($source);

        $this->assertStringContainsString('"todos":[]', $result['js']);
        $this->assertStringContainsString('"newTodo":""', $result['js']);
    }

    public function testTodoAppJsHasMethods(): void
    {
        $source = file_get_contents(__DIR__ . '/../examples/TodoApp.php');
        $result = $this->compiler->compile($source);

        $this->assertStringContainsString('addTodo', $result['js']);
    }

    public function testCounterCompiles(): void
    {
        $source = file_get_contents(__DIR__ . '/../examples/Counter.php');
        $result = $this->compiler->compile($source);

        $this->assertStringContainsString('data-nexph-component="Counter"', $result['html']);
        $this->assertStringContainsString('data-nexph-click="increment"', $result['html']);
        $this->assertStringContainsString('"count":0', $result['js']);
        $this->assertStringContainsString('state.count++', $result['js']);
    }

    public function testComputedPropertyInJs(): void
    {
        $source = <<<'PHP'
<?php
class Profile extends Component
{
    public string $firstName = 'John';
    public string $lastName = 'Doe';

    public function computed(): array
    {
        return [
            'fullName' => fn() => "{$this->firstName} {$this->lastName}",
        ];
    }

    public function render(): string
    {
        return <<<HTML
<div><span>{$this->fullName}</span></div>
HTML;
    }
}
PHP;

        $result = $this->compiler->compile($source);

        $this->assertStringContainsString('_computed_fullName', $result['js']);
    }

    public function testLifecycleOnMountInJs(): void
    {
        $source = <<<'PHP'
<?php
class Timer extends Component
{
    public int $count = 0;

    public function onMount(): void
    {
        $this->count = 1;
    }

    public function render(): string
    {
        return '<div></div>';
    }
}
PHP;

        $result = $this->compiler->compile($source);

        $this->assertStringContainsString('state.count', $result['js']);
        $this->assertStringContainsString('onMount', $result['js']);
    }

    public function testCssScopingInCompile(): void
    {
        $source = <<<'PHP'
<?php
class Card extends Component
{
    public function style(): string
    {
        return <<<CSS
.card { padding: 20px; }
CSS;
    }

    public function render(): string
    {
        return '<div class="card">Hello</div>';
    }
}
PHP;

        $result = $this->compiler->compile($source);

        $this->assertStringContainsString('data-nx-scope', $result['html']);
        $this->assertStringContainsString('[data-nx-scope=', $result['css']);
    }

    public function testBuildPipelineTodoApp(): void
    {
        $tmpDir = sys_get_temp_dir() . '/nexph_int_' . uniqid();
        mkdir($tmpDir, 0755, true);

        $pipeline = new \Nexph\Builder\BuildPipeline();
        $result = $pipeline->run(__DIR__ . '/../examples/TodoApp.php', [
            'output' => $tmpDir,
            'minify' => false,
        ]);

        $this->assertTrue($result['success']);
        $this->assertFileExists($tmpDir . '/index.html');
        $this->assertContains('TodoApp', $result['manifest']['components']);

        // cleanup
        $this->removeDir($tmpDir);
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
}
