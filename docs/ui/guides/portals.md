# Portals

Render elements outside their component's DOM hierarchy.

## Basic Usage

```html
<!-- Teleport to #modals container -->
<div nx-portal="#modals">
    <dialog open>
        <h2>Modal Title</h2>
        <p>Modal content here</p>
    </dialog>
</div>

<!-- Teleport to body (default) -->
<div nx-portal>
    <div class="tooltip">Tooltip content</div>
</div>
```

## How It Works

1. Element renders in component template
2. Portal moves it to target container
3. Placeholder comment marks original position
4. Reactivity still works normally

## Use Cases

### Modals

```php
<?php

use Nexph\Component;

class ConfirmDialog extends Component
{
    public bool $open = false;
    public string $message = 'Are you sure?';

    public function show(): void
    {
        $this->open = true;
    }

    public function confirm(): void
    {
        $this->emit('confirm');
        $this->open = false;
    }

    public function cancel(): void
    {
        $this->emit('cancel');
        $this->open = false;
    }

    public function render(): string
    {
        return <<<HTML
<div nx-portal="#modals" nx-if="open">
    <div class="modal-backdrop" nx-click="cancel"></div>
    <div class="modal">
        <p>{$this->message}</p>
        <button nx-click="confirm">Yes</button>
        <button nx-click="cancel">No</button>
    </div>
</div>
HTML;
    }
}
```

### Tooltips

```php
<?php

use Nexph\Component;

class Tooltip extends Component
{
    public string $text = '';
    public bool $visible = false;

    public function show(): void
    {
        $this->visible = true;
    }

    public function hide(): void
    {
        $this->visible = false;
    }

    public function render(): string
    {
        return <<<HTML
<span 
    class="tooltip-trigger"
    nx-mouseenter="show"
    nx-mouseleave="hide"
>
    <slot />
</span>

<div nx-portal nx-show="visible" class="tooltip">
    {$this->text}
</div>
HTML;
    }
}
```

### Dropdown Menus

```html
<div class="dropdown">
    <button nx-click="toggle">Menu</button>
    
    <div nx-portal nx-if="open" class="dropdown-menu">
        <a href="#">Option 1</a>
        <a href="#">Option 2</a>
        <a href="#">Option 3</a>
    </div>
</div>
```

### Toast Notifications

```php
<?php

use Nexph\Component;

class Toast extends Component
{
    public string $message = '';
    public string $type = 'info';
    public bool $visible = false;

    public function show(string $message, string $type = 'info'): void
    {
        $this->message = $message;
        $this->type = $type;
        $this->visible = true;
    }

    public function dismiss(): void
    {
        $this->visible = false;
    }

    public function render(): string
    {
        return <<<HTML
<div 
    nx-portal="#toasts" 
    nx-if="visible"
    nx-class="{ 'toast': true, 'toast-success': type === 'success', 'toast-error': type === 'error' }"
    nx-animate="slide-right"
    nx-animate-leave="fade-out"
>
    <p>{$this->message}</p>
    <button nx-click="dismiss">×</button>
</div>
HTML;
    }
}
```

## Portal Containers

Add containers to your layout:

```html
<!DOCTYPE html>
<html>
<head>...</head>
<body>
    <div id="app">
        <!-- Your app -->
    </div>
    
    <!-- Portal containers -->
    <div id="modals"></div>
    <div id="toasts"></div>
    <div id="tooltips"></div>
</body>
</html>
```

## Programmatic API

```javascript
// Mount element to portal
window.NEXPH.portal.mount(element);

// Unmount from portal
window.NEXPH.portal.unmount(element);
```

## Styling

Portal elements exist outside component scope, so use global styles:

```css
/* Global modal styles */
#modals .modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 100;
}

#modals .modal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 2rem;
    border-radius: 8px;
    z-index: 101;
}

/* Global toast styles */
#toasts {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 200;
}

#toasts .toast {
    padding: 1rem;
    margin-bottom: 0.5rem;
    border-radius: 4px;
    background: #333;
    color: white;
}
```

## Performance

- Portal runtime injected only when `nx-portal` detected (~1KB)
- No overhead if portals not used
- Cleanup automatic on component unmount

## Next Steps

- [Animations](./animations.md)
- [Lazy Loading](./lazy-loading.md)
