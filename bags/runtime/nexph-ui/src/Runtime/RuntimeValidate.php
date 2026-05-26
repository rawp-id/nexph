<?php

namespace Nexph\Runtime;

/**
 * Phase 4: runtime-validate module.
 * Injected only when data-nexph-validate is present in compiled HTML.
 */
class RuntimeValidate
{
    public function generate(): string
    {
        return <<<'JS'
(function() {
'use strict';
var RULES = {
    required:     function(v)    { return v !== null && v !== undefined && String(v).trim() !== ''; },
    email:        function(v)    { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); },
    min:          function(v, n) { return String(v).length >= parseInt(n); },
    max:          function(v, n) { return String(v).length <= parseInt(n); },
    minval:       function(v, n) { return parseFloat(v) >= parseFloat(n); },
    maxval:       function(v, n) { return parseFloat(v) <= parseFloat(n); },
    pattern:      function(v, p) { return new RegExp(p).test(v); },
    numeric:      function(v)    { return /^-?\d+(\.\d+)?$/.test(v); },
    alpha:        function(v)    { return /^[a-zA-Z]+$/.test(v); },
    alphanumeric: function(v)    { return /^[a-zA-Z0-9]+$/.test(v); },
    url:          function(v)    { try { new URL(v); return true; } catch(e) { return false; } },
    confirmed:    function(v, f) { var o = document.querySelector('[name="' + f + '"]'); return o ? v === o.value : false; },
};
var MESSAGES = {
    required: 'This field is required.',
    email: 'Enter a valid email address.',
    min: 'Must be at least {0} characters.',
    max: 'Must be at most {0} characters.',
    minval: 'Must be at least {0}.',
    maxval: 'Must be at most {0}.',
    pattern: 'Invalid format.',
    numeric: 'Must be a number.',
    alpha: 'Letters only.',
    alphanumeric: 'Letters and numbers only.',
    url: 'Enter a valid URL.',
    confirmed: 'Fields do not match.',
};

function msg(rule, param) {
    return (MESSAGES[rule] || rule).replace('{0}', param || '');
}

function validateField(el) {
    var rules  = el.getAttribute('data-nexph-validate');
    if (!rules) return true;
    var errors = [];
    rules.split('|').forEach(function(rule) {
        var parts = rule.split(':');
        var name  = parts[0].trim();
        var param = parts[1] ? parts[1].trim() : null;
        if (RULES[name] && !RULES[name](el.value, param)) errors.push(msg(name, param));
    });
    var id  = el.getAttribute('data-nexph-validate-id') || el.name || el.id;
    var err = document.querySelector('[data-nexph-error="' + id + '"]');
    el.setAttribute('data-nexph-valid', errors.length === 0 ? 'true' : 'false');
    if (err) { err.textContent = errors[0] || ''; err.style.display = errors.length ? '' : 'none'; }
    errors.length ? (el.classList.add('nx-invalid'), el.classList.remove('nx-valid'))
                  : (el.classList.add('nx-valid'),   el.classList.remove('nx-invalid'));
    return errors.length === 0;
}

function validateForm(form) {
    var valid = true;
    form.querySelectorAll('[data-nexph-validate]').forEach(function(el) {
        if (!validateField(el)) valid = false;
    });
    return valid;
}

document.addEventListener('input',  function(e) { if (e.target.hasAttribute('data-nexph-validate')) validateField(e.target); });
document.addEventListener('blur',   function(e) { if (e.target.hasAttribute('data-nexph-validate')) validateField(e.target); }, true);
document.addEventListener('submit', function(e) {
    if (!e.target.hasAttribute('data-nexph-validate-form')) return;
    if (!validateForm(e.target)) { e.preventDefault(); e.stopPropagation(); }
});

window.NEXPH.validate = { field: validateField, form: validateForm, rules: RULES };
})();
JS;
    }
}
