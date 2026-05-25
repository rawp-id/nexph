<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\GzipCompressor;

class GzipCompressorTest extends TestCase
{
    private GzipCompressor $gz;

    protected function setUp(): void
    {
        $this->gz = new GzipCompressor();
    }

    public function testCompressReturnsString(): void
    {
        $result = $this->gz->compress('hello world');
        $this->assertIsString($result);
    }

    public function testCompressedIsSmallerThanOriginal(): void
    {
        $content = str_repeat('hello world this is a test ', 100);
        $compressed = $this->gz->compress($content);
        $this->assertLessThan(strlen($content), strlen($compressed));
    }

    public function testCompressedIsValidGzip(): void
    {
        $content    = 'test content for gzip';
        $compressed = $this->gz->compress($content);
        // gzip magic bytes: 0x1f 0x8b
        $this->assertEquals("\x1f\x8b", substr($compressed, 0, 2));
    }

    public function testDecompressRoundtrip(): void
    {
        $content    = 'round trip test content 12345';
        $compressed = $this->gz->compress($content);
        $this->assertEquals($content, gzdecode($compressed));
    }

    public function testWriteGzCreatesFile(): void
    {
        $tmp  = sys_get_temp_dir() . '/nexph_gz_test_' . uniqid() . '.js';
        file_put_contents($tmp, 'console.log("hello");');

        $dest = $this->gz->writeGz($tmp, file_get_contents($tmp));

        $this->assertFileExists($dest);
        $this->assertStringEndsWith('.gz', $dest);

        unlink($tmp);
        unlink($dest);
    }

    public function testWriteGzContentIsValid(): void
    {
        $content = 'body { color: red; }';
        $tmp     = sys_get_temp_dir() . '/nexph_gz_css_' . uniqid() . '.css';
        file_put_contents($tmp, $content);

        $dest = $this->gz->writeGz($tmp, $content);
        $this->assertEquals($content, gzdecode(file_get_contents($dest)));

        unlink($tmp);
        unlink($dest);
    }

    public function testRatioIsPercentage(): void
    {
        $original   = str_repeat('aaaa', 200);
        $compressed = $this->gz->compress($original);
        $ratio      = $this->gz->ratio($original, $compressed);

        $this->assertGreaterThan(0, $ratio);
        $this->assertLessThanOrEqual(100, $ratio);
    }

    public function testRatioZeroForEmptyOriginal(): void
    {
        $this->assertEquals(0.0, $this->gz->ratio('', ''));
    }

    public function testDifferentLevelsProduceDifferentSizes(): void
    {
        $content = str_repeat('nexph framework test ', 500);
        $low     = $this->gz->compress($content, 1);
        $high    = $this->gz->compress($content, 9);
        // level 9 should be <= level 1
        $this->assertLessThanOrEqual(strlen($low), strlen($high));
    }
}
