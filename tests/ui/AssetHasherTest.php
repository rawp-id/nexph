<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\AssetHasher;

class AssetHasherTest extends TestCase
{
    private AssetHasher $hasher;

    protected function setUp(): void
    {
        $this->hasher = new AssetHasher();
    }

    public function testHashReturns8Chars(): void
    {
        $hash = $this->hasher->hash('some content');

        $this->assertEquals(8, strlen($hash));
    }

    public function testHashIsHexadecimal(): void
    {
        $hash = $this->hasher->hash('some content');

        $this->assertMatchesRegularExpression('/^[a-f0-9]{8}$/', $hash);
    }

    public function testSameContentSameHash(): void
    {
        $this->assertEquals(
            $this->hasher->hash('content'),
            $this->hasher->hash('content')
        );
    }

    public function testDifferentContentDifferentHash(): void
    {
        $this->assertNotEquals(
            $this->hasher->hash('content a'),
            $this->hasher->hash('content b')
        );
    }

    public function testHashFilenameInsertsHashBeforeExtension(): void
    {
        $result = $this->hasher->hashFilename('app.js', 'content');

        $this->assertMatchesRegularExpression('/^app\.[a-f0-9]{8}\.js$/', $result);
    }

    public function testHashFilenameWithCss(): void
    {
        $result = $this->hasher->hashFilename('app.css', 'content');

        $this->assertMatchesRegularExpression('/^app\.[a-f0-9]{8}\.css$/', $result);
    }

    public function testHashFilenameWithNoExtension(): void
    {
        $result = $this->hasher->hashFilename('app', 'content');

        $this->assertMatchesRegularExpression('/^app\.[a-f0-9]{8}$/', $result);
    }
}
