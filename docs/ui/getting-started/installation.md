# Installation

## Requirements

- PHP 8.1+
- Composer 2.x

## Install via Composer

```bash
composer require nexph/nexph
```

## Verify Installation

```bash
vendor/bin/nexph --version
```

## Quick Setup

Scaffold a new project:

```bash
vendor/bin/nexph init my-app
cd my-app
```

This creates:

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

## Manual Setup

Create a minimal component:

```php
<?php
// src/App.php

use Nexph\Component;

class App extends Component
{
    public string $message = 'Hello, Nexph!';

    public function render(): string
    {
        return <<<HTML
<div class="app">
    <h1>{$this->message}</h1>
</div>
HTML;
    }
}
```

Build it:

```bash
vendor/bin/nexph build src/App.php --output=dist/
```

Open `dist/index.html` in your browser.

## Development Server

For live development with Hot Module Replacement:

```bash
vendor/bin/nexph dev src/App.php
```

Opens at `http://localhost:3000` with auto-reload on file changes.

## Next Steps

- [Your First Component](./first-component.md)
- [Project Structure](./project-structure.md)
- [Configuration](./configuration.md)
