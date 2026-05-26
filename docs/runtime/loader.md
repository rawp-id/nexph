# Runtime Loader

The Nexph Runtime Loader discovers, validates, and boots modules from `nexph_modules/` and `packages/` directories.

## How It Works

1. **Discovery** — scans configured paths for `nexph.json` manifests
2. **Validation** — checks required fields, semver format, type
3. **Dependency Sort** — topological sort based on `requires`
4. **Preload** — loads files declared in `preload` (with path traversal protection)
5. **Boot** — instantiates and boots service providers

## Usage

The loader boots automatically when `bags/local/`, `bags/installed/`, or `modules/` exist:

```php
// Stateful (serve.php) — automatic
// Stateless (FPM autoload.php) — automatic

// Manual usage:
$loader = new \Core\Runtime\Loader\RuntimeLoader();
$loader->discover([__DIR__ . '/bags/local', __DIR__ . '/bags/installed', __DIR__ . '/modules']);
$loader->boot();

// Stats
$stats = $loader->stats();
```

## Directory Roles

| Directory | Role | `nexph.json` | Auto-discovered |
|-----------|------|-------------|----------------|
| `src/` | Core internal | NO | NO |
| `bags/local/` | Local dev packages | YES | YES |
| `bags/installed/` | Registry-installed | YES | YES |
| `modules/` | App-level modules | YES | YES |

See [module-boundaries.md](module-boundaries.md) for full rules.

## Module Lifecycle

```
discover → validate → register → sort → preload → boot providers
```

## Contracts

Modules can implement these interfaces:

| Interface | Purpose |
|-----------|---------|
| `ServiceProviderInterface` | register() + boot() |
| `RouteProviderInterface` | Provide routes |
| `CommandProviderInterface` | Provide CLI commands |
| `ConfigProviderInterface` | Provide config arrays |
| `HookProviderInterface` | Runtime lifecycle hooks |
| `PreloadableInterface` | Declare preload files |
| `BootableInterface` | Boot logic |
| `ShutdownableInterface` | Cleanup on shutdown |

## Security

- Path traversal blocked on preload and route files
- Provider namespace validated against package autoload
- Invalid manifests skipped gracefully (no crash)

## Observability

```php
$obs = new \Core\Runtime\Loader\LoaderObservability();
$metrics = $obs->metrics($loader);
$prometheus = $obs->prometheusText($loader);
```
