<?php

namespace Nexph\Builder;

class PagesCommand
{
    private BuildPipeline $pipeline;
    private ConfigLoader $configLoader;

    public function __construct()
    {
        $this->pipeline     = new BuildPipeline();
        $this->configLoader = new ConfigLoader();
    }

    public function run(array $argv): int
    {
        $args    = array_slice($argv, 1);
        $config  = $this->configLoader->fromArgs($args);
        $output  = $config['output'] ?? 'dist/';
        $pages   = $this->resolvePagesConfig($args, $config);

        if (empty($pages)) {
            $this->error('No pages found. Use --pages=src/Home.php,src/About.php or define pages[] in nexph.config.php');
            return 1;
        }

        $this->info('Building ' . count($pages) . ' page(s)...');

        $total   = ['html' => 0, 'js' => 0, 'css' => 0, 'total' => 0];
        $failed  = 0;
        $start   = microtime(true);

        foreach ($pages as $slug => $entry) {
            if (!file_exists($entry)) {
                $this->error("  ✗ [{$slug}] Entry not found: {$entry}");
                $failed++;
                continue;
            }

            $pageOutput = rtrim($output, '/') . ($slug === 'index' ? '' : '/' . $slug);
            $pageConfig = array_merge($config, ['output' => $pageOutput, 'hmrPort' => 0]);

            $t0 = microtime(true);
            try {
                $result  = $this->pipeline->run($entry, $pageConfig);
                $elapsed = round((microtime(true) - $t0) * 1000);

                if (!$result['success']) {
                    $this->error("  ✗ [{$slug}] Build failed");
                    $failed++;
                    continue;
                }

                $size = $result['manifest']['size'];
                $total['html']  += $size['html'];
                $total['js']    += $size['js'];
                $total['css']   += $size['css'];
                $total['total'] += $size['total'];

                $this->info(sprintf(
                    '  ✓ %-20s → %s  (%s, %dms)',
                    $slug,
                    $pageOutput,
                    $this->formatBytes($size['total']),
                    $elapsed
                ));
            } catch (\Throwable $e) {
                $this->error("  ✗ [{$slug}] " . $e->getMessage());
                $failed++;
            }
        }

        $elapsed = round((microtime(true) - $start) * 1000);
        $built   = count($pages) - $failed;

        $this->info('');
        $this->info("Built {$built}/" . count($pages) . " pages in {$elapsed}ms");
        $this->info('  Total size: ' . $this->formatBytes($total['total']));

        if ($failed > 0) {
            $this->error("{$failed} page(s) failed.");
            return 1;
        }

        // write pages manifest
        $this->writePagesManifest($output, $pages, $total);

        return 0;
    }

    private function resolvePagesConfig(array $args, array $config): array
    {
        // --pages=src/Home.php,src/About.php
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--pages=')) {
                $entries = explode(',', substr($arg, 8));
                $pages   = [];
                foreach ($entries as $entry) {
                    $entry = trim($entry);
                    $slug  = $this->entryToSlug($entry);
                    $pages[$slug] = $entry;
                }
                return $pages;
            }
        }

        // pages[] in nexph.config.php
        if (!empty($config['pages']) && is_array($config['pages'])) {
            $pages = [];
            foreach ($config['pages'] as $slug => $entry) {
                if (is_int($slug)) {
                    $slug = $this->entryToSlug($entry);
                }
                $pages[$slug] = $entry;
            }
            return $pages;
        }

        // auto-discover src/*.php or examples/*.php
        $dirs = ['src', 'examples'];
        foreach ($dirs as $dir) {
            if (!is_dir($dir)) continue;
            $files = glob("{$dir}/*.php");
            if (!empty($files)) {
                $pages = [];
                foreach ($files as $f) {
                    $slug = $this->entryToSlug($f);
                    $pages[$slug] = $f;
                }
                return $pages;
            }
        }

        return [];
    }

    private function entryToSlug(string $entry): string
    {
        $base = strtolower(basename($entry, '.php'));
        return $base === 'app' ? 'index' : $base;
    }

    private function writePagesManifest(string $outputDir, array $pages, array $total): void
    {
        $manifest = [
            'builtAt' => date('c'),
            'pages'   => array_map(fn($e) => ['entry' => $e], $pages),
            'total'   => $total,
        ];
        $path = rtrim($outputDir, '/') . '/pages-manifest.json';
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0755, true);
        file_put_contents($path, json_encode($manifest, JSON_PRETTY_PRINT));
        $this->info('  Manifest: ' . $path);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) return $bytes . ' B';
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1024 / 1024, 2) . ' MB';
    }

    private function info(string $msg): void  { echo $msg . "\n"; }
    private function error(string $msg): void { fwrite(STDERR, $msg . "\n"); }
}
