# Packages

Nexph packages are self-contained modules with a `nexph.json` manifest.

## Package Locations

| Location | Purpose | Install method |
|----------|---------|---------------|
| `bags/local/<name>/` | Local dev packages | Manual / `nexph init` |
| `bags/installed/<name>/` | Registry packages | `nexph install` |
| `modules/<name>/` | App-level modules | Manual |

## Minimal Package

```
bags/local/my-package/
  nexph.json
  src/
    MyService.php
```

```json
{
    "name": "nexph/my-package",
    "version": "0.1.0",
    "type": "module",
    "autoload": {"psr-4": {"MyPackage\\": "src/"}},
    "requires": {}
}
```

## Package Types

| Type | Use case |
|------|----------|
| `library` | Reusable code, no providers |
| `module` | Full module with providers/routes/commands |
| `plugin` | Extends runtime behavior via hooks |
| `app` | Standalone application |

## Manifest Fields

| Field | Required | Description |
|-------|----------|-------------|
| `name` | YES | Package name (e.g. `nexph/auth`) |
| `version` | YES | Semver (e.g. `0.1.0`) |
| `type` | no | `library`, `module`, `plugin`, `app` |
| `description` | no | Short description |
| `autoload` | no | PSR-4 class mapping |
| `preload` | no | Files to preload at boot |
| `lazy` | no | Lazy-loaded class/file map |
| `providers` | no | Service provider classes |
| `routes` | no | Route files |
| `commands` | no | CLI command classes |
| `hooks` | no | Runtime lifecycle hooks |
| `config` | no | Config file mapping |
| `requires` | no | Dependencies with semver constraints |
| `conflicts` | no | Conflicting packages |

## Example: Route Package

```json
{
    "name": "my-app/api",
    "version": "1.0.0",
    "type": "module",
    "routes": ["routes/api.php"]
}
```

```php
// routes/api.php
return function ($router) {
    $router->get('/hello', fn($req, $res) => $res->json(['hello' => 'world']));
};
```

## Example: Middleware Package

```json
{
    "name": "nexph/cors",
    "version": "1.0.0",
    "type": "plugin",
    "hooks": [{"middleware": "Nexph\\Cors\\CorsMiddleware"}],
    "autoload": {"psr-4": {"Nexph\\Cors\\": "src/"}}
}
```

## Example: CLI-only Package

```json
{
    "name": "nexph/migrations",
    "version": "1.0.0",
    "type": "module",
    "commands": {
        "migrate:up": "Nexph\\Migrations\\MigrateUpCommand",
        "migrate:down": "Nexph\\Migrations\\MigrateDownCommand"
    }
}
```
