# Directives

Complete reference for all `nx-*` directives.

## Events

### nx-click

Bind click handler:

```html
<button nx-click="handleClick">Click</button>
<button nx-click="increment">+1</button>
```

### nx-input

Bind input event:

```html
<input nx-input="onInput" />
<textarea nx-input="onTextChange"></textarea>
```

### nx-submit

Bind form submission:

```html
<form nx-submit="onSubmit">...</form>
<form nx-submit.prevent="onSubmit">...</form>
```

### nx-keydown / nx-keyup

Bind keyboard events:

```html
<input nx-keydown="onKeyDown" />
<input nx-keydown.enter="onEnter" />
<input nx-keyup.escape="onEscape" />
```

### Event Modifiers

| Modifier | Description |
|----------|-------------|
| `.prevent` | Calls `event.preventDefault()` |
| `.enter` | Only fires on Enter key |
| `.escape` | Only fires on Escape key |

```html
<form nx-submit.prevent="save">
<input nx-keydown.enter="submit" />
```

---

## Data Binding

### nx-model

Two-way binding for inputs:

```html
<input type="text" nx-model="username" />
<input type="checkbox" nx-model="agreed" />
<textarea nx-model="message"></textarea>
<select nx-model="country">
    <option value="us">USA</option>
    <option value="uk">UK</option>
</select>
```

---

## Conditionals

### nx-if

Conditionally render (removes from DOM):

```html
<div nx-if="isLoggedIn">Welcome!</div>
<div nx-if="items.length > 0">Has items</div>
<div nx-if="!loading">Content loaded</div>
```

### nx-show

Toggle visibility (display: none):

```html
<div nx-show="isVisible">Toggleable</div>
<div nx-show="count > 0">Count: {$this->count}</div>
```

**Difference:**
- `nx-if` — Removes element from DOM entirely
- `nx-show` — Keeps element, toggles `display: none`

---

## Loops

### nx-for

Iterate over arrays:

```html
<!-- Simple -->
<li nx-for="item in items">{$item}</li>

<!-- With index -->
<li nx-for="(item, index) in items">
    {$index}: {$item}
</li>

<!-- Object properties -->
<li nx-for="todo in todos">
    {$todo.text} - {$todo.done}
</li>
```

---

## Dynamic Attributes

### nx-class

Conditional CSS classes:

```html
<div nx-class="{ 'active': isActive }">
<div nx-class="{ 'btn': true, 'btn-primary': isPrimary, 'disabled': !enabled }">
<li nx-class="{ 'completed': todo.done, 'pending': !todo.done }">
```

### nx-style

Dynamic inline styles:

```html
<div nx-style="{ 'color': textColor }">
<div nx-style="{ 'width': progress + '%', 'background': bgColor }">
<div nx-style="{ 
    'transform': 'rotate(' + angle + 'deg)',
    'opacity': isVisible ? 1 : 0 
}">
```

---

## Routing

### nx-route

Declare a route view:

```html
<div nx-route="/">Home content</div>
<div nx-route="/about">About content</div>
<div nx-route="/users/:id">User profile</div>
```

### nx-link

Client-side navigation:

```html
<a nx-link="/">Home</a>
<a nx-link="/about">About</a>
<a nx-link="/users/123">User 123</a>
```

### nx-outlet

Router outlet (renders matched route):

```html
<main nx-outlet></main>
```

---

## Store Bindings

### nx-store-bind

One-way bind store value to text:

```html
<span nx-store-bind="cart.total"></span>
<p nx-store-bind="user.name"></p>
```

### nx-store-model

Two-way bind store value to input:

```html
<input nx-store-model="cart.coupon" />
<input nx-store-model="settings.theme" />
```

### nx-store-action

Trigger store action on click:

```html
<button nx-store-action="cart.clear">Clear Cart</button>
<button nx-store-action="auth.logout">Logout</button>
```

---

## Form Validation

### nx-validate

Declare validation rules:

```html
<input nx-validate="required" />
<input nx-validate="required|email" />
<input nx-validate="required|min:3|max:20" />
<input nx-validate="numeric|minval:0|maxval:100" />
```

**Built-in rules:**

| Rule | Description |
|------|-------------|
| `required` | Field must have value |
| `email` | Valid email format |
| `min:n` | Minimum length |
| `max:n` | Maximum length |
| `minval:n` | Minimum numeric value |
| `maxval:n` | Maximum numeric value |
| `numeric` | Numbers only |
| `alpha` | Letters only |
| `alphanumeric` | Letters and numbers |
| `url` | Valid URL format |
| `pattern:regex` | Custom regex |
| `confirmed` | Matches confirmation field |

### nx-validate-form

Block form submit when invalid:

```html
<form nx-validate-form nx-submit.prevent="save">
    <input nx-validate="required|email" data-nexph-validate-id="email" />
    <button type="submit">Save</button>
</form>
```

### nx-error

Display validation error:

```html
<input nx-validate="required|email" data-nexph-validate-id="email" />
<span nx-error="email"></span>
```

---

## Animations

### nx-animate

Enter animation on mount:

```html
<div nx-animate="fade-in">Fades in</div>
<div nx-animate="slide-up">Slides up</div>
<div nx-animate="zoom-in:duration=500">Custom duration</div>
```

### nx-animate-scroll

Trigger on scroll into view:

```html
<section nx-animate-scroll="slide-up">
<div nx-animate-scroll="fade-in:duration=800">
```

### nx-animate-leave

Exit animation before removal:

```html
<div nx-animate-leave="fade-out">
```

### nx-animate-repeat

Repeat animation on interval:

```html
<button nx-animate-repeat="pulse:2000">Every 2s</button>
```

**Built-in animations:**

`fade-in`, `fade-out`, `slide-up`, `slide-down`, `slide-left`, `slide-right`, `zoom-in`, `zoom-out`, `bounce`, `shake`, `pulse`, `flip`

**Options:**

```html
nx-animate="name:duration=500:easing=ease-out"
```

---

## Portals

### nx-portal

Teleport element to another DOM location:

```html
<!-- Teleport to #modals -->
<div nx-portal="#modals">
    <dialog>Modal content</dialog>
</div>

<!-- Teleport to body (default) -->
<div nx-portal>
    <div class="tooltip">Tooltip</div>
</div>
```

---

## Lazy Loading

### nx-lazy

Load script when element enters viewport:

```html
<div nx-lazy="chunks/chart.js">
    <p>Loading chart...</p>
</div>
```

CSS classes applied:
- `nx-lazy-pending` — Waiting to load
- `nx-lazy-loaded` — Successfully loaded
- `nx-lazy-error` — Load failed

---

## Async Data

### nx-fetch

Declarative data fetching:

```html
<div nx-fetch="/api/users" nx-fetch-as="users">
    <ul>
        <li nx-for="user in users">{$user.name}</li>
    </ul>
</div>
```

With loading/error states:

```html
<div nx-fetch="/api/data" nx-fetch-as="data">
    <div nx-if="$loading">Loading...</div>
    <div nx-if="$error">Error: {$error}</div>
    <div nx-if="data">...</div>
</div>
```

## Next Steps

- [Components](./components.md)
- [Routing](./routing.md)
- [State Management](./state.md)
