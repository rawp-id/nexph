# Runtime API

JavaScript APIs available via `window.NEXPH`.

## Store API

### NEXPH.store.use(name)

Get store instance:

```javascript
const cart = window.NEXPH.store.use('cart');

// Read state
console.log(cart.items);
console.log(cart.total);

// Update state (reactive)
cart.total = 100;
cart.items.push({ id: 1, name: 'Product' });
```

### NEXPH.store.subscribe(name, callback)

Subscribe to store changes:

```javascript
window.NEXPH.store.subscribe('cart', (state) => {
    console.log('Cart updated:', state);
    localStorage.setItem('cart', JSON.stringify(state));
});
```

---

## Router API

### NEXPH.router.push(path)

Navigate to route:

```javascript
window.NEXPH.router.push('/about');
window.NEXPH.router.push('/users/123');
```

### NEXPH.router.back()

Go back in history:

```javascript
window.NEXPH.router.back();
```

### NEXPH.router.current

Get current route path:

```javascript
console.log(window.NEXPH.router.current); // '/about'
```

### NEXPH.router.params

Get route parameters:

```javascript
// URL: /users/123
console.log(window.NEXPH.router.params); // { id: '123' }

// URL: /posts/hello-world
console.log(window.NEXPH.router.params); // { slug: 'hello-world' }
```

---

## Animation API

### NEXPH.animate.run(element, name, options)

Run animation programmatically:

```javascript
const el = document.querySelector('.my-element');

// Basic
window.NEXPH.animate.run(el, 'fade-in');

// With options
window.NEXPH.animate.run(el, 'slide-up', {
    duration: 500,
    easing: 'ease-out'
});

// Async/await
await window.NEXPH.animate.run(el, 'bounce');
console.log('Animation complete');
```

**Options:**

| Option | Type | Default |
|--------|------|---------|
| `duration` | number | `300` |
| `easing` | string | `'ease'` |

### NEXPH.animate.add(name, keyframes)

Register custom animation:

```javascript
window.NEXPH.animate.add('swing', [
    { transform: 'rotate(0deg)' },
    { transform: 'rotate(15deg)' },
    { transform: 'rotate(-10deg)' },
    { transform: 'rotate(5deg)' },
    { transform: 'rotate(0deg)' },
]);

// Now usable
window.NEXPH.animate.run(el, 'swing');
```

---

## Portal API

### NEXPH.portal.mount(element)

Mount element to portal target:

```javascript
const modal = document.querySelector('.modal');
window.NEXPH.portal.mount(modal);
```

### NEXPH.portal.unmount(element)

Unmount element from portal:

```javascript
window.NEXPH.portal.unmount(modal);
```

---

## Validation API

### NEXPH.validate.field(element)

Validate single field:

```javascript
const input = document.querySelector('input[name="email"]');
const isValid = window.NEXPH.validate.field(input);

if (!isValid) {
    console.log('Email is invalid');
}
```

### NEXPH.validate.form(form)

Validate entire form:

```javascript
const form = document.querySelector('form');
const isValid = window.NEXPH.validate.form(form);

if (isValid) {
    form.submit();
}
```

---

## Lazy API

### NEXPH.lazy.load(element)

Manually trigger lazy load:

```javascript
const el = document.querySelector('[nx-lazy]');
window.NEXPH.lazy.load(el);
```

---

## Event Bus

### NEXPH.events.on(event, callback)

Listen for component events:

```javascript
window.NEXPH.events.on('cart:updated', (data) => {
    console.log('Cart updated:', data);
});
```

### NEXPH.events.emit(event, data)

Emit custom event:

```javascript
window.NEXPH.events.emit('cart:updated', { total: 100 });
```

### NEXPH.events.off(event, callback)

Remove event listener:

```javascript
const handler = (data) => console.log(data);
window.NEXPH.events.on('cart:updated', handler);
window.NEXPH.events.off('cart:updated', handler);
```

---

## Component Registry

### NEXPH.components.get(id)

Get component instance by ID:

```javascript
const counter = window.NEXPH.components.get('counter-1');
console.log(counter.state.count);
```

### NEXPH.components.all()

Get all component instances:

```javascript
const all = window.NEXPH.components.all();
console.log(all);
```

---

## DevTools

Available in development mode only:

```javascript
// Check if DevTools available
if (window.NEXPH.devtools) {
    // Open DevTools
    window.NEXPH.devtools.open();
    
    // Close DevTools
    window.NEXPH.devtools.close();
    
    // Toggle DevTools
    window.NEXPH.devtools.toggle();
}
```

Keyboard shortcut: **Alt+D**
