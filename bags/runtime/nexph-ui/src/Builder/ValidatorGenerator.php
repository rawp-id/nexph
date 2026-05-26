<?php

namespace Nexph\Builder;

/**
 * Generates the nx-validate runtime JS.
 * Declarative form validation — no boilerplate.
 */
class ValidatorGenerator
{
    /**
     * Generate the validation runtime JS.
     */
    public function generate(): string
    {
        return <<<'JS'
(function() {
    var RULES = {
        required: function(v) { return v !== null && v !== undefined && String(v).trim() !== ''; },
        email: function(v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v); },
        min: function(v, n) { return String(v).length >= parseInt(n); },
        max: function(v, n) { return String(v).length <= parseInt(n); },
        minval: function(v, n) { return parseFloat(v) >= parseFloat(n); },
        maxval: function(v, n) { return parseFloat(v) <= parseFloat(n); },
        pattern: function(v, p) { return new RegExp(p).test(v); },
        numeric: function(v) { return /^-?\d+(\.\d+)?$/.test(v); },
        alpha: function(v) { return /^[a-zA-Z]+$/.test(v); },
        alphanumeric: function(v) { return /^[a-zA-Z0-9]+$/.test(v); },
        url: function(v) { try { new URL(v); return true; } catch(e) { return false; } },
        confirmed: function(v, field) {
            var other = document.querySelector('[name="' + field + '"]');
            return other ? v === other.value : false;
        },
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
        var rules = el.getAttribute('data-nexph-validate');
        if (!rules) return true;
        var value = el.value;
        var errors = [];
        rules.split('|').forEach(function(rule) {
            var parts = rule.split(':');
            var name  = parts[0].trim();
            var param = parts[1] ? parts[1].trim() : null;
            if (RULES[name] && !RULES[name](value, param)) {
                errors.push(msg(name, param));
            }
        });
        showErrors(el, errors);
        return errors.length === 0;
    }

    function showErrors(el, errors) {
        var id  = el.getAttribute('data-nexph-validate-id') || el.name || el.id;
        var err = document.querySelector('[data-nexph-error="' + id + '"]');
        el.setAttribute('data-nexph-valid', errors.length === 0 ? 'true' : 'false');
        if (err) {
            err.textContent = errors[0] || '';
            err.style.display = errors.length ? '' : 'none';
        }
        if (errors.length) {
            el.classList.add('nx-invalid');
            el.classList.remove('nx-valid');
        } else {
            el.classList.add('nx-valid');
            el.classList.remove('nx-invalid');
        }
    }

    function validateForm(form) {
        var fields = form.querySelectorAll('[data-nexph-validate]');
        var valid  = true;
        fields.forEach(function(el) {
            if (!validateField(el)) valid = false;
        });
        return valid;
    }

    // live validation on input/change
    document.addEventListener('input', function(e) {
        if (e.target.hasAttribute('data-nexph-validate')) {
            validateField(e.target);
        }
    });

    document.addEventListener('blur', function(e) {
        if (e.target.hasAttribute('data-nexph-validate')) {
            validateField(e.target);
        }
    }, true);

    // intercept form submit
    document.addEventListener('submit', function(e) {
        var form = e.target;
        if (!form.hasAttribute('data-nexph-validate-form')) return;
        if (!validateForm(form)) {
            e.preventDefault();
            e.stopPropagation();
        }
    });

    window.NEXPH = window.NEXPH || {};
    window.NEXPH.validate = { field: validateField, form: validateForm, rules: RULES };
})();
JS;
    }
}
