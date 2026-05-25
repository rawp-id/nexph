# NEXPH UI - Week 8 Progress

**Date**: 2026-05-21
**Phase**: Week 8 - Animations, Portals, DevTools, Test Runner, TypeScript & Multi-page

## Status: Complete ✅

## Completed Features

### nx-animate — CSS Animation Directive ✅
- `nx-animate="fade-in"` — enter animation on mount
- `nx-animate-scroll="slide-up"` — trigger on viewport enter (IntersectionObserver)
- `nx-animate-leave="fade-out"` — leave animation before removal
- `nx-animate-repeat="pulse:2000"` — repeat animation on interval
- 12 built-in animations: `fade-in`, `fade-out`, `slide-up`, `slide-down`, `slide-left`, `slide-right`, `zoom-in`, `zoom-out`, `bounce`, `shake`, `pulse`, `flip`
- Options via colon syntax: `nx-animate="zoom-in:duration=500:easing=ease-out"`
- `window.NEXPH.animate.add(name, frames)` — register custom animations
- `window.NEXPH.animate.run(el, name, opts)` — programmatic trigger
- Runtime injected only when `nx-animate` / `data-nexph-animate` found in HTML

### nx-portal — Render Outside Component Root ✅
- `nx-portal="#modals"` — teleport element to CSS selector target
- `nx-portal` (bare) — teleport to `document.body`
- Inserts placeholder comment at original position
- `window.NEXPH.portal.mount(el)` / `.unmount(el)` — programmatic API
- Runtime injected only when `nx-portal` / `data-nexph-portal` found in HTML

### NEXPH DevTools — Browser Debug Panel ✅
- Toggle with **Alt+D** or click **NEXPH** button (bottom-right)
- **Components tab** — live state inspector, refs, call methods directly
- **Stores tab** — inspect & edit store state inline, trigger actions
- **Events tab** — log all `nexph:*` dispatched events with timestamp
- **Network tab** — intercept all `fetch()` calls, status & timing
- **Build tab** — entry, component list, file sizes, runtime stats
- Resizable panel (drag top border)
- Auto-refresh every 500ms when open
- Injected only in dev mode (`hmrPort > 0`) — zero cost in production

### nexph test — Component Test Runner ✅
- `nexph test [dir]` — scan for `*.test.php` files
- `nexph test tests/components --filter=Counter` — filter by name
- `nexph test --watch` — re-run on file change
- `nexph test --verbose` — show all assertion results
- Built-in assertions: `build_success`, `html_contains`, `html_not_contains`, `js_contains`, `css_contains`, `component_exists`, `has_state`, `has_method`, `js_size_lt`, `css_size_lt`
- Test file format: `nexph_test('name', 'entry.php', ['assertion' => 'value'])`
- Exit code 0 = all pass, 1 = any fail

### TypeScript Definitions — `nexph.d.ts` ✅
- Full type definitions for `window.NEXPH` global
- `NexphComponent`, `NexphStore`, `NexphStoreAPI` interfaces
- `NexphAnimateAPI`, `NexphPortalAPI`, `NexphValidateAPI`, `NexphLazyAPI`
- `NexphAnimationName` union type with all 12 built-in names
- Directive type aliases: `NxClick`, `NxModel`, `NxIf`, `NxFor`, `NxAnimate`, `NxPortal`, etc.
- Optional React JSX augmentation for all `nx-*` attributes

### nexph pages — Multi-page Build ✅
- `nexph pages --pages=src/Home.php,src/About.php` — build multiple entry files
- `nexph pages --output=dist/` — shared output root
- Auto-discovers `src/*.php` or `examples/*.php` when no `--pages` given
- `pages[]` array in `nexph.config.php` — slug → entry mapping
- `App.php` → `dist/index.html`, others → `dist/slug/index.html`
- Writes `pages-manifest.json` with per-page info and total sizes
- Per-page build timing and size reporting

## New Files

| File | Purpose |
|---|---|
| `src/Builder/AnimateGenerator.php` | Animation JS runtime |
| `src/Builder/PortalGenerator.php` | Portal JS runtime |
| `src/Builder/TestCommand.php` | Component test runner CLI |
| `src/Builder/PagesCommand.php` | Multi-page build CLI |
| `src/DevTools/DevToolsGenerator.php` | Browser DevTools panel |
| `nexph.d.ts` | TypeScript type definitions |
| `tests/AnimateGeneratorTest.php` | 17 tests |
| `tests/PortalGeneratorTest.php` | 12 tests |
| `tests/TestCommandTest.php` | 11 tests |
| `tests/PagesCommandTest.php` | 9 tests |
| `tests/HtmlGeneratorWeek8Test.php` | 9 tests |
| `tests/BuildPipelineWeek8Test.php` | 8 tests |

## Modified Files

| File | Change |
|---|---|
| `src/Compiler/HtmlGenerator.php` | `nx-animate`, `nx-animate-scroll`, `nx-animate-leave`, `nx-animate-repeat`, `nx-portal` directives |
| `src/Builder/BuildPipeline.php` | AnimateGenerator, PortalGenerator, DevToolsGenerator injection; devToolsInfo pass-through |
| `src/Builder/StoreGenerator.php` | Expose `window.NEXPH.stores` for DevTools |
| `src/Builder/ConfigLoader.php` | `pages` default |
| `bin/nexph` | `test`, `pages` commands; updated help |
| `CHANGELOG.md` | Week 8 entry |

## CLI Reference

```bash
# animations
nexph build src/App.php   # nx-animate runtime auto-injected when used

# portals
nexph build src/App.php   # nx-portal runtime auto-injected when used

# dev tools (auto in dev mode)
nexph dev src/App.php     # Alt+D in browser to open DevTools

# test runner
nexph test
nexph test tests/components
nexph test tests/components --filter=Counter
nexph test tests/components --verbose --watch

# multi-page
nexph pages --pages=src/Home.php,src/About.php --output=dist/
nexph pages --output=dist/ --production
```

## nx-animate Usage

```php
<!-- enter animation -->
<div nx-animate="fade-in">Hello</div>

<!-- scroll-triggered -->
<section nx-animate-scroll="slide-up:duration=600">Content</section>

<!-- bounce on repeat -->
<button nx-animate-repeat="bounce:3000">Click me</button>

<!-- leave animation -->
<div nx-animate="zoom-in" nx-animate-leave="zoom-out">Modal</div>
```

## nx-portal Usage

```php
<!-- teleport to #modals -->
<div nx-portal="#modals">
    <dialog open>Modal content</dialog>
</div>

<!-- teleport to body -->
<div nx-portal>
    <div class="tooltip">Tooltip</div>
</div>
```

## nexph test Usage

```php
// tests/components/Counter.test.php
nexph_test('counter renders', 'examples/Counter.php', [
    'build_success'   => 'build_success',
    'component_exists' => 'Counter',
    'has_state'       => 'count',
    'has_method'      => 'increment',
]);

nexph_test('counter has html', 'examples/Counter.php', [
    'html_contains' => 'data-nexph-component',
]);
```

## Test Results

```
Tests:      436
Assertions: 652
Failures:   0
New tests:  65 (Week 8)
Time:       ~4s
```

## Week 9 Candidates

1. `nx-teleport` alias for `nx-portal` (Vue-style naming)
2. `nexph build --watch` — watch mode for build command
3. SSR / pre-render support — `nexph export --ssr`
4. Component slots with named slots
5. `nexph upgrade` — self-update CLI command
6. Plugin system — `nexph.config.php` plugins array
