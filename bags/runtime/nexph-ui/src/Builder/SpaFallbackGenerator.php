<?php

namespace Nexph\Builder;

class SpaFallbackGenerator
{
    public function generate(string $outputDir, array $config): array
    {
        $files = [];

        // _redirects for Cloudflare Pages / Netlify
        $redirectsPath = "{$outputDir}/_redirects";
        file_put_contents($redirectsPath, "/* /index.html 200\n");
        $files['_redirects'] = $redirectsPath;

        // _headers for Cloudflare Pages (optional cache headers)
        $headersPath = "{$outputDir}/_headers";
        $headers = <<<HEADERS
/*
  X-Frame-Options: DENY
  X-Content-Type-Options: nosniff

/assets/*
  Cache-Control: public, max-age=31536000, immutable
HEADERS;
        file_put_contents($headersPath, $headers);
        $files['_headers'] = $headersPath;

        return $files;
    }

    public function generateVercel(string $outputDir): string
    {
        $vercelConfig = [
            'rewrites' => [
                ['source' => '/((?!assets/).*)', 'destination' => '/index.html']
            ]
        ];
        $path = "{$outputDir}/vercel.json";
        file_put_contents($path, json_encode($vercelConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        return $path;
    }

    public function printWarning(): void
    {
        $warning = <<<WARN

⚠  History mode requires server fallback to index.html

   For static hosting, use --spa-fallback to generate _redirects.
   For custom servers, configure fallback manually:

   Cloudflare Workers:
     export default {
       async fetch(request, env) {
         const url = new URL(request.url);
         let response = await env.ASSETS.fetch(request);
         if (response.status === 404 && !url.pathname.startsWith('/assets/')) {
           response = await env.ASSETS.fetch(new Request(url.origin + '/index.html', request));
         }
         return response;
       }
     };

   Nginx:
     location / {
       try_files \$uri \$uri/ /index.html;
     }

   Apache (.htaccess):
     RewriteEngine On
     RewriteCond %{REQUEST_FILENAME} !-f
     RewriteCond %{REQUEST_FILENAME} !-d
     RewriteRule . /index.html [L]

WARN;
        echo $warning;
    }
}
