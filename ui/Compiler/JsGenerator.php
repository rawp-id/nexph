<?php

namespace Nexph\Compiler;

/**
 * Phase 1+2: Fine-grained reactivity + compiler-generated direct DOM refs.
 * Phase 3: Keyed nx-for diffing.
 * Phase 6: Microtask scheduler — batches state updates, one flush per tick.
 */
class JsGenerator
{
    public function generate(array $ast, array $deps = [], array $nodeRefs = []): string
    {
        $componentName = $ast['class'] ?? 'Unknown';
        $properties    = $ast['properties'] ?? [];
        $methods       = $ast['methods'] ?? [];
        $computed      = $ast['computed'] ?? [];
        $lifecycle     = $ast['lifecycle'] ?? [];

        $stateJs     = $this->generateStateJs($properties);
        $methodsJs   = $this->generateMethodsJs($methods);
        $computedJs  = $this->generateComputedJs($computed);
        $onMountJs   = isset($lifecycle['onMount'])   ? $this->convertPhpToJs($lifecycle['onMount'])   : '';
        $onDestroyJs = isset($lifecycle['onDestroy']) ? $this->convertPhpToJs($lifecycle['onDestroy']) : '';
        $depsJs      = $this->generateDepsJs($deps);
        $nodeRefsJs  = $this->generateNodeRefsJs($nodeRefs);

        // Use string concatenation to avoid PHP parsing JS arrow functions inside heredoc
        $head = '(function() {' . "\n"
            . '    const component = document.querySelector(\'[data-nexph-component="' . $componentName . '"]\');' . "\n"
            . '    if (!component) return;' . "\n"
            . '    const _nodeRefs = {};' . "\n"
            . '    function _initRefs() {' . "\n"
            . '        ' . $nodeRefsJs . "\n"
            . '    }' . "\n"
            . '    const _deps = ' . $depsJs . ';' . "\n";

        $body = <<<'JS'
    // ── Phase 6: microtask scheduler ──────────────────────────────────────────
    let _scheduled = false;
    const _dirty   = new Set();

    function _scheduleFlush(key) {
        _dirty.add(key);
        if (!_scheduled) {
            _scheduled = true;
            Promise.resolve().then(_flush);
        }
    }

    function _flush() {
        _scheduled = false;
        const keys = Array.from(_dirty);
        _dirty.clear();
        keys.forEach(trigger);
    }

JS;

        $stateBlock = '    const _raw = ' . $stateJs . ';' . "\n\n"
            . <<<'JS'
    // wrap arrays so .push/.splice/.pop etc. trigger reactivity
    function _reactiveArray(arr, key) {
        var mutators = ['push','pop','shift','unshift','splice','sort','reverse','fill'];
        var proxy = new Proxy(arr, {
            get(target, prop) {
                if (mutators.indexOf(prop) !== -1) {
                    return function() {
                        var result = Array.prototype[prop].apply(target, arguments);
                        _scheduleFlush(key);
                        return result;
                    };
                }
                return target[prop];
            },
            set(target, prop, value) {
                target[prop] = value;
                if (prop !== 'length') _scheduleFlush(key);
                return true;
            }
        });
        return proxy;
    }

    // wrap all initial array values
    Object.keys(_raw).forEach(function(k) {
        if (Array.isArray(_raw[k])) _raw[k] = _reactiveArray(_raw[k], k);
    });

    const state = new Proxy(_raw, {
        set(target, key, value) {
            if (Array.isArray(value)) value = _reactiveArray(value, key);
            if (target[key] === value) return true;
            target[key] = value;
            _scheduleFlush(key);
            return true;
        },
        get(target, key) { return target[key]; }
    });

    // ── Phase 1: trigger — patch only dependent nodes ─────────────────────────
    // querySelectorAll so cloned route views in outlet are also patched
    function _resolveEls(id) {
        var els = Array.from(document.querySelectorAll('[data-nx-id="' + id + '"]'));
        // exclude elements inside hidden route views (originals)
        var valid = els.filter(function(el) {
            var route = el.closest('[data-nexph-route]');
            if (route && route.style.display === 'none') return false;
            return true;
        });
        return valid.length ? valid : els;
    }

    function trigger(key) {
        const nodes = _deps[key];
        if (!nodes) return;
        nodes.forEach(function(dep) {
            var els = _resolveEls(dep.id);
            els.forEach(function(el) {
                if (dep.type === 'text') {
                    el.textContent = _raw[key] !== undefined ? _raw[key] : '';
                } else if (dep.type === 'ternary') {
                    el.textContent = _raw[key] ? dep.trueVal : dep.falseVal;
                } else if (dep.type === 'if' || dep.type === 'show') {
                    el.style.display = evalCondition(dep.condition) ? '' : 'none';
                } else if (dep.type === 'for') {
                    patchLoop(el, dep.item, key);
                } else if (dep.type === 'class') {
                    applyClass(el, dep.expr);
                } else if (dep.type === 'model') {
                    if (document.activeElement !== el) el.value = _raw[key] !== undefined ? _raw[key] : '';
                }
            });
        });
        _updateComputedFor(key);
    }

    function updateUI() {
        Object.keys(_deps).forEach(trigger);
        updateStyles();
        applyTransitions();
    }

    function evalCondition(condition) {
        try {
            if (/^\w+$/.test(condition)) {
                const value = _raw[condition];
                return Array.isArray(value) ? value.length > 0 : !!value;
            }
            return !!(new Function('state', 'with(state){return (' + condition + ')}')(_raw));
        } catch(e) { return false; }
    }

    function isInHiddenRoute(el) {
        const route = el.closest('[data-nexph-route]');
        return route && route.style.display === 'none';
    }

    // ── Phase 3: keyed loop diffing ───────────────────────────────────────────
    function patchLoop(template, itemName, arrayName) {
        if (isInHiddenRoute(template)) return;
        const items = _raw[arrayName] || [];
        if (!template._nexphParent) {
            template._nexphParent   = template.parentNode;
            template._nexphTemplate = template.cloneNode(true);
            template.style.display  = 'none';
        }
        const parent  = template._nexphParent;
        const useKey  = template.hasAttribute('data-nexph-key');
        const keyExpr = useKey ? template.getAttribute('data-nexph-key') : null;
        const existing = Array.from(parent.querySelectorAll('[data-nexph-item]'));
        if (useKey && keyExpr) {
            _keyedDiff(parent, template, existing, items, itemName, keyExpr);
        } else {
            _simplePatch(parent, template, existing, items, itemName);
        }
    }

    function _keyedDiff(parent, template, existing, items, itemName, keyExpr) {
        function getKey(item, index) {
            try { return new Function(itemName, 'index', 'return (' + keyExpr + ')')(item, index); }
            catch(e) { return index; }
        }
        const existingMap = {};
        existing.forEach(function(el) { existingMap[el.getAttribute('data-nexph-key-val')] = el; });
        const newKeys = items.map(function(item, i) { return String(getKey(item, i)); });
        const newSet  = new Set(newKeys);
        Object.keys(existingMap).forEach(function(k) { if (!newSet.has(k)) existingMap[k].remove(); });
        items.forEach(function(item, index) {
            const k  = String(getKey(item, index));
            const el = existingMap[k];
            if (el) {
                _patchItemText(el, item, itemName, index);
                el.setAttribute('data-nexph-item', index);
            } else {
                const clone = _buildItem(template, item, itemName, index);
                clone.setAttribute('data-nexph-key-val', k);
                parent.insertBefore(clone, template);
            }
        });
    }

    function _simplePatch(parent, template, existing, items, itemName) {
        items.forEach(function(item, index) {
            if (existing[index]) {
                _patchItemText(existing[index], item, itemName, index);
                existing[index].setAttribute('data-nexph-item', index);
            } else {
                parent.insertBefore(_buildItem(template, item, itemName, index), template);
            }
        });
        for (var i = items.length; i < existing.length; i++) existing[i].remove();
    }

    function _buildItem(template, item, itemName, index) {
        const clone = template._nexphTemplate.cloneNode(true);
        clone.removeAttribute('data-nexph-for');
        clone.removeAttribute('data-nexph-key');
        clone.removeAttribute('data-nx-id');
        clone.querySelectorAll('[data-nx-id]').forEach(function(e) { e.removeAttribute('data-nx-id'); });
        clone.setAttribute('data-nexph-item', index);
        clone.style.display = '';
        _patchItemText(clone, item, itemName, index);
        _applyItemClasses(clone, item, itemName, index);
        return clone;
    }

    function _patchItemText(el, item, itemName, index) {
        // patch text nodes
        const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
        let node;
        while ((node = walker.nextNode())) {
            let t = node._nxOriginal || node.textContent;
            node._nxOriginal = t;
            t = t.replace(new RegExp('\\{\\$' + itemName + '\\.([a-zA-Z]+)\\}', 'g'),
                function(_, prop) { return typeof item === 'object' ? (item[prop] !== undefined ? item[prop] : '') : ''; });
            t = t.replace(new RegExp('\\{\\$' + itemName + '\\}', 'g'),
                typeof item === 'object' ? JSON.stringify(item) : item);
            t = t.replace(/\{\$index\}/g, index);
            node.textContent = t;
        }
        // patch data-index attributes on el and descendants
        var allEls = [el].concat(Array.from(el.querySelectorAll('[data-index]')));
        allEls.forEach(function(e) {
            var v = e.getAttribute('data-index');
            if (v && v.indexOf('{$index}') !== -1) {
                e.setAttribute('data-index', String(index));
            }
        });
    }

    function _applyItemClasses(clone, item, itemName, index) {
        var els = [clone].concat(Array.from(clone.querySelectorAll('[data-nexph-class]')));
        els.forEach(function(el) {
            if (!el.hasAttribute('data-nexph-class')) return;
            const classExpr = el.getAttribute('data-nexph-class');
            try {
                const ctx      = Object.assign({}, _raw, { index: index });
                ctx[itemName]  = item;
                const classObj = new Function('ctx', 'with(ctx){return (' + classExpr + ')}')(ctx);
                Object.keys(classObj).forEach(function(cls) {
                    classObj[cls] ? el.classList.add(cls) : el.classList.remove(cls);
                });
            } catch(e) { console.error('nx-class loop error:', e.message); }
            el.removeAttribute('data-nexph-class');
        });
    }

    function applyClass(el, classExpr) {
        if (/\b\w+\s*\./.test(classExpr)) return;
        try {
            const classObj = new Function('state', 'with(state){return (' + classExpr + ')}')(_raw);
            Object.keys(classObj).forEach(function(cls) {
                classObj[cls] ? el.classList.add(cls) : el.classList.remove(cls);
            });
        } catch(e) { console.error('nx-class error:', e.message); }
    }

    function updateStyles() {
        component.querySelectorAll('[data-nexph-style]').forEach(function(el) {
            const styleExpr = el.getAttribute('data-nexph-style');
            try {
                const styleObj = eval('(' + styleExpr + ')');
                Object.keys(styleObj).forEach(function(prop) {
                    const val = styleObj[prop];
                    el.style[prop] = (typeof val === 'string' && _raw[val] !== undefined) ? _raw[val] : val;
                });
            } catch(e) { console.error('nx-style error:', e); }
        });
    }

    const _computedDeps = {};
JS;

        $computedBlock = "\n    function _updateComputedFor(name) {\n        " . $computedJs . "\n    }\n";

        $emitBlock = '    function emit(event, data) {' . "\n"
            . '        component.dispatchEvent(new CustomEvent(\'nexph:\' + event, {' . "\n"
            . '            detail: { componentName: \'' . $componentName . '\', data },' . "\n"
            . '            bubbles: true,' . "\n"
            . '        }));' . "\n"
            . '    }' . "\n";

        $methodsBlock = '    const methods = {' . "\n"
            . '        ' . $methodsJs . "\n"
            . '    };' . "\n";

        $events = <<<'JS'

    component.addEventListener('click', function(e) {
        const el = e.target.closest('[data-nexph-click]');
        if (!el || !component.contains(el)) return;
        const name = el.getAttribute('data-nexph-click');
        if (methods[name]) {
            const idx = el.getAttribute('data-index');
            methods[name](idx !== null ? parseInt(idx) : null, e);
        }
    });

    component.addEventListener('submit', function(e) {
        const el = e.target.closest('[data-nexph-submit]');
        if (!el || !component.contains(el)) return;
        if (el.hasAttribute('data-nexph-prevent')) e.preventDefault();
        const name = el.getAttribute('data-nexph-submit');
        if (methods[name]) methods[name](e);
    });

    component.addEventListener('input', function(e) {
        const el = e.target.closest('[data-nexph-model]');
        if (!el || !component.contains(el)) return;
        state[el.getAttribute('data-nexph-model')] = e.target.value;
    });

    component.addEventListener('input', function(e) {
        const el = e.target.closest('[data-nexph-input]');
        if (!el || !component.contains(el)) return;
        const name = el.getAttribute('data-nexph-input');
        if (methods[name]) methods[name](e);
    });

    component.addEventListener('keydown', function(e) {
        const el = e.target.closest('[data-nexph-keydown]');
        if (!el || !component.contains(el)) return;
        const key = el.getAttribute('data-nexph-key');
        if (!key || e.key === key) {
            const name = el.getAttribute('data-nexph-keydown');
            if (methods[name]) methods[name](e);
        }
    });

    component.addEventListener('keyup', function(e) {
        const el = e.target.closest('[data-nexph-keyup]');
        if (!el || !component.contains(el)) return;
        const name = el.getAttribute('data-nexph-keyup');
        if (methods[name]) methods[name](e);
    });

    component.querySelectorAll('*').forEach(function(el) {
        Array.from(el.attributes).forEach(function(attr) {
            if (attr.name.startsWith('data-nexph-event-')) {
                const eventName = attr.name.replace('data-nexph-event-', '');
                const handler   = attr.value;
                component.addEventListener('nexph:' + eventName, function(e) {
                    if (methods[handler]) methods[handler](e.detail.data);
                });
            }
        });
    });

    const refs = {};
    component.querySelectorAll('[data-nexph-ref]').forEach(function(el) {
        refs[el.getAttribute('data-nexph-ref')] = el;
    });

    function applyTransitions() {
        component.querySelectorAll('[data-nexph-transition]').forEach(function(el) {
            const name = el.getAttribute('data-nexph-transition');
            if (el.style.display !== 'none') {
                el.classList.remove(name + '-leave', name + '-leave-active');
                if (!el._nxEntered) {
                    el._nxEntered = true;
                    el.classList.add(name + '-enter');
                    requestAnimationFrame(function() {
                        el.classList.add(name + '-enter-active');
                        el.addEventListener('transitionend', function() {
                            el.classList.remove(name + '-enter', name + '-enter-active');
                        }, { once: true });
                    });
                }
            } else {
                el._nxEntered = false;
                el.classList.add(name + '-leave');
                requestAnimationFrame(function() {
                    el.classList.add(name + '-leave-active');
                    el.addEventListener('transitionend', function() {
                        el.classList.remove(name + '-leave', name + '-leave-active');
                    }, { once: true });
                });
            }
        });
    }

    component.querySelectorAll('[data-nexph-fetch]').forEach(function(el) {
        if (el.closest('[data-nexph-route]')) return;
        const url     = el.getAttribute('data-nexph-fetch');
        const target  = el.getAttribute('data-nexph-fetch-target') || null;
        const method  = (el.getAttribute('data-nexph-fetch-method') || 'GET').toUpperCase();
        const bodyKey = el.getAttribute('data-nexph-fetch-body') || null;
        el.setAttribute('data-nexph-fetch-status', 'loading');
        const opts = { method: method };
        if (bodyKey && _raw[bodyKey] !== undefined) {
            opts.headers = { 'Content-Type': 'application/json' };
            opts.body    = JSON.stringify(_raw[bodyKey]);
        }
        fetch(url, opts)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (target) state[target] = data;
                el.setAttribute('data-nexph-fetch-status', 'done');
            })
            .catch(function(err) {
                el.setAttribute('data-nexph-fetch-status', 'error');
                console.error('[nexph] nx-fetch error:', err);
            });
    });

