# Component API

## Base Class

```php
<?php

use Nexph\Component;

class MyComponent extends Component
{
    // ...
}
```

## Reactive State

Public properties are automatically reactive:

```php
class Counter extends Component
{
    public int $count = 0;
    public string $name = '';
    public array $items = [];
    public bool $active = false;
    public ?User $user = null;
}
```

**Supported types:**
- `int`, `float`
- `string`
- `bool`
- `array`
- `?object` (nullable objects)

## Methods

Public methods can be called from templates:

```php
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

    public function reset(): void
    {
        $this->count = 0;
    }
}
```

```html
<button nx-click="increment">+</button>
<button nx-click="decrement">-</button>
<button nx-click="reset">Reset</button>
```

## Computed Properties

Override `computed()` to define derived values:

```php
class UserCard extends Component
{
    public string $firstName = 'John';
    public string $lastName = 'Doe';

    protected function computed(): array
    {
        return [
            'fullName' => fn() => "{$this->firstName} {$this->lastName}",
            'initials' => fn() => strtoupper(
                substr($this->firstName, 0, 1) . 
                substr($this->lastName, 0, 1)
            ),
            'isValid' => fn() => !empty($this->firstName) && !empty($this->lastName),
        ];
    }
}
```

Access in template:

```html
<h1>{$this->fullName}</h1>
<div class="avatar">{$this->initials}</div>
```

## Event Emission

Emit events to parent components:

```php
class ColorPicker extends Component
{
    public function selectColor(string $color): void
    {
        $this->emit('change', ['color' => $color]);
    }

    public function clear(): void
    {
        $this->emit('clear');
    }
}
```

**Signature:**

```php
public function emit(string $event, array $data = []): void
```

## Render Method

Required method that returns HTML:

```php
public function render(): string
{
    return <<<HTML
<div class="my-component">
    <h1>{$this->title}</h1>
    <p>{$this->description}</p>
</div>
HTML;
}
```

**Rules:**
- Must return valid HTML string
- Use `{$this->property}` for interpolation
- Use `nx-*` directives for reactivity

## Props

Props are passed from parent components:

```php
class Button extends Component
{
    public string $label = 'Click';
    public string $variant = 'primary';
    public bool $disabled = false;
    public int $size = 16;
}
```

Usage:

```html
<Button label="Save" variant="success" />
<Button label="Delete" variant="danger" disabled="true" />
```

**Type coercion:**
- `"true"` / `"false"` → `bool`
- Numeric strings → `int` / `float`
- JSON strings → `array`

## Slots

Accept child content:

```php
class Card extends Component
{
    public string $title = '';

    public function render(): string
    {
        return <<<HTML
<div class="card">
    <h3>{$this->title}</h3>
    <div class="card-body">
        <slot />
    </div>
</div>
HTML;
    }
}
```

Usage:

```html
<Card title="Welcome">
    <p>This goes into the slot.</p>
</Card>
```

## Constructor

Override for initialization:

```php
class ProductList extends Component
{
    public array $products = [];

    public function __construct()
    {
        parent::__construct();
        
        $this->products = [
            ['id' => 1, 'name' => 'Item 1'],
            ['id' => 2, 'name' => 'Item 2'],
        ];
    }
}
```

**Important:** Always call `parent::__construct()`.

## Complete Example

```php
<?php

use Nexph\Component;

class TodoList extends Component
{
    public array $todos = [];
    public string $newTodo = '';
    public string $filter = 'all';

    protected function computed(): array
    {
        return [
            'filteredTodos' => function() {
                return match($this->filter) {
                    'active' => array_filter($this->todos, fn($t) => !$t['done']),
                    'done' => array_filter($this->todos, fn($t) => $t['done']),
                    default => $this->todos,
                };
            },
            'remaining' => fn() => count(array_filter($this->todos, fn($t) => !$t['done'])),
        ];
    }

    public function addTodo(): void
    {
        if (empty($this->newTodo)) return;
        
        $this->todos[] = [
            'id' => uniqid(),
            'text' => $this->newTodo,
            'done' => false,
        ];
        $this->newTodo = '';
    }

    public function toggleTodo(int $index): void
    {
        $this->todos[$index]['done'] = !$this->todos[$index]['done'];
    }

    public function removeTodo(int $index): void
    {
        array_splice($this->todos, $index, 1);
        $this->emit('change', ['count' => count($this->todos)]);
    }

    public function render(): string
    {
        return <<<HTML
<div class="todo-list">
    <form nx-submit.prevent="addTodo">
        <input nx-model="newTodo" placeholder="New todo..." />
        <button type="submit">Add</button>
    </form>
    
    <div class="filters">
        <button nx-click="filter = 'all'">All</button>
        <button nx-click="filter = 'active'">Active</button>
        <button nx-click="filter = 'done'">Done</button>
    </div>
    
    <ul>
        <li nx-for="(todo, i) in filteredTodos">
            <input type="checkbox" nx-click="toggleTodo(i)" />
            <span nx-class="{ 'done': todo.done }">{\$todo.text}</span>
            <button nx-click="removeTodo(i)">×</button>
        </li>
    </ul>
    
    <p>{$this->remaining} items remaining</p>
</div>
HTML;
    }
}
```
