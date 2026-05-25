<?php

namespace Nexph\Builder;

class AnimateGenerator
{
    public function generate(): string
    {
        return <<<'JS'
(function() {
    var _defaults = {
        duration: 300,
        easing: 'ease',
        fill: 'both',
    };

    function parseDirective(val) {
        var parts = val.split(':');
        var name  = parts[0];
        var opts  = {};
        for (var i = 1; i < parts.length; i++) {
            var kv = parts[i].split('=');
            if (kv.length === 2) opts[kv[0]] = kv[1];
            else opts['duration'] = parseInt(kv[0]);
        }
        return { name: name, opts: Object.assign({}, _defaults, opts) };
    }

    var KEYFRAMES = {
        'fade-in':    [{ opacity: 0 }, { opacity: 1 }],
        'fade-out':   [{ opacity: 1 }, { opacity: 0 }],
        'slide-up':   [{ opacity: 0, transform: 'translateY(20px)' }, { opacity: 1, transform: 'translateY(0)' }],
        'slide-down': [{ opacity: 1, transform: 'translateY(0)' }, { opacity: 0, transform: 'translateY(20px)' }],
        'slide-left': [{ opacity: 0, transform: 'translateX(20px)' }, { opacity: 1, transform: 'translateX(0)' }],
        'slide-right':[{ opacity: 0, transform: 'translateX(-20px)' }, { opacity: 1, transform: 'translateX(0)' }],
        'zoom-in':    [{ opacity: 0, transform: 'scale(0.85)' }, { opacity: 1, transform: 'scale(1)' }],
        'zoom-out':   [{ opacity: 1, transform: 'scale(1)' }, { opacity: 0, transform: 'scale(0.85)' }],
        'bounce':     [
            { transform: 'translateY(0)' },
            { transform: 'translateY(-12px)' },
            { transform: 'translateY(0)' },
            { transform: 'translateY(-6px)' },
            { transform: 'translateY(0)' },
        ],
        'shake': [
            { transform: 'translateX(0)' },
            { transform: 'translateX(-8px)' },
            { transform: 'translateX(8px)' },
            { transform: 'translateX(-6px)' },
            { transform: 'translateX(6px)' },
            { transform: 'translateX(0)' },
        ],
        'pulse': [{ opacity: 1 }, { opacity: 0.4 }, { opacity: 1 }],
        'flip':  [
            { transform: 'perspective(400px) rotateY(0)' },
            { transform: 'perspective(400px) rotateY(180deg)' },
        ],
    };

    function animate(el, animName, opts) {
        var frames = KEYFRAMES[animName];
        if (!frames) {
            console.warn('[nexph] unknown animation:', animName);
            return Promise.resolve();
        }
        var timing = {
            duration: parseInt(opts.duration) || 300,
            easing:   opts.easing || 'ease',
            fill:     opts.fill   || 'both',
        };
        if (el.animate) {
            return el.animate(frames, timing).finished;
        }
        return Promise.resolve();
    }

    function initEnter(el) {
        var raw = el.getAttribute('data-nexph-animate');
        if (!raw) return;
        var d = parseDirective(raw);
        animate(el, d.name, d.opts);
    }

    function initLeave(el, cb) {
        var raw = el.getAttribute('data-nexph-animate-leave');
        if (!raw) { cb(); return; }
        var d = parseDirective(raw);
        animate(el, d.name, d.opts).then(cb);
    }

    function initScroll(el) {
        var raw = el.getAttribute('data-nexph-animate-scroll');
        if (!raw) return;
        var d = parseDirective(raw);
        var obs = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    animate(el, d.name, d.opts);
                    obs.unobserve(el);
                }
            });
        }, { threshold: 0.15 });
        obs.observe(el);
    }

    function initRepeat(el) {
        var raw = el.getAttribute('data-nexph-animate-repeat');
        if (!raw) return;
        var parts = raw.split(':');
        var animName = parts[0];
        var interval = parseInt(parts[1]) || 1000;
        var d = parseDirective(animName);
        setInterval(function() { animate(el, d.name, d.opts); }, interval);
    }

    function initAll() {
        document.querySelectorAll('[data-nexph-animate]').forEach(initEnter);
        document.querySelectorAll('[data-nexph-animate-scroll]').forEach(initScroll);
        document.querySelectorAll('[data-nexph-animate-repeat]').forEach(initRepeat);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }

    window.NEXPH = window.NEXPH || {};
    window.NEXPH.animate = {
        run:       animate,
        enter:     initEnter,
        leave:     initLeave,
        keyframes: KEYFRAMES,
        add: function(name, frames) { KEYFRAMES[name] = frames; },
    };
})();
JS;
    }

    public function hasAnimations(string $html): bool
    {
        return str_contains($html, 'data-nexph-animate')
            || str_contains($html, 'nx-animate');
    }
}