JS;

        $mountBlock  = '    // Lifecycle: onMount' . "\n    " . $onMountJs . "\n";
        $destroyBlock = '    window.addEventListener(\'beforeunload\', function() {' . "\n"
            . '        ' . $onDestroyJs . "\n"
            . '    });' . "\n";

        $tail = '    _initRefs();' . "\n"
            . '    updateUI();' . "\n"
            . '    window.NEXPH = window.NEXPH || {};' . "\n"
            . '    window.NEXPH.components = window.NEXPH.components || {};' . "\n"
            . '    window.NEXPH.components[\'' . $componentName . '\'] = { state, methods, updateUI, refs };' . "\n"
            . '})();' . "\n";

        return $head . $body . $stateBlock . $computedBlock . $emitBlock . $methodsBlock . $events . $mountBlock . $destroyBlock . $tail;
    }

    // ── Code generators ───────────────────────────────────────────────────────

    private function generateNodeRefsJs(array $nodeRefs): string
    {
        if (empty($nodeRefs)) return '// no static refs';
        $lines = [];
        foreach ($nodeRefs as $id => $info) {
            $lines[] = "_nodeRefs[{$id}] = component.querySelector('[data-nx-id=\"{$id}\"]');";
        }
        return implode("\n        ", $lines);
    }

    private function generateDepsJs(array $deps): string
    {
        if (empty($deps)) return '{}';
        $entries = [];
        foreach ($deps as $key => $nodes) {
            $nodeEntries = [];
            foreach ($nodes as $node) {
                $parts = [];
                foreach ($node as $k => $v) {
                    if (is_string($v)) {
                        $parts[] = $k . ': "' . addslashes($v) . '"';
                    } elseif (is_int($v)) {
                        $parts[] = $k . ': ' . $v;
                    }
                }
                $nodeEntries[] = '{' . implode(', ', $parts) . '}';
            }
            $entries[] = '    "' . $key . '": [' . implode(', ', $nodeEntries) . ']';
        }
        return "{\n" . implode(",\n", $entries) . "\n}";
    }

    private function generateStateJs(array $properties): string
    {
        $state = [];
        foreach ($properties as $name => $prop) {
            $state[$name] = $this->convertPhpValueToJs($prop['default']);
        }
        return json_encode($state);
    }

    private function generateMethodsJs(array $methods): string
    {
        if (empty($methods)) return '';
        $js = [];
        foreach ($methods as $name => $body) {
            $jsBody   = $this->convertPhpToJs($body);
            $lines    = array_filter(explode("\n", $jsBody), fn($l) => trim($l) !== '');
            $indented = implode("\n", array_map(fn($l) => '            ' . $l, $lines));
            $js[]     = "{$name}: function(index, e) {\n{$indented}\n        }";
        }
        return implode(",\n        ", $js);
    }

    private function generateComputedJs(array $computed): string
    {
        if (empty($computed)) return '// no computed';
        $lines = [];
        foreach ($computed as $name => $expr) {
            $jsExpr  = trim($this->convertPhpToJs($expr));
            $lines[] = "if (name === '" . $name . "') {";
            $lines[] = "    _raw._computed_" . $name . " = (function() { return " . $jsExpr . "; })();";
            $lines[] = "    var _cel = component.querySelector('[data-nexph-bind=\"" . $name . "\"]');";
            $lines[] = "    if (_cel) _cel.textContent = _raw._computed_" . $name . ";";
            $lines[] = "}";
        }
        return implode("\n        ", $lines);
    }

    private function convertPhpValueToJs(string $value): mixed
    {
        $value = trim($value);
        if ($value === '[]') return [];
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float)$value : (int)$value;
        }
        if ($value === 'true')  return true;
        if ($value === 'false') return false;
        if ($value === 'null')  return null;
        if (preg_match('/^["\'](.*)["\']\s*$/', $value, $m)) return $m[1];
        return $value;
    }

    private function convertPhpToJs(string $phpCode): string
    {
        $lines   = explode("\n", trim($phpCode));
        $jsLines = [];

        foreach ($lines as $line) {
            $jsLine = $line;

            $jsLine = preg_replace('/\buniqid\s*\(\s*\)/', '(Date.now().toString(36)+Math.random().toString(36).slice(2,8))', $jsLine);
            $jsLine = preg_replace('/\btime\s*\(\s*\)/', 'Math.floor(Date.now()/1000)', $jsLine);
            $jsLine = preg_replace('/\bstrtolower\s*\(/', '(function(s){return String(s).toLowerCase();})(', $jsLine);
            $jsLine = preg_replace('/\bstrtoupper\s*\(/', '(function(s){return String(s).toUpperCase();})(', $jsLine);
            $jsLine = preg_replace('/\btrim\s*\(/', '(function(s){return String(s).trim();})(', $jsLine);
            $jsLine = preg_replace(
                '/array_splice\s*\(\s*\$this->(\w+)\s*,\s*(\$?\w+)\s*,\s*(\d+)\s*\)/',
                'state.$1.splice($2, $3)', $jsLine
            );
            $jsLine = preg_replace('/isset\s*\(\s*\$this->(\w+)\[(\$?\w+)\]\s*\)/', 'state.$1[$2] !== undefined', $jsLine);
            $jsLine = preg_replace('/isset\s*\(\s*\$this->(\w+)\s*\)/', 'state.$1 !== undefined', $jsLine);
            $jsLine = preg_replace('/!empty\s*\(\s*\$this->(\w+)\s*\)/', 'state.$1 && state.$1.length > 0', $jsLine);
            $jsLine = preg_replace('/if\s*\(\s*!empty\s*\(\s*\$this->(\w+)\s*\)\s*\)/', 'if (state.$1 && state.$1.length > 0)', $jsLine);
            $jsLine = preg_replace('/count\s*\(\s*\$this->(\w+)\s*\)/', 'state.$1.length', $jsLine);
            $jsLine = preg_replace('/\$this->(\w+)\[\]\s*=\s*\[(.+?)\];/', 'state.$1.push({$2});', $jsLine);
            $jsLine = preg_replace('/\$this->(\w+)\[\]\s*=\s*\[/', 'state.$1.push({', $jsLine);
            $jsLine = preg_replace('/\$this->(\w+)\[\]\s*=\s*([^;]+);/', 'state.$1.push($2);', $jsLine);
            $jsLine = preg_replace('/\$this->(\w+)\+\+/', 'state.$1++', $jsLine);
            $jsLine = preg_replace('/\$this->(\w+)--/', 'state.$1--', $jsLine);
            $jsLine = preg_replace('/\$this->(\w+)\s*=\s*!\$this->(\w+);/', 'state.$1 = !state.$2;', $jsLine);
            $jsLine = preg_replace('/\'(\w+)\'\s*=>/', '$1:', $jsLine);
            $jsLine = preg_replace('/\$this->emit\s*\(\s*\'(\w+)\'\s*\)/', 'emit("$1")', $jsLine);
            $jsLine = preg_replace('/\$this->(\w+)/', 'state.$1', $jsLine);
            $jsLine = preg_replace('/\$index/', 'index', $jsLine);
            $jsLine = preg_replace('/\$(\w+)/', '$1', $jsLine);
            if (preg_match('/^(\s*)\];\s*$/', $jsLine, $m)) {
                $jsLine = $m[1] . '});';
            }

            $jsLines[] = $jsLine;
        }

        return implode("\n", $jsLines);
    }
}
