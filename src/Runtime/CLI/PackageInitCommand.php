<?php
namespace Core\Runtime\CLI;

class PackageInitCommand extends Command
{
    protected string $name = 'package:init';
    protected string $description = 'Initialize a new package manifest';

    public function execute(array $args = []): int
    {
        $parsed = $this->parseArgs($args);
        $dir = $parsed['arguments'][0] ?? '.';
        $json = isset($parsed['options']['json']);

        $targetDir = realpath($dir) ?: getcwd();
        $manifestPath = $targetDir . '/nexph.json';

        if (file_exists($manifestPath)) {
            $this->error("nexph.json already exists in {$targetDir}");
            return 1;
        }

        $name = $parsed['options']['name'] ?? basename($targetDir);
        $type = $parsed['options']['type'] ?? 'library';
        $version = $parsed['options']['version'] ?? '0.1.0';

        $manifest = [
            'name' => $name,
            'version' => $version,
            'type' => $type,
            'description' => '',
            'autoload' => [
                'psr-4' => [
                    $this->toNamespace($name) . '\\' => 'src/',
                ],
            ],
            'requires' => (object)[],
        ];

        $content = json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";

        if ($json) {
            $this->output($content);
            return 0;
        }

        if (!is_dir($targetDir . '/src')) {
            mkdir($targetDir . '/src', 0755, true);
        }

        file_put_contents($manifestPath, $content);
        $this->output("  ✓ Created {$manifestPath}");
        $this->output("  ✓ Created {$targetDir}/src/");
        $this->output("");
        $this->output("  name:    {$name}");
        $this->output("  version: {$version}");
        $this->output("  type:    {$type}");

        return 0;
    }

    private function toNamespace(string $name): string
    {
        $parts = explode('/', $name);
        $ns = array_map(function ($p) {
            return str_replace(['-', '_'], '', ucwords($p, '-_'));
        }, $parts);
        return implode('\\', $ns);
    }
}
