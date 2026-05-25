<?php

namespace Nexph\Builder;

class HtmlMinifier
{
    public function minify(string $html): string
    {
        // Remove HTML comments (preserve IE conditionals)
        $html = preg_replace('/<!--(?!\[if).*?-->/s', '', $html);

        // Collapse whitespace between tags
        $html = preg_replace('/>\s+</', '><', $html);

        // Collapse internal whitespace
        $html = preg_replace('/\s+/', ' ', $html);

        return trim($html);
    }
}
