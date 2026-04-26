(function () {
    'use strict';

    function parseYear(v) {
        var n = parseInt(String(v), 10);
        if (isNaN(n) || n < 1990 || n > 2100) {
            return null;
        }
        return n;
    }

    function normalizeReportCode(code) {
        return String(code || '')
            .trim()
            .replace(/\s+/g, '')
            .toUpperCase();
    }

    function getSelectedReportCode() {
        var checked = document.querySelector('input[name="report_id"]:checked');
        if (!checked) {
            return '';
        }
        var c = checked.getAttribute('data-report-code');
        if (c !== null && String(c).trim() !== '') {
            return String(c);
        }
        var label = checked.closest('label');
        if (label && label.getAttribute) {
            c = label.getAttribute('data-report-code');
            if (c !== null && String(c).trim() !== '') {
                return String(c);
            }
        }
        return '';
    }

    function setPersonalRowVisible(show) {
        var row = document.getElementById('reports_rpa_personal_row');
        if (!row) {
            return;
        }
        if (show) {
            row.removeAttribute('hidden');
            row.style.display = '';
        } else {
            row.setAttribute('hidden', 'hidden');
            row.style.display = 'none';
            var cb = document.getElementById('reports_include_personal_data');
            if (cb) {
                cb.checked = false;
            }
        }
    }

    function setTrainingTypeRowVisible(show) {
        var row = document.getElementById('reports_rpa_training_type_row');
        if (!row) {
            return;
        }
        if (show) {
            row.removeAttribute('hidden');
            row.style.display = '';
        } else {
            row.setAttribute('hidden', 'hidden');
            row.style.display = 'none';
            var sel = document.getElementById('reports_training_type');
            if (sel) {
                sel.value = 'all';
            }
        }
    }

    function setInitialDateRowVisible(show) {
        var row = document.getElementById('reports_rpa_fc_02_initial_row');
        if (!row) {
            return;
        }
        if (show) {
            row.removeAttribute('hidden');
            row.style.display = '';
        } else {
            row.setAttribute('hidden', 'hidden');
            row.style.display = 'none';
            var cb = document.getElementById('reports_initial_date_only');
            if (cb) {
                cb.checked = false;
            }
        }
    }

    function setRpeFc02AssistRowVisible(show) {
        var row = document.getElementById('reports_rpe_fc_02_assist_row');
        var hidden = document.getElementById('reports_amb_assistents_hidden');
        var cb2 = document.getElementById('reports_amb_assistents_rpefc02');
        if (!row) {
            return;
        }
        if (show) {
            row.removeAttribute('hidden');
            row.style.display = '';
            if (hidden) {
                hidden.disabled = false;
            }
            if (cb2) {
                cb2.disabled = false;
            }
        } else {
            row.setAttribute('hidden', 'hidden');
            row.style.display = 'none';
            if (hidden) {
                hidden.disabled = true;
            }
            if (cb2) {
                cb2.disabled = true;
                cb2.checked = true;
            }
        }
    }

    function setRpeFc04InscritsRowVisible(show) {
        var row = document.getElementById('reports_rpe_fc_04_inscrits_row');
        var hidden = document.getElementById('reports_dades_inscrits_hidden');
        var cb = document.getElementById('reports_dades_inscrits_rpefc04');
        if (!row) {
            return;
        }
        if (show) {
            row.removeAttribute('hidden');
            row.style.display = '';
            if (hidden) {
                hidden.disabled = false;
            }
            if (cb) {
                cb.disabled = false;
            }
        } else {
            row.setAttribute('hidden', 'hidden');
            row.style.display = 'none';
            if (hidden) {
                hidden.disabled = true;
            }
            if (cb) {
                cb.disabled = true;
                cb.checked = true;
            }
        }
    }

    function updateRpaPersonalRowVisibility() {
        var code = getSelectedReportCode();
        if (code === '') {
            setPersonalRowVisible(false);
            return;
        }
        setPersonalRowVisible(normalizeReportCode(code) === 'RPAFC-01');
    }

    function updateRpaTrainingTypeRowVisibility() {
        var code = getSelectedReportCode();
        if (code === '') {
            setTrainingTypeRowVisible(false);
            return;
        }
        var n = normalizeReportCode(code);
        setTrainingTypeRowVisible(
            n === 'RPAFC-01' || n === 'RPAFC-03' || n === 'RPAFC-02' || n === 'RPAFC-04' || n === 'REEFC-01'
        );
    }

    function updateRpaFc02InitialRowVisibility() {
        var code = getSelectedReportCode();
        if (code === '') {
            setInitialDateRowVisible(false);
            return;
        }
        setInitialDateRowVisible(normalizeReportCode(code) === 'RPAFC-02');
    }

    function updateRpeFc02AssistRowVisibility() {
        var code = getSelectedReportCode();
        if (code === '') {
            setRpeFc02AssistRowVisible(false);
            return;
        }
        setRpeFc02AssistRowVisible(normalizeReportCode(code) === 'RPEFC-02');
    }

    function updateRpeFc04InscritsRowVisibility() {
        var code = getSelectedReportCode();
        if (code === '') {
            setRpeFc04InscritsRowVisible(false);
            return;
        }
        setRpeFc04InscritsRowVisible(normalizeReportCode(code) === 'RPEFC-04');
    }

    function updateReportSpecificRows() {
        updateRpaPersonalRowVisibility();
        updateRpaTrainingTypeRowVisibility();
        updateRpaFc02InitialRowVisibility();
        updateRpeFc02AssistRowVisibility();
        updateRpeFc04InscritsRowVisibility();
    }

    function initYearSteppers() {
        var input = document.getElementById('reports_program_year');
        var prev = document.getElementById('reports-year-prev');
        var next = document.getElementById('reports-year-next');
        if (!input || !prev || !next) {
            return;
        }

        function setYear(y) {
            input.value = String(y);
            try {
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            } catch (e) {
                /* IE */
            }
        }

        function onPrev(ev) {
            if (ev) {
                ev.preventDefault();
                ev.stopPropagation();
            }
            var y = parseYear(input.value);
            if (y === null) {
                y = new Date().getFullYear();
            }
            if (y > 1990) {
                setYear(y - 1);
            }
        }

        function onNext(ev) {
            if (ev) {
                ev.preventDefault();
                ev.stopPropagation();
            }
            var y = parseYear(input.value);
            if (y === null) {
                y = new Date().getFullYear();
            }
            if (y < 2100) {
                setYear(y + 1);
            }
        }

        prev.addEventListener('click', onPrev, false);
        next.addEventListener('click', onNext, false);
    }

    function initReportRadios() {
        var radios = document.querySelectorAll('input[name="report_id"]');
        radios.forEach(function (radio) {
            radio.addEventListener('change', updateReportSpecificRows);
        });
        var picker = document.querySelector('.reports-picker');
        if (picker) {
            picker.addEventListener('click', function () {
                window.setTimeout(updateReportSpecificRows, 0);
            });
        }
        updateReportSpecificRows();
    }

    function init() {
        initYearSteppers();
        initReportRadios();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
