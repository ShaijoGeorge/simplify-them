(function () {
    'use strict';

    var TEL_INPUT_SELECTOR = 'input[data-intl-phone="true"]';
    var instances = new WeakMap();

    function normalizeValue(input, iti) {
        if (!input || !iti) {
            return;
        }

        var raw = (input.value || '').trim();
        if (!raw) {
            return;
        }

        var normalized = iti.getNumber();
        if (normalized && normalized.charAt(0) === '+') {
            input.value = normalized;
            return;
        }

        var digitsOnly = raw.replace(/\D+/g, '');
        if (!digitsOnly) {
            return;
        }

        var countryData = iti.getSelectedCountryData();
        var dialCode = countryData && countryData.dialCode ? countryData.dialCode : '';

        if (dialCode && digitsOnly.indexOf(dialCode) !== 0) {
            input.value = '+' + dialCode + digitsOnly;
            return;
        }

        input.value = '+' + digitsOnly;
    }

    function initInput(input) {
        if (!input || instances.has(input) || typeof window.intlTelInput !== 'function') {
            return;
        }

        var defaultCountry = input.dataset.defaultCountry || 'in';

        var iti = window.intlTelInput(input, {
            initialCountry: defaultCountry,
            separateDialCode: true,
            nationalMode: true,
            autoPlaceholder: 'aggressive',
            formatOnDisplay: true,
            strictMode: false,
            utilsScript: 'https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.1/build/js/utils.js',
        });

        instances.set(input, iti);

        input.addEventListener('blur', function () {
            normalizeValue(input, iti);
        });

        input.addEventListener('countrychange', function () {
            normalizeValue(input, iti);
        });

        var form = input.closest('form');
        if (form && !form.dataset.intlPhoneBound) {
            form.dataset.intlPhoneBound = 'true';
            form.addEventListener('submit', function () {
                form.querySelectorAll(TEL_INPUT_SELECTOR).forEach(function (field) {
                    var fieldIti = instances.get(field);
                    normalizeValue(field, fieldIti);
                });
            });
        }

        if ((input.value || '').trim().charAt(0) === '+') {
            normalizeValue(input, iti);
        }
    }

    function initAll(root) {
        if (typeof window.intlTelInput !== 'function') {
            return;
        }

        var scope = root || document;
        scope.querySelectorAll(TEL_INPUT_SELECTOR).forEach(function (input) {
            initInput(input);
        });
    }

    function bootstrapWhenLibraryReady() {
        if (typeof window.intlTelInput === 'function') {
            initAll(document);
            return;
        }

        var retries = 0;
        var timer = window.setInterval(function () {
            retries += 1;
            if (typeof window.intlTelInput === 'function') {
                window.clearInterval(timer);
                initAll(document);
                return;
            }

            if (retries > 40) {
                window.clearInterval(timer);
            }
        }, 100);
    }

    document.addEventListener('DOMContentLoaded', bootstrapWhenLibraryReady);

    document.addEventListener('ea.collection.item-added', function (event) {
        initAll(event.target || document);
    });
})();
