<?php

namespace Nexph\Runtime;

/**
 * Phase 4: runtime-core module.
 * Shared helpers used by all other runtime modules.
 * Always injected — zero optional code here.
 */
class RuntimeCore
{
    public function generate(): string
    {
        return <<<'JS'
(function() {
'use strict';
window.NEXPH = window.NEXPH || {};
window.NEXPH.components = window.NEXPH.components || {};
window.NEXPH.stores     = window.NEXPH.stores     || {};

// shared microtask scheduler used by all modules
var _queue     = new Set();
var _scheduled = false;
window.NEXPH._schedule = function(key, flush) {
    _queue.add(key + ':' + (flush.__nxId = flush.__nxId || (Math.random().toString(36).slice(2))));
    if (!_scheduled) {
        _scheduled = true;
        Promise.resolve().then(function() {
            _scheduled = false;
            var items = Array.from(_queue);
            _queue.clear();
            var seen = new Set();
            items.forEach(function(item) {
                var flushId = item.split(':')[1];
                if (!seen.has(flushId)) {
                    seen.add(flushId);
                    flush();
                }
            });
        });
    }
};

// shared event bus
var _listeners = {};
window.NEXPH.on = function(event, cb) {
    (_listeners[event] = _listeners[event] || []).push(cb);
};
window.NEXPH.off = function(event, cb) {
    if (_listeners[event]) _listeners[event] = _listeners[event].filter(function(f) { return f !== cb; });
};
window.NEXPH.emit = function(event, data) {
    (_listeners[event] || []).forEach(function(cb) { cb(data); });
};
})();
JS;
    }
}
