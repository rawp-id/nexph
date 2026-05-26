<?php

namespace Nexph\Builder;

/**
 * nexph init — scaffolds a new NEXPH project.
 */
class InitCommand
{
    public function run(array $argv): int
    {
        $args    = array_slice($argv, 1);
        $name    = $this->extractName($args);
        $dir     = $name ? rtrim($name, '/') : '.';
        $force   = in_array('--force', $args);
        $minimal = in_array('--minimal', $args);

        if ($dir !== '.' && file_exists($dir) && !$force) {
            $this->error("Directory '{$dir}' already exists. Use --force to overwrite.");
            return 1;
        }

        $this->info("Scaffolding NEXPH project" . ($name ? " in '{$dir}'" : ' in current directory') . '...');

        $this->ensureDir($dir);
        $this->ensureDir("{$dir}/src");
        $this->ensureDir("{$dir}/examples");
        $this->ensureDir("{$dir}/dist");

        $this->writeFile("{$dir}/examples/App.php",       $this->stubApp());
        $this->writeFile("{$dir}/examples/Counter.php",   $this->stubCounter());
        $this->writeFile("{$dir}/nexph.config.php",       $this->stubConfig());
        $this->writeFile("{$dir}/.gitignore",             $this->stubGitignore());

        if (!$minimal) {
            $this->writeFile("{$dir}/examples/TodoApp.php", $this->stubTodo());
        }

        $this->info('✓ Created project structure');
        $this->info('');
        $this->info('  Next steps:');
        $this->info("    cd {$dir}");
        $this->info('    composer install');
        $this->info('    nexph dev examples/App.php');
        $this->info('');

        return 0;
    }

    private function extractName(array $args): ?string
    {
        foreach ($args as $arg) {
            if (!str_starts_with($arg, '--')) return $arg;
        }
        return null;
    }

    private function stubApp(): string
    {
        return <<<'PHP'
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Nexph\Component;

class App extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }

    public function style(): string
    {
        return <<<CSS
body {
    font-family: sans-serif;
    display: flex;
    justify-content: center;
    padding: 40px;
    background: #0f0f1a;
    color: #e2e8f0;
}
.app { text-align: center; }
.btn {
    padding: 10px 24px;
    background: #6366f1;
    color: #fff;
    border: none;
    border-radius: 8px;
    font-size: 16px;
    cursor: pointer;
}
.count { font-size: 48px; font-weight: 800; margin: 24px 0; }
CSS;
    }

    public function render(): string
    {
        return <<<HTML
<div class="app">
    <h1>NEXPH App</h1>
    <div class="count">{$this->count}</div>
    <button class="btn" nx-click="increment">Increment</button>
</div>
HTML;
    }
}
PHP;
    }

    private function stubCounter(): string
    {
        return <<<'PHP'
<?php

use Nexph\Component;

class Counter extends Component
{
    public int $count = 0;

    public function increment(): void
    {
        $this->count++;
    }

    public function decrement(): void
    {
        $this->count--;
    }

    public function render(): string
    {
        return <<<HTML
<div>
    <button nx-click="decrement">−</button>
    <span>{$this->count}</span>
    <button nx-click="increment">+</button>
</div>
HTML;
    }
}
PHP;
    }

    private function stubTodo(): string
    {
        return <<<'PHP'
<?php

use Nexph\Component;

class TodoApp extends Component
{
    public array $todos = [];
    public string $newTodo = '';

    public function addTodo(): void
    {
        if (!empty($this->newTodo)) {
            $this->todos[] = ['text' => $this->newTodo, 'done' => false];
            $this->newTodo = '';
        }
    }

    public function toggleTodo(int $index): void
    {
        if (isset($this->todos[$index])) {
            $this->todos[$index]['done'] = !$this->todos[$index]['done'];
        }
    }

    public function render(): string
    {
        return <<<HTML
<div>
    <form nx-submit.prevent="addTodo">
        <input nx-model="newTodo" placeholder="New todo..." />
        <button type="submit">Add</button>
    </form>
    <ul>
        <li nx-for="todo in todos">{$todo.text}</li>
    </ul>
</div>
HTML;
    }
}
PHP;
    }

    private function stubConfig(): string
    {
        return <<<'PHP'
<?php

return [
    'entry'     => 'examples/App.php',
    'output'    => 'dist/',
    'minify'    => false,
    'sourcemap' => false,
    'tailwind'  => false,
    'devServer' => [
        'port' => 3000,
        'hot'  => true,
    ],
];
PHP;
    }

    private function stubGitignore(): string
    {
        return implode("\n", [
            'vendor/',
            'dist/',
            '.DS_Store',
            '*.gz',
            '',
        ]);
    }

    private function writeFile(string $path, string $content): void
    {
        file_put_contents($path, $content);
        $this->info("  created {$path}");
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) mkdir($dir, 0755, true);
    }

    private function info(string $msg): void
    {
        echo $msg . PHP_EOL;
    }

    private function error(string $msg): void
    {
        fwrite(STDERR, "Error: {$msg}" . PHP_EOL);
    }
}
