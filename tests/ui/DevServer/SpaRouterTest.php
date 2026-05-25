<?php

namespace Nexph\Tests\DevServer;

use PHPUnit\Framework\TestCase;

class SpaRouterTest extends TestCase
{
    private string $docRoot;

    protected function setUp(): void
    {
        $this->docRoot = sys_get_temp_dir() . '/nexph_spa_test_' . uniqid();
        mkdir($this->docRoot . '/assets', 0755, true);

        // create index.html
        file_put_contents($this->docRoot . '/index.html', '<!DOCTYPE html><html><body>SPA</body></html>');

        // create real asset
        file_put_contents($this->docRoot . '/assets/app.js', 'console.log("app");');
    }

    protected function tearDown(): void
    {
        $this->rmdir($this->docRoot);
    }

    private function rmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..') continue;
            $path = "{$dir}/{$f}";
            is_dir($path) ? $this->rmdir($path) : unlink($path);
        }
        rmdir($dir);
    }

    public function testIsAssetPath(): void
    {
        $assetPaths = [
            '/assets/app.js'      => true,
            '/assets/style.css'   => true,
            '/images/logo.png'    => true,
            '/images/photo.jpg'   => true,
            '/images/photo.jpeg'  => true,
            '/icons/icon.svg'     => true,
            '/images/hero.webp'   => true,
            '/favicon.ico'        => true,
            '/data/config.json'   => true,
            '/assets/app.js.map'  => true,
            '/assets/app.js.gz'   => true,
            '/fonts/font.woff'    => true,
            '/fonts/font.woff2'   => true,
            '/counter'            => false,
            '/users/123'          => false,
            '/fetch'              => false,
            '/'                   => false,
            '/about'              => false,
        ];

        foreach ($assetPaths as $path => $expected) {
            $this->assertSame(
                $expected,
                $this->isAssetPath($path),
                "Path '{$path}' should " . ($expected ? 'be' : 'not be') . ' an asset'
            );
        }
    }

    public function testExistingFileServed(): void
    {
        $path = $this->docRoot . '/assets/app.js';
        $this->assertTrue(is_file($path));
    }

    public function testMissingAssetReturns404(): void
    {
        $path = $this->docRoot . '/assets/missing.js';
        $this->assertFalse(is_file($path));
        $this->assertTrue($this->isAssetPath('/assets/missing.js'));
    }

    public function testSpaRouteServesIndex(): void
    {
        // /counter is not a file, not an asset -> should serve index.html
        $uri = '/counter';
        $this->assertFalse(is_file($this->docRoot . $uri));
        $this->assertFalse($this->isAssetPath($uri));
        $this->assertTrue(is_file($this->docRoot . '/index.html'));
    }

    public function testDynamicRouteServesIndex(): void
    {
        // /users/123 is not a file, not an asset -> should serve index.html
        $uri = '/users/123';
        $this->assertFalse(is_file($this->docRoot . $uri));
        $this->assertFalse($this->isAssetPath($uri));
        $this->assertTrue(is_file($this->docRoot . '/index.html'));
    }

    private function isAssetPath(string $path): bool
    {
        $assetExtensions = [
            'js', 'css', 'png', 'jpg', 'jpeg', 'svg', 'webp', 'ico',
            'json', 'map', 'gz', 'woff', 'woff2', 'ttf', 'eot'
        ];
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        return in_array($ext, $assetExtensions, true);
    }
}
