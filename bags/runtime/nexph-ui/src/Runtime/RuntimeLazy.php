<?php

namespace Nexph\Runtime;

/**
 * Phase 4: runtime-lazy module.
 * Injected only when data-nexph-lazy is present in compiled HTML.
 */
class RuntimeLazy
{
    public function generate(): string
    {
        return <<<'JS'
(function() {
'use strict';
function loadComponent(el) {
    var src = el.getAttribute('data-nexph-lazy');
    if (!src || el._nxLazyLoaded) return;
    el._nxLazyLoaded = true;
    el.setAttribute('data-nexph-lazy-status', 'loading');
    var script    = document.createElement('script');
    script.src    = src;
    script.onload = function() {
        el.setAttribute('data-nexph-lazy-status', 'loaded');
        el.classList.remove('nx-lazy-pending');
        el.classList.add('nx-lazy-loaded');
        window.NEXPH.emit('lazy:loaded', { src: src });
    };
    script.onerror = function() {
        el.setAttribute('data-nexph-lazy-status', 'error');
        el.classList.add('nx-lazy-error');
        console.error('[nexph:lazy] load failed:', src);
        window.NEXPH.emit('lazy:error', { src: src });
    };
    document.head.appendChild(script);
}

if ('IntersectionObserver' in window) {
    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) { loadComponent(entry.target); observer.unobserve(entry.target); }
        });
    }, { rootMargin: '100px' });
    document.querySelectorAll('[data-nexph-lazy]').forEach(function(el) {
        el.classList.add('nx-lazy-pending');
        observer.observe(el);
    });
} else {
    document.querySelectorAll('[data-nexph-lazy]').forEach(loadComponent);
}

window.NEXPH.lazy = { load: loadComponent };
})();
JS;
    }
}
