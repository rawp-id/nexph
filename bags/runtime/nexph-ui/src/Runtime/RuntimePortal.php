<?php

namespace Nexph\Runtime;

/**
 * Phase 4: runtime-portal module.
 * Injected only when data-nexph-portal is present in compiled HTML.
 */
class RuntimePortal
{
    public function generate(): string
    {
        return <<<'JS'
(function() {
'use strict';
var _portals = [];

function isHiddenByPlaceholder(comment) {
    var p = comment.parentElement;
    while (p) { if (p.style && p.style.display === 'none') return true; p = p.parentElement; }
    return false;
}

function syncVisibility(el) {
    if (!el._nxPortalPlaceholder) return;
    el.style.display = isHiddenByPlaceholder(el._nxPortalPlaceholder) ? 'none' : '';
}

function mountPortal(el) {
    if (el._nxPortalMounted) return;
    var target = el.getAttribute('data-nexph-portal');
    var dest   = (target && target !== 'body') ? document.querySelector(target) : document.body;
    if (!dest) { console.warn('[nexph:portal] target not found:', target); return; }
    var placeholder = document.createComment('nexph-portal:' + (target || 'body'));
    el.parentNode.insertBefore(placeholder, el);
    dest.appendChild(el);
    el._nxPortalPlaceholder = placeholder;
    el._nxPortalMounted     = true;
    syncVisibility(el);
    _portals.push(el);
}

function unmountPortal(el) {
    if (!el._nxPortalPlaceholder) return;
    el._nxPortalPlaceholder.parentNode.insertBefore(el, el._nxPortalPlaceholder);
    el._nxPortalPlaceholder.remove();
    delete el._nxPortalPlaceholder;
    delete el._nxPortalMounted;
    _portals = _portals.filter(function(p) { return p !== el; });
}

function syncAll() { _portals.forEach(syncVisibility); }

function initAll() {
    document.querySelectorAll('[data-nexph-portal]').forEach(function(el) {
        var p = el.parentElement;
        var hidden = false;
        while (p) { if (p.style && p.style.display === 'none') { hidden = true; break; } p = p.parentElement; }
        if (!hidden) mountPortal(el);
    });
    if (window.MutationObserver) {
        new MutationObserver(function(mutations) {
            var needSync = mutations.some(function(m) { return m.type === 'attributes' && m.attributeName === 'style'; });
            if (needSync) syncAll();
        }).observe(document.body, { attributes: true, subtree: true, attributeFilter: ['style'] });
    }
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAll);
else initAll();

window.NEXPH.portal = { mount: mountPortal, unmount: unmountPortal, sync: syncAll };
})();
JS;
    }

    public function hasPortals(string $html): bool
    {
        return str_contains($html, 'data-nexph-portal') || str_contains($html, 'nx-portal');
    }
}
