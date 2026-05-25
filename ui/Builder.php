<?php

namespace Nexph;

use Nexph\Compiler\Compiler;

class Builder
{
    private Compiler $compiler;
    private string $outputDir;

    public function __construct(string $outputDir = 'dist')
    {
        $this->compiler = new Compiler();
        $this->outputDir = $outputDir;
    }

    public function build(string $componentPath): void
    {
        if (!file_exists($componentPath)) {
            throw new \RuntimeException("Component file not found: {$componentPath}");
        }

        $source = file_get_contents($componentPath);
        $compiled = $this->compiler->compile($source);

        $this->ensureOutputDir();
        $this->writeAssets($compiled);
    }

    private function ensureOutputDir(): void
    {
        if (!is_dir($this->outputDir)) {
            mkdir($this->outputDir, 0755, true);
        }
    }

    private function writeAssets(array $compiled): void
    {
        file_put_contents("{$this->outputDir}/index.html", $this->wrapHtml($compiled['html']));
        file_put_contents("{$this->outputDir}/app.css", $compiled['css']);
        file_put_contents("{$this->outputDir}/app.js", $compiled['js']);
        file_put_contents("{$this->outputDir}/manifest.json", json_encode($compiled['manifest'], JSON_PRETTY_PRINT));
    }

    private function wrapHtml(string $body): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEXPH UI</title>
    <link rel="stylesheet" href="app.css">
</head>
<body>
    {$body}
    <script src="app.js"></script>
</body>
</html>
HTML;
    }
}
