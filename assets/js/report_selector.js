(function () {
    'use strict';

    var cfg = window.APP_REPORT_SELECTOR;
    if (!cfg || typeof cfg.runUrl !== 'string' || !cfg.runUrl) {
        return;
    }

    var year = parseInt(String(cfg.catalogYear || ''), 10);
    if (!year || year < 1990 || year > 2100) {
        return;
    }

    function warnNoSelection() {
        if (typeof window.showAlert === 'function') {
            window.showAlert('warning', 'Avís', 'Selecciona un informe.');
        } else {
            window.alert('Selecciona un informe.');
        }
    }

    function navigateRun(code) {
        var url = cfg.runUrl + (cfg.runUrl.indexOf('?') >= 0 ? '&' : '?');
        url += 'code=' + encodeURIComponent(code) + '&catalog_year=' + encodeURIComponent(String(year));
        window.location.href = url;
    }

    function tryAcceptFromForm(form) {
        if (!form) {
            return;
        }
        var checked = form.querySelector('input[name="report_code"]:checked');
        var code = checked && checked.value ? String(checked.value).trim() : '';
        if (!code) {
            warnNoSelection();
            return;
        }
        navigateRun(code);
    }

    document.addEventListener('submit', function (ev) {
        var form = ev.target;
        if (!form || form.id !== 'report-selector-form') {
            return;
        }
        ev.preventDefault();
        tryAcceptFromForm(form);
    });

    document.addEventListener('click', function (ev) {
        var t = ev.target;
        if (!t || !t.closest) {
            return;
        }

        if (t.closest('.js-report-selector-cancel')) {
            ev.preventDefault();
            document.querySelectorAll('input[name="report_code"]').forEach(function (radio) {
                radio.checked = false;
            });
            return;
        }

        var acceptBtn = t.closest('.js-report-selector-accept');
        if (!acceptBtn) {
            return;
        }
        ev.preventDefault();
        tryAcceptFromForm(document.getElementById('report-selector-form'));
    });
})();
