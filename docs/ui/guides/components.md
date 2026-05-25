# Components

## Defining Components

```php
<?php

use Nexph\Component;

class MyComponent extends Component
{
    // Reactive state
    public string $title = 'Hello';
    public int $count = 0;
    public array $items = [];
    public bool $active = false;

    // Methods
    public function doSomething(): void
    {
        $this->count++;
    }

    // Required: render method
    public function render(): string
    {
        return <<<HTML
<div class="my-component">
    <h1>{$this->title}</h1>
</div>
HTML;
    }
}
```

## Props

Pass data to child components:

```php
<?php

use Nexph\Component;

class Button extends Component
{
    public string $label = 'Click';
    public string $variant = 'primary';
    public bool $disabled = false;

    public function render(): string
    {
        return <<<HTML
<button 
    nx-class="{ 
        'btn': true,
        'btn-primary': variant === 'primary',
        'btn-danger': variant === 'danger',
        'btn-disabled': disabled 
    }"
>
    {$this->label}
</button>
HTML;
    }
}
```

Usage:

```html
<Button label="Save" variant="primary" />
<Button label="Delete" variant="danger" />
<Button label="Disabled" disabled="true" />
```

## Computed Properties

Derived values that cache automatically:

```php
<?php

use Nexph\Component;

class UserCard extends Component
{
    public string $firstName = 'John';
    public string $lastName = 'Doe';

    protected function computed(): array
    {
        return [
            'fullName' => fn() => "{$this->firstName} {$this->lastName}",
            'initials' => fn() => strtoupper(
                substr($this->firstName, 0, 1) . substr($this->lastName, 0, 1)
            ),
        ];
    }

    public function render(): string
    {
        return <<<HTML
<div class="user-card">
    <div class="avatar">{$this->initials}</div>
    <h3>{$this->fullName}</h3>
</div>
HTML;
    }
}
```

## Event Emission

Components can emit events to parents:

```php
<?php

use Nexph\Component;

class ColorPicker extends Component
{
    public string $selected = '#000000';

    public function selectColor(string $color): void
    {
        $this->selected = $color;
        $this->emit('change', ['color' => $color]);
    }

    public function render(): string
    {
        return <<<HTML
<div class="color-picker">
    <button nx-click="selectColor('#ff0000')">Red</button>
    <button nx-click="selectColor('#00ff00')">Green</button>
    <button nx-click="selectColor('#0000ff')">Blue</button>
</div>
HTML;
    }
}
```

Listen in parent:

```html
<ColorPicker @change="onColorChange" />
```

## Slots

Project content into components:

```php
<?php

use Nexph\Component;

class Card extends Component
{
    public string $title = '';

    public function render(): string
    {
        return <<<HTML
<div class="card">
    <div class="card-header">
        <h3>{$this->title}</h3>
    </div>
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
    <p>This content goes into the slot.</p>
    <button>Action</button>
</Card>
```

## Lifecycle

Components compile to vanilla JS with this lifecycle:

1. **Mount** — Component initializes, state binds to DOM
2. **Update** — State changes trigger DOM updates
3. **Unmount** — Cleanup when removed (automatic)

## Scoped Styles

Styles defined in components are automatically scoped:

```php
public function render(): string
{
    return <<<HTML
<style>
    .card {
        padding: 1rem;
        border-radius: 8px;
    }
    .card h3 {
        margin: 0;
    }
</style>
<div class="card">
    <h3>{$this->title}</h3>
</div>
HTML;
}
```

Compiles to scoped CSS that won't leak to other components.

## Component Registration

Components are auto-discovered by class name. Use PascalCase:

```html
<!-- Resolves to Counter.php -->
<Counter />

<!-- Resolves to UserProfile.php -->
<UserProfile firstName="Jane" />

<!-- Resolves to TodoList.php -->
<TodoList items="todos" />
```

## Next Steps

- [Directives](./directives.md)
- [State Management](./state.md)
- [Routing](./routing.md)
