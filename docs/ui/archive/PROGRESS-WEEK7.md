# NEXPH UI - Week 7 Progress

**Date**: 2026-05-20
**Phase**: Week 7 - Store, Validation, Lazy Loading, Scaffolding & Bundle Analysis

## Status: Complete ✅

## Completed Features

### nx-store — Shared Reactive State ✅
- `Store::define('name', $state, $actions)` — PHP-side store registration
- `StoreGenerator::generate($stores)` — emits JS Proxy-based reactive store
- `nx-store-bind="store.prop"` — one-way binding to DOM text
- `nx-store-model="store.prop"` — two-way input binding
- `nx-store-action="store.action"` — click triggers store action
- `subscribe(name, cb)` — reactive subscription API
- `window.NEXPH.store.use('name')` — access store from JS
- Store runtime injected only when store bindings detected in HTML

### nx-validate — Declarative Form Validation ✅
- `nx-validate="required|email|min:8"` — pipe-separated rules on inputs
- `nx-validate-form` on `<form>` — blocks submit if invalid
- `nx-error="fieldId"` — renders first error message
- Built-in rules: `required`, `email`, `min`, `max`, `minval`, `maxval`, `numeric`, `alpha`, `alphanumeric`, `url`, `pattern`, `confirmed`
- Live validation on `input` and `blur` events
- CSS classes: `nx-valid`, `nx-invalid` applied automatically
- `window.NEXPH.validate.field(el)` / `.form(form)` — programmatic API
- Validator runtime injected only when `data-nexph-validate` found in HTML

### nx-lazy — Lazy Component Loading ✅
- `nx-lazy="chunks/Chart.js"` — defers script load until element enters viewport
- Uses `IntersectionObserver` with 100px root margin
- Fallback: immediate load when `IntersectionObserver` unavailable
- Status tracking: `data-nexph-lazy-status`: `loading` → `loaded` | `error`
- CSS classes: `nx-lazy-pending`, `nx-lazy-loaded`, `nx-lazy-error`
- `window.NEXPH.lazy.load(el)` — programmatic trigger
- Lazy runtime injected only when `data-nexph-lazy` found in HTML

### nexph init — Project Scaffolding ✅
- `nexph init my-app` — creates full project structure
- `nexph init my-app --minimal` — skips TodoApp example
- `nexph init my-app --force` — overwrites existing directory
- Generates: `examples/App.php`, `examples/Counter.php`, `examples/TodoApp.php`
- Generates: `nexph.config.php`, `.gitignore`, `src/`, `dist/` dirs
- Config file returns valid PHP array with sensible defaults

### nexph build --analyze — Bundle Size Analyzer ✅
- `--analyze` flag on `nexph build`
- Prints per-component breakdown: JS / CSS / HTML / Total
- Sorted by total size descending
- Writes `bundle-report.json` to output dir
- `BundleAnalyzer::render()` — ASCII table output
- `BundleAnalyzer::writeJson()` — machine-readable report

## New Files

| File | Purpose |
|---|---|
| `src/Runtime/Store.php` | PHP-side store registry |
| `src/Builder/StoreGenerator.php` | Reactive store JS runtime |
| `src/Builder/ValidatorGenerator.php` | Form validation JS runtime |
| `src/Builder/LazyLoader.php` | Lazy component loading JS runtime |
| `src/Builder/BundleAnalyzer.php` | Bundle size analysis + report |
| `src/Builder/InitCommand.php` | Project scaffolding CLI command |
| `tests/StoreTest.php` | 9 tests |
| `tests/StoreGeneratorTest.php` | 15 tests |
| `tests/ValidatorGeneratorTest.php` | 17 tests |
| `tests/LazyLoaderTest.php` | 12 tests |
| `tests/BundleAnalyzerTest.php` | 14 tests |
| `tests/InitCommandTest.php` | 17 tests |
| `tests/ConfigLoaderWeek7Test.php` | 4 tests |
| `tests/HtmlGeneratorWeek7Test.php` | 8 tests |

## Modified Files

| File | Change |
|---|---|
| `src/Compiler/HtmlGenerator.php` | `nx-lazy`, `nx-validate`, `nx-validate-form`, `nx-error`, `nx-store-*` directives |
| `src/Builder/BuildPipeline.php` | store/validate/lazy/analyze injection, `--analyze` flag |
| `src/Builder/ConfigLoader.php` | `analyze`, `stores` defaults + arg parsing |
| `bin/nexph` | `init` command, `--analyze` flag, updated help |
| `CHANGELOG.md` | Week 7 entry |

## CLI Reference

```bash
# Scaffold new project
nexph init my-app
nexph init my-app --minimal
nexph init . --force

# Bundle analysis
nexph build src/App.php --analyze
nexph build src/App.php --production --analyze

# Full production build
nexph build src/App.php --production --compress --pwa --analyze
```

## nx-store Usage

```php
// PHP: define store
Store::define('cart', ['items' => [], 'total' => 0], [
    'clear' => fn() => null,
]);

// Template
<span nx-store-bind="cart.total"></span>
<input nx-store-model="cart.coupon" />
<button nx-store-action="cart.clear">Clear</button>
```

## nx-validate Usage

```php
<form nx-validate-form nx-submit.prevent="save">
    <input
        name="email"
        nx-validate="required|email"
        data-nexph-validate-id="email"
    />
    <span nx-error="email"></span>

    <input
        name="password"
        nx-validate="required|min:8"
        data-nexph-validate-id="password"
    />
    <span nx-error="password"></span>

    <button type="submit">Submit</button>
</form>
```

## nx-lazy Usage

```php
<div nx-lazy="assets/chunks/Chart.js">
    <p>Loading chart...</p>
</div>
```

## Bundle Analysis Output

```
  Bundle Analysis
  ────────────────────────────────────────────────────
  Component                      JS      CSS    Total
  ────────────────────────────────────────────────────
  App                       13.4 KB  11.4 KB    31 KB
  ────────────────────────────────────────────────────
  TOTAL                     13.4 KB  11.4 KB    31 KB
```

## Test Results

```
Tests:      371
Assertions: 577
Failures:   0
New tests:  95 (Week 7)
Time:       ~4s
```

## Week 8 Candidates

1. `nx-animate` — CSS animation directive with enter/leave sequences
2. `nx-portal` — render content outside component root (modals, tooltips)
3. `nexph test` — built-in component test runner
4. TypeScript type definitions for runtime API (`nexph.d.ts`)
5. `nexph build --target=es2015` — transpilation target support
6. Multi-page build (`nexph build --pages`)
