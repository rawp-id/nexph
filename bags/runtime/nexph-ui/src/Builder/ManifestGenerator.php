<?php

namespace Nexph\Builder;

class ManifestGenerator
{
    public function generate(array $options): array
    {
        return [
            'version'   => '1.0.0',
            'buildTime' => date('c'),
            'entry'     => $options['entry'] ?? '',
            'assets'    => $options['assets'] ?? [],
            'components' => $options['components'] ?? [],
            'size'      => $options['size'] ?? [
                'html'  => 0,
                'js'    => 0,
                'css'   => 0,
                'total' => 0,
            ],
        ];
    }

    public function write(array $manifest, string $outputDir): void
    {
        $path = rtrim($outputDir, '/') . '/manifest.json';
        file_put_contents($path, json_encode($manifest, JSON_PRETTY_PRINT));
    }
}
