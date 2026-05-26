<?php

namespace Nexph\Compiler;

class StyleExtractor
{
    /**
     * Extract CSS from a component's style() method body in the AST source.
     * Returns empty string if no style() method exists.
     */
    public function extract(array $ast): string
    {
        $source = $ast['fullSource'] ?? '';

        if (empty($source)) {
            return '';
        }

        // Match: public function style(): string { return <<<CSS ... CSS; }
        if (preg_match('/public\s+function\s+style\s*\(\s*\)\s*(?::\s*string\s*)?\{.*?return\s+<<<CSS\s*(.*?)\s*CSS;\s*\}/s', $source, $matches)) {
            return trim($matches[1]);
        }

        // Match: public function style(): string { return '...'; }
        if (preg_match('/public\s+function\s+style\s*\(\s*\)\s*(?::\s*string\s*)?\{.*?return\s+["\'](.+?)["\']\s*;\s*\}/s', $source, $matches)) {
            return trim($matches[1]);
        }

        return '';
    }
}
