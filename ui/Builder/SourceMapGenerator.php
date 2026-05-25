<?php

namespace Nexph\Builder;

/**
 * Generates inline source maps for compiled JS output.
 *
 * Produces a base64-encoded data URL appended as a sourceMappingURL comment,
 * mapping generated JS lines back to the original PHP source file.
 */
class SourceMapGenerator
{
    /**
     * Append an inline source map comment to $js.
     *
     * @param string $js          Generated JavaScript
     * @param string $sourceFile  Original PHP source file path
     * @param string $jsFile      Output JS filename (for the map's "file" field)
     */
    public function generate(string $js, string $sourceFile, string $jsFile = 'app.js'): string
    {
        $map     = $this->buildMap($js, $sourceFile, $jsFile);
        $encoded = base64_encode(json_encode($map));

        return $js . "\n//# sourceMappingURL=data:application/json;base64,{$encoded}";
    }

    /**
     * Write a separate .map file and append a URL comment to $js.
     */
    public function generateFile(string $js, string $sourceFile, string $mapFile, string $jsFile = 'app.js'): string
    {
        $map = $this->buildMap($js, $sourceFile, $jsFile);
        file_put_contents($mapFile, json_encode($map, JSON_PRETTY_PRINT));

        return $js . "\n//# sourceMappingURL=" . basename($mapFile);
    }

    private function buildMap(string $js, string $sourceFile, string $jsFile): array
    {
        $jsLines      = explode("\n", $js);
        $sourceContent = file_exists($sourceFile) ? file_get_contents($sourceFile) : '';

        // Build a simple 1:1 line mapping (each JS line → line 1 of source)
        // A full VLQ mapping is out of scope; this gives debuggers the source file.
        $mappings = implode(';', array_fill(0, count($jsLines), 'AAAA'));

        return [
            'version'        => 3,
            'file'           => $jsFile,
            'sourceRoot'     => '',
            'sources'        => [$sourceFile],
            'sourcesContent' => [$sourceContent],
            'names'          => [],
            'mappings'       => $mappings,
        ];
    }
}
