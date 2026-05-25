<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\ConfigLoader;

class ConfigLoaderTest extends TestCase
{
    private ConfigLoader $loader;
    private string $tmpDir;

    protected function setUp(): void
    {
        $this->loader = new ConfigLoader();
        $this->tmpDir = sys_get_temp_dir() . '/nexph_config_' . uniqid();
        mkdir($this->tmpDir, 0755, true);
    }

    protected function tearDown(): void
    {
        $config = $this->tmpDir . '/nexph.config.php';
        if (file_exists($config)) {
            unlink($config);
        }
        rmdir($this->tmpDir);
    }

    public function testLoadReturnsDefaultsWhenNoConfigFile(): void
    {
        $config = $this->loader->load($this->tmpDir . '/nexph.config.php');

        $this->assertEquals('src/App.php', $config['entry']);
        $this->assertEquals('dist/', $config['output']);
        $this->assertFalse($config['minify']);
        $this->assertTrue($config['cssExtract']);
    }

    public function testLoadMergesUserConfig(): void
    {
        $configFile = $this->tmpDir . '/nexph.config.php';
        file_put_contents($configFile, "<?php\nreturn ['entry' => 'src/MyApp.php', 'minify' => true];");

        $config = $this->loader->load($configFile);

        $this->assertEquals('src/MyApp.php', $config['entry']);
        $this->assertTrue($config['minify']);
        // defaults preserved
        $this->assertEquals('dist/', $config['output']);
    }

    public function testLoadReturnsDefaultsForInvalidConfig(): void
    {
        $configFile = $this->tmpDir . '/nexph.config.php';
        file_put_contents($configFile, "<?php\nreturn 'not an array';");

        $config = $this->loader->load($configFile);

        $this->assertEquals('src/App.php', $config['entry']);
    }

    public function testFromArgsMinifyFlag(): void
    {
        $config = $this->loader->fromArgs(['src/App.php', '--minify']);

        $this->assertTrue($config['minify']);
    }

    public function testFromArgsProductionFlag(): void
    {
        $config = $this->loader->fromArgs(['src/App.php', '--production']);

        $this->assertTrue($config['minify']);
        $this->assertTrue($config['optimization']['treeshake']);
        $this->assertTrue($config['optimization']['compress']);
    }

    public function testFromArgsOutputFlag(): void
    {
        $config = $this->loader->fromArgs(['src/App.php', '--output=build/']);

        $this->assertEquals('build/', $config['output']);
    }

    public function testFromArgsPortFlag(): void
    {
        $config = $this->loader->fromArgs(['src/App.php', '--port=8080']);

        $this->assertEquals(8080, $config['devServer']['port']);
    }

    public function testFromArgsNoFlagsKeepsDefaults(): void
    {
        $config = $this->loader->fromArgs(['src/App.php']);

        $this->assertFalse($config['minify']);
        $this->assertEquals('dist/', $config['output']);
    }

    public function testNestedConfigMerge(): void
    {
        $configFile = $this->tmpDir . '/nexph.config.php';
        file_put_contents($configFile, "<?php\nreturn ['devServer' => ['port' => 4000]];");

        $config = $this->loader->load($configFile);

        $this->assertEquals(4000, $config['devServer']['port']);
        // other devServer defaults preserved
        $this->assertFalse($config['devServer']['hot']);
    }
}
