<?php

namespace Nexph\Compiler;

class HtmlGenerator
{
    public function generate(array $ast, string $scopeId = ''): string
    {
        $componentName = $ast['class'] ?? 'unknown';
        $renderMethod  = $ast['renderMethod'] ?? '';

        $scopeAttr = $scopeId ? ' data-nx-scope="' . $scopeId . '"' : '';

        if (empty($renderMethod)) {
            return '<div data-nexph-component="' . $componentName . '"' . $scopeAttr . '></div>';
        }

        $html = $this->extractHtmlFromRender($renderMethod);
        $html = $this->processBindings($html);
        $html = $this->processConditionals($html);
        $html = $this->processLoops($html);

        return '<div data-nexph-component="' . $componentName . '"' . $scopeAttr . '>' . $html . '</div>';
    }
    
    private function extractHtmlFromRender(string $renderMethod): string
    {
        if (preg_match('/return\s+<<<HTML\s*(.*?)\s*HTML;/s', $renderMethod, $matches)) {
            return trim($matches[1]);
        }
        
        if (preg_match('/return\s+["\'](.+?)["\']/s', $renderMethod, $matches)) {
            return $matches[1];
        }
        
        return '';
    }
    
    private function processBindings(string $html): string
    {
        $html = preg_replace('/\{(\$this->(\w+))\s*\?\s*[^}]+\s*:\s*[^}]+\}/', '<span data-nexph-ternary="$2"></span>', $html);
        $html = preg_replace('/\{(\$this->(\w+))\}/', '<span data-nexph-bind="$2"></span>', $html);
        $html = preg_replace('/nx-click="(\w+)"/', 'data-nexph-click="$1"', $html);
        $html = preg_replace('/nx-input="(\w+)"/', 'data-nexph-input="$1"', $html);
        $html = preg_replace('/nx-submit="(\w+)"/', 'data-nexph-submit="$1"', $html);
        $html = preg_replace('/nx-submit\.prevent="(\w+)"/', 'data-nexph-submit="$1" data-nexph-prevent', $html);
        $html = preg_replace('/nx-model="(\w+)"/', 'data-nexph-model="$1"', $html);
        $html = preg_replace('/nx-keydown="(\w+)"/', 'data-nexph-keydown="$1"', $html);
        $html = preg_replace('/nx-keyup="(\w+)"/', 'data-nexph-keyup="$1"', $html);
        $html = preg_replace('/nx-keydown\.enter="(\w+)"/', 'data-nexph-keydown="$1" data-nexph-key="Enter"', $html);
        $html = preg_replace('/nx-class="([^"]+)"/', 'data-nexph-class="$1"', $html);
        $html = preg_replace('/nx-style="([^"]+)"/', 'data-nexph-style="$1"', $html);
        $html = preg_replace('/nx-ref="(\w+)"/', 'data-nexph-ref="$1"', $html);
        $html = preg_replace('/nx-transition="([^"]+)"/', 'data-nexph-transition="$1"', $html);
        $html = preg_replace('/nx-fetch="([^"]+)"/', 'data-nexph-fetch="$1"', $html);
        $html = preg_replace('/nx-route="([^"]+)"/', 'data-nexph-route="$1"', $html);
        $html = preg_replace('/nx-link="([^"]+)"/', 'data-nexph-link="$1"', $html);
        $html = preg_replace('/nx-outlet/', 'data-nexph-outlet', $html);
        $html = preg_replace('/nx-animate="([^"]+)"/', 'data-nexph-animate="$1"', $html);
        $html = preg_replace('/nx-animate-scroll="([^"]+)"/', 'data-nexph-animate-scroll="$1"', $html);
        $html = preg_replace('/nx-animate-leave="([^"]+)"/', 'data-nexph-animate-leave="$1"', $html);
        $html = preg_replace('/nx-animate-repeat="([^"]+)"/', 'data-nexph-animate-repeat="$1"', $html);
        $html = preg_replace('/nx-portal="([^"]+)"/', 'data-nexph-portal="$1"', $html);
        $html = preg_replace('/(?<=\s)nx-portal(?=[\s>\/])/', 'data-nexph-portal="body"', $html);
        $html = preg_replace('/nx-lazy="([^"]+)"/', 'data-nexph-lazy="$1"', $html);
        $html = preg_replace('/nx-validate="([^"]+)"/', 'data-nexph-validate="$1"', $html);
        $html = preg_replace('/nx-validate-form/', 'data-nexph-validate-form', $html);
        $html = preg_replace('/nx-error="([^"]+)"/', 'data-nexph-error="$1"', $html);
        $html = preg_replace('/nx-store-bind="([^"]+)"/', 'data-nexph-store-bind="$1"', $html);
        $html = preg_replace('/nx-store-model="([^"]+)"/', 'data-nexph-store-model="$1"', $html);
        $html = preg_replace('/nx-store-action="([^"]+)"/', 'data-nexph-store-action="$1"', $html);
        $html = preg_replace('/:([a-z][a-zA-Z0-9]*)="([^"]+)"/', 'data-nexph-prop-$1="$2"', $html);
        $html = preg_replace('/@([a-z][a-zA-Z0-9]*)="([^"]+)"/', 'data-nexph-event-$1="$2"', $html);
        
        // unescape \$ → $ in loop item expressions
        $html = str_replace('{\$', '{$', $html);

        return $html;
    }

    private function processConditionals(string $html): string
    {
        $html = preg_replace('/<(\w+)([^>]*)\s+nx-if="([^"]+)"([^>]*)>/', '<$1$2 data-nexph-if="$3"$4 style="display:none">', $html);
        $html = preg_replace('/<(\w+)([^>]*)\s+nx-show="([^"]+)"([^>]*)>/', '<$1$2 data-nexph-show="$3"$4 style="display:none">', $html);

        return $html;
    }

    private function processLoops(string $html): string
    {
        $html = preg_replace('/<(\w+)([^>]*)\s+nx-for="(\w+)\s+in\s+(\w+)"([^>]*)>/', '<$1$2 data-nexph-for="$3:$4"$5 style="display:none">', $html);

        return $html;
    }
}
