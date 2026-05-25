# Counter Example

Basic reactive counter component.

## Code

```php
<?php
// examples/Counter.php

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

    public function reset(): void
    {
        $this->count = 0;
    }

    public function render(): string
    {
        return <<<HTML
<div class="counter">
    <h1>{$this->count}</h1>
    <div class="buttons">
        <button nx-click="decrement">-</button>
        <button nx-click="reset">Reset</button>
        <button nx-click="increment">+</button>
    </div>
</div>

<style>
.counter {
    text-align: center;
    padding: 2rem;
}
.counter h1 {
    font-size: 4rem;
    margin: 0;
}
.buttons {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
}
.buttons button {
    padding: 0.5rem 1rem;
    font-size: 1.25rem;
    cursor: pointer;
}
</style>
HTML;
    }
}
```

## Features Demonstrated

- Reactive state (`$count`)
- Event handling (`nx-click`)
- Multiple methods
- Scoped styles

## Run

```bash
nexph dev examples/Counter.php
```
