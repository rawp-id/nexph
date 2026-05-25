# Your First Component

## Component Basics

A Nexph component is a PHP class that extends `Component`:

```php
<?php

use Nexph\Component;

class Greeting extends Component
{
    public string $name = 'World';

    public function render(): string
    {
        return <<<HTML
<div class="greeting">
    <h1>Hello, {$this->name}!</h1>
</div>
HTML;
    }
}
```

## Reactive State

Public properties are automatically reactive:

```php
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
<div class="counter">
    <button nx-click="decrement">-</button>
    <span>{$this->count}</span>
    <button nx-click="increment">+</button>
</div>
HTML;
    }
}
```

When `$count` changes, the DOM updates automatically.

## Handling Events

Use `nx-click` to bind click handlers:

```html
<button nx-click="methodName">Click me</button>
```

Other event directives:

```html
<input nx-input="onInput" />
<form nx-submit.prevent="onSubmit">
<input nx-keydown.enter="onEnter" />
```

## Two-Way Binding

Use `nx-model` for input binding:

```php
<?php

use Nexph\Component;

class SearchBox extends Component
{
    public string $query = '';

    public function render(): string
    {
        return <<<HTML
<div class="search">
    <input type="text" nx-model="query" placeholder="Search..." />
    <p>Searching for: {$this->query}</p>
</div>
HTML;
    }
}
```

## Conditionals

Show/hide elements based on state:

```php
public bool $isVisible = true;
public bool $isLoggedIn = false;
```

```html
<!-- Removes from DOM when false -->
<div nx-if="isLoggedIn">Welcome back!</div>

<!-- Toggles display:none -->
<div nx-show="isVisible">I can be hidden</div>
```

## Loops

Render lists with `nx-for`:

```php
public array $items = ['Apple', 'Banana', 'Cherry'];
```

```html
<ul>
    <li nx-for="item in items">{$item}</li>
</ul>
```

With index:

```html
<li nx-for="(item, index) in items">
    {$index}: {$item}
</li>
```

## Build & Run

```bash
# Development with HMR
nexph dev src/Counter.php

# Production build
nexph build src/Counter.php --output=dist/ --production
```

## Next Steps

- [Project Structure](./project-structure.md)
- [Components Guide](../guides/components.md)
- [Directives Reference](../guides/directives.md)
