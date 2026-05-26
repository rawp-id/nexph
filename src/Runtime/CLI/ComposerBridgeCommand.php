<?php
namespace Core\Runtime\CLI;

class ComposerBridgeCommand extends Command
{
    protected string $name = 'composer:bridge';
    protected string $description = 'Show Composer bridge status and diagnostics';

    public function execute(array $args = []): int
    {
        $parsed = $this->parseArgs($args);
        $json = isset($parsed['options']['json']);

        $root = defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 3);

        $status = [
            'composer_json' => file_exists($root . '/composer.json'),
            'vendor_autoload' => file_exists($root . '/vendor/autoload.php'),
            'composer_binary' => $this->findComposerBinary(),
            'mode' => 'disabled',
            'packages' => [],
        ];

        if ($status['composer_json'] && $status['vendor_autoload']) {
            $status['mode'] = 'auto';
        }

        // Parse installed Composer packages
        $installedPath = $root . '/vendor/composer/installed.json';
        if (file_exists($installedPath)) {
            $installed = json_decode(file_get_contents($installedPath), true);
            $packages = $installed['packages'] ?? $installed ?? [];
            foreach ($packages as $pkg) {
                $status['packages'][] = [
                    'name' => $pkg['name'] ?? 'unknown',
                    'version' => $pkg['version'] ?? 'unknown',
                ];
            }
        }

        if ($json) {
            $this->output(json_encode($status, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return 0;
        }

        $this->output("Composer Bridge Status");
        $this->output("  Mode:            {$status['mode']}");
        $this->output("  composer.json:   " . ($status['composer_json'] ? '✓' : '✗'));
        $this->output("  vendor/autoload: " . ($status['vendor_autoload'] ? '✓' : '✗'));
        $this->output("  composer binary: " . ($status['composer_binary'] ?: 'not found'));

        if (!empty($status['packages'])) {
            $this->output("\n  Composer packages (" . count($status['packages']) . "):");
            foreach ($status['packages'] as $pkg) {
                $this->output("    {$pkg['name']} ({$pkg['version']})");
            }
        }

        return 0;
    }

    private function findComposerBinary(): ?string
    {
        $paths = ['composer', 'composer.phar', '/usr/local/bin/composer'];
        foreach ($paths as $bin) {
            exec("which {$bin} 2>/dev/null", $output, $code);
            if ($code === 0 && !empty($output)) {
                return trim($output[0]);
            }
            $output = [];
        }
        return null;
    }
}
