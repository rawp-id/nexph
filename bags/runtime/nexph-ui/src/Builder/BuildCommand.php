<?php

namespace Nexph\Builder;

class BuildCommand
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
        $args      = array_slice($argv, 1);
        $entryFile = $this->extractEntry($args);

        if (!$entryFile) {
            $this->error('Usage: nexph build <entry-file> [--output=dist/] [--minify] [--production]');
            return 1;
        }

        if (!file_exists($entryFile)) {
            $this->error("Entry file not found: {$entryFile}");
            return 1;
        }

        $config = $this->configLoader->fromArgs($args);
        $config['entry'] = $entryFile;

        $mode = in_array('--production', $args) ? 'production' : 'development';
        $this->info("Building {$entryFile} [{$mode}]...");

        $start  = microtime(true);
        $result = $this->pipeline->run($entryFile, $config);
        $elapsed = round((microtime(true) - $start) * 1000);

        if (!$result['success']) {
            $this->error('Build failed.');
            return 1;
        }

        $manifest = $result['manifest'];
        $size     = $manifest['size'];

        $this->info("✓ Built in {$elapsed}ms");
        $this->info("  HTML : {$result['files']['html']} (" . $this->formatBytes($size['html']) . ')');
        $this->info("  JS   : {$result['files']['js']} (" . $this->formatBytes($size['js']) . ')');
        $this->info("  CSS  : {$result['files']['css']} (" . $this->formatBytes($size['css']) . ')');
        $this->info("  Total: " . $this->formatBytes($size['total']));

        if (in_array('--compress', $args)) {
            $this->info('  GZ   : .gz sidecar files written');
        }
        if (in_array('--pwa', $args)) {
            $this->info("  PWA  : {$result['files']['pwa_manifest']}");
            $this->info("  SW   : {$result['files']['sw']}");
        }
        if (isset($result['files']['_redirects'])) {
            $this->info("  SPA  : {$result['files']['_redirects']}");
        }

        return 0;
    }

    private function extractEntry(array $args): ?string
    {
        foreach ($args as $arg) {
            if (!str_starts_with($arg, '--')) {
                return $arg;
            }
        }
        return null;
    }

    private function info(string $msg): void
    {
        echo $msg . PHP_EOL;
    }

    private function error(string $msg): void
    {
        fwrite(STDERR, "Error: {$msg}" . PHP_EOL);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}
