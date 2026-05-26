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

Composer global:

```bash
composer global require nexph/nexph
nexph new my-app
cd my-app
nexph serve
```

Composer project template:

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

## License

MIT