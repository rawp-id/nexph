<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Nexph\Builder\BundleAnalyzer;

class BundleAnalyzerTest extends TestCase
{
    private BundleAnalyzer $analyzer;

    protected function setUp(): void
    {
        $this->analyzer = new BundleAnalyzer();
    }

    public function testAnalyzeReturnsArray(): void
    {
        $result = $this->analyzer->analyze(['App'], ['console.log("a")'], ['.a{color:red}'], ['<div></div>']);
        $this->assertIsArray($result);
    }

    public function testAnalyzeHasComponentsKey(): void
    {
        $result = $this->analyzer->analyze(['App'], ['js'], ['css'], ['html']);
        $this->assertArrayHasKey('components', $result);
    }

    public function testAnalyzeHasTotalsKey(): void
    {
        $result = $this->analyzer->analyze(['App'], ['js'], ['css'], ['html']);
        $this->assertArrayHasKey('totals', $result);
    }

    public function testAnalyzeComponentHasName(): void
    {
        $result = $this->analyzer->analyze(['MyComp'], ['js'], ['css'], ['html']);
        $this->assertEquals('MyComp', $result['components'][0]['name']);
    }

    public function testAnalyzeComponentHasSizes(): void
    {
        $result = $this->analyzer->analyze(['App'], ['console.log()'], ['.a{}'], ['<div>']);
        $comp   = $result['components'][0];
        $this->assertArrayHasKey('js', $comp);
        $this->assertArrayHasKey('css', $comp);
        $this->assertArrayHasKey('html', $comp);
        $this->assertArrayHasKey('total', $comp);
    }

    public function testAnalyzeTotalsAreCorrect(): void
    {
        $js   = 'console.log()';
        $css  = '.a{}';
        $html = '<div>';
        $result = $this->analyzer->analyze(['App'], [$js], [$css], [$html]);
        $this->assertEquals(strlen($js),   $result['totals']['js']);
        $this->assertEquals(strlen($css),  $result['totals']['css']);
        $this->assertEquals(strlen($html), $result['totals']['html']);
    }

    public function testAnalyzeMultipleComponents(): void
    {
        $result = $this->analyzer->analyze(
            ['App', 'Button'],
            ['js1', 'js2'],
            ['css1', 'css2'],
            ['html1', 'html2']
        );
        $this->assertCount(2, $result['components']);
    }

    public function testAnalyzeSortsByTotalDesc(): void
    {
        $result = $this->analyzer->analyze(
            ['Small', 'Large'],
            ['x', str_repeat('x', 1000)],
            ['', ''],
            ['', '']
        );
        $this->assertEquals('Large', $result['components'][0]['name']);
    }

    public function testRenderReturnsString(): void
    {
        $report = $this->analyzer->analyze(['App'], ['js'], ['css'], ['html']);
        $this->assertIsString($this->analyzer->render($report));
    }

    public function testRenderContainsComponentName(): void
    {
        $report = $this->analyzer->analyze(['MyApp'], ['js'], ['css'], ['html']);
        $this->assertStringContainsString('MyApp', $this->analyzer->render($report));
    }

    public function testRenderContainsTotalRow(): void
    {
        $report = $this->analyzer->analyze(['App'], ['js'], ['css'], ['html']);
        $this->assertStringContainsString('TOTAL', $this->analyzer->render($report));
    }

    public function testWriteJsonCreatesFile(): void
    {
        $dir    = sys_get_temp_dir() . '/nexph_analyze_' . uniqid();
        mkdir($dir, 0755, true);
        $report = $this->analyzer->analyze(['App'], ['js'], ['css'], ['html']);
        $path   = $this->analyzer->writeJson($report, $dir);
        $this->assertFileExists($path);
        $data = json_decode(file_get_contents($path), true);
        $this->assertIsArray($data);
        unlink($path);
        rmdir($dir);
    }

    public function testWriteJsonIsValidJson(): void
    {
        $dir    = sys_get_temp_dir() . '/nexph_analyze_' . uniqid();
        mkdir($dir, 0755, true);
        $report = $this->analyzer->analyze(['App'], ['js'], ['css'], ['html']);
        $path   = $this->analyzer->writeJson($report, $dir);
        $data   = json_decode(file_get_contents($path), true);
        $this->assertArrayHasKey('components', $data);
        $this->assertArrayHasKey('totals', $data);
        unlink($path);
        rmdir($dir);
    }
}
