# CLI Reference

## nexph build

Compile component to static HTML/JS/CSS.

```bash
nexph build <entry> [options]
```

**Arguments:**
- `<entry>` — Path to entry component (e.g., `src/App.php`)

**Options:**

| Flag | Description | Default |
|------|-------------|---------|
| `--output=<dir>` | Output directory | `dist/` |
| `--minify` | Minify HTML, JS, CSS | `false` |
| `--production` | Enable all optimizations | `false` |
| `--sourcemap` | Generate source maps | `false` |
| `--compress` | Generate .gz files | `false` |
| `--tailwind` | Include Tailwind CSS v4 | `false` |
| `--pwa` | Enable PWA support | `false` |
| `--pwa-name=<s>` | PWA app name | `'Nexph App'` |
| `--pwa-short=<s>` | PWA short name | `'App'` |
| `--pwa-theme=<hex>` | PWA theme color | `'#3b82f6'` |
| `--route-mode=<m>` | Router mode (`hash`/`history`) | `'hash'` |
| `--analyze` | Print bundle size report | `false` |

**Examples:**

```bash
# Basic build
nexph build src/App.php

# Production build
nexph build src/App.php --production --output=build/

# Full production with PWA
nexph build src/App.php \
    --production \
    --compress \
    --pwa \
    --pwa-name="My App" \
    --analyze
```

---

## nexph dev

Development server with Hot Module Replacement.

```bash
nexph dev <entry> [options]
```

**Arguments:**
- `<entry>` — Path to entry component

**Options:**

| Flag | Description | Default |
|------|-------------|---------|
| `--port=<n>` | HTTP server port | `3000` |
| `--hmr-port=<n>` | WebSocket HMR port | `35729` |
| `--tailwind` | Include Tailwind CSS v4 | `false` |

**Examples:**

```bash
# Start dev server
nexph dev src/App.php

# Custom port
nexph dev src/App.php --port=8080

# With Tailwind
nexph dev src/App.php --tailwind
```

**Features:**
- Auto-reload on file changes
- DevTools panel (Alt+D to toggle)
- Error overlay
- Fast rebuilds

---

## nexph serve

Build and serve static files.

```bash
nexph serve <entry> [options]
```

**Arguments:**
- `<entry>` — Path to entry component

**Options:**

| Flag | Description | Default |
|------|-------------|---------|
| `--port=<n>` | HTTP server port | `3000` |
| `--watch` | Rebuild on file changes | `false` |
| `--output=<dir>` | Output directory | `dist/` |

**Examples:**

```bash
# Build and serve
nexph serve src/App.php

# With watch mode
nexph serve src/App.php --watch --port=8000
```

---

## nexph init

Scaffold a new project.

```bash
nexph init [name] [options]
```

**Arguments:**
- `[name]` — Project directory name (default: current directory)

**Options:**

| Flag | Description |
|------|-------------|
| `--minimal` | Skip TodoApp example |
| `--force` | Overwrite existing files |

**Examples:**

```bash
# Create new project
nexph init my-app

# Minimal setup
nexph init my-app --minimal

# Initialize in current directory
nexph init . --force
```

**Generated structure:**

```
my-app/
├── examples/
│   ├── App.php
│   ├── Counter.php
│   └── TodoApp.php
├── src/
├── dist/
├── nexph.config.php
└── .gitignore
```

---

## nexph pages

Build multiple pages.

```bash
nexph pages [options]
```

**Options:**

| Flag | Description | Default |
|------|-------------|---------|
| `--pages=<list>` | Comma-separated entry files | Auto-discover |
| `--output=<dir>` | Output directory | `dist/` |
| `--production` | Enable optimizations | `false` |

**Examples:**

```bash
# Auto-discover pages
nexph pages --output=dist/

# Explicit pages
nexph pages --pages=src/Home.php,src/About.php --production
```

**Config-based:**

```php
// nexph.config.php
return [
    'pages' => [
        '/'        => 'src/Pages/Home.php',
        '/about'   => 'src/Pages/About.php',
        '/contact' => 'src/Pages/Contact.php',
    ],
];
```

---

## nexph test

Run component tests.

```bash
nexph test [dir] [options]
```

**Arguments:**
- `[dir]` — Test directory (default: `tests/components`)

**Options:**

| Flag | Description |
|------|-------------|
| `--filter=<name>` | Only run matching tests |
| `--watch`, `-w` | Re-run on file changes |
| `--verbose`, `-v` | Show all assertion results |

**Examples:**

```bash
# Run all tests
nexph test

# Filter tests
nexph test --filter=Counter

# Watch mode
nexph test --watch --verbose
```

**Test file format:**

```php
// tests/components/Counter.test.php
nexph_test('counter renders', 'examples/Counter.php', [
    'build_success'    => 'build_success',
    'component_exists' => 'Counter',
    'has_state'        => 'count',
    'has_method'       => 'increment',
]);
```

---

## nexph export

Export single-file HTML with inlined assets.

```bash
nexph export <entry> [options]
```

**Arguments:**
- `<entry>` — Path to entry component

**Options:**

| Flag | Description | Default |
|------|-------------|---------|
| `--output=<dir>` | Output directory | `dist/` |
| `--minify` | Minify output | `false` |

**Examples:**

```bash
nexph export src/App.php --output=export/ --minify
```

Creates single `index.html` with all JS/CSS embedded.

---

## Exit Codes

| Code | Meaning |
|------|---------|
| `0` | Success |
| `1` | Build error |
| `2` | Invalid arguments |
| `3` | File not found |
