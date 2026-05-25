# NEXPH UI - Week 4 Progress

**Date**: 2026-05-20
**Phase**: Week 4 - Build System

## Status: Complete ✅

## Completed Features

### Build System Core ✅
- [x] `BuildPipeline` — compile → bundle → hash → sourcemap → write
- [x] `BuildCommand` — CLI handler with flags, output, error reporting
- [x] `ExportCommand` — static export CLI handler
- [x] `DependencyResolver` — topological file resolution, circular dep protection
- [x] `ManifestGenerator` — JSON manifest with sizes, hashes, component list
- [x] `ConfigLoader` — nexph.config.php + CLI arg merging

### Asset Optimization ✅
- [x] `HtmlMinifier` — comment removal, whitespace collapse, IE conditional preservation
- [x] `CssMinifier` — comment removal, whitespace collapse, trailing semicolon removal
- [x] `JsMinifier` — comment removal, whitespace collapse, operator spacing
- [x] `AssetHasher` — 8-char MD5 content hash, filename insertion

### CSS Scoping ✅
- [x] `StyleExtractor` — extracts CSS from component `style()` methods
- [x] `StyleScoper` — deterministic scope IDs, selector prefixing, body/html exclusion
- [x] `Compiler` — wires scoping into compile pipeline
- [x] `HtmlGenerator` — stamps `data-nx-scope` on component root element

### Static Export ✅
- [x] `StaticExporter` — single self-contained HTML file, inline CSS + JS
- [x] `--no-js` flag for pure static HTML (no runtime)
- [x] Minification support

### Source Maps ✅
- [x] `SourceMapGenerator` — inline base64 data URL or separate `.map` file
- [x] `--sourcemap` flag wired into `BuildPipeline`
- [x] Map file included in manifest assets

### Dev Server & Watch Mode ✅
- [x] `Watcher` — polls .php files for mtime changes, recursive directory support
- [x] `LiveReload` — rebuilds on change, error reporting
- [x] `bin/nexph serve --watch` — forks server + watcher

### CLI Binary ✅
- [x] `nexph build <entry> [--output=] [--minify] [--production] [--sourcemap]`
- [x] `nexph export <entry> [--output=] [--minify] [--no-js]`
- [x] `nexph serve <entry> [--port=] [--watch]`
- [x] Registered in `composer.json` bin

## New Classes (Week 4)

| Class | Location | Purpose |
|---|---|---|
| BuildPipeline | src/Builder/BuildPipeline.php | Orchestrates full build |
| BuildCommand | src/Builder/BuildCommand.php | `nexph build` CLI |
| ExportCommand | src/Builder/ExportCommand.php | `nexph export` CLI |
| DependencyResolver | src/Builder/DependencyResolver.php | Component dep tree |
| ManifestGenerator | src/Builder/ManifestGenerator.php | Build manifest |
| ConfigLoader | src/Builder/ConfigLoader.php | Config file + CLI args |
| HtmlMinifier | src/Builder/HtmlMinifier.php | HTML minification |
| CssMinifier | src/Builder/CssMinifier.php | CSS minification |
| JsMinifier | src/Builder/JsMinifier.php | JS minification |
| AssetHasher | src/Builder/AssetHasher.php | Content hashing |
| StaticExporter | src/Builder/StaticExporter.php | Inline single-file export |
| SourceMapGenerator | src/Builder/SourceMapGenerator.php | JS source maps |
| StyleExtractor | src/Compiler/StyleExtractor.php | Extract component styles |
| StyleScoper | src/Compiler/StyleScoper.php | Scope CSS selectors |
| Watcher | src/DevServer/Watcher.php | File system watcher |
| LiveReload | src/DevServer/LiveReload.php | Rebuild on change |

## Build Output Examples

### Standard build
```
dist/
├── index.html              495 B
├── app.fa831af7.js         8.2 KB
├── app.24c0628a.css        870 B
└── manifest.json           393 B
```

### Production build (--production)
```
dist/
├── index.html              458 B
├── app.fa831af7.js         5.3 KB  (minified)
├── app.db251fa9.css        658 B   (minified)
└── manifest.json           357 B
Total: 6.4 KB
```

### Build with source maps (--sourcemap)
```
dist/
├── index.html              495 B
├── app.03da62d0.js         8.3 KB  (+ sourceMappingURL)
├── app.24c0628a.css        870 B
├── app.js.map              1.7 KB
└── manifest.json           393 B
```

### Static export (nexph export)
```
dist/
├── index.html              9.5 KB  (CSS + JS inlined)
└── manifest.json           357 B
```

## Test Coverage

### New Tests Added (Week 4)
- `AssetHasherTest` — 7 tests
- `HtmlMinifierTest` — 6 tests
- `JsMinifierTest` — 6 tests
- `CssMinifierTest` — 6 tests
- `ManifestGeneratorTest` — 6 tests
- `ConfigLoaderTest` — 9 tests
- `BuildPipelineTest` — 14 tests
- `BuildCommandTest` — 8 tests
- `DependencyResolverTest` — 5 tests
- `StyleExtractorTest` — 4 tests
- `StyleScoperTest` — 11 tests
- `WatcherTest` — 7 tests
- `SourceMapGeneratorTest` — 9 tests
- `StaticExporterTest` — 10 tests

### Total Test Suite
```
Tests: 184
Assertions: 300
Failures: 0
Deprecations: 0
Time: ~4s
Memory: 10.00 MB
```

## Week 4 Target Metrics

✅ nexph build command working
✅ nexph export command working
✅ Static HTML output generated
✅ CSS extracted and scoped
✅ JS bundled and minified
✅ Asset hashing functional
✅ Manifest generated
✅ Build time <500ms (~2-3ms)
✅ Output size <15 KB total (~6.4 KB production)
✅ Production build optimised
✅ Watch mode working
✅ CSS scoping per component
✅ Source maps generated

## CLI Reference

```bash
# Development build
nexph build src/App.php

# Production build (minified + optimised)
nexph build src/App.php --output=dist/ --production

# Build with source maps
nexph build src/App.php --sourcemap

# Static single-file export
nexph export src/App.php --output=dist/

# Static export, no JS runtime
nexph export src/App.php --output=dist/ --no-js

# Dev server
nexph serve src/App.php --port=3000

# Dev server with watch mode
nexph serve src/App.php --port=3000 --watch
```

## Remaining / Week 5 Candidates

1. **CSS `@media` / nested rule scoping** — StyleScoper handles flat rules only
2. **Dev server error overlay** — errors shown in terminal only
3. **Lifecycle hooks** — `onMount`, `onUpdate`, `onDestroy`
4. **Async components** — lazy loading
5. **Code splitting** — per-route chunks
6. **Gzip output** — `--compress` flag

## Conclusion

Week 4 build system is complete. NEXPH UI now has a full compile-to-static pipeline:
CSS scoping, asset hashing, minification, source maps, static export, manifest generation,
and file watching. All 184 tests pass clean.
