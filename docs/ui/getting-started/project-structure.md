# Project Structure

## Recommended Layout

```
my-app/
├── src/
│   ├── App.php              # Main entry component
│   ├── Components/
│   │   ├── Header.php
│   │   ├── Footer.php
│   │   └── Sidebar.php
│   ├── Pages/
│   │   ├── Home.php
│   │   ├── About.php
│   │   └── Contact.php
│   └── Stores/
│       └── CartStore.php
├── assets/
│   ├── css/
│   └── images/
├── dist/                    # Build output
├── tests/
│   └── components/
├── nexph.config.php         # Configuration
├── composer.json
└── .gitignore
```

## Entry Component

Your main `App.php` typically includes layout and routing:

```php
<?php
// src/App.php

use Nexph\Component;

class App extends Component
{
    public function render(): string
    {
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>My App</title>
</head>
<body>
    <Header />
    <main nx-outlet></main>
    <Footer />
    
    <div nx-route="/">
        <Home />
    </div>
    <div nx-route="/about">
        <About />
    </div>
</body>
</html>
HTML;
    }
}
```

## Configuration

`nexph.config.php`:

```php
<?php

return [
    'entry'      => 'src/App.php',
    'output'     => 'dist/',
    'minify'     => true,
    'sourcemap'  => true,
    'tailwind'   => false,
    'pwa'        => false,
    'routeMode'  => 'hash',  // 'hash' or 'history'
    
    // Multi-page builds
    'pages' => [
        '/'       => 'src/Pages/Home.php',
        '/about'  => 'src/Pages/About.php',
        '/contact'=> 'src/Pages/Contact.php',
    ],
];
```

## Build Output

After `nexph build`:

```
dist/
├── index.html           # Main HTML
├── app.[hash].js        # Bundled JS
├── app.[hash].css       # Extracted CSS
├── app.[hash].js.map    # Source map (if enabled)
├── app.[hash].js.gz     # Compressed (if --compress)
├── manifest.json        # PWA manifest (if --pwa)
└── sw.js                # Service worker (if --pwa)
```

## Multi-Page Output

With `nexph pages`:

```
dist/
├── index.html           # Home (/)
├── about/
│   └── index.html       # About (/about)
├── contact/
│   └── index.html       # Contact (/contact)
└── pages-manifest.json  # Build manifest
```

## Next Steps

- [Configuration](./configuration.md)
- [Build & Deploy](../guides/build.md)
