# NEXPH UI - Week 2 Roadmap

## Goals

- Multiple event types
- Two-way binding (nx-model)
- Conditional rendering (nx-if)
- Loop rendering (nx-for)
- Component props
- Nested components

## Day 1-2: Event System

### nx-input
```php
<input nx-input="handleInput" />
```

### nx-submit
```php
<form nx-submit="handleSubmit">
```

### nx-keydown, nx-keyup
```php
<input nx-keydown="handleKey" />
```

### Event modifiers
```php
<form nx-submit.prevent="save">
<input nx-keydown.enter="submit">
```

## Day 3: Two-Way Binding

### nx-model
```php
public string $name = '';

<input nx-model="name" />
<p>Hello {$this->name}</p>
```

### Implementation
- Parse nx-model attribute
- Generate input event listener
- Bind value to state
- Update on input change

## Day 4: Conditional Rendering

### nx-if
```php
public bool $visible = true;

<div nx-if="visible">
    Content
</div>
```

### nx-show
```php
<div nx-show="visible">
    Content
</div>
```

### Difference
- nx-if: removes from DOM
- nx-show: display:none

## Day 5: Loop Rendering

### nx-for
```php
public array $items = ['a', 'b', 'c'];

<ul>
    <li nx-for="item in items">{$item}</li>
</ul>
```

### With index
```php
<li nx-for="(item, index) in items">
    {$index}: {$item}
</li>
```

## Day 6-7: Component System

### Props
```php
class Button extends Component
{
    public string $label = 'Click';
    public string $color = 'blue';
}

<Button label="Submit" color="green" />
```

### Slots
```php
class Card extends Component
{
    public function render(): string
    {
        return <<<HTML
<div class="card">
    <slot name="header" />
    <slot />
    <slot name="footer" />
</div>
HTML;
    }
}

<Card>
    <template slot="header">
        <h1>Title</h1>
    </template>
    <p>Content</p>
</Card>
```

### Nested components
```php
<TodoList>
    <TodoItem title="Task 1" />
    <TodoItem title="Task 2" />
</TodoList>
```

## Technical Tasks

### Parser enhancements
- Extract event types
- Parse nx-model
- Parse nx-if/nx-show
- Parse nx-for loops
- Parse component props
- Parse slots

### HtmlGenerator updates
- Handle conditionals
- Handle loops
- Handle component nesting
- Handle slots

### JsGenerator updates
- Multiple event types
- Two-way binding logic
- Conditional DOM updates
- Loop rendering
- Component communication

### Runtime additions
- Event delegation
- Model binding
- Conditional rendering
- List diffing
- Component registry

## Examples

### TodoList (full)
```php
class TodoList extends Component
{
    public array $todos = [];
    public string $newTodo = '';

    public function addTodo()
    {
        if (!empty($this->newTodo)) {
            $this->todos[] = [
                'id' => uniqid(),
                'text' => $this->newTodo,
                'done' => false,
            ];
            $this->newTodo = '';
        }
    }

    public function toggle(string $id)
    {
        foreach ($this->todos as &$todo) {
            if ($todo['id'] === $id) {
                $todo['done'] = !$todo['done'];
            }
        }
    }

    public function render(): string
    {
        return <<<HTML
<div class="todo-app">
    <form nx-submit.prevent="addTodo">
        <input 
            nx-model="newTodo" 
            placeholder="Add todo..."
        />
        <button type="submit">Add</button>
    </form>

    <ul>
        <li nx-for="todo in todos">
            <input 
                type="checkbox" 
                nx-checked="todo.done"
                nx-click="toggle(todo.id)"
            />
            <span nx-class="{'done': todo.done}">
                {$todo.text}
            </span>
        </li>
    </ul>
</div>
HTML;
    }
}
```

### Form validation
```php
class LoginForm extends Component
{
    public string $email = '';
    public string $password = '';
    public string $error = '';

    public function submit()
    {
        if (empty($this->email)) {
            $this->error = 'Email required';
            return;
        }
        
        if (empty($this->password)) {
            $this->error = 'Password required';
            return;
        }
        
        // Submit logic
    }

    public function render(): string
    {
        return <<<HTML
<form nx-submit.prevent="submit">
    <div nx-if="error" class="error">
        {$this->error}
    </div>

    <input 
        type="email" 
        nx-model="email"
        placeholder="Email"
    />

    <input 
        type="password" 
        nx-model="password"
        placeholder="Password"
    />

    <button type="submit">Login</button>
</form>
HTML;
    }
}
```

## Success Metrics

- [ ] 5+ event types working
- [ ] nx-model two-way binding
- [ ] nx-if/nx-show conditionals
- [ ] nx-for loops with arrays
- [ ] Props passing between components
- [ ] Slots working
- [ ] TodoList example fully functional
- [ ] Runtime still <5kb
- [ ] Tests covering new features

## Stretch Goals

- nx-class dynamic classes
- nx-style dynamic styles
- Component lifecycle hooks
- Computed properties
- Watchers
- Transitions/animations
