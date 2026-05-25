# NEXPH UI - Week 3 Roadmap

## Goals

- Component props system
- Slots implementation
- Nested components
- Component communication
- Dynamic classes (nx-class)
- Dynamic styles (nx-style)
- Computed properties
- Build system foundation

## Day 1-2: Props System

### Basic Props
```php
class Button extends Component
{
    public string $label = 'Click';
    public string $variant = 'primary';
    public bool $disabled = false;
    
    public function render(): string
    {
        return <<<HTML
<button 
    class="btn btn-{$this->variant}"
    disabled="{$this->disabled}"
>
    {$this->label}
</button>
HTML;
    }
}

// Usage
<Button label="Submit" variant="success" />
<Button label="Cancel" variant="danger" disabled="true" />
```

### Props Validation
```php
class Avatar extends Component
{
    public string $src;
    public string $alt = 'Avatar';
    public int $size = 48;
    
    protected array $propTypes = [
        'src' => 'required|string',
        'size' => 'int|min:16|max:256',
    ];
}
```

### Props Passing
- Parse component attributes
- Extract prop values
- Type conversion (string → bool, int)
- Default values
- Required props validation

## Day 3: Slots System

### Default Slot
```php
class Card extends Component
{
    public function render(): string
    {
        return <<<HTML
<div class="card">
    <slot />
</div>
HTML;
    }
}

<Card>
    <p>Card content here</p>
</Card>
```

### Named Slots
```php
class Modal extends Component
{
    public function render(): string
    {
        return <<<HTML
<div class="modal">
    <div class="modal-header">
        <slot name="header" />
    </div>
    <div class="modal-body">
        <slot />
    </div>
    <div class="modal-footer">
        <slot name="footer" />
    </div>
</div>
HTML;
    }
}

<Modal>
    <template slot="header">
        <h2>Title</h2>
    </template>
    
    <p>Modal body content</p>
    
    <template slot="footer">
        <button>Close</button>
    </template>
</Modal>
```

### Scoped Slots
```php
class List extends Component
{
    public array $items = [];
    
    public function render(): string
    {
        return <<<HTML
<ul>
    <li nx-for="item in items">
        <slot :item="item" />
    </li>
</ul>
HTML;
    }
}

<List :items="users">
    <template slot-scope="{ item }">
        <strong>{$item.name}</strong>
    </template>
</List>
```

## Day 4: Nested Components

### Component Registry
```php
class ComponentRegistry
{
    private static array $components = [];
    
    public static function register(string $name, string $class): void
    {
        self::$components[$name] = $class;
    }
    
    public static function resolve(string $name): ?string
    {
        return self::$components[$name] ?? null;
    }
}
```

### Component Resolution
```php
// Auto-register from namespace
ComponentRegistry::register('Button', Button::class);
ComponentRegistry::register('Card', Card::class);

// Parse nested components
<Card>
    <Button label="Click me" />
</Card>
```

### Component Tree
```php
class TodoApp extends Component
{
    public array $todos = [];
    
    public function render(): string
    {
        return <<<HTML
<div class="app">
    <TodoForm @add="addTodo" />
    <TodoList :items="todos" @toggle="toggleTodo" />
</div>
HTML;
    }
}
```

## Day 5: Component Communication

### Events Up
```php
class TodoItem extends Component
{
    public array $todo;
    
    public function toggle()
    {
        $this->emit('toggle', $this->todo['id']);
    }
    
    public function render(): string
    {
        return <<<HTML
<li>
    <input 
        type="checkbox" 
        nx-click="toggle"
        checked="{$this->todo['done']}"
    />
    <span>{$this->todo['text']}</span>
</li>
HTML;
    }
}

// Parent listens
<TodoItem :todo="todo" @toggle="handleToggle" />
```

### Props Down
```php
class Parent extends Component
{
    public string $message = 'Hello';
    
    public function render(): string
    {
        return <<<HTML
<Child :message="message" />
HTML;
    }
}

class Child extends Component
{
    public string $message;
    
    public function render(): string
    {
        return <<<HTML
<p>{$this->message}</p>
HTML;
    }
}
```

### State Management
```php
class Store
{
    private static array $state = [];
    
    public static function set(string $key, $value): void
    {
        self::$state[$key] = $value;
        self::notify($key);
    }
    
    public static function get(string $key)
    {
        return self::$state[$key] ?? null;
    }
}

// Usage in component
$count = Store::get('count');
Store::set('count', $count + 1);
```

## Day 6: Dynamic Classes & Styles

### nx-class
```php
public bool $active = true;
public bool $disabled = false;

<div nx-class="{
    'active': active,
    'disabled': disabled,
    'btn-primary': true
}">
    Button
</div>

// Output when active=true, disabled=false
<div class="active btn-primary">
```

### nx-style
```php
public string $color = 'red';
public int $size = 16;

<div nx-style="{
    'color': color,
    'font-size': size + 'px',
    'display': 'block'
}">
    Styled text
</div>

// Output
<div style="color: red; font-size: 16px; display: block;">
```

### Array/String Syntax
```php
// Array syntax
<div nx-class="['btn', 'btn-' + variant]">

// Object syntax
<div nx-class="{ active, disabled }">

// Mixed
<div nx-class="['btn', { active, disabled }]">
```

## Day 7: Computed Properties

### Basic Computed
```php
class UserProfile extends Component
{
    public string $firstName = 'John';
    public string $lastName = 'Doe';
    
    public function computed(): array
    {
        return [
            'fullName' => fn() => "{$this->firstName} {$this->lastName}",
            'initials' => fn() => strtoupper(
                $this->firstName[0] . $this->lastName[0]
            ),
        ];
    }
    
    public function render(): string
    {
        return <<<HTML
<div>
    <h1>{$this->fullName}</h1>
    <span class="avatar">{$this->initials}</span>
</div>
HTML;
    }
}
```

