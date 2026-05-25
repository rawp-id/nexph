# Animations

Built-in animations and custom keyframes.

## Basic Usage

### Enter Animation

```html
<div nx-animate="fade-in">Fades in on mount</div>
<div nx-animate="slide-up">Slides up on mount</div>
<div nx-animate="zoom-in">Zooms in on mount</div>
```

### Scroll-Triggered

```html
<section nx-animate-scroll="slide-up">
    Animates when scrolled into view
</section>
```

### Exit Animation

```html
<div nx-animate-leave="fade-out">
    Fades out before removal
</div>
```

### Repeating

```html
<button nx-animate-repeat="pulse:2000">
    Pulses every 2 seconds
</button>
```

## Built-in Animations

| Name | Description |
|------|-------------|
| `fade-in` | Opacity 0 → 1 |
| `fade-out` | Opacity 1 → 0 |
| `slide-up` | Translate from below |
| `slide-down` | Translate from above |
| `slide-left` | Translate from right |
| `slide-right` | Translate from left |
| `zoom-in` | Scale 0.5 → 1 |
| `zoom-out` | Scale 1 → 0.5 |
| `bounce` | Bouncing effect |
| `shake` | Horizontal shake |
| `pulse` | Scale pulse |
| `flip` | 3D flip |

## Options

Customize with colon syntax:

```html
<!-- Duration in ms -->
<div nx-animate="fade-in:duration=500">

<!-- Easing function -->
<div nx-animate="slide-up:easing=ease-out">

<!-- Combined -->
<div nx-animate="zoom-in:duration=300:easing=ease-in-out">
```

Available easings:
- `linear`
- `ease`
- `ease-in`
- `ease-out`
- `ease-in-out`
- `cubic-bezier(...)` (custom)

## Custom Animations

Register custom keyframes via JS:

```javascript
window.NEXPH.animate.add('swing', [
    { transform: 'rotate(0deg)' },
    { transform: 'rotate(15deg)' },
    { transform: 'rotate(-10deg)' },
    { transform: 'rotate(5deg)' },
    { transform: 'rotate(0deg)' },
]);

window.NEXPH.animate.add('flash', [
    { opacity: 1 },
    { opacity: 0 },
    { opacity: 1 },
    { opacity: 0 },
    { opacity: 1 },
]);
```

Then use:

```html
<div nx-animate="swing">Swings on mount</div>
<div nx-animate-scroll="flash">Flashes on scroll</div>
```

## Programmatic API

```javascript
const el = document.querySelector('.my-element');

// Run animation
window.NEXPH.animate.run(el, 'bounce', {
    duration: 500,
    easing: 'ease-out'
});

// Chain animations
await window.NEXPH.animate.run(el, 'fade-in');
await window.NEXPH.animate.run(el, 'pulse');
```

## Examples

### Hero Section

```html
<section class="hero">
    <h1 nx-animate="fade-in:duration=800">Welcome</h1>
    <p nx-animate="slide-up:duration=600">Build reactive UIs with PHP</p>
    <button nx-animate="zoom-in:duration=400">Get Started</button>
</section>
```

### Scroll Reveal

```html
<section nx-animate-scroll="slide-up">
    <h2>Features</h2>
</section>

<section nx-animate-scroll="slide-left">
    <h2>Pricing</h2>
</section>

<section nx-animate-scroll="fade-in:duration=1000">
    <h2>Testimonials</h2>
</section>
```

### Notification

```html
<div class="notification" nx-animate="slide-right" nx-animate-leave="fade-out">
    <p>Item added to cart!</p>
    <button nx-click="dismiss">×</button>
</div>
```

### Attention Grabber

```html
<button 
    class="cta" 
    nx-animate-repeat="pulse:3000"
>
    Limited Offer!
</button>
```

### Loading Spinner

```javascript
// Custom spinner animation
window.NEXPH.animate.add('spin', [
    { transform: 'rotate(0deg)' },
    { transform: 'rotate(360deg)' },
]);
```

```html
<div class="spinner" nx-animate-repeat="spin:1000"></div>
```

## Performance

- Animations use CSS `@keyframes` under the hood
- Hardware-accelerated (transform, opacity)
- Runtime injected only when `nx-animate*` detected (~4KB)
- No animation code in production if unused

## Next Steps

- [Portals](./portals.md)
- [Forms & Validation](./forms.md)
