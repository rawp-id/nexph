# Composer Bridge

The Composer Bridge allows Nexph to use Composer libraries without making Composer a hard dependency.

## Modes

- **Disabled** — no `vendor/` directory, bridge inactive
- **Auto** — detects `vendor/autoload.php`, loads if present
- **Required** — explicitly enabled in config

## How It Works

```php
$bridge = new \Core\Runtime\Loader\ComposerBridge(__DIR__);

if ($bridge->isEnabled()) {
    $bridge->load(); // requires vendor/autoload.php
    $packages = $bridge->getPackages();
    $stats = $bridge->stats();
}
```

## Installing Composer Packages

```bash
nexph install composer:monolog/monolog
nexph install composer:guzzlehttp/guzzle:^7.0
```

This runs `composer require` under the hood and records the package in `nexph.lock`.

## Priority

1. Native `nexph.json` manifest — always first
2. Composer packages — fallback for class resolution

## Diagnostics

```bash
# Check bridge status
nexph composer:bridge
```

```php
$bridge->diagnostics();
// [
//   'vendor_autoload' => true,
//   'composer_json' => true,
//   'composer_binary' => true,
//   'installed_json' => true,
// ]
```

## Class Resolution

The bridge maps PSR-4 prefixes from `vendor/composer/installed.json`:

```php
$package = $bridge->resolveClass('Monolog\\Logger');
// 'monolog/monolog'
```
