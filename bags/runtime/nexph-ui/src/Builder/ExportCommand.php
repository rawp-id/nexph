<?php

namespace Nexph\Builder;

class ExportCommand
{
    private StaticExporter $exporter;
    private ConfigLoader $configLoader;

    public function __construct()
    {
        $this->exporter     = new StaticExporter();
        $this->configLoader = new ConfigLoader();
    }

    public function run(array $argv): int
    {
        $args      = array_slice($argv, 1);
        $entryFile = $this->extractEntry($args);

        if (!$entryFile) {
            $this->error('Usage: nexph export <entry-file> [--output=dist/] [--minify] [--no-js]');
            return 1;
        }

        if (!file_exists($entryFile)) {
            $this->error("Entry file not found: {$entryFile}");
            return 1;
        }

        $config            = $this->configLoader->fromArgs($args);
        $config['entry']   = $entryFile;
        $config['noJs']    = in_array('--no-js', $args);

        $this->info("Exporting {$entryFile} [static]...");

        $start   = microtime(true);
        $result  = $this->exporter->export($entryFile, $config);
        $elapsed = round((microtime(true) - $start) * 1000);

        if (!$result['success']) {
            $this->error('Export failed.');
            return 1;
        }

        $size = $result['manifest']['size'];

        $this->info("✓ Exported in {$elapsed}ms");
        $this->info("  HTML : {$result['files']['html']} (" . $this->formatBytes($size['html']) . ')');
        $this->info("  Total: " . $this->formatBytes($size['total']));

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
