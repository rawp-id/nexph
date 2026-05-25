<?php

namespace Nexph\Builder;

use Nexph\Compiler\Compiler;

/**
 * Generates fully self-contained static HTML — no external JS or CSS files.
 * Suitable for CDN deployment, email templates, or zero-dependency pages.
 */
class StaticExporter
{
    private Compiler $compiler;
    private DependencyResolver $dependencyResolver;
    private HtmlMinifier $htmlMinifier;
    private CssMinifier $cssMinifier;
    private JsMinifier $jsMinifier;
    private ManifestGenerator $manifestGenerator;

    public function __construct()
    {
        $this->compiler           = new Compiler();
        $this->dependencyResolver = new DependencyResolver();
        $this->htmlMinifier       = new HtmlMinifier();
        $this->cssMinifier        = new CssMinifier();
        $this->jsMinifier         = new JsMinifier();
        $this->manifestGenerator  = new ManifestGenerator();
    }

    public function export(string $entryFile, array $config = []): array
    {
        $outputDir = rtrim($config['output'] ?? 'dist/', '/');
        $minify    = $config['minify'] ?? false;
        $noJs      = $config['noJs'] ?? false;

        $files = $this->dependencyResolver->resolve($entryFile);
        if (empty($files)) {
            $files = [realpath($entryFile)];
        }

        $htmlParts  = [];
        $cssParts   = [];
        $jsParts    = [];
        $components = [];

        foreach ($files as $file) {
            $source = file_get_contents($file);
            $output = $this->compiler->compile($source);

            $htmlParts[]  = $output['html'];
            $cssParts[]   = $output['css'];
            $jsParts[]    = $output['js'];
            $components[] = $output['manifest']['component'] ?? basename($file, '.php');
        }

        $html = implode("\n", $htmlParts);
        $css  = implode("\n", $cssParts);
        $js   = implode("\n", $jsParts);

        if ($minify) {
            $html = $this->htmlMinifier->minify($html);
            $css  = $this->cssMinifier->minify($css);
            $js   = $this->jsMinifier->minify($js);
        }

        $page = $this->buildInlinePage($html, $css, $noJs ? '' : $js);

        $this->ensureDir($outputDir);
        file_put_contents("{$outputDir}/index.html", $page);

        $manifest = $this->manifestGenerator->generate([
            'entry'      => $entryFile,
            'assets'     => ['index.html' => 'index.html'],
            'components' => $components,
            'size'       => [
                'html'  => strlen($page),
                'js'    => $noJs ? 0 : strlen($js),
                'css'   => strlen($css),
                'total' => strlen($page),
            ],
        ]);

        $this->manifestGenerator->write($manifest, $outputDir);

        return [
            'success'   => true,
            'outputDir' => $outputDir,
            'files'     => [
                'html'     => "{$outputDir}/index.html",
                'manifest' => "{$outputDir}/manifest.json",
            ],
            'manifest'  => $manifest,
        ];
    }

    private function buildInlinePage(string $body, string $css, string $js): string
    {
        $styleBlock  = $css  ? "\n<style>\n{$css}\n</style>" : '';
        $scriptBlock = $js   ? "\n<script>\n{$js}\n</script>" : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEXPH App</title>{$styleBlock}
</head>
<body>
{$body}{$scriptBlock}
</body>
</html>
HTML;
    }

    private function ensureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}
