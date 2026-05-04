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

    var paramDefs = cfg.reportParams && typeof cfg.reportParams === 'object' ? cfg.reportParams : {};

    /**
     * Accepta definició nova { title, fields } o llegat com array de camps.
     * @returns {{ title: string|null, fields: Array }|null}
     */
    function resolveReportParamDefinition(code) {
        var def = paramDefs[code];
        if (!def) {
            return null;
        }
        if (Array.isArray(def)) {
            return { title: null, fields: def };
        }
        if (def.fields && Array.isArray(def.fields) && def.fields.length > 0) {
            return {
                title: def.title != null && String(def.title).trim() !== '' ? String(def.title) : null,
                fields: def.fields,
            };
        }
        return null;
    }

    function warnNoSelection() {
        if (typeof window.showAlert === 'function') {
            window.showAlert('warning', 'Avís', 'Selecciona un informe.');
        } else {
            window.alert('Selecciona un informe.');
        }
    }

    function escAttr(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/"/g, '&quot;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    function navigateRun(code, extraParams) {
        var q = { code: code, catalog_year: String(year) };
        if (extraParams && typeof extraParams === 'object') {
            Object.keys(extraParams).forEach(function (k) {
                var v = extraParams[k];
                if (v !== undefined && v !== null) {
                    q[k] = String(v);
                }
            });
        }
        var base = cfg.runUrl + (cfg.runUrl.indexOf('?') >= 0 ? '&' : '?');
        var usp = new URLSearchParams(q);
        window.location.href = base + usp.toString();
    }

    function buildParamsFormHtml(fields) {
        if (!Array.isArray(fields) || fields.length === 0) {
            return '';
        }
        var html = '<div class="report-params-modal">';
        fields.forEach(function (f) {
            var name = f && f.name ? String(f.name) : '';
            if (!name) {
                return;
            }
            var label = f.label ? String(f.label) : name;
            var id = 'report-param-' + name.replace(/[^a-z0-9_-]/gi, '_');
            var type = f.type ? String(f.type) : 'text';

            html += '<div class="form-group">';
            html += '<label class="form-label" for="' + escAttr(id) + '">' + escAttr(label);
            if (f.required) {
                html += ' <span class="users-modal-form__req">*</span>';
            }
            html += '</label>';

            if (type === 'select' && Array.isArray(f.options)) {
                html += '<select class="form-select" id="' + escAttr(id) + '" data-report-param="' + escAttr(name) + '">';
                f.options.forEach(function (opt) {
                    var ov = opt && opt.value !== undefined ? String(opt.value) : '';
                    var ol = opt && opt.label !== undefined ? String(opt.label) : ov;
                    var sel = f.default !== undefined && String(f.default) === ov ? ' selected' : '';
                    html += '<option value="' + escAttr(ov) + '"' + sel + '>' + escAttr(ol) + '</option>';
                });
                html += '</select>';
            } else if (type === 'textarea') {
                var rows = f.rows ? parseInt(String(f.rows), 10) : 3;
                if (!rows || rows < 2) {
                    rows = 3;
                }
                var defTa = f.default !== undefined && f.default !== null ? String(f.default) : '';
                html += '<textarea class="form-input" id="' + escAttr(id) + '" data-report-param="' + escAttr(name) + '" rows="' + rows + '">' + escAttr(defTa) + '</textarea>';
            } else {
                var defTx = f.default !== undefined && f.default !== null ? String(f.default) : '';
                html += '<input class="form-input" type="text" id="' + escAttr(id) + '" data-report-param="' + escAttr(name) + '" value="' + escAttr(defTx) + '">';
            }
            html += '</div>';
        });
        html += '</div>';
        return html;
    }

    function collectParamsFromModal(root) {
        var out = {};
        if (!root) {
            return out;
        }
        root.querySelectorAll('[data-report-param]').forEach(function (el) {
            var name = el.getAttribute('data-report-param');
            if (!name) {
                return;
            }
            out[name] = el.value;
        });
        return out;
    }

    function openParamsModal(code, fields, modalTitle, onConfirm) {
        var html = buildParamsFormHtml(fields);
        if (!html) {
            onConfirm({});
            return;
        }
        if (typeof window.showActionModal !== 'function') {
            onConfirm({});
            return;
        }
        var title = modalTitle && String(modalTitle).trim() !== '' ? String(modalTitle) : 'Paràmetres de l’informe';
        window.showActionModal({
            title: title,
            message: 'Codi: ' + code,
            size: 'lg',
            type: 'info',
            contentHtml: html,
            buttons: [
                { label: 'Cancel·lar', className: 'btn--ghost', dataClose: true, closeOnClick: true },
                {
                    label: 'Executar',
                    className: 'btn--primary',
                    closeOnClick: true,
                    autofocus: false,
                    onClick: function (closeModal, btn) {
                        var shell = btn && btn.closest ? btn.closest('.js-app-modal') : null;
                        var custom = shell ? shell.querySelector('.report-params-modal') : document.querySelector('.js-app-modal .report-params-modal');
                        var vals = collectParamsFromModal(custom);
                        onConfirm(vals);
                    },
                },
            ],
        });
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
        var resolved = resolveReportParamDefinition(code);
        if (resolved && resolved.fields.length > 0) {
            openParamsModal(code, resolved.fields, resolved.title, function (vals) {
                navigateRun(code, vals);
            });
        } else {
            navigateRun(code, {});
        }
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
