<?php

namespace Nexph\Runtime;

/**
 * Phase 4: runtime-router module.
 * Extracted from RouterGenerator — injected only when nx-route is used.
 */
class RuntimeRouter
{
    public function generate(array $routes, string $mode = 'hash'): string
    {
        $routeMap = json_encode($routes, JSON_PRETTY_PRINT);
        $isHash   = $mode === 'hash' ? 'true' : 'false';

        return <<<JS
(function() {
'use strict';
var ROUTES    = {$routeMap};
var HASH_MODE = {$isHash};
var _current  = null;

function getPath() {
    if (HASH_MODE) {
        var h = window.location.hash.slice(1) || '/';
        return h.split('?')[0];
    }
    return window.location.pathname;
}

function matchRoute(path) {
    if (ROUTES[path]) return { component: ROUTES[path], params: {} };
    for (var pattern in ROUTES) {
        var re = new RegExp('^' + pattern.replace(/:([^/]+)/g, '([^/]+)') + '$');
        var m  = path.match(re);
        if (m) {
            var keys = (pattern.match(/:([^/]+)/g) || []).map(function(k) { return k.slice(1); });
            var params = {};
            keys.forEach(function(k, i) { params[k] = m[i + 1]; });
            return { component: ROUTES[pattern], params: params };
        }
    }
    return null;
}

function runFetch(container) {
    container.querySelectorAll('[data-nexph-fetch]').forEach(function(el) {
        if (el.getAttribute('data-nexph-fetch-status') === 'done') return;
        var url    = el.getAttribute('data-nexph-fetch');
        var target = el.getAttribute('data-nexph-fetch-target') || null;
        var method = (el.getAttribute('data-nexph-fetch-method') || 'GET').toUpperCase();
        el.setAttribute('data-nexph-fetch-status', 'loading');
        fetch(url, { method: method })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                el.setAttribute('data-nexph-fetch-status', 'done');
                var comp = window.NEXPH && window.NEXPH.components && window.NEXPH.components['App'];
                if (comp && target) { comp.state[target] = data; comp.updateUI(); }
            })
            .catch(function(err) {
                el.setAttribute('data-nexph-fetch-status', 'error');
                console.error('[nexph:router] nx-fetch error:', err);
            });
    });
}

function render(path) {
    var match   = matchRoute(path);
    var outlets = document.querySelectorAll('[data-nexph-outlet]');
    outlets.forEach(function(outlet) {
        outlet.innerHTML = '';
        if (!match) {
            outlet.innerHTML = '<div style="padding:20px;color:#ef4444">404 — Route not found: ' + path + '</div>';
            return;
        }
        var routeEl = document.querySelector('[data-nexph-route][data-nexph-component="' + match.component + '"]');
        if (routeEl) {
            var clone = routeEl.cloneNode(true);
            clone.style.display = '';
            clone.removeAttribute('data-nexph-route');
            outlet.appendChild(clone);
                // re-trigger all state so cloned nodes get current values
                if (window.NEXPH && window.NEXPH.components) {
                    Object.keys(window.NEXPH.components).forEach(function(name) {
                        var comp = window.NEXPH.components[name];
                        if (comp && comp.updateUI) comp.updateUI();
                    });
                }
                runFetch(outlet);
        } else {
            outlet.innerHTML = '<div style="padding:20px;color:#ef4444">Route view not found: ' + match.component + '</div>';
        }
    });
    document.querySelectorAll('[data-nexph-link]').forEach(function(el) {
        el.getAttribute('data-nexph-link') === path
            ? el.classList.add('router-link-active')
            : el.classList.remove('router-link-active');
    });
    _current = path;
    window.NEXPH.router = { currentRoute: _current, navigate: navigate, params: match ? match.params : {} };
    window.NEXPH.emit('router:change', { path: path, params: match ? match.params : {} });
}

function navigate(path) {
    if (!HASH_MODE) history.pushState({}, '', path);
    else window.location.hash = path;
    render(path);
}

document.addEventListener('click', function(e) {
    var el = e.target.closest('[data-nexph-link]');
    if (!el) return;
    e.preventDefault();
    navigate(el.getAttribute('data-nexph-link'));
});

if (HASH_MODE) window.addEventListener('hashchange', function() { render(getPath()); });
else           window.addEventListener('popstate',   function() { render(getPath()); });

render(getPath());
})();
JS;
    }

    public function extractRoutes(string $html): array
    {
        $routes = [];
        if (preg_match_all('/data-nexph-route="([^"]+)"\s+data-nexph-component="([^"]+)"/', $html, $m)) {
            foreach ($m[1] as $i => $path) {
                $routes[$path] = $m[2][$i];
            }
        }
        return $routes;
    }
}
