# NEXPH UI - Week 6 Progress

**Date**: 2026-05-20
**Phase**: Week 6 - PWA, Routing, Fetch & Compression

## Status: Complete ✅

## Completed Features

### nx-fetch — Declarative Async Data Loading ✅
- `nx-fetch="/api/url"` on any element triggers `fetch()` on component init
- `data-nexph-fetch-target="stateProp"` — writes response JSON into state
- `data-nexph-fetch-method="POST"` — supports GET/POST/PUT/DELETE
- `data-nexph-fetch-body="stateProp"` — serializes state prop as JSON body
- Status tracking via `data-nexph-fetch-status`: `loading` → `done` | `error`
- Calls `updateUI()` on success, `console.error` on failure

### Client-Side Router ✅
- `nx-route="/path"` on component wrapper — registers route
- `nx-link="/path"` on anchors/buttons — intercepts clicks, calls `navigate()`
- `nx-outlet` on container — router renders matched component here
- Hash mode (default): `#/path` — no server config needed
- History mode: `--route-mode=history` — uses `pushState`
- Dynamic params: `/user/:id` → `window.NEXPH.router.params.id`
- Active link class: `router-link-active` applied automatically
- 404 fallback rendered inline in outlet
- `RouterGenerator::extractRoutes()` scans compiled HTML for route map
- Router JS injected into bundle only when routes are present

### Gzip Compression ✅
- `--compress` flag on `nexph build`
- Writes `.gz` sidecar next to every output file (HTML, JS, CSS)
- Level 9 compression by default
- `GzipCompressor::ratio()` reports compression percentage
- Compatible with nginx `gzip_static on` and Apache `mod_deflate`

### PWA Support ✅
- `--pwa` flag on `nexph build`
- Generates `manifest.webmanifest` with name, icons, theme color, display mode
- Generates `sw.js` — cache-first service worker (install/activate/fetch)
- Injects `<link rel="manifest">`, `<meta name="theme-color">`, apple meta tags
- Injects SW registration script into `<head>`
- `--pwa-name`, `--pwa-short`, `--pwa-theme` CLI options
- Assets listed in SW cache include JS and CSS bundles

## New Files

| File | Purpose |
|---|---|
| `src/Builder/GzipCompressor.php` | Gzip compression + .gz sidecar writer |
| `src/Builder/PwaGenerator.php` | Web app manifest + service worker generator |
| `src/Builder/RouterGenerator.php` | Client-side router runtime generator |
| `tests/GzipCompressorTest.php` | 9 tests |
| `tests/PwaGeneratorTest.php` | 15 tests |
| `tests/RouterGeneratorTest.php` | 15 tests |
| `tests/BuildPipelineWeek6Test.php` | 11 tests |
| `tests/ConfigLoaderWeek6Test.php` | 12 tests |
| `tests/HtmlGeneratorWeek6Test.php` | 7 tests |
| `tests/JsGeneratorWeek6Test.php` | 11 tests |

## Modified Files

| File | Change |
|---|---|
| `src/Compiler/HtmlGenerator.php` | `nx-fetch`, `nx-route`, `nx-link`, `nx-outlet` directives |
| `src/Compiler/JsGenerator.php` | `nx-fetch` runtime (fetch API, status, target, POST body) |
| `src/Builder/BuildPipeline.php` | compress, PWA, router injection, updated `buildIndexHtml` |
| `src/Builder/BuildCommand.php` | Reports PWA manifest, SW, and GZ output |
| `src/Builder/ConfigLoader.php` | `compress`, `pwa`, `pwaOptions`, `routeMode` defaults + arg parsing |
| `bin/nexph` | Updated help text with Week 6 flags |
| `CHANGELOG.md` | Week 6 entry |

## CLI Reference

```bash
# Gzip compressed build
nexph build src/App.php --compress

# PWA build
nexph build src/App.php --pwa
nexph build src/App.php --pwa --pwa-name="My App" --pwa-theme=#6366f1

# History mode routing
nexph build src/App.php --route-mode=history

# Full production build with all Week 6 features
nexph build src/App.php --production --compress --pwa --route-mode=history

# Dev server (unchanged)
nexph dev src/App.php --port=3000
```

## nx-fetch Usage

```php
public function render(): string
{
    return <<<HTML
<div
    nx-fetch="/api/users"
    data-nexph-fetch-target="users"
></div>
<ul>
    <li nx-for="user in users">{$user.name}</li>
</ul>
HTML;
}
```

```php
// POST with body
<button
    nx-fetch="/api/save"
    data-nexph-fetch-method="POST"
    data-nexph-fetch-body="formData"
    nx-click="submitForm"
>Save</button>
```

## Router Usage

```php
public function render(): string
{
    return <<<HTML
<nav>
    <a nx-link="/">Home</a>
    <a nx-link="/about">About</a>
</nav>
<main nx-outlet></main>

<div nx-route="/" data-nexph-component="Home"></div>
<div nx-route="/about" data-nexph-component="About"></div>
HTML;
}
```

## PWA Output Structure

```
dist/
├── index.html              # PWA meta tags injected
├── app.a1b2c3d4.js
├── app.e5f6g7h8.css
├── manifest.webmanifest    # Web app manifest
├── sw.js                   # Cache-first service worker
├── manifest.json           # Build manifest
├── index.html.gz           # (--compress)
├── app.a1b2c3d4.js.gz      # (--compress)
└── app.e5f6g7h8.css.gz     # (--compress)
```

## Test Results

```
Tests:      276
Assertions: 459
Failures:   0
New tests:  79 (Week 6)
Time:       ~4s
```

## Week 7 Candidates

1. `nx-store` — shared reactive state across components (Pinia-style)
2. `nx-validate` — form validation directive
3. `nx-lazy` — lazy-loaded components (dynamic import)
4. TypeScript type definitions for the runtime API
5. CLI `nexph init` — project scaffolding
6. `nexph build --analyze` — bundle size analyzer
