<?php

namespace Nexph\Compiler;

class Parser
{
    public function parse(string $source): array
    {
        $ast = [
            'type' => 'component',
            'class' => null,
            'properties' => [],
            'methods' => [],
            'computed' => [],
            'lifecycle' => [],
            'renderMethod' => '',
            'fullSource' => $source,
        ];

        $tokens = token_get_all($source);

        $ast['class'] = $this->extractClassName($tokens);
        $ast['properties'] = $this->extractProperties($source);
        $ast['renderMethod'] = $this->extractRenderMethod($source);
        $ast['methods'] = $this->extractMethods($source);
        $ast['computed'] = $this->extractComputed($source);
        $ast['lifecycle'] = $this->extractLifecycle($source);

        return $ast;
    }

    private function extractClassName(array $tokens): ?string
    {
        foreach ($tokens as $i => $token) {
            if (is_array($token) && $token[0] === T_CLASS) {
                for ($j = $i + 1; $j < count($tokens); $j++) {
                    if (is_array($tokens[$j]) && $tokens[$j][0] === T_STRING) {
                        return $tokens[$j][1];
                    }
                }
            }
        }
        return null;
    }
    
    private function extractProperties(string $source): array
    {
        $properties = [];
        
        if (preg_match_all('/^\s*public\s+(int|string|bool|float|array)\s+\$(\w+)\s*=\s*([^;]+);/m', $source, $matches)) {
            foreach ($matches[2] as $i => $varName) {
                $properties[$varName] = [
                    'type' => $matches[1][$i],
                    'default' => trim($matches[3][$i]),
                ];
            }
        }
        
        return $properties;
    }
    
    private function extractMethods(string $source): array
    {
        $methods = [];
        $skip    = ['render', 'style', '__construct', 'computed', 'onMount', 'onUpdate', 'onDestroy'];

        // find every "public function name(...)" then brace-balance to get body
        if (!preg_match_all(
            '/public\s+function\s+(\w+)\s*\([^)]*\)\s*(?::\s*\w+)?\s*\{/s',
            $source,
            $matches,
            PREG_OFFSET_CAPTURE
        )) {
            return $methods;
        }

        foreach ($matches[1] as $i => $nameMatch) {
            $name   = $nameMatch[0];
            $offset = $matches[0][$i][1]; // offset of the full match start

            if (in_array($name, $skip)) continue;

            // find opening brace position
            $bracePos = strpos($source, '{', $offset);
            if ($bracePos === false) continue;

            // brace-balance from opening brace
            $depth  = 1;
            $pos    = $bracePos + 1;
            $len    = strlen($source);
            $inStr  = false;
            $strCh  = '';

            while ($pos < $len && $depth > 0) {
                $ch = $source[$pos];

                if ($inStr) {
                    if ($ch === '\\') { $pos++; } // skip escaped char
                    elseif ($ch === $strCh) { $inStr = false; }
                } else {
                    if ($ch === '"' || $ch === "'") { $inStr = true; $strCh = $ch; }
                    elseif ($ch === '{') { $depth++; }
                    elseif ($ch === '}') { $depth--; }
                }
                $pos++;
            }

            $body = substr($source, $bracePos + 1, $pos - $bracePos - 2);
            $methods[$name] = $body;
        }

        return $methods;
    }

    private function extractComputed(string $source): array
    {
        $computed = [];

        if (preg_match('/public\s+function\s+computed\s*\(\)\s*(?::\s*array)?\s*\{\s*return\s*\[(.*?)\];/s', $source, $match)) {
            if (preg_match_all('/\'(\w+)\'\s*=>\s*fn\s*\(\)\s*=>\s*(.+?)(?:,\s*$|$)/m', $match[1], $entries)) {
                foreach ($entries[1] as $i => $name) {
                    $computed[$name] = trim($entries[2][$i]);
                }
            }
        }

        return $computed;
    }

    private function extractLifecycle(string $source): array
    {
        $hooks = [];
        $hookNames = ['onMount', 'onUpdate', 'onDestroy'];

        foreach ($hookNames as $hook) {
            if (preg_match('/public\s+function\s+' . $hook . '\s*\(\)\s*(?::\s*void)?\s*\{(.*?)\n\s{4}\}/s', $source, $match)) {
                $body = trim($match[1]);
                if (!empty($body)) {
                    $hooks[$hook] = $body;
                }
            }
        }

        return $hooks;
    }
    
    private function extractRenderMethod(string $source): string
    {
        if (preg_match('/public\s+function\s+render\s*\([^)]*\)\s*:\s*string\s*\{(.*)\}/s', $source, $matches)) {
            return $matches[1];
        }
        
        return '';
    }
}
