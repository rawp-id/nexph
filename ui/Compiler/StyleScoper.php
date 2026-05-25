<?php

namespace Nexph\Compiler;

class StyleScoper
{
    /**
     * Generate a deterministic 6-char scope ID from a component name.
     */
    public function scopeId(string $componentName): string
    {
        return 'nx-' . substr(md5($componentName), 0, 6);
    }

    /**
     * Prefix every CSS selector in $css with the scope attribute selector,
     * so styles are isolated to the component's root element.
     *
     * e.g. `.btn { }` → `.btn[data-nx-scope="nx-a1b2c3"] { }`
     */
    public function scope(string $css, string $scopeId): string
    {
        if (empty(trim($css))) {
            return '';
        }

        $scoped = $this->scopeBlock($css, $scopeId);
        return $scoped;
    }

    private function scopeBlock(string $css, string $scopeId): string
    {
        $result = '';
        $len    = strlen($css);
        $i      = 0;

        while ($i < $len) {
            // skip whitespace
            $start = $i;

            // find next { or end
            $brace = strpos($css, '{', $i);
            if ($brace === false) break;

            $selector = trim(substr($css, $i, $brace - $i));
            $i        = $brace + 1;

            // find matching closing brace
            $depth  = 1;
            $body   = '';
            while ($i < $len && $depth > 0) {
                if ($css[$i] === '{') $depth++;
                elseif ($css[$i] === '}') { $depth--; if ($depth === 0) { $i++; break; } }
                $body .= $css[$i];
                $i++;
            }

            if (empty(trim($selector))) continue;

            // at-rule with nested block (@media, @supports, @keyframes, etc.)
            if (str_starts_with(ltrim($selector), '@')) {
                if (preg_match('/^@keyframes\b/i', ltrim($selector))) {
                    // keyframes — no selector scoping inside
                    $result .= $selector . ' {' . $body . "} ";
                } else {
                    // scope inner rules recursively
                    $inner   = $this->scopeBlock($body, $scopeId);
                    $result .= $selector . ' {' . $inner . "} ";
                }
            } else {
                $scoped  = $this->scopeSelectors($selector, $scopeId);
                $result .= $scoped . ' {' . $body . "} ";
            }
        }

        return $result;
    }

    private function scopeSelectors(string $selectorBlock, string $scopeId): string
    {
        $attr      = '[data-nx-scope="' . $scopeId . '"]';
        $selectors = array_map('trim', explode(',', $selectorBlock));
        $scoped    = [];

        foreach ($selectors as $selector) {
            if (empty($selector)) {
                continue;
            }

            if (preg_match('/^(body|html|:root)\b/i', $selector)) {
                $scoped[] = $selector;
                continue;
            }

            $scoped[] = $attr . ' ' . $selector;
        }

        return implode(', ', $scoped);
    }
}
