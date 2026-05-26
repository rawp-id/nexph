<?php

namespace Nexph\Builder;

class CssMinifier
{
    public function minify(string $css): string
    {
        // Remove comments
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);

        // Collapse whitespace
        $css = preg_replace('/\s+/', ' ', $css);

        // Remove spaces around structural characters
        $css = preg_replace('/\s*([{}:;,>~+])\s*/', '$1', $css);

        // Remove trailing semicolons before closing brace
        $css = str_replace(';}', '}', $css);

        // Remove leading/trailing whitespace
        return trim($css);
    }
}
