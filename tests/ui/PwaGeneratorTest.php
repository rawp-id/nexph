<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\PwaGenerator;

class PwaGeneratorTest extends TestCase
{
    private PwaGenerator $pwa;

    protected function setUp(): void
    {
        $this->pwa = new PwaGenerator();
    }

    public function testGenerateManifestReturnsJson(): void
    {
        $json = $this->pwa->generateManifest();
        $data = json_decode($json, true);
        $this->assertIsArray($data);
    }

    public function testManifestHasRequiredFields(): void
    {
        $data = json_decode($this->pwa->generateManifest(), true);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('short_name', $data);
        $this->assertArrayHasKey('start_url', $data);
        $this->assertArrayHasKey('display', $data);
        $this->assertArrayHasKey('theme_color', $data);
        $this->assertArrayHasKey('icons', $data);
    }

    public function testManifestDefaultName(): void
    {
        $data = json_decode($this->pwa->generateManifest(), true);
        $this->assertEquals('NEXPH App', $data['name']);
    }

    public function testManifestCustomOptions(): void
    {
        $data = json_decode($this->pwa->generateManifest([
            'name'        => 'My App',
            'short_name'  => 'MA',
            'theme_color' => '#ff0000',
        ]), true);

        $this->assertEquals('My App', $data['name']);
        $this->assertEquals('MA', $data['short_name']);
        $this->assertEquals('#ff0000', $data['theme_color']);
    }

    public function testManifestHasIcons(): void
    {
        $data = json_decode($this->pwa->generateManifest(), true);
        $this->assertNotEmpty($data['icons']);
        $this->assertArrayHasKey('src', $data['icons'][0]);
        $this->assertArrayHasKey('sizes', $data['icons'][0]);
    }

    public function testGenerateServiceWorkerContainsCacheName(): void
    {
        $sw = $this->pwa->generateServiceWorker([], 'my-cache-v1');
        $this->assertStringContainsString('my-cache-v1', $sw);
    }

    public function testServiceWorkerHasInstallEvent(): void
    {
        $sw = $this->pwa->generateServiceWorker();
        $this->assertStringContainsString("addEventListener('install'", $sw);
    }

    public function testServiceWorkerHasActivateEvent(): void
    {
        $sw = $this->pwa->generateServiceWorker();
        $this->assertStringContainsString("addEventListener('activate'", $sw);
    }

    public function testServiceWorkerHasFetchEvent(): void
    {
        $sw = $this->pwa->generateServiceWorker();
        $this->assertStringContainsString("addEventListener('fetch'", $sw);
    }

    public function testServiceWorkerIncludesAssets(): void
    {
        $sw = $this->pwa->generateServiceWorker(['app.js', 'app.css']);
        $this->assertStringContainsString('app.js', $sw);
        $this->assertStringContainsString('app.css', $sw);
    }

    public function testHeadSnippetContainsManifestLink(): void
    {
        $snippet = $this->pwa->headSnippet();
        $this->assertStringContainsString('manifest.webmanifest', $snippet);
    }

    public function testHeadSnippetContainsThemeColor(): void
    {
        $snippet = $this->pwa->headSnippet('#ff5500');
        $this->assertStringContainsString('#ff5500', $snippet);
    }

    public function testHeadSnippetContainsServiceWorkerRegistration(): void
    {
        $snippet = $this->pwa->headSnippet();
        $this->assertStringContainsString('serviceWorker', $snippet);
        $this->assertStringContainsString('sw.js', $snippet);
    }

    public function testWriteCreatesManifestFile(): void
    {
        $dir = sys_get_temp_dir() . '/nexph_pwa_' . uniqid();
        mkdir($dir, 0755, true);

        $files = $this->pwa->write($dir);

        $this->assertFileExists($files['manifest']);
        $this->assertFileExists($files['sw']);

        unlink($files['manifest']);
        unlink($files['sw']);
        rmdir($dir);
    }

    public function testWriteManifestIsValidJson(): void
    {
        $dir = sys_get_temp_dir() . '/nexph_pwa_' . uniqid();
        mkdir($dir, 0755, true);

        $files = $this->pwa->write($dir);
        $data  = json_decode(file_get_contents($files['manifest']), true);

        $this->assertIsArray($data);
        $this->assertArrayHasKey('name', $data);

        unlink($files['manifest']);
        unlink($files['sw']);
        rmdir($dir);
    }
}
