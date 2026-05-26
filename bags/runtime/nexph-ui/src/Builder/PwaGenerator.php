<?php

namespace Nexph\Builder;

class PwaGenerator
{
    /**
     * Generate a web app manifest (manifest.webmanifest).
     */
    public function generateManifest(array $options = []): string
    {
        $manifest = [
            'name'             => $options['name']        ?? 'NEXPH App',
            'short_name'       => $options['short_name']  ?? 'App',
            'description'      => $options['description'] ?? 'Built with NEXPH UI',
            'start_url'        => $options['start_url']   ?? '/',
            'display'          => $options['display']     ?? 'standalone',
            'background_color' => $options['bg_color']    ?? '#0f0f1a',
            'theme_color'      => $options['theme_color'] ?? '#6366f1',
            'icons'            => $options['icons']       ?? [
                [
                    'src'   => 'icon-192.png',
                    'sizes' => '192x192',
                    'type'  => 'image/png',
                ],
                [
                    'src'   => 'icon-512.png',
                    'sizes' => '512x512',
                    'type'  => 'image/png',
                ],
            ],
        ];

        return json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate a minimal service worker with cache-first strategy.
     */
    public function generateServiceWorker(array $assets = [], string $cacheName = 'nexph-v1'): string
    {
        $assetList = json_encode(array_values($assets), JSON_PRETTY_PRINT);

        return <<<JS
const CACHE = '{$cacheName}';
const ASSETS = {$assetList};

self.addEventListener('install', e => {
    e.waitUntil(
        caches.open(CACHE).then(c => c.addAll(ASSETS)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', e => {
    e.waitUntil(
        caches.keys().then(keys =>
            Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', e => {
    if (e.request.method !== 'GET') return;
    e.respondWith(
        caches.match(e.request).then(cached => {
            if (cached) return cached;
            return fetch(e.request).then(res => {
                if (!res || res.status !== 200 || res.type === 'opaque') return res;
                const clone = res.clone();
                caches.open(CACHE).then(c => c.put(e.request, clone));
                return res;
            });
        })
    );
});
JS;
    }

    /**
     * Build the <head> snippet to inject into HTML.
     */
    public function headSnippet(string $themeColor = '#6366f1'): string
    {
        return implode("\n    ", [
            '<link rel="manifest" href="manifest.webmanifest">',
            '<meta name="theme-color" content="' . htmlspecialchars($themeColor) . '">',
            '<meta name="mobile-web-app-capable" content="yes">',
            '<meta name="apple-mobile-web-app-capable" content="yes">',
            '<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">',
            '<script>if("serviceWorker"in navigator){navigator.serviceWorker.register("sw.js").catch(()=>{});}</script>',
        ]);
    }

    public function write(string $outputDir, array $assets = [], array $options = []): array
    {
        $dir = rtrim($outputDir, '/');

        $manifestJson = $this->generateManifest($options);
        $swJs         = $this->generateServiceWorker(
            array_merge(['/', '/index.html'], array_values($assets)),
            'nexph-v1'
        );

        file_put_contents("{$dir}/manifest.webmanifest", $manifestJson);
        file_put_contents("{$dir}/sw.js", $swJs);

        return [
            'manifest' => "{$dir}/manifest.webmanifest",
            'sw'       => "{$dir}/sw.js",
        ];
    }
}
