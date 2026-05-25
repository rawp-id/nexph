# Build & Deploy

Production builds, optimization, and deployment.

## Basic Build

```bash
nexph build src/App.php --output=dist/
```

Output:
```
dist/
├── index.html
├── app.abc123.js
└── app.abc123.css
```

## Production Build

```bash
nexph build src/App.php --output=dist/ --production
```

`--production` enables:
- Minification (HTML, JS, CSS)
- Asset hashing
- Dead code elimination
- No DevTools injection

## Build Flags

| Flag | Description |
|------|-------------|
| `--output=<dir>` | Output directory (default: `dist/`) |
| `--production` | Enable all optimizations |
| `--minify` | Minify HTML, JS, CSS |
| `--sourcemap` | Generate source maps |
| `--compress` | Generate .gz files |
| `--pwa` | Add PWA support |
| `--tailwind` | Include Tailwind CSS v4 |
| `--analyze` | Print bundle size report |
| `--route-mode=hash\|history` | Router mode |

## Minification

```bash
nexph build src/App.php --minify
```

Minifies:
- HTML — Removes whitespace, comments
- JS — Uglifies, shortens variables
- CSS — Removes whitespace, combines rules

## Source Maps

```bash
nexph build src/App.php --sourcemap
```

Generates `.map` files for debugging production code.

## Compression

```bash
nexph build src/App.php --compress
```

Creates `.gz` sidecar files:
```
dist/
├── app.abc123.js
├── app.abc123.js.gz
├── app.abc123.css
└── app.abc123.css.gz
```

Configure server to serve pre-compressed files:

**Nginx:**
```nginx
gzip_static on;
```

**Apache:**
```apache
AddEncoding gzip .gz
RewriteCond %{HTTP:Accept-Encoding} gzip
RewriteCond %{REQUEST_FILENAME}.gz -f
RewriteRule ^(.*)$ $1.gz [L]
```

## PWA Support

```bash
nexph build src/App.php --pwa --pwa-name="My App" --pwa-theme="#3b82f6"
```

Generates:
- `manifest.json` — App manifest
- `sw.js` — Service worker for offline support

PWA flags:
| Flag | Description |
|------|-------------|
| `--pwa` | Enable PWA |
| `--pwa-name=<s>` | App name |
| `--pwa-short=<s>` | Short name |
| `--pwa-theme=<hex>` | Theme color |

## Tailwind CSS

```bash
nexph build src/App.php --tailwind
```

Injects Tailwind CSS v4 CDN. Use Tailwind classes in templates:

```html
<div class="flex items-center gap-4 p-6 bg-white rounded-lg shadow">
    <h1 class="text-2xl font-bold text-gray-900">Hello</h1>
</div>
```

## Bundle Analysis

```bash
nexph build src/App.php --analyze
```

Prints size report:
```
  Bundle Analysis
  ─────────────────────────────────────────────
  Component                  JS     CSS   Total
  ─────────────────────────────────────────────
  App                   13.4 KB  11.4 KB   31 KB
  ─────────────────────────────────────────────
  TOTAL                 13.4 KB  11.4 KB   31 KB
```

Also writes `bundle-report.json`.

## Multi-Page Build

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
├── index.html
├── about/index.html
├── contact/index.html
└── pages-manifest.json
```

## Static Export

Single-file HTML with all assets inlined:

```bash
nexph export src/App.php --output=dist/
```

Creates one `index.html` with embedded JS/CSS. Perfect for:
- Email templates
- Offline documentation
- Single-file distribution

## Deployment

### Static Hosting

Works with any static host:
- Netlify
- Vercel
- GitHub Pages
- Cloudflare Pages
- AWS S3 + CloudFront

Just upload the `dist/` folder.

### Netlify

```toml
# netlify.toml
[build]
  command = "composer install && vendor/bin/nexph build src/App.php --production"
  publish = "dist"
```

### Vercel

```json
{
  "buildCommand": "composer install && vendor/bin/nexph build src/App.php --production",
  "outputDirectory": "dist"
}
```

### GitHub Pages

```yaml
# .github/workflows/deploy.yml
name: Deploy
on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.1'
      - run: composer install
      - run: vendor/bin/nexph build src/App.php --production
      - uses: peaceiris/actions-gh-pages@v3
        with:
          github_token: ${{ secrets.GITHUB_TOKEN }}
          publish_dir: ./dist
```

### Docker

```dockerfile
FROM php:8.1-cli
WORKDIR /app
COPY . .
RUN curl -sS https://getcomposer.org/installer | php
RUN php composer.phar install
RUN vendor/bin/nexph build src/App.php --production

FROM nginx:alpine
COPY --from=0 /app/dist /usr/share/nginx/html
```

## History Mode Routing

For clean URLs (`/about` instead of `/#/about`):

```bash
nexph build src/App.php --route-mode=history
```

Requires server fallback to `index.html`:

**Nginx:**
```nginx
location / {
    try_files $uri $uri/ /index.html;
}
```

**Apache (.htaccess):**
```apache
RewriteEngine On
RewriteBase /
RewriteRule ^index\.html$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.html [L]
```

## Next Steps

- [CLI Reference](../api/cli.md)
- [Configuration](../getting-started/configuration.md)
