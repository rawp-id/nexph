# Lazy Loading

Load scripts on-demand when elements enter the viewport.

## Basic Usage

```html
<div nx-lazy="chunks/chart.js">
    <p>Loading chart...</p>
</div>
```

When the element scrolls into view:
1. Script loads asynchronously
2. Content inside executes
3. CSS class updates to reflect status

## CSS Classes

| Class | State |
|-------|-------|
| `nx-lazy-pending` | Waiting to enter viewport |
| `nx-lazy-loaded` | Script loaded successfully |
| `nx-lazy-error` | Script failed to load |

Style loading states:

```css
[nx-lazy].nx-lazy-pending {
    min-height: 200px;
    background: #f3f4f6;
}

[nx-lazy].nx-lazy-loaded {
    animation: fade-in 0.3s;
}

[nx-lazy].nx-lazy-error::after {
    content: 'Failed to load';
    color: #ef4444;
}
```

## Use Cases

### Heavy Components

```html
<!-- Chart library -->
<div nx-lazy="chunks/chart.js" class="chart-container">
    <div class="skeleton">Loading chart...</div>
</div>

<!-- Map widget -->
<div nx-lazy="chunks/map.js" class="map-container">
    <div class="skeleton">Loading map...</div>
</div>

<!-- Video player -->
<div nx-lazy="chunks/video-player.js">
    <img src="thumbnail.jpg" alt="Video thumbnail" />
</div>
```

### Below-the-Fold Content

```html
<header>
    <!-- Always loaded -->
</header>

<main>
    <!-- Always loaded -->
</main>

<section nx-lazy="chunks/testimonials.js">
    <!-- Loads when scrolled to -->
</section>

<section nx-lazy="chunks/pricing.js">
    <!-- Loads when scrolled to -->
</section>

<footer nx-lazy="chunks/footer-widgets.js">
    <!-- Loads when scrolled to -->
</footer>
```

### Third-Party Widgets

```html
<!-- Social feeds -->
<div nx-lazy="https://platform.twitter.com/widgets.js">
    <a href="https://twitter.com/user">Loading tweets...</a>
</div>

<!-- Analytics (non-critical) -->
<div nx-lazy="https://analytics.example.com/tracker.js"></div>
```

## Code Splitting

Structure your build for lazy loading:

```
dist/
├── index.html
├── app.js           # Core bundle
└── chunks/
    ├── chart.js     # Lazy-loaded
    ├── map.js       # Lazy-loaded
    └── editor.js    # Lazy-loaded
```

## Loading Placeholder

Show skeleton or spinner while loading:

```html
<div nx-lazy="chunks/dashboard.js" class="dashboard">
    <div class="skeleton-loader">
        <div class="skeleton-header"></div>
        <div class="skeleton-content"></div>
        <div class="skeleton-footer"></div>
    </div>
</div>
```

```css
.skeleton-loader {
    padding: 1rem;
}

.skeleton-header,
.skeleton-content,
.skeleton-footer {
    background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
    border-radius: 4px;
    margin-bottom: 0.5rem;
}

@keyframes shimmer {
    0% { background-position: 200% 0; }
    100% { background-position: -200% 0; }
}
```

## Error Handling

```html
<div nx-lazy="chunks/widget.js" class="widget">
    <p class="loading-text">Loading widget...</p>
    <p class="error-text" style="display:none">Failed to load widget</p>
</div>
```

```css
.widget.nx-lazy-pending .loading-text { display: block; }
.widget.nx-lazy-pending .error-text { display: none; }

.widget.nx-lazy-loaded .loading-text { display: none; }
.widget.nx-lazy-loaded .error-text { display: none; }

.widget.nx-lazy-error .loading-text { display: none; }
.widget.nx-lazy-error .error-text { display: block; }
```

## Intersection Observer Options

The lazy loader uses `IntersectionObserver` with these defaults:
- `rootMargin: '50px'` — Loads slightly before entering viewport
- `threshold: 0` — Triggers as soon as any part is visible

## Fallback

For browsers without `IntersectionObserver` (rare), scripts load immediately.

## Performance

- Runtime injected only when `nx-lazy` detected
- Uses native `IntersectionObserver` (no polling)
- Scripts cached by browser after first load
- Minimal memory footprint

## Next Steps

- [Build & Deploy](./build.md)
- [Animations](./animations.md)