### Cached Computed
```php
class ProductList extends Component
{
    public array $products = [];
    public string $filter = '';
    
    public function computed(): array
    {
        return [
            'filteredProducts' => fn() => array_filter(
                $this->products,
                fn($p) => str_contains($p['name'], $this->filter)
            ),
            'totalPrice' => fn() => array_sum(
                array_column($this->filteredProducts, 'price')
            ),
        ];
    }
}
```

## Technical Tasks

### Parser Enhancements
- [x] Extract component tags
- [x] Parse component attributes as props
- [x] Parse slot tags
- [x] Parse slot names
- [x] Parse event bindings (@event)
- [x] Parse prop bindings (:prop)
- [x] Parse nx-class object syntax
- [x] Parse nx-style object syntax

### Compiler Updates
- [ ] ComponentResolver
- [ ] PropsExtractor
- [ ] SlotProcessor
- [ ] EventEmitter generator
- [ ] ClassBinding generator
- [ ] StyleBinding generator
- [ ] ComputedProperty processor

### HtmlGenerator Updates
- [ ] Process component tags
- [ ] Inject props as data attributes
- [ ] Process slot placeholders
- [ ] Process nx-class
- [ ] Process nx-style
- [ ] Process computed properties

### JsGenerator Updates
- [ ] Component instantiation
- [ ] Props reactivity
- [ ] Event emission
- [ ] Event listening
- [ ] Class binding logic
- [ ] Style binding logic
- [ ] Computed property caching

### Runtime Additions
- [ ] Component registry
- [ ] Props system
- [ ] Slot rendering
- [ ] Event bus
- [ ] Class binding
- [ ] Style binding
- [ ] Computed cache

## Build System Foundation

### nexph build command
```bash
nexph build src/App.phpx --output dist/
```

### Output Structure
```
dist/
├── index.html
├── app.js
├── app.css
├── manifest.json
└── assets/
    └── [hashed files]
```

### Build Pipeline
1. Parse all components
2. Resolve dependencies
3. Generate HTML
4. Extract CSS
5. Bundle JS runtime
6. Optimize assets
7. Generate manifest

### Build Config
```php
// nexph.config.php
return [
    'entry' => 'src/App.phpx',
    'output' => 'dist/',
    'minify' => true,
    'sourcemap' => true,
    'cssExtract' => true,
    'target' => 'es2020',
];
```

## Examples

### Complete Component System
```php
// Button.phpx
class Button extends Component
{
    public string $label = 'Click';
    public string $variant = 'primary';
    public bool $disabled = false;
    
    public function handleClick()
    {
        $this->emit('click');
    }
    
    public function render(): string
    {
        return <<<HTML
<button 
    nx-class="{
        'btn': true,
        'btn-primary': variant === 'primary',
        'btn-danger': variant === 'danger',
        'disabled': disabled
    }"
    nx-click="handleClick"
    disabled="{$this->disabled}"
>
    {$this->label}
</button>
HTML;
    }
}

// TodoApp.phpx
class TodoApp extends Component
{
    public array $todos = [];
    public string $filter = 'all';
    
    public function computed(): array
    {
        return [
            'filteredTodos' => fn() => match($this->filter) {
                'active' => array_filter($this->todos, fn($t) => !$t['done']),
                'completed' => array_filter($this->todos, fn($t) => $t['done']),
                default => $this->todos,
            },
            'activeCount' => fn() => count(array_filter(
                $this->todos, 
                fn($t) => !$t['done']
            )),
        ];
    }
    
    public function addTodo(string $text)
    {
        $this->todos[] = [
            'id' => uniqid(),
            'text' => $text,
            'done' => false,
        ];
    }
    
    public function toggleTodo(string $id)
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
    <TodoForm @add="addTodo" />
    
    <div class="filters">
        <Button 
            label="All" 
            :variant="filter === 'all' ? 'primary' : 'secondary'"
            @click="filter = 'all'"
        />
        <Button 
            label="Active ({$this->activeCount})" 
            :variant="filter === 'active' ? 'primary' : 'secondary'"
            @click="filter = 'active'"
        />
        <Button 
            label="Completed" 
            :variant="filter === 'completed' ? 'primary' : 'secondary'"
            @click="filter = 'completed'"
        />
    </div>
    
    <TodoList 
        :items="filteredTodos" 
        @toggle="toggleTodo" 
    />
</div>
HTML;
    }
}
```

## Success Metrics

- [ ] Props passing with type conversion
- [ ] Default and named slots working
- [ ] Nested components rendering
- [ ] Event emission and listening
- [ ] nx-class dynamic classes
- [ ] nx-style dynamic styles
- [ ] Computed properties with caching
- [ ] Component registry functional
- [ ] Build command prototype
- [ ] Runtime <10kb
- [ ] Full TodoApp example working

## Stretch Goals

- Lifecycle hooks (onMount, onUpdate, onDestroy)
- Watchers for reactive side effects
- Provide/Inject for deep prop passing
- Async components
- Component lazy loading
- CSS scoping per component
- Hot module replacement
- Dev server with watch mode

## Notes

**Runtime Budget**: Currently 4.7kb, target <10kb after Week 3
- Props system: ~1.5kb
- Slots: ~1kb
- Event bus: ~0.8kb
- Class/style binding: ~1kb
- Computed properties: ~0.5kb
- Component registry: ~0.5kb

**Total estimated**: ~10kb (still very competitive)

**Architecture Focus**:
- Keep compile-time heavy, runtime light
- Generate as much as possible at build time
- Minimal runtime overhead for features
- Tree-shakeable runtime modules
