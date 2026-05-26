<?php

namespace Nexph\Builder;

class ConfigLoader
{
    private array $defaults = [
        'entry'      => 'src/App.php',
        'output'     => 'dist/',
        'minify'     => false,
        'sourcemap'  => false,
        'tailwind'   => false,
        'hmrPort'    => 0,
        'cssExtract' => true,
        'target'     => 'es2020',
        'compress'   => false,
        'pwa'        => false,
        'pwaOptions' => [
            'name'        => 'NEXPH App',
            'short_name'  => 'App',
            'theme_color' => '#6366f1',
            'bg_color'    => '#0f0f1a',
        ],
        'routeMode'  => 'hash',
        'spaFallback' => false,
        'analyze'    => false,
        'stores'     => [],
        'pages'      => [],
        'optimization' => [
            'treeshake'   => false,
            'splitChunks' => false,
            'compress'    => false,
        ],
        'devServer' => [
            'port' => 3000,
            'hot'  => false,
            'open' => false,
        ],
    ];

    public function load(string $configPath = 'nexph.config.php'): array
    {
        if (!file_exists($configPath)) {
            return $this->defaults;
        }

        $userConfig = require $configPath;

        if (!is_array($userConfig)) {
            return $this->defaults;
        }

        return $this->merge($this->defaults, $userConfig);
    }

    public function fromArgs(array $args): array
    {
        $config = $this->load();

        foreach ($args as $arg) {
            if ($arg === '--minify' || $arg === '--production') {
                $config['minify'] = true;
            }
            if ($arg === '--sourcemap') {
                $config['sourcemap'] = true;
            }
            if ($arg === '--tailwind') {
                $config['tailwind'] = true;
            }
            if (str_starts_with($arg, '--output=')) {
                $config['output'] = substr($arg, 9);
            }
            if ($arg === '--compress') {
                $config['compress'] = true;
            }
            if ($arg === '--pwa') {
                $config['pwa'] = true;
            }
            if (str_starts_with($arg, '--pwa-name=')) {
                $config['pwaOptions']['name'] = substr($arg, 11);
            }
            if (str_starts_with($arg, '--pwa-short=')) {
                $config['pwaOptions']['short_name'] = substr($arg, 12);
            }
            if (str_starts_with($arg, '--pwa-theme=')) {
                $config['pwaOptions']['theme_color'] = substr($arg, 12);
            }
            if (str_starts_with($arg, '--route-mode=')) {
                $config['routeMode'] = substr($arg, 13);
            }
            if ($arg === '--spa-fallback') {
                $config['spaFallback'] = true;
            }
            if ($arg === '--analyze') {
                $config['analyze'] = true;
            }
            if (str_starts_with($arg, '--port=')) {
                $config['devServer']['port'] = (int) substr($arg, 7);
            }
        }

        if (in_array('--production', $args)) {
            $config['optimization']['treeshake'] = true;
            $config['optimization']['compress']  = true;
        }

        return $config;
    }

    private function merge(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (is_array($value) && isset($base[$key]) && is_array($base[$key])) {
                $base[$key] = $this->merge($base[$key], $value);
            } else {
                $base[$key] = $value;
            }
        }

        return $base;
    }
}
