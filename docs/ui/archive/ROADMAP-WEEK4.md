# NEXPH UI - Week 4 Roadmap

## Goals

- Build system implementation
- Static export functionality
- CSS extraction and scoping
- Asset optimization
- Dev server with watch mode
- Production-ready output

## Day 1-2: Build Command Foundation

### nexph build CLI
```bash
nexph build src/App.php --output dist/
nexph build src/App.php --output dist/ --minify
nexph build src/App.php --output dist/ --watch
```

### Build Pipeline
1. Parse entry component
2. Resolve all dependencies
3. Compile components
4. Extract CSS
5. Bundle JavaScript
6. Optimize assets
7. Generate manifest
8. Write output files

### Files to Create
- `src/Builder/BuildCommand.php` - CLI command handler
- `src/Builder/BuildPipeline.php` - Build orchestration
- `src/Builder/DependencyResolver.php` - Component dependency tree
- `src/Builder/AssetOptimizer.php` - Minification and optimization
- `src/Builder/ManifestGenerator.php` - Build manifest

### Output Structure
```
dist/
├── index.html          # Entry HTML
├── app.js              # Bundled runtime
├── app.css             # Extracted styles
├── manifest.json       # Build manifest
└── assets/
    ├── [hash].js       # Code-split chunks
    └── [hash].css      # Component styles
```

## Day 3: CSS Extraction & Scoping

### Scoped Styles
```php
class Button extends Component
{
    public function style(): string
    {
        return <<<CSS
.btn {
    padding: 10px 20px;
    border-radius: 6px;
}
CSS;
    }
}
```

### CSS Processing
- Extract styles from components
- Generate unique scope IDs
- Prefix selectors with scope
- Combine into single CSS file
- Minify for production

### Files to Create
- `src/Compiler/StyleExtractor.php` - Extract component styles
- `src/Compiler/StyleScoper.php` - Scope CSS selectors
- `src/Builder/CssMinifier.php` - CSS minification

### Scoped Output
```css
/* Button component - scope: btn-a1b2c3 */
.btn-a1b2c3 .btn {
    padding: 10px 20px;
    border-radius: 6px;
}
```

## Day 4: Asset Optimization

### JavaScript Minification
- Remove whitespace
- Shorten variable names
- Dead code elimination
- Tree shaking

### HTML Optimization
- Minify HTML output
- Remove comments
- Compress whitespace
- Inline critical CSS

### Asset Hashing
- Content-based hashing
- Cache busting
- Manifest mapping

### Files to Create
- `src/Builder/JsMinifier.php` - JavaScript minification
- `src/Builder/HtmlMinifier.php` - HTML minification
- `src/Builder/AssetHasher.php` - Content hashing

### Build Manifest
```json
{
    "version": "1.0.0",
    "buildTime": "2026-05-20T06:33:49.843Z",
    "entry": "src/App.php",
    "assets": {
        "app.js": "app.a1b2c3d4.js",
        "app.css": "app.e5f6g7h8.css"
    },
    "components": [
        "Button",
        "Card",
        "TodoList"
    ],
    "size": {
        "html": 2048,
        "js": 8192,
        "css": 1024,
        "total": 11264
    }
}
```

## Day 5: Dev Server & Watch Mode

### Dev Server
```bash
nexph serve src/App.php --port 3000
nexph serve src/App.php --port 3000 --hot
```

### Features
- File watching
- Auto-rebuild on change
- Live reload
- Hot module replacement (future)
- Error overlay

### Files to Create
- `src/DevServer/Server.php` - HTTP server
- `src/DevServer/Watcher.php` - File system watcher
- `src/DevServer/LiveReload.php` - Browser refresh
- `src/DevServer/ErrorHandler.php` - Error display

### Watch Mode
```php
class Watcher
{
    public function watch(string $path, callable $onChange): void
    {
        // Watch for file changes
        // Trigger rebuild
        // Notify browser
    }
}
```

## Day 6: Static Export

### Static Site Generation
```bash
nexph export src/App.php --output dist/
```

### Features
- Pure static HTML output
- No runtime required
- CDN-ready
- Pre-rendered content

### Use Cases
- Landing pages
- Documentation sites
- Blogs
- Marketing sites
- Catalogs

### Export Process
1. Compile all components
2. Render to static HTML
3. Extract inline styles
4. Remove runtime JS (optional)
5. Generate static assets

