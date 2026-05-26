<?php

namespace Nexph\Builder;

/**
 * Generates the client-side router runtime for nx-route based navigation.
 * Supports hash routing (#/path) and history API routing (/path).
 */
class RouterGenerator
{
    /**
     * Generate the router runtime JS.
     *
     * @param array  $routes  [ 'path' => 'ComponentName', ... ]
     * @param string $mode    'hash' | 'history'
     */
    public function generate(array $routes, string $mode = 'hash'): string
    {
        $routeMap = json_encode($routes, JSON_PRETTY_PRINT);
        $isHash   = $mode === 'hash' ? 'true' : 'false';

        return <<<JS
(function() {
    var ROUTES = {$routeMap};
    var HASH_MODE = {$isHash};
    var currentRoute = null;

    function getPath() {
        if (HASH_MODE) {
            var hash = window.location.hash.slice(1) || '/';
            return hash.split('?')[0];
        }
        return window.location.pathname;
    }

    function matchRoute(path) {
        if (ROUTES[path]) return { component: ROUTES[path], params: {} };
        for (var pattern in ROUTES) {
            var re = new RegExp('^' + pattern.replace(/:([^/]+)/g, '([^/]+)') + '$');
            var m  = path.match(re);
            if (m) {
                var keys   = [];
                var kMatch = pattern.match(/:([^/]+)/g) || [];
                kMatch.forEach(function(k) { keys.push(k.slice(1)); });
                var params = {};
                keys.forEach(function(k, i) { params[k] = m[i + 1]; });
                return { component: ROUTES[pattern], params: params };
            }
        }
        return null;
    }

    function navigate(path) {
        if (!HASH_MODE) {
            history.pushState({}, '', path);
        } else {
            window.location.hash = path;
        }
        render(path);
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
                    if (comp && target) {
                        comp.state[target] = data;
                        comp.updateUI();
                    }
                })
                .catch(function(err) {
                    el.setAttribute('data-nexph-fetch-status', 'error');
                    console.error('[nexph] nx-fetch error:', err);
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
            // find route view by component name
            var routeEl = document.querySelector('[data-nexph-route][data-nexph-component="' + match.component + '"]');
            if (routeEl) {
                var clone = routeEl.cloneNode(true);
                clone.style.display = '';
                clone.removeAttribute('data-nexph-route');
                outlet.appendChild(clone);
                // trigger nx-fetch inside newly activated route
                runFetch(outlet);
            } else {
                outlet.innerHTML = '<div style="padding:20px;color:#ef4444">Route view not found: ' + match.component + '</div>';
            }
        });

        // active links
        document.querySelectorAll('[data-nexph-link]').forEach(function(el) {
            if (el.getAttribute('data-nexph-link') === path) {
                el.classList.add('router-link-active');
            } else {
                el.classList.remove('router-link-active');
            }
        });

        currentRoute = path;
        window.NEXPH = window.NEXPH || {};
        window.NEXPH.router = { currentRoute: currentRoute, navigate: navigate, params: match ? match.params : {} };
    }

    // intercept nx-link clicks
    document.addEventListener('click', function(e) {
        var el = e.target.closest('[data-nexph-link]');
        if (!el) return;
        e.preventDefault();
        navigate(el.getAttribute('data-nexph-link'));
    });

    // popstate / hashchange
    if (HASH_MODE) {
        window.addEventListener('hashchange', function() { render(getPath()); });
    } else {
        window.addEventListener('popstate', function() { render(getPath()); });
    }

    // initial render
    render(getPath());
})();
JS;
    }

    /**
     * Extract nx-route definitions from compiled HTML.
     * Returns [ 'path' => 'ComponentName' ]
     */
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
