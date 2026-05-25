# NEXPH UI - Week 5 Progress

**Date**: 2026-05-20
**Phase**: Week 5 - Hot Reload, DX & Runtime Polish

## Status: Complete ✅

## Completed Features

### Hot Reload (HMR) ✅
- [x] `HotReloadServer` — pure PHP WebSocket server (RFC 6455), no dependencies
- [x] `HotReloadLive` — watcher + rebuild + broadcast loop
- [x] `nexph dev` command — forks HTTP server, runs HMR + watcher in parent
- [x] HMR client script injected into HTML when `hmrPort > 0`
- [x] Auto-reconnect with exponential backoff on disconnect
- [x] `reload` message triggers `window.location.reload()`
- [x] `error` message shows in-browser error overlay

### Error Overlay ✅
- [x] Build errors pushed via WebSocket to browser
- [x] Overlay renders at top of page with error message
- [x] Dismissable via ✕ button
- [x] Auto-clears on next successful reload

### CSS Scoping — @media / Nested Rules ✅
- [x] `StyleScoper::scopeBlock()` — recursive brace-balanced parser
- [x] `@media`, `@supports` — inner rules scoped recursively
- [x] `@keyframes` — left unscoped (animation names are global)
- [x] Descendant selector pattern: `[data-nx-scope="..."] .selector`
- [x] `body`, `html`, `:root` excluded from scoping

### nx-ref ✅
- [x] `nx-ref="name"` in templates → `data-nexph-ref="name"`
- [x] Runtime collects refs into `refs` object on init
- [x] Exposed on `window.NEXPH.components[name].refs`

### nx-transition ✅
- [x] `nx-transition="name"` → CSS class-based enter/leave transitions
- [x] Enter: `name-enter` → `name-enter-active` on next frame
- [x] Leave: `name-leave` → `name-leave-active` on next frame
- [x] Classes cleaned up on `transitionend`
- [x] Re-evaluated on every `updateUI()`

### PHP → JS Builtins ✅
- [x] `uniqid()` → `Date.now().toString(36) + Math.random()...`
- [x] `time()` → `Math.floor(Date.now()/1000)`
- [x] `strtolower()`, `strtoupper()`, `trim()` → JS equivalents

### evalCondition / updateClasses Fix ✅
- [x] `with(state){return (expr)}` — no regex mangling of string literals
- [x] Loop item classes resolved at render time with item context
- [x] `data-nexph-class` removed from loop clones after resolution

### Tailwind CDN ✅
- [x] `--tailwind` flag injects `@tailwindcss/browser@4` CDN script
- [x] Available on `nexph build` and `nexph dev`

## New Files (Week 5)

| File | Purpose |
|---|---|
| `src/DevServer/HotReloadServer.php` | WebSocket server (RFC 6455) |
| `src/DevServer/HotReloadLive.php` | Watcher + HMR broadcast loop |

## Modified Files (Week 5)

| File | Change |
|---|---|
| `src/Builder/BuildPipeline.php` | HMR client injection, `hmrPort` config |
| `src/Builder/ConfigLoader.php` | `tailwind`, `hmrPort` defaults |
| `src/Compiler/StyleScoper.php` | Recursive at-rule scoping |
| `src/Compiler/HtmlGenerator.php` | `nx-ref`, `nx-transition` directives |
| `src/Compiler/JsGenerator.php` | refs, transitions, `with(state)` eval, PHP builtins |
| `bin/nexph` | `nexph dev` command, updated help |
| `tests/StyleScoperTest.php` | Updated assertions for descendant selector pattern |

## CLI Reference

```bash
# Dev server with hot reload
nexph dev src/App.php

# Dev server with Tailwind CDN
nexph dev src/App.php --tailwind

# Custom ports
nexph dev src/App.php --port=3000 --hmr-port=35729

# Standard build
nexph build src/App.php --output=dist/

# Build with Tailwind
nexph build src/App.php --output=dist/ --tailwind

# Production build
nexph build src/App.php --output=dist/ --production
```

## nx-ref Usage

```php
public function render(): string
{
    return <<<HTML
<input nx-ref="input" type="text" />
<button nx-click="focusInput">Focus</button>
HTML;
}
```

```js
// accessible via
window.NEXPH.components['MyComponent'].refs.input.focus();
```

## nx-transition Usage

```php
<div nx-transition="fade" nx-show="visible">Hello</div>
```

```css
.fade-enter { opacity: 0; }
.fade-enter-active { transition: opacity 0.3s; opacity: 1; }
.fade-leave { opacity: 1; }
.fade-leave-active { transition: opacity 0.3s; opacity: 0; }
```

## Test Results

```
Tests: 197
Assertions: 334
Failures: 0
Time: ~4s
```

## Week 6 Candidates

1. Component props passing (`<Card :title="My Title" />`)
2. Slots (`nx-slot`, `nx-slot-content`)
3. Async data fetching (`nx-fetch`)
4. Code splitting per route
5. Gzip output (`--compress`)
6. PWA manifest injection (`--pwa`)
