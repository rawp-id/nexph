<?php

namespace Nexph\Runtime;

/**
 * Phase 4+7: runtime-devtools module.
 * Plugin-aware DevTools — never affects production runtime.
 * Injected only in dev mode (hmrPort > 0).
 * Supports external tab registration via window.NEXPH.devtools.addTab().
 */
class RuntimeDevtools
{
    public function generate(array $buildInfo = [], array $extraTabs = []): string
    {
        $buildJson   = json_encode([
            'entry'      => $buildInfo['entry']      ?? '',
            'components' => $buildInfo['components']  ?? [],
            'sizes'      => $buildInfo['sizes']       ?? [],
            'builtAt'    => date('H:i:s'),
        ]);
        $extraTabsJs = $this->buildExtraTabsJs($extraTabs);

        return <<<HTML
<script>
(function() {
'use strict';

var BUILD_INFO  = {$buildJson};
var _open       = false;
var _tab        = 'components';
var _eventLog   = [];
var _netLog     = [];
var _MAX_LOG    = 200;
var _pluginTabs = {};

// ── plugin tab API ────────────────────────────────────────────────────────────
window.NEXPH = window.NEXPH || {};
window.NEXPH.devtools = {
    addTab: function(name, label, renderFn) {
        _pluginTabs[name] = { label: label, render: renderFn };
        if (_open) _render();
    }
};

// ── register extra tabs from plugins ─────────────────────────────────────────
{$extraTabsJs}

// ── intercept fetch ───────────────────────────────────────────────────────────
var _origFetch = window.fetch;
window.fetch = function(url, opts) {
    var entry = { url: url, method: (opts && opts.method) || 'GET', status: '…', time: null, ts: Date.now() };
    _netLog.unshift(entry);
    if (_netLog.length > _MAX_LOG) _netLog.pop();
    var t0 = performance.now();
    return _origFetch.apply(this, arguments)
        .then(function(res) { entry.status = res.status; entry.time = Math.round(performance.now() - t0) + 'ms'; _render(); return res; })
        .catch(function(err) { entry.status = 'ERR'; entry.time = Math.round(performance.now() - t0) + 'ms'; _render(); throw err; });
};

// ── intercept nexph events ────────────────────────────────────────────────────
var _origDispatch = EventTarget.prototype.dispatchEvent;
EventTarget.prototype.dispatchEvent = function(e) {
    if (e.type && e.type.startsWith('nexph:')) {
        _eventLog.unshift({ name: e.type.replace('nexph:', ''), component: (e.detail && e.detail.componentName) || '?', data: (e.detail && e.detail.data), ts: Date.now() });
        if (_eventLog.length > _MAX_LOG) _eventLog.pop();
        setTimeout(_render, 0);
    }
    return _origDispatch.call(this, e);
};

// ── DOM scaffold ──────────────────────────────────────────────────────────────
var _root;

function _boot() {
    var btn = document.createElement('div');
    btn.id  = '__nx_dt_btn';
    btn.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2"/></svg> NEXPH';
    btn.title   = 'Toggle NEXPH DevTools (Alt+D)';
    btn.onclick = _toggle;
    document.body.appendChild(btn);
    _root = document.createElement('div');
    _root.id = '__nx_dt_root';
    _root.style.display = 'none';
    document.body.appendChild(_root);
    _injectStyles();
    _render();
}

// ── styles ────────────────────────────────────────────────────────────────────
function _injectStyles() {
    var s = document.createElement('style');
    s.textContent = [
        '#__nx_dt_btn{position:fixed;bottom:16px;right:16px;z-index:2147483646;background:#6366f1;color:#fff;font:700 11px/1 monospace;padding:7px 12px;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:6px;box-shadow:0 2px 12px #0006;user-select:none;transition:background .15s}',
        '#__nx_dt_btn:hover{background:#4f46e5}',
        '#__nx_dt_root{position:fixed;bottom:0;left:0;right:0;z-index:2147483645;height:340px;background:#0f0f1a;color:#e2e8f0;font:13px/1.5 monospace;display:flex;flex-direction:column;border-top:2px solid #6366f1;box-shadow:0 -4px 24px #0008}',
        '#__nx_dt_root *{box-sizing:border-box}',
        '.__nx_topbar{display:flex;align-items:center;background:#1a1a2e;border-bottom:1px solid #2d2d4e;padding:0 8px;height:36px;gap:2px;flex-shrink:0}',
        '.__nx_topbar span{font:700 11px monospace;color:#6366f1;margin-right:8px;letter-spacing:.05em}',
        '.__nx_tab{background:none;border:none;color:#94a3b8;font:12px monospace;padding:6px 12px;cursor:pointer;border-bottom:2px solid transparent;transition:color .15s,border-color .15s;white-space:nowrap}',
        '.__nx_tab:hover{color:#e2e8f0}',
        '.__nx_tab.__nx_active{color:#a5b4fc;border-bottom-color:#6366f1}',
        '.__nx_close{margin-left:auto;background:none;border:none;color:#64748b;font-size:18px;cursor:pointer;padding:0 4px;line-height:1}',
        '.__nx_close:hover{color:#e2e8f0}',
        '.__nx_body{flex:1;overflow:auto;padding:10px 14px}',
        '.__nx_grid{display:grid;grid-template-columns:200px 1fr;height:100%;gap:0}',
        '.__nx_sidebar{border-right:1px solid #2d2d4e;overflow-y:auto;padding:6px 0}',
        '.__nx_sidebar_item{padding:5px 12px;cursor:pointer;font-size:12px;color:#94a3b8;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;border-left:2px solid transparent}',
        '.__nx_sidebar_item:hover{background:#1e1e3a;color:#e2e8f0}',
        '.__nx_sidebar_item.__nx_sel{background:#1e1e3a;color:#a5b4fc;border-left-color:#6366f1}',
        '.__nx_detail{overflow:auto;padding:10px 14px}',
        '.__nx_kv{display:grid;grid-template-columns:140px 1fr;gap:2px 8px;align-items:start}',
        '.__nx_k{color:#64748b;font-size:11px;padding:2px 0}',
        '.__nx_v{color:#e2e8f0;font-size:12px;padding:2px 0;word-break:break-all}',
        '.__nx_badge{display:inline-block;font-size:10px;padding:1px 5px;border-radius:3px;margin-left:4px}',
        '.__nx_badge_number{background:#1e3a5f;color:#60a5fa}',
        '.__nx_badge_string{background:#1a3a1a;color:#4ade80}',
        '.__nx_badge_boolean{background:#3a1a3a;color:#c084fc}',
        '.__nx_badge_arr{background:#3a2a1a;color:#fb923c}',
        '.__nx_badge_null{background:#2a2a2a;color:#64748b}',
        '.__nx_log_row{display:flex;gap:8px;padding:3px 0;border-bottom:1px solid #1a1a2e;font-size:12px;align-items:baseline}',
        '.__nx_log_ts{color:#475569;font-size:10px;flex-shrink:0;width:60px}',
        '.__nx_log_name{color:#a5b4fc;flex-shrink:0;min-width:120px}',
        '.__nx_log_comp{color:#64748b;font-size:11px;flex-shrink:0}',
        '.__nx_log_data{color:#94a3b8;word-break:break-all}',
        '.__nx_net_ok{color:#4ade80}.__nx_net_err{color:#f87171}.__nx_net_pend{color:#fbbf24}',
        '.__nx_section_title{font-size:10px;color:#475569;text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px}',
        '.__nx_empty{color:#475569;font-size:12px;padding:20px 0;text-align:center}',
        '.__nx_resize{height:4px;background:#2d2d4e;cursor:ns-resize;flex-shrink:0}',
        '.__nx_resize:hover{background:#6366f1}',
        '.__nx_store_edit{background:#1a1a2e;border:1px solid #2d2d4e;color:#e2e8f0;font:12px monospace;padding:2px 6px;border-radius:3px;width:100%}',
        '.__nx_store_edit:focus{outline:none;border-color:#6366f1}',
        '.__nx_btn_sm{background:#1e1e3a;border:1px solid #2d2d4e;color:#a5b4fc;font:11px monospace;padding:2px 8px;border-radius:3px;cursor:pointer}',
        '.__nx_btn_sm:hover{background:#2d2d4e}',
        '.__nx_build_row{display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid #1a1a2e;font-size:12px}',
        '.__nx_build_label{color:#64748b}',
        '.__nx_build_val{color:#e2e8f0}',
        '.__nx_timing_bar{height:6px;background:#6366f1;border-radius:3px;margin-top:2px}',
    ].join('');
    document.head.appendChild(s);
}

function _toggle() { _open = !_open; _root.style.display = _open ? 'flex' : 'none'; _root.style.flexDirection = 'column'; if (_open) _render(); }
document.addEventListener('keydown', function(e) { if (e.altKey && e.key === 'd') _toggle(); });

// ── helpers ───────────────────────────────────────────────────────────────────
function _ts(ts) { var d = new Date(ts); return [d.getHours(),d.getMinutes(),d.getSeconds()].map(function(n){return String(n).padStart(2,'0');}).join(':'); }
function _esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function _badge(val) {
    var t = Array.isArray(val) ? 'arr' : typeof val;
    var label = Array.isArray(val) ? '['+val.length+']' : val === null ? 'null' : String(val);
    if (label.length > 40) label = label.slice(0,40) + '…';
    return '<span class="__nx_badge __nx_badge_' + t + '">' + _esc(label) + '</span>';
}
function _fmtBytes(n) { if (n < 1024) return n+' B'; if (n < 1048576) return (n/1024).toFixed(1)+' KB'; return (n/1048576).toFixed(2)+' MB'; }
function _getComponents() { return (window.NEXPH && window.NEXPH.components) || {}; }
function _getStores()     { return (window.NEXPH && window.NEXPH.stores)     || {}; }

var _selComp = null, _selStore = null;

// ── render ────────────────────────────────────────────────────────────────────
function _render() {
    if (!_open) return;
    var builtinTabs = { components: '⬡ Components', stores: '⬡ Stores', events: '⚡ Events', network: '⇅ Network', build: '⚙ Build' };
    var allTabs = Object.assign({}, builtinTabs);
    Object.keys(_pluginTabs).forEach(function(k) { allTabs[k] = _pluginTabs[k].label; });

    var html = '<div class="__nx_topbar"><span>NEXPH DevTools</span>';
    Object.keys(allTabs).forEach(function(t) {
        html += '<button class="__nx_tab' + (t === _tab ? ' __nx_active' : '') + '" data-tab="' + t + '">' + _esc(allTabs[t]) + '</button>';
    });
    html += '<button class="__nx_close" data-close>✕</button></div>';
    html += '<div class="__nx_resize" data-resize></div>';
    html += '<div class="__nx_body">' + _renderTab() + '</div>';
    _root.innerHTML = html;
    _bindEvents();
}

function _renderTab() {
    if (_tab === 'components') return _renderComponents();
    if (_tab === 'stores')     return _renderStores();
    if (_tab === 'events')     return _renderEvents();
    if (_tab === 'network')    return _renderNetwork();
    if (_tab === 'build')      return _renderBuild();
    if (_pluginTabs[_tab])     return _pluginTabs[_tab].render();
    return '';
}

// ── Components tab ────────────────────────────────────────────────────────────
function _renderComponents() {
    var comps = _getComponents(), names = Object.keys(comps);
    if (!names.length) return '<div class="__nx_empty">No components registered yet.</div>';
    if (!_selComp || !comps[_selComp]) _selComp = names[0];
    var sidebar = '<div class="__nx_sidebar">' + names.map(function(n) {
        return '<div class="__nx_sidebar_item' + (n === _selComp ? ' __nx_sel' : '') + '" data-comp="' + _esc(n) + '">' + _esc(n) + '</div>';
    }).join('') + '</div>';
    var comp   = comps[_selComp];
    var state  = comp.state || {};
    var detail = '<div class="__nx_detail"><div class="__nx_section_title">State</div><div class="__nx_kv">';
    Object.keys(state).forEach(function(k) {
        if (k.startsWith('_')) return;
        detail += '<div class="__nx_k">' + _esc(k) + '</div><div class="__nx_v">' + _badge(state[k]) + '</div>';
    });
    detail += '</div>';
    var refs = comp.refs || {}, refKeys = Object.keys(refs);
    if (refKeys.length) {
        detail += '<div class="__nx_section_title" style="margin-top:10px">Refs</div><div class="__nx_kv">';
        refKeys.forEach(function(k) { detail += '<div class="__nx_k">' + _esc(k) + '</div><div class="__nx_v" style="color:#64748b">&lt;' + _esc(refs[k].tagName.toLowerCase()) + '&gt;</div>'; });
        detail += '</div>';
    }
    var mKeys = Object.keys(comp.methods || {});
    if (mKeys.length) {
        detail += '<div class="__nx_section_title" style="margin-top:10px">Methods</div><div style="display:flex;flex-wrap:wrap;gap:4px">';
        mKeys.forEach(function(m) { detail += '<button class="__nx_btn_sm" data-call-method="' + _esc(_selComp) + ':' + _esc(m) + '">' + _esc(m) + '()</button>'; });
        detail += '</div>';
    }
    detail += '</div>';
    return '<div class="__nx_grid">' + sidebar + detail + '</div>';
}

// ── Stores tab ────────────────────────────────────────────────────────────────
function _renderStores() {
    var stores = _getStores(), names = Object.keys(stores);
    if (!names.length) return '<div class="__nx_empty">No stores registered.</div>';
    if (!_selStore || !stores[_selStore]) _selStore = names[0];
    var sidebar = '<div class="__nx_sidebar">' + names.map(function(n) {
        return '<div class="__nx_sidebar_item' + (n === _selStore ? ' __nx_sel' : '') + '" data-store="' + _esc(n) + '">' + _esc(n) + '</div>';
    }).join('') + '</div>';
    var store  = stores[_selStore], state = store.state || {};
    var detail = '<div class="__nx_detail"><div class="__nx_section_title">State — <span style="color:#64748b">' + _esc(_selStore) + '</span></div><div class="__nx_kv">';
    Object.keys(state).forEach(function(k) {
        var vStr = typeof state[k] === 'object' ? JSON.stringify(state[k]) : String(state[k]);
        detail += '<div class="__nx_k">' + _esc(k) + '</div><div class="__nx_v"><input class="__nx_store_edit" data-store-key="' + _esc(k) + '" value="' + _esc(vStr) + '"></div>';
    });
    detail += '</div>';
    var aKeys = Object.keys(store.actions || {});
    if (aKeys.length) {
        detail += '<div class="__nx_section_title" style="margin-top:10px">Actions</div><div style="display:flex;flex-wrap:wrap;gap:4px">';
        aKeys.forEach(function(a) { detail += '<button class="__nx_btn_sm" data-call-action="' + _esc(_selStore) + ':' + _esc(a) + '">' + _esc(a) + '()</button>'; });
        detail += '</div>';
    }
    detail += '</div>';
    return '<div class="__nx_grid">' + sidebar + detail + '</div>';
}

// ── Events tab ────────────────────────────────────────────────────────────────
function _renderEvents() {
    if (!_eventLog.length) return '<div class="__nx_empty">No events captured yet.</div>';
    var html = '<div style="display:flex;justify-content:space-between;margin-bottom:6px"><div class="__nx_section_title">Event Log (' + _eventLog.length + ')</div><button class="__nx_btn_sm" data-clear-events>Clear</button></div>';
    _eventLog.forEach(function(e) {
        html += '<div class="__nx_log_row"><span class="__nx_log_ts">' + _ts(e.ts) + '</span><span class="__nx_log_name">' + _esc(e.name) + '</span><span class="__nx_log_comp">' + _esc(e.component || '') + '</span><span class="__nx_log_data">' + (e.data !== undefined ? _esc(JSON.stringify(e.data)) : '') + '</span></div>';
    });
    return html;
}

// ── Network tab ───────────────────────────────────────────────────────────────
function _renderNetwork() {
    if (!_netLog.length) return '<div class="__nx_empty">No fetch requests captured yet.</div>';
    var html = '<div style="display:flex;justify-content:space-between;margin-bottom:6px"><div class="__nx_section_title">Network (' + _netLog.length + ')</div><button class="__nx_btn_sm" data-clear-net>Clear</button></div>';
    _netLog.forEach(function(r) {
        var sc = r.status === '…' ? '__nx_net_pend' : (r.status >= 200 && r.status < 300 ? '__nx_net_ok' : '__nx_net_err');
        html += '<div class="__nx_log_row"><span class="__nx_log_ts">' + _ts(r.ts) + '</span><span class="__nx_log_comp" style="width:40px">' + _esc(r.method) + '</span><span class="' + sc + '" style="width:36px">' + _esc(String(r.status)) + '</span><span class="__nx_log_data" style="flex:1">' + _esc(r.url) + '</span><span class="__nx_log_ts" style="text-align:right">' + (r.time || '') + '</span></div>';
    });
    return html;
}

// ── Build tab ─────────────────────────────────────────────────────────────────
function _renderBuild() {
    var html  = '<div class="__nx_section_title">Build Info</div>';
    var sizes = BUILD_INFO.sizes || {};
    var rows  = [
        ['Entry',      BUILD_INFO.entry   || '—'],
        ['Built at',   BUILD_INFO.builtAt || '—'],
        ['Components', (BUILD_INFO.components || []).join(', ') || '—'],
    ];
    if (sizes.html)  rows.push(['HTML',  _fmtBytes(sizes.html)]);
    if (sizes.js)    rows.push(['JS',    _fmtBytes(sizes.js)]);
    if (sizes.css)   rows.push(['CSS',   _fmtBytes(sizes.css)]);
    if (sizes.total) rows.push(['Total', _fmtBytes(sizes.total)]);
    rows.forEach(function(r) {
        html += '<div class="__nx_build_row"><span class="__nx_build_label">' + _esc(r[0]) + '</span><span class="__nx_build_val">' + _esc(r[1]) + '</span></div>';
    });
    html += '<div class="__nx_section_title" style="margin-top:12px">Runtime</div>';
    var cNames = Object.keys(_getComponents());
    html += '<div class="__nx_build_row"><span class="__nx_build_label">Mounted components</span><span class="__nx_build_val">' + cNames.length + '</span></div>';
    html += '<div class="__nx_build_row"><span class="__nx_build_label">Component names</span><span class="__nx_build_val">' + _esc(cNames.join(', ') || '—') + '</span></div>';
    html += '<div class="__nx_build_row"><span class="__nx_build_label">Events captured</span><span class="__nx_build_val">' + _eventLog.length + '</span></div>';
    html += '<div class="__nx_build_row"><span class="__nx_build_label">Network requests</span><span class="__nx_build_val">' + _netLog.length + '</span></div>';
    return html;
}

// ── event binding ─────────────────────────────────────────────────────────────
function _bindEvents() {
    _root.querySelectorAll('[data-tab]').forEach(function(el) {
        el.addEventListener('click', function() { _tab = el.getAttribute('data-tab'); _render(); });
    });
    var closeBtn = _root.querySelector('[data-close]');
    if (closeBtn) closeBtn.addEventListener('click', _toggle);
    _root.querySelectorAll('[data-comp]').forEach(function(el) {
        el.addEventListener('click', function() { _selComp = el.getAttribute('data-comp'); _render(); });
    });
    _root.querySelectorAll('[data-store]').forEach(function(el) {
        el.addEventListener('click', function() { _selStore = el.getAttribute('data-store'); _render(); });
    });
    _root.querySelectorAll('[data-store-key]').forEach(function(inp) {
        inp.addEventListener('change', function() {
            var key = inp.getAttribute('data-store-key');
            var stores = _getStores();
            if (!stores[_selStore]) return;
            var parsed; try { parsed = JSON.parse(inp.value); } catch(e) { parsed = inp.value; }
            stores[_selStore].state[key] = parsed;
            if (stores[_selStore].sync) stores[_selStore].sync();
            _render();
        });
    });
    _root.querySelectorAll('[data-call-method]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var parts = btn.getAttribute('data-call-method').split(':');
            var comps = _getComponents();
            if (comps[parts[0]] && comps[parts[0]].methods[parts[1]]) {
                comps[parts[0]].methods[parts[1]](null, null);
                comps[parts[0]].updateUI();
                setTimeout(_render, 50);
            }
        });
    });
    _root.querySelectorAll('[data-call-action]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var parts  = btn.getAttribute('data-call-action').split(':');
            var stores = _getStores();
            if (stores[parts[0]] && stores[parts[0]].actions && stores[parts[0]].actions[parts[1]]) {
                stores[parts[0]].actions[parts[1]]();
                setTimeout(_render, 50);
            }
        });
    });
    var ce = _root.querySelector('[data-clear-events]');
    if (ce) ce.addEventListener('click', function() { _eventLog = []; _render(); });
    var cn = _root.querySelector('[data-clear-net]');
    if (cn) cn.addEventListener('click', function() { _netLog = []; _render(); });
    var rb = _root.querySelector('[data-resize]');
    if (rb) {
        rb.addEventListener('mousedown', function(e) {
            e.preventDefault();
            var startY = e.clientY, startH = _root.offsetHeight;
            function onMove(ev) { _root.style.height = Math.max(120, Math.min(window.innerHeight * 0.85, startH + (startY - ev.clientY))) + 'px'; }
            function onUp()   { document.removeEventListener('mousemove', onMove); document.removeEventListener('mouseup', onUp); }
            document.addEventListener('mousemove', onMove);
            document.addEventListener('mouseup', onUp);
        });
    }
}

setInterval(function() { if (_open && ['components','stores','build'].includes(_tab)) _render(); }, 500);

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', _boot);
else _boot();
})();
</script>
HTML;
    }

    private function buildExtraTabsJs(array $extraTabs): string
    {
        if (empty($extraTabs)) return '// no plugin tabs';
        $lines = [];
        foreach ($extraTabs as $name => $js) {
            $lines[] = "// plugin tab: {$name}";
            $lines[] = $js;
        }
        return implode("\n", $lines);
    }
}