### Files to Create
- `src/Builder/StaticExporter.php` - Static generation
- `src/Builder/PreRenderer.php` - Server-side rendering

## Day 7: Production Build & Optimization

### Production Build
```bash
nexph build src/App.php --production
```

### Optimizations
- Aggressive minification
- Code splitting
- Tree shaking
- Gzip compression
- Source maps (optional)

### Build Config
```php
// nexph.config.php
return [
    'entry' => 'src/App.php',
    'output' => 'dist/',
    'minify' => true,
    'sourcemap' => false,
    'cssExtract' => true,
    'target' => 'es2020',
    'optimization' => [
        'treeshake' => true,
        'splitChunks' => true,
        'compress' => true,
    ],
    'devServer' => [
        'port' => 3000,
        'hot' => true,
        'open' => true,
    ],
];
```

### Files to Create
- `src/Builder/ConfigLoader.php` - Load build config
- `src/Builder/ProductionOptimizer.php` - Production optimizations

## Technical Tasks

### Build System Core
- [x] CLI command structure
- [x] Build pipeline orchestration
- [x] Dependency resolution
- [x] Component compilation
- [x] Asset bundling
- [x] Output generation

### CSS Processing
- [x] Style extraction
- [x] CSS scoping
- [x] CSS minification
- [x] CSS bundling

### Asset Optimization
- [x] JS minification
- [x] HTML minification
- [x] Asset hashing
- [x] Manifest generation

### Dev Experience
- [x] Dev server
- [x] File watching
- [x] Live reload
- [x] Error handling

### Static Export
- [x] Static HTML generation
- [x] Pre-rendering
- [x] CDN optimization

## Examples

### Full Build Example
```bash
# Development build
nexph build src/TodoApp.php

# Production build
nexph build src/TodoApp.php --production --minify

# Watch mode
nexph build src/TodoApp.php --watch

# Dev server
nexph serve src/TodoApp.php --port 3000

# Static export
nexph export src/TodoApp.php --output dist/
```

### Build Output
```
dist/
├── index.html                    # 2.1 KB
├── app.a1b2c3d4.js              # 8.2 KB (minified)
├── app.e5f6g7h8.css             # 1.0 KB (minified)
├── manifest.json                 # 512 B
└── assets/
    ├── button.f9g0h1i2.css      # 256 B
    └── card.j3k4l5m6.css        # 384 B

Total: 12.4 KB (gzipped: ~4.8 KB)
```

## Success Metrics

- [ ] nexph build command working
- [ ] Static HTML output generated
- [ ] CSS extracted and scoped
- [ ] JS bundled and minified
- [ ] Asset hashing functional
- [ ] Manifest generated
- [ ] Dev server running
- [ ] Watch mode working
- [ ] Build time <500ms for small app
- [ ] Output size <15kb total
- [ ] Production build optimized

## Performance Targets

### Build Time
- Small app (<5 components): <100ms
- Medium app (5-20 components): <500ms
- Large app (20+ components): <2s

### Output Size
- Runtime JS: <10kb (minified)
- Component CSS: <5kb (minified)
- HTML: <5kb
- Total: <20kb (before gzip)
- Gzipped: <8kb

## Stretch Goals

- Source maps generation
- Code splitting by route
- Lazy loading components
- Image optimization
- SVG inlining
- Critical CSS extraction
- Service worker generation
- PWA manifest
- Preload/prefetch hints

## Integration Examples

### Deployment Targets

#### Cloudflare Pages
```bash
nexph build src/App.php --output dist/
# Deploy dist/ to Cloudflare Pages
```

#### Netlify
```bash
nexph build src/App.php --output dist/
# Deploy dist/ to Netlify
```

#### Vercel
```bash
nexph build src/App.php --output dist/
# Deploy dist/ to Vercel
```

#### Static Hosting
```bash
nexph export src/App.php --output dist/
# Upload dist/ to any static host
```

## Notes

**Build System Philosophy**:
- Fast builds (compile-time optimization)
- Small output (aggressive minification)
- Simple deployment (static files)
- No runtime dependencies (optional)

**Architecture**:
- Modular build pipeline
- Pluggable optimizers
- Configurable output
- Multiple build modes

**Developer Experience**:
- Fast feedback loop
- Clear error messages
- Watch mode for development
- Production-ready output

## Conclusion

Week 4 will complete the NEXPH UI MVP with a full build system, enabling developers to create production-ready applications with a simple command. The focus is on fast builds, small output, and excellent developer experience.
