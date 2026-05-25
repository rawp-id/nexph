# Package Manager

Nexph has a native package manager. Install packages without Composer, or use Composer libraries via bridge.

## Commands

```bash
nexph install nexph/auth          # Native package
nexph install composer:monolog/monolog  # Composer package
nexph remove nexph/auth
nexph update
nexph module:list
nexph module:validate nexph.json
```

## Directory Structure

```
project/
  nexph.json          # Project manifest
  nexph.lock          # Lockfile (reproducible installs)
  nexph_modules/      # Installed native packages
  packages/           # Local dev packages
  vendor/             # Composer packages (via bridge)
```

## nexph.json (Project)

```json
{
    "name": "my-app",
    "version": "1.0.0",
    "type": "app",
    "requires": {
        "nexph/auth": "^0.1.0",
        "nexph/cache": "^0.1.0"
    }
}
```

## nexph.json (Package)

```json
{
    "name": "nexph/auth",
    "version": "0.1.0",
    "type": "module",
    "description": "Authentication for Nexph",
    "autoload": {"psr-4": {"Nexph\\Auth\\": "src/"}},
    "providers": ["Nexph\\Auth\\AuthServiceProvider"],
    "routes": ["routes/auth.php"],
    "config": {"session": "config/session.php"},
    "requires": {"nexph/backend-engine": ">=0.1.0"}
}
```

## Semver Constraints

| Syntax | Meaning |
|--------|---------|
| `1.0.0` | Exact |
| `^1.0.0` | >=1.0.0 <2.0.0 |
| `~1.2.0` | >=1.2.0 <1.3.0 |
| `>=1.0.0` | Greater or equal |
| `1.0.*` | Wildcard |
| `>=1.0.0 <2.0.0` | Range (AND) |
| `1.0.0 \|\| 2.0.0` | OR |

## Composer Bridge

Composer packages are installed via the `composer:` prefix:

```bash
nexph install composer:monolog/monolog
```

The bridge:
- Detects `vendor/autoload.php` automatically
- Maps PSR-4 namespaces from installed.json
- Runs `composer require` under the hood
- Native `nexph.json` always takes priority over Composer metadata

## Lockfile

`nexph.lock` records exact resolved versions for reproducible installs:

```json
{
    "packages": {
        "nexph/auth": {
            "version": "0.1.0",
            "source": "registry",
            "checksum": "sha256:...",
            "requires": {}
        }
    },
    "_generated": "2026-05-25T20:00:00+00:00"
}
```
