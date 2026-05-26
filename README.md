# Nexph

Adaptive PHP runtime with native loader, package manager, HTTP/WS/SSE runtime, and UI compiler.

## Install

Native:

```bash
curl -fsSL https://nexph.dev/install | sh
nexph new my-app
cd my-app
nexph serve
```

Composer global from GitHub test repo:

```bash
composer global config repositories.nexph vcs https://github.com/rawp-id/nexph.git
composer global require nexph/nexph:dev-main
nexph new my-app
cd my-app
nexph serve
```

Composer create project from GitHub test repo:

```bash
rm -rf test-app
composer create-project --repository='{"type":"vcs","url":"https://github.com/rawp-id/nexph.git"}' nexph/nexph test-app dev-main
cd test-app
php nexph serve
```

Composer project template later:

```bash
composer create-project nexph/nexph-framework my-app
cd my-app
php nexph serve
```

Dev/no install:

```bash
php nexph help
php nexph new my-app
```

## Layout

```txt
my-app/
  nexph
  nexph.json
  src/
  bags/
    runtime/
    local/
    installed/
    cache/
```

## Packages

```bash
nexph install nexph/auth
nexph install composer:monolog/monolog
```

## Notes

- `composer create-project` needs empty target directory.
- Until Packagist + stable tag exist, use `dev-main` for Composer testing.
- User install should use curl/global Composer, not git clone.

## License

MIT