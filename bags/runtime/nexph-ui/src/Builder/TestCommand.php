<?php

namespace Nexph\Builder;

class TestCommand
{
    private BuildPipeline $pipeline;

    public function __construct()
    {
        $this->pipeline = new BuildPipeline();
    }

    public function run(array $argv): int
    {
        $args    = array_slice($argv, 1);
        $dir     = $this->extractDir($args);
        $watch   = in_array('--watch', $args) || in_array('-w', $args);
        $verbose = in_array('--verbose', $args) || in_array('-v', $args);
        $filter  = $this->extractFilter($args);

        if (!$dir) $dir = 'tests/components';

        $files = $this->collectTests($dir, $filter);

        if (empty($files)) {
            $this->info("No component test files found in '{$dir}'.");
            $this->info("Create a .test.php file, e.g.: tests/components/Counter.test.php");
            return 0;
        }

        $this->info("NEXPH Component Test Runner");
        $this->info("  Found " . count($files) . " test file(s)\n");

        $result = $this->runSuite($files, $verbose);

        $this->printSummary($result);

        if ($watch) {
            $this->info("\nWatch mode — press Ctrl+C to stop");
            $watcher = new Watcher();
            $watcher->seed([$dir]);
            while (true) {
                usleep(500 * 1000);
                $changed = $watcher->check([$dir]);
                if (!empty($changed)) {
                    $this->info("\n[changed] " . implode(', ', array_map('basename', $changed)));
                    $files  = $this->collectTests($dir, $filter);
                    $result = $this->runSuite($files, $verbose);
                    $this->printSummary($result);
                }
            }
        }

        return $result['failed'] > 0 ? 1 : 0;
    }

    private function runSuite(array $files, bool $verbose): array
    {
        $passed = 0;
        $failed = 0;
        $errors = [];

        foreach ($files as $file) {
            $tests = $this->parseTestFile($file);
            $name  = basename($file, '.test.php');

            foreach ($tests as $test) {
                $res = $this->runTest($test, $verbose);
                if ($res['pass']) {
                    $passed++;
                    if ($verbose) $this->info("  ✔ [{$name}] " . $test['name']);
                } else {
                    $failed++;
                    $errors[] = "  ✗ [{$name}] " . $test['name'] . "\n    " . $res['message'];
                    $this->info("  ✗ [{$name}] " . $test['name']);
                    if ($verbose) $this->info("    " . $res['message']);
                }
            }
        }

        return ['passed' => $passed, 'failed' => $failed, 'errors' => $errors];
    }

    private function runTest(array $test, bool $verbose): array
    {
        try {
            $output = $this->pipeline->run($test['entry'], [
                'output'  => sys_get_temp_dir() . '/nexph_test_' . uniqid(),
                'minify'  => false,
                'hmrPort' => 0,
            ]);

            $html = file_get_contents($output['files']['html']);
            $js   = file_get_contents($output['files']['js']);
            $css  = file_get_contents($output['files']['css']);

            foreach ($test['assertions'] as $assertion) {
                $result = $this->assert($assertion, $html, $js, $css, $output);
                if (!$result['pass']) {
                    return $result;
                }
            }

            // cleanup
            $outDir = dirname($output['files']['html']);
            $this->rrmdir($outDir);

            return ['pass' => true, 'message' => ''];
        } catch (\Throwable $e) {
            return ['pass' => false, 'message' => 'Exception: ' . $e->getMessage()];
        }
    }

