# Configuration

## Config File

Create `nexph.config.php` in your project root:

```php
<?php

return [
    // Entry component
    'entry' => 'src/App.php',
    
    // Output directory
    'output' => 'dist/',
    
    // Minify HTML, JS, CSS
    'minify' => false,
    
    // Generate source maps
    'sourcemap' => false,
    
    // Include Tailwind CSS v4
    'tailwind' => false,
    
    // Generate .gz compressed files
    'compress' => false,
    
    // PWA support
    'pwa' => false,
    'pwaName' => 'My App',
    'pwaShortName' => 'App',
    'pwaThemeColor' => '#3b82f6',
    
    // Router mode: 'hash' or 'history'
    'routeMode' => 'hash',
    
    // Dev server port
    'port' => 3000,
    
    // HMR WebSocket port
    'hmrPort' => 35729,
    
    // Multi-page routes
    'pages' => [
        '/' => 'src/Pages/Home.php',
        '/about' => 'src/Pages/About.php',
    ],
];
```

## CLI Overrides

CLI flags override config file values:

```bash
# Override output directory
nexph build src/App.php --output=build/

# Enable production mode
nexph build src/App.php --production

# Custom port
nexph dev src/App.php --port=8080
```

## Environment-Based Config

```php
<?php

$isProd = getenv('APP_ENV') === 'production';

return [
    'entry'     => 'src/App.php',
    'output'    => 'dist/',
    'minify'    => $isProd,
    'sourcemap' => !$isProd,
    'compress'  => $isProd,
];
```

## Config Priority

1. CLI flags (highest)
2. `nexph.config.php`
3. Default values (lowest)

## All Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `entry` | string | `src/App.php` | Entry component path |
| `output` | string | `dist/` | Output directory |
| `minify` | bool | `false` | Minify output |
| `sourcemap` | bool | `false` | Generate source maps |
| `tailwind` | bool | `false` | Include Tailwind CSS |
| `compress` | bool | `false` | Generate .gz files |
| `pwa` | bool | `false` | Enable PWA |
| `pwaName` | string | `'Nexph App'` | PWA app name |
| `pwaShortName` | string | `'App'` | PWA short name |
| `pwaThemeColor` | string | `'#3b82f6'` | PWA theme color |
| `routeMode` | string | `'hash'` | Router mode |
| `port` | int | `3000` | Dev server port |
| `hmrPort` | int | `35729` | HMR WebSocket port |
| `pages` | array | `[]` | Multi-page routes |

## Next Steps

- [Build & Deploy](../guides/build.md)
- [CLI Reference](../api/cli.md)
