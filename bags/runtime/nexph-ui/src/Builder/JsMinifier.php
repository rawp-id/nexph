<?php

namespace Nexph\Builder;

class JsMinifier
{
    public function minify(string $js): string
    {
        // Remove single-line comments (not inside strings)
        $js = preg_replace('/(?<!:)\/\/[^\n]*/', '', $js);

        // Remove multi-line comments
        $js = preg_replace('/\/\*.*?\*\//s', '', $js);

        // Collapse whitespace
        $js = preg_replace('/\s+/', ' ', $js);

        // Remove spaces around operators and punctuation
        $js = preg_replace('/\s*([{}();,=+\-*\/<>!&|?:])\s*/', '$1', $js);

        // Remove spaces around brackets
        $js = preg_replace('/\s*([\[\]])\s*/', '$1', $js);

        return trim($js);
    }
}