    private function assert(array $a, string $html, string $js, string $css, array $output): array
    {
        $type = $a['type'];
        $val  = $a['value'] ?? '';

        switch ($type) {
            case 'html_contains':
                if (!str_contains($html, $val)) {
                    return ['pass' => false, 'message' => "Expected HTML to contain: {$val}"];
                }
                break;
            case 'html_not_contains':
                if (str_contains($html, $val)) {
                    return ['pass' => false, 'message' => "Expected HTML NOT to contain: {$val}"];
                }
                break;
            case 'js_contains':
                if (!str_contains($js, $val)) {
                    return ['pass' => false, 'message' => "Expected JS to contain: {$val}"];
                }
                break;
            case 'css_contains':
                if (!str_contains($css, $val)) {
                    return ['pass' => false, 'message' => "Expected CSS to contain: {$val}"];
                }
                break;
            case 'component_exists':
                if (!str_contains($html, 'data-nexph-component="' . $val . '"')) {
                    return ['pass' => false, 'message' => "Expected component '{$val}' in HTML"];
                }
                break;
            case 'has_state':
                if (!str_contains($js, '"' . $val . '"')) {
                    return ['pass' => false, 'message' => "Expected state key '{$val}' in JS"];
                }
                break;
            case 'has_method':
                if (!str_contains($js, $val . ':')) {
                    return ['pass' => false, 'message' => "Expected method '{$val}' in JS"];
                }
                break;
            case 'build_success':
                if (!$output['success']) {
                    return ['pass' => false, 'message' => "Expected build to succeed"];
                }
                break;
            case 'js_size_lt':
                $size = strlen($js);
                if ($size >= (int)$val) {
                    return ['pass' => false, 'message' => "Expected JS size < {$val}, got {$size}"];
                }
                break;
            case 'css_size_lt':
                $size = strlen($css);
                if ($size >= (int)$val) {
                    return ['pass' => false, 'message' => "Expected CSS size < {$val}, got {$size}"];
                }
                break;
        }

        return ['pass' => true, 'message' => ''];
    }

    private function parseTestFile(string $file): array
    {
        $content = file_get_contents($file);
        $tests   = [];

        // parse nexph_test() calls
        if (!preg_match_all('/nexph_test\s*\(\s*([^,]+),\s*([^,]+),\s*\[(.*?)\]\s*\)/s', $content, $matches)) {
            return $tests;
        }

        foreach ($matches[1] as $i => $namePart) {
            $name  = trim(trim($namePart), "'\"");
            $entry = trim(trim($matches[2][$i]), "'\"");
            $assertStr = $matches[3][$i];

            $assertions = [];
            if (preg_match_all('/\'([^\']+)\'\s*=>\s*\'([^\']+)\'/', $assertStr, $am)) {
                foreach ($am[1] as $j => $aType) {
                    $assertions[] = ['type' => $aType, 'value' => $am[2][$j]];
                }
            }
            // bare 'build_success'
            if (preg_match_all('/\'(build_success)\'/', $assertStr, $bm)) {
                foreach ($bm[1] as $aType) {
                    $assertions[] = ['type' => $aType];
                }
            }

            $tests[] = ['name' => $name, 'entry' => $entry, 'assertions' => $assertions];
        }

        return $tests;
    }

    private function collectTests(string $dir, ?string $filter): array
    {
        if (!is_dir($dir)) return [];
        $files = [];
        $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->getExtension() === 'php' && str_ends_with($f->getFilename(), '.test.php')) {
                if (!$filter || str_contains($f->getFilename(), $filter)) {
                    $files[] = $f->getPathname();
                }
            }
        }
        sort($files);
        return $files;
    }

    private function extractDir(array $args): ?string
    {
        foreach ($args as $arg) {
            if (!str_starts_with($arg, '--') && $arg !== '-w' && $arg !== '-v') return $arg;
        }
        return null;
    }

    private function extractFilter(array $args): ?string
    {
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--filter=')) return substr($arg, 9);
        }
        return null;
    }

    private function printSummary(array $result): void
    {
        $this->info('');
        foreach ($result['errors'] as $e) $this->info($e);
        $total = $result['passed'] + $result['failed'];
        $status = $result['failed'] === 0 ? '✓ All tests passed' : '✗ ' . $result['failed'] . ' failed';
        $this->info("{$status} ({$result['passed']}/{$total})");
    }

    private function info(string $msg): void
    {
        echo $msg . "\n";
    }

    private function rrmdir(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (scandir($dir) as $f) {
            if ($f === '.' || $f === '..') continue;
            $p = $dir . '/' . $f;
            is_dir($p) ? $this->rrmdir($p) : unlink($p);
        }
        rmdir($dir);
    }
}
