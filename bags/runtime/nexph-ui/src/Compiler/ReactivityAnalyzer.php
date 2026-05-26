<?php

namespace Nexph\Compiler;

/**
 * Phase 1 + 2: Analyzes HTML to build a dependency map of state keys → DOM nodes,
 * and assigns stable data-nx-id attributes for direct DOM references.
 *
 * Output used by JsGenerator to emit fine-grained reactive updates
 * instead of broad querySelectorAll scans.
 */
class ReactivityAnalyzer
{
    private int $idCounter = 0;

    /**
     * Analyze compiled HTML and return:
     *   - 'html'    : HTML with data-nx-id injected on reactive nodes
     *   - 'deps'    : [ stateKey => [ [id, type, extra], ... ] ]
     *   - 'refs'    : [ nxId => refName ] for nx-ref nodes
     */
    public function analyze(string $html): array
    {
        $this->idCounter = 0;
        $deps = [];
        $refs = [];

        // Assign IDs and collect deps for bind nodes
        $html = preg_replace_callback(
            '/<span\s+data-nexph-bind="(\w+)"([^>]*)><\/span>/',
            function ($m) use (&$deps) {
                $key = $m[1];
                $id  = $this->nextId();
                $deps[$key][] = ['id' => $id, 'type' => 'text'];
                return '<span data-nexph-bind="' . $key . '" data-nx-id="' . $id . '"' . $m[2] . '></span>';
            },
            $html
        );

        // Assign IDs for ternary nodes
        $html = preg_replace_callback(
            '/<span\s+data-nexph-ternary="(\w+)"([^>]*)><\/span>/',
            function ($m) use (&$deps) {
                $key = $m[1];
                $id  = $this->nextId();
                $deps[$key][] = ['id' => $id, 'type' => 'ternary'];
                return '<span data-nexph-ternary="' . $key . '" data-nx-id="' . $id . '"' . $m[2] . '></span>';
            },
            $html
        );

        // Assign IDs for nx-if / nx-show nodes
        $html = preg_replace_callback(
            '/<(\w+)([^>]*)\s+data-nexph-(if|show)="([^"]+)"([^>]*)>/',
            function ($m) use (&$deps) {
                $tag       = $m[1];
                $pre       = $m[2];
                $directive = $m[3];
                $condition = $m[4];
                $post      = $m[5];
                $id        = $this->nextId();
                // extract simple state key from condition
                $keys = $this->extractKeys($condition);
                foreach ($keys as $key) {
                    $deps[$key][] = ['id' => $id, 'type' => $directive, 'condition' => $condition];
                }
                return '<' . $tag . $pre . ' data-nexph-' . $directive . '="' . $condition . '" data-nx-id="' . $id . '"' . $post . '>';
            },
            $html
        );

        // Assign IDs for nx-for nodes
        $html = preg_replace_callback(
            '/<(\w+)([^>]*)\s+data-nexph-for="(\w+):(\w+)"([^>]*)>/',
            function ($m) use (&$deps) {
                $tag      = $m[1];
                $pre      = $m[2];
                $item     = $m[3];
                $arrKey   = $m[4];
                $post     = $m[5];
                $id       = $this->nextId();
                $deps[$arrKey][] = ['id' => $id, 'type' => 'for', 'item' => $item];
                return '<' . $tag . $pre . ' data-nexph-for="' . $item . ':' . $arrKey . '" data-nx-id="' . $id . '"' . $post . '>';
            },
            $html
        );

        // Assign IDs for nx-class nodes — reuse existing data-nx-id if already set
        $html = preg_replace_callback(
            '/<(\w+)([^>]*)\s+data-nexph-class="([^"]+)"([^>]*)>/',
            function ($m) use (&$deps) {
                $tag   = $m[1];
                $pre   = $m[2];
                $expr  = $m[3];
                $post  = $m[4];
                // reuse existing id if element already has one (e.g. from nx-for pass)
                if (preg_match('/data-nx-id="(\d+)"/', $pre . $post, $existing)) {
                    $id = (int)$existing[1];
                } else {
                    $id = $this->nextId();
                    $post = ' data-nx-id="' . $id . '"' . $post;
                }
                $keys = $this->extractKeys($expr);
                foreach ($keys as $key) {
                    $deps[$key][] = ['id' => $id, 'type' => 'class', 'expr' => $expr];
                }
                return '<' . $tag . $pre . ' data-nexph-class="' . $expr . '"' . $post . '>';
            },
            $html
        );

        // Assign IDs for nx-model inputs (model → state key)
        $html = preg_replace_callback(
            '/<(\w+)([^>]*)\s+data-nexph-model="(\w+)"([^>]*)>/',
            function ($m) use (&$deps) {
                $tag  = $m[1];
                $pre  = $m[2];
                $key  = $m[3];
                $post = $m[4];
                $id   = $this->nextId();
                $deps[$key][] = ['id' => $id, 'type' => 'model'];
                return '<' . $tag . $pre . ' data-nexph-model="' . $key . '" data-nx-id="' . $id . '"' . $post . '>';
            },
            $html
        );

        // Collect nx-ref IDs
        $html = preg_replace_callback(
            '/<(\w+)([^>]*)\s+data-nexph-ref="(\w+)"([^>]*)>/',
            function ($m) use (&$refs) {
                $tag     = $m[1];
                $pre     = $m[2];
                $refName = $m[3];
                $post    = $m[4];
                $id      = $this->nextId();
                $refs[$id] = $refName;
                return '<' . $tag . $pre . ' data-nexph-ref="' . $refName . '" data-nx-id="' . $id . '"' . $post . '>';
            },
            $html
        );

        return [
            'html' => $html,
            'deps' => $deps,
            'refs' => $refs,
        ];
    }

    /**
     * Extract simple state key names from a JS-like expression.
     * e.g. "count > 0" → ['count'], "isOpen && items.length" → ['isOpen','items']
     */
    public function extractKeys(string $expr): array
    {
        $keys = [];
        // match bare identifiers not preceded by . (not property access)
        if (preg_match_all('/(?<![.\w])([a-zA-Z_]\w*)(?!\s*\()/', $expr, $m)) {
            $reserved = ['true','false','null','undefined','typeof','instanceof',
                         'new','return','if','else','function','var','let','const',
                         'length','index'];
            foreach ($m[1] as $k) {
                if (!in_array($k, $reserved) && !in_array($k, $keys)) {
                    $keys[] = $k;
                }
            }
        }
        return $keys;
    }

    private function nextId(): int
    {
        return ++$this->idCounter;
    }
}
