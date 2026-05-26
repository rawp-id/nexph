<?php

namespace Nexph\Builder;

/**
 * Generates the nx-lazy runtime JS for deferred component loading.
 */
class LazyLoader
{
    /**
     * Generate the lazy-load runtime.
     * Components marked nx-lazy are loaded only when they enter the viewport.
     */
    public function generate(): string
    {
        return <<<'JS'
(function() {
    function loadComponent(el) {
        var src = el.getAttribute('data-nexph-lazy');
        if (!src || el._nxLazyLoaded) return;
        el._nxLazyLoaded = true;
        el.setAttribute('data-nexph-lazy-status', 'loading');
        var script = document.createElement('script');
        script.src = src;
        script.onload = function() {
            el.setAttribute('data-nexph-lazy-status', 'loaded');
            el.classList.remove('nx-lazy-pending');
            el.classList.add('nx-lazy-loaded');
        };
        script.onerror = function() {
            el.setAttribute('data-nexph-lazy-status', 'error');
            el.classList.add('nx-lazy-error');
            console.error('[nexph] lazy load failed:', src);
        };
        document.head.appendChild(script);
    }

    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    loadComponent(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { rootMargin: '100px' });

        document.querySelectorAll('[data-nexph-lazy]').forEach(function(el) {
            el.classList.add('nx-lazy-pending');
            observer.observe(el);
        });
    } else {
        // fallback: load immediately
        document.querySelectorAll('[data-nexph-lazy]').forEach(loadComponent);
    }

    window.NEXPH = window.NEXPH || {};
    window.NEXPH.lazy = { load: loadComponent };
})();
JS;
    }
}
