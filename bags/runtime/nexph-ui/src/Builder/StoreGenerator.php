<?php

namespace Nexph\Builder;

/**
 * Generates the nx-store runtime JS.
 * Emits a reactive store system that components can subscribe to.
 */
class StoreGenerator
{
    /**
     * Generate the store runtime with initial store definitions.
     *
     * @param array $stores  [ 'name' => ['state' => [...], 'actions' => [...]] ]
     */
    public function generate(array $stores): string
    {
        $storeMap = json_encode($stores, JSON_PRETTY_PRINT);

        return <<<JS
(function() {
    var _stores = {};
    var _subscribers = {};
    var _initial = {$storeMap};

    function syncStoreDOM(storeName) {
        var store = _stores[storeName];
        if (!store) return;
        document.querySelectorAll('[data-nexph-store-bind]').forEach(function(el) {
            var parts = el.getAttribute('data-nexph-store-bind').split('.');
            if (parts[0] !== storeName) return;
            var val = store.state[parts[1]];
            el.textContent = val !== undefined ? val : '';
        });
        document.querySelectorAll('[data-nexph-store-model]').forEach(function(el) {
            var parts = el.getAttribute('data-nexph-store-model').split('.');
            if (parts[0] !== storeName) return;
            var val = store.state[parts[1]];
            if (val !== undefined && el.value !== String(val)) el.value = val;
        });
    }

    function defineStore(name, def) {
        var state = Object.assign({}, def.state || {});
        var actions = def.actions || {};
        var builtins = {
            add:       function() { if(state.items!==undefined){state.items.push({});} if(state.count!==undefined){state.count++;} },
            clear:     function() { if(state.items!==undefined){state.items=[];} if(state.count!==undefined){state.count=0;} if(state.coupon!==undefined){state.coupon='';} },
            increment: function() { if(state.count!==undefined){state.count++;} },
            decrement: function() { if(state.count!==undefined){state.count--;} },
            reset:     function() { if(state.count!==undefined){state.count=0;} },
            toggle:    function() { if(state.active!==undefined){state.active=!state.active;} },
        };
        Object.keys(actions).forEach(function(k) {
            if (actions[k] === null && builtins[k]) actions[k] = builtins[k];
            else if (actions[k] === null) actions[k] = function() {};
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
        _stores[name] = { state: proxy, actions: boundActions };
        _subscribers[name] = [];
    }

    function notify(storeName) {
        (_subscribers[storeName] || []).forEach(function(cb) {
            cb(storeName);
        });
        syncStoreDOM(storeName);
    }

    function subscribe(storeName, cb) {
        if (!_subscribers[storeName]) _subscribers[storeName] = [];
        _subscribers[storeName].push(cb);
        return function() {
            _subscribers[storeName] = _subscribers[storeName].filter(function(s) { return s !== cb; });
        };
    }

    function useStore(name) {
        return _stores[name] || null;
    }

    Object.keys(_initial).forEach(function(name) {
        defineStore(name, _initial[name]);
    });

    // initial DOM sync
    Object.keys(_stores).forEach(function(name) { syncStoreDOM(name); });

    // store-model two-way binding
    document.addEventListener('input', function(e) {
        var el = e.target;
        if (!el.hasAttribute('data-nexph-store-model')) return;
        var parts = el.getAttribute('data-nexph-store-model').split('.');
        var store = _stores[parts[0]];
        if (!store) return;
        store.state[parts[1]] = el.value;
        syncStoreDOM(parts[0]);
    });

    // store-action click delegation
    document.addEventListener('click', function(e) {
        var el = e.target.closest('[data-nexph-store-action]');
        if (!el) return;
        e.preventDefault();
        e.stopPropagation();
        var parts = el.getAttribute('data-nexph-store-action').split('.');
        var store = _stores[parts[0]];
        if (store && store.actions[parts[1]]) {
            store.actions[parts[1]]();
        }
    });

    window.NEXPH = window.NEXPH || {};
    window.NEXPH.store   = { define: defineStore, use: useStore, subscribe: subscribe, sync: syncStoreDOM };
    window.NEXPH.stores  = _stores;
})();
JS;
    }

    /**
     * Extract nx-store-* attributes from compiled HTML to discover used stores.
     * Returns unique store names referenced in the template.
     */
    public function extractStoreNames(string $html): array
    {
        $names = [];
        $attrs = ['data-nexph-store-bind', 'data-nexph-store-model', 'data-nexph-store-action'];
        foreach ($attrs as $attr) {
            if (preg_match_all('/' . $attr . '="([^.]+)\.[^"]*"/', $html, $m)) {
                foreach ($m[1] as $name) {
                    if (!in_array($name, $names)) {
                        $names[] = $name;
                    }
                }
            }
        }
        return $names;
    }
}
