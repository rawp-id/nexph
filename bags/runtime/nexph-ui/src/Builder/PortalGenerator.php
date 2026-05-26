<?php

namespace Nexph\Builder;

class PortalGenerator
{
    public function generate(): string
    {
        return <<<'JS'
(function() {
    var _portals = [];

    function isHidden(el) {
        var p = el.parentElement;
        while (p) {
            if (p.style && p.style.display === 'none') return true;
            p = p.parentElement;
        }
        return false;
    }

    function mountPortal(el) {
        if (el._nxPortalMounted) return;
        var target = el.getAttribute('data-nexph-portal');
        var dest   = (target && target !== 'body') ? document.querySelector(target) : document.body;
        if (!dest) {
            console.warn('[nexph] portal target not found:', target);
            return;
        }
        var placeholder = document.createComment('nexph-portal:' + (target || 'body'));
        el.parentNode.insertBefore(placeholder, el);
        dest.appendChild(el);
        el._nxPortalPlaceholder = placeholder;
        el._nxPortalDest        = dest;
        el._nxPortalMounted     = true;
        // sync visibility from placeholder parent
        syncVisibility(el);
        _portals.push(el);
    }

    function unmountPortal(el) {
        if (!el._nxPortalPlaceholder) return;
        el._nxPortalPlaceholder.parentNode.insertBefore(el, el._nxPortalPlaceholder);
        el._nxPortalPlaceholder.remove();
        delete el._nxPortalPlaceholder;
        delete el._nxPortalDest;
        delete el._nxPortalMounted;
        _portals = _portals.filter(function(p) { return p !== el; });
    }

    function syncVisibility(el) {
        if (!el._nxPortalPlaceholder) return;
        var hidden = isHiddenByPlaceholder(el._nxPortalPlaceholder);
        el.style.display = hidden ? 'none' : '';
    }

    function isHiddenByPlaceholder(comment) {
        var p = comment.parentElement;
        while (p) {
            if (p.style && p.style.display === 'none') return true;
            p = p.parentElement;
        }
        return false;
    }

    function syncAll() {
        _portals.forEach(syncVisibility);
    }

    function initAll() {
        document.querySelectorAll('[data-nexph-portal]').forEach(function(el) {
            if (!isHidden(el)) mountPortal(el);
        });
        // watch for nx-if parent visibility changes
        if (window.MutationObserver) {
            var obs = new MutationObserver(function(mutations) {
                var needSync = false;
                mutations.forEach(function(m) {
                    if (m.type === 'attributes' && m.attributeName === 'style') needSync = true;
                });
                if (needSync) syncAll();
            });
            obs.observe(document.body, { attributes: true, subtree: true, attributeFilter: ['style'] });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    window.NEXPH = window.NEXPH || {};
    window.NEXPH.portal = { mount: mountPortal, unmount: unmountPortal, sync: syncAll };
})();
JS;
    }

    public function hasPortals(string $html): bool
    {
        return str_contains($html, 'data-nexph-portal')
            || str_contains($html, 'nx-portal');
    }
}
