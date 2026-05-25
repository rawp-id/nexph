# Todo App Example

Classic todo list with add, toggle, and delete.

## Code

```php
<?php
// examples/TodoApp.php

use Nexph\Component;

class TodoApp extends Component
{
    public array $todos = [];
    public string $newTodo = '';

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

    public function deleteTodo(int $index): void
    {
        array_splice($this->todos, $index, 1);
    }

    public function clearCompleted(): void
    {
        $this->todos = array_values(
            array_filter($this->todos, fn($t) => !$t['done'])
        );
    }

    protected function computed(): array
    {
        return [
            'remaining' => fn() => count(
                array_filter($this->todos, fn($t) => !$t['done'])
            ),
            'hasCompleted' => fn() => count(
                array_filter($this->todos, fn($t) => $t['done'])
            ) > 0,
        ];
    }

    public function render(): string
    {
        return <<<HTML
<div class="todo-app">
    <h1>Todo List</h1>
    
    <form nx-submit.prevent="addTodo" class="add-form">
        <input 
            type="text" 
            nx-model="newTodo"
            placeholder="What needs to be done?"
        />
        <button type="submit">Add</button>
    </form>
    
    <ul class="todo-list">
        <li nx-for="(todo, i) in todos" nx-class="{ 'done': todo.done }">
            <input 
                type="checkbox" 
                nx-click="toggleTodo(i)"
            />
            <span>{\$todo.text}</span>
            <button nx-click="deleteTodo(i)" class="delete">×</button>
        </li>
    </ul>
    
    <div class="footer" nx-if="todos.length > 0">
        <span>{$this->remaining} items left</span>
        <button nx-if="hasCompleted" nx-click="clearCompleted">
            Clear completed
        </button>
    </div>
</div>

<style>
.todo-app {
    max-width: 400px;
    margin: 2rem auto;
    padding: 1rem;
}
.add-form {
    display: flex;
    gap: 0.5rem;
    margin-bottom: 1rem;
}
.add-form input {
    flex: 1;
    padding: 0.5rem;
}
.todo-list {
    list-style: none;
    padding: 0;
}
.todo-list li {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0;
    border-bottom: 1px solid #eee;
}
.todo-list li.done span {
    text-decoration: line-through;
    color: #999;
}
.todo-list li span {
    flex: 1;
}
.delete {
    background: none;
    border: none;
    color: #e53e3e;
    cursor: pointer;
    font-size: 1.25rem;
}
.footer {
    display: flex;
    justify-content: space-between;
    margin-top: 1rem;
    font-size: 0.875rem;
    color: #666;
}
</style>
HTML;
    }
}
```

## Features Demonstrated

- Array state management
- Two-way binding (`nx-model`)
- Form submission (`nx-submit.prevent`)
- Loops with index (`nx-for`)
- Conditional rendering (`nx-if`)
- Dynamic classes (`nx-class`)
- Computed properties

## Run

```bash
nexph dev examples/TodoApp.php
```
