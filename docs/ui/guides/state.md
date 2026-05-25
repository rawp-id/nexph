# State Management

Shared reactive state across components using Stores.

## Defining a Store

```php
<?php

use Nexph\Runtime\Store;

Store::define('cart', [
    // State
    'items' => [],
    'total' => 0,
    'coupon' => '',
], [
    // Actions
    'add'    => fn() => null,
    'remove' => fn() => null,
    'clear'  => fn() => null,
]);
```

## Store Directives

### nx-store-bind

One-way binding (display value):

```html
<span nx-store-bind="cart.total"></span>
<p nx-store-bind="user.name"></p>
<div nx-store-bind="settings.theme"></div>
```

### nx-store-model

Two-way binding (input):

```html
<input nx-store-model="cart.coupon" />
<input nx-store-model="user.email" />
<select nx-store-model="settings.language">
    <option value="en">English</option>
    <option value="id">Indonesian</option>
</select>
```

### nx-store-action

Trigger action on click:

```html
<button nx-store-action="cart.add">Add to Cart</button>
<button nx-store-action="cart.clear">Clear Cart</button>
<button nx-store-action="auth.logout">Logout</button>
```

## JavaScript API

```javascript
// Get store instance
const cart = window.NEXPH.store.use('cart');

// Read state
console.log(cart.items);
console.log(cart.total);

// Update state (reactive)
cart.total = 100;
cart.items.push({ id: 1, name: 'Product' });

// Subscribe to changes
window.NEXPH.store.subscribe('cart', (state) => {
    console.log('Cart updated:', state);
});
```

## Example: Shopping Cart

### Store Definition

```php
<?php
// src/Stores/CartStore.php

use Nexph\Runtime\Store;

Store::define('cart', [
    'items' => [],
    'total' => 0,
    'count' => 0,
    'coupon' => '',
    'discount' => 0,
], [
    'add'    => fn() => null,
    'remove' => fn() => null,
    'clear'  => fn() => null,
    'applyCoupon' => fn() => null,
]);
```

### Cart Component

```php
<?php

use Nexph\Component;

class Cart extends Component
{
    public function render(): string
    {
        return <<<HTML
<div class="cart">
    <h2>Shopping Cart (<span nx-store-bind="cart.count"></span>)</h2>
    
    <div class="cart-items">
        <!-- Items rendered via JS -->
    </div>
    
    <div class="cart-summary">
        <p>Subtotal: $<span nx-store-bind="cart.total"></span></p>
        <p>Discount: $<span nx-store-bind="cart.discount"></span></p>
    </div>
    
    <div class="cart-coupon">
        <input 
            type="text" 
            nx-store-model="cart.coupon" 
            placeholder="Coupon code"
        />
        <button nx-store-action="cart.applyCoupon">Apply</button>
    </div>
    
    <button nx-store-action="cart.clear">Clear Cart</button>
</div>
HTML;
    }
}
```

### Product Component

```php
<?php

use Nexph\Component;

class ProductCard extends Component
{
    public int $id = 0;
    public string $name = '';
    public float $price = 0;

    public function addToCart(): void
    {
        $this->emit('add-to-cart', [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
        ]);
    }

    public function render(): string
    {
        return <<<HTML
<div class="product-card">
    <h3>{$this->name}</h3>
    <p>\${$this->price}</p>
    <button nx-click="addToCart">Add to Cart</button>
</div>
HTML;
    }
}
```

## Example: Auth Store

```php
<?php

use Nexph\Runtime\Store;

Store::define('auth', [
    'user' => null,
    'isLoggedIn' => false,
    'token' => '',
], [
    'login'  => fn() => null,
    'logout' => fn() => null,
]);
```

```html
<div nx-if="auth.isLoggedIn">
    <p>Welcome, <span nx-store-bind="auth.user.name"></span></p>
    <button nx-store-action="auth.logout">Logout</button>
</div>

<div nx-if="!auth.isLoggedIn">
    <a nx-link="/login">Login</a>
</div>
```

## Example: Theme Store

```php
<?php

use Nexph\Runtime\Store;

Store::define('theme', [
    'mode' => 'light',
    'primaryColor' => '#3b82f6',
], [
    'toggle' => fn() => null,
    'setColor' => fn() => null,
]);
```

```html
<button nx-store-action="theme.toggle">
    Toggle Dark Mode
</button>

<select nx-store-model="theme.mode">
    <option value="light">Light</option>
    <option value="dark">Dark</option>
    <option value="system">System</option>
</select>
```

## Multiple Stores

Define multiple stores for different concerns:

```php
Store::define('cart', [...], [...]);
Store::define('auth', [...], [...]);
Store::define('theme', [...], [...]);
Store::define('notifications', [...], [...]);
```

Access each independently:

```javascript
const cart = window.NEXPH.store.use('cart');
const auth = window.NEXPH.store.use('auth');
const theme = window.NEXPH.store.use('theme');
```

## Store Persistence

Persist store to localStorage:

```javascript
// Save on change
window.NEXPH.store.subscribe('cart', (state) => {
    localStorage.setItem('cart', JSON.stringify(state));
});

// Restore on load
const saved = localStorage.getItem('cart');
if (saved) {
    const cart = window.NEXPH.store.use('cart');
    Object.assign(cart, JSON.parse(saved));
}
```

## Next Steps

- [Forms & Validation](./forms.md)
- [Build & Deploy](./build.md)
