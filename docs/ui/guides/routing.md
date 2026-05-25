# Routing

Client-side routing with hash or history mode.

## Basic Setup

```php
<?php

use Nexph\Component;

class App extends Component
{
    public function render(): string
    {
        return <<<HTML
<div class="app">
    <nav>
        <a nx-link="/">Home</a>
        <a nx-link="/about">About</a>
        <a nx-link="/contact">Contact</a>
    </nav>
    
    <main nx-outlet></main>
    
    <div nx-route="/">
        <h1>Home</h1>
        <p>Welcome to our site!</p>
    </div>
    
    <div nx-route="/about">
        <h1>About</h1>
        <p>Learn more about us.</p>
    </div>
    
    <div nx-route="/contact">
        <h1>Contact</h1>
        <p>Get in touch.</p>
    </div>
</div>
HTML;
    }
}
```

## Route Parameters

Extract dynamic segments from URLs:

```html
<!-- Define route with parameter -->
<div nx-route="/users/:id">
    <UserProfile />
</div>

<div nx-route="/posts/:slug">
    <PostDetail />
</div>

<!-- Multiple parameters -->
<div nx-route="/categories/:category/products/:id">
    <ProductDetail />
</div>
```

Access parameters in JS:

```javascript
// window.NEXPH.router.params
// { id: '123' }
// { slug: 'hello-world' }
// { category: 'electronics', id: '456' }
```

## Navigation Links

```html
<!-- Static links -->
<a nx-link="/">Home</a>
<a nx-link="/about">About</a>

<!-- Dynamic links -->
<a nx-link="/users/123">User 123</a>
<a nx-link="/posts/hello-world">Read Post</a>
```

Active link styling:

```css
[nx-link].active {
    font-weight: bold;
    color: #3b82f6;
}
```

## Router Modes

### Hash Mode (Default)

URLs use hash fragment: `example.com/#/about`

```bash
nexph build src/App.php --route-mode=hash
```

Or in config:

```php
return [
    'routeMode' => 'hash',
];
```

**Pros:**
- Works without server configuration
- Compatible with static hosting

### History Mode

Clean URLs: `example.com/about`

```bash
nexph build src/App.php --route-mode=history
```

```php
return [
    'routeMode' => 'history',
];
```

**Requires server configuration:**

Nginx:
```nginx
location / {
    try_files $uri $uri/ /index.html;
}
```

Apache (.htaccess):
```apache
RewriteEngine On
RewriteBase /
RewriteRule ^index\.html$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.html [L]
```

## Programmatic Navigation

```javascript
// Navigate to route
window.NEXPH.router.push('/about');

// Navigate with params
window.NEXPH.router.push('/users/123');

// Go back
window.NEXPH.router.back();

// Get current route
window.NEXPH.router.current; // '/about'

// Get params
window.NEXPH.router.params; // { id: '123' }
```

## Nested Routes

```html
<div nx-route="/dashboard">
    <DashboardLayout>
        <div nx-outlet></div>
        
        <div nx-route="/dashboard/overview">
            <Overview />
        </div>
        
        <div nx-route="/dashboard/settings">
            <Settings />
        </div>
    </DashboardLayout>
</div>
```

## 404 Handling

```html
<!-- Catch-all route (must be last) -->
<div nx-route="*">
    <h1>404</h1>
    <p>Page not found</p>
    <a nx-link="/">Go home</a>
</div>
```

## Route Guards

Handle navigation in component methods:

```php
public function beforeNavigate(): bool
{
    if (!$this->isLoggedIn) {
        // Redirect to login
        return false;
    }
    return true;
}
```

## Multi-Page Builds

For static multi-page sites:

```bash
nexph pages --output=dist/
```

Config:

```php
return [
    'pages' => [
        '/'        => 'src/Pages/Home.php',
        '/about'   => 'src/Pages/About.php',
        '/contact' => 'src/Pages/Contact.php',
    ],
];
```

Output:

```
dist/
├── index.html        # /
├── about/
│   └── index.html    # /about
└── contact/
    └── index.html    # /contact
```

## Next Steps

- [State Management](./state.md)
- [Build & Deploy](./build.md)
