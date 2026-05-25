<?php

namespace Nexph\Runtime;

/**
 * Phase 4: runtime-store module.
 * Extracted from StoreGenerator — injected only when nx-store-* is used.
 */
class RuntimeStore
{
    public function generate(array $stores): string
    {
        $storeMap = json_encode($stores, JSON_PRETTY_PRINT);

        return <<<JS
(function() {
'use strict';
var _stores      = {};
var _subscribers = {};
var _initial     = {$storeMap};

function syncStoreDOM(name) {
    var store = _stores[name];
    if (!store) return;
    document.querySelectorAll('[data-nexph-store-bind]').forEach(function(el) {
        var parts = el.getAttribute('data-nexph-store-bind').split('.');
        if (parts[0] !== name) return;
        var val = store.state[parts[1]];
        el.textContent = val !== undefined ? val : '';
    });
    document.querySelectorAll('[data-nexph-store-model]').forEach(function(el) {
        var parts = el.getAttribute('data-nexph-store-model').split('.');
        if (parts[0] !== name) return;
        var val = store.state[parts[1]];
        if (val !== undefined && el.value !== String(val)) el.value = val;
    });
}

function notify(name) {
    (_subscribers[name] || []).forEach(function(cb) { cb(name); });
    syncStoreDOM(name);
    window.NEXPH.emit('store:change', { store: name });
}

function defineStore(name, def) {
    var state   = Object.assign({}, def.state || {});
    var actions = def.actions || {};
    var builtins = {
        add:       function() { if (state.items !== undefined) state.items.push({}); if (state.count !== undefined) state.count++; },
        clear:     function() { if (state.items !== undefined) state.items = []; if (state.count !== undefined) state.count = 0; if (state.coupon !== undefined) state.coupon = ''; },
        increment: function() { if (state.count !== undefined) state.count++; },
        decrement: function() { if (state.count !== undefined) state.count--; },
        reset:     function() { if (state.count !== undefined) state.count = 0; },
        toggle:    function() { if (state.active !== undefined) state.active = !state.active; },
    };
    Object.keys(actions).forEach(function(k) {
        if (actions[k] === null) actions[k] = builtins[k] || function() {};
    });
    var proxy = new Proxy(state, {
        set: function(target, key, value) {
            target[key] = value;
            notify(name);
            return true;
        }
    });
    var boundActions = {};
    Object.keys(actions).forEach(function(k) {
        boundActions[k] = function() {
            actions[k].apply({ state: proxy }, arguments);
            syncStoreDOM(name);
        };
    });
    _stores[name] = { state: proxy, actions: boundActions, sync: function() { syncStoreDOM(name); } };
    _subscribers[name] = [];
}

function subscribe(name, cb) {
    if (!_subscribers[name]) _subscribers[name] = [];
    _subscribers[name].push(cb);
    return function() {
        _subscribers[name] = _subscribers[name].filter(function(s) { return s !== cb; });
    };
}

Object.keys(_initial).forEach(function(name) { defineStore(name, _initial[name]); });
Object.keys(_stores).forEach(syncStoreDOM);

// two-way model binding
document.addEventListener('input', function(e) {
    if (!e.target.hasAttribute('data-nexph-store-model')) return;
    var parts = e.target.getAttribute('data-nexph-store-model').split('.');
    var store = _stores[parts[0]];
    if (!store) return;
    store.state[parts[1]] = e.target.value;
    syncStoreDOM(parts[0]);
});

// action click delegation
document.addEventListener('click', function(e) {
    var el = e.target.closest('[data-nexph-store-action]');
    if (!el) return;
    e.preventDefault();
    e.stopPropagation();
    var parts = el.getAttribute('data-nexph-store-action').split('.');
    var store = _stores[parts[0]];
    if (store && store.actions[parts[1]]) store.actions[parts[1]]();
});

window.NEXPH.store  = { define: defineStore, use: function(n) { return _stores[n] || null; }, subscribe: subscribe, sync: syncStoreDOM };
window.NEXPH.stores = _stores;
})();
JS;
    }

    public function extractStoreNames(string $html): array
    {
        $names = [];
        foreach (['data-nexph-store-bind', 'data-nexph-store-model', 'data-nexph-store-action'] as $attr) {
            if (preg_match_all('/' . $attr . '="([^.]+)\.[^"]*"/', $html, $m)) {
                foreach ($m[1] as $name) {
                    if (!in_array($name, $names)) $names[] = $name;
                }
            }
        }
        return $names;
    }
}
