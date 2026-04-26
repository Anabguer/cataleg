(function () {
    'use strict';

    function cfg() {
        return window.APP_JOB_POSITIONS || {};
    }
    function $(s, r) {
        return (r || document).querySelector(s);
    }
    function overlay() {
        return document.getElementById('job-positions-modal-overlay');
    }
    function lock() {
        if (window.lockModalBodyScroll) {
            window.lockModalBodyScroll();
        }
    }
    function unlock() {
        if (window.unlockModalBodyScroll) {
            window.unlockModalBodyScroll();
        }
    }
    function pad2(n) {
        n = parseInt(n, 10);
        if (isNaN(n) || n < 0) {
            return '00';
        }
        return (n < 10 ? '0' : '') + n;
    }
    function pad4(n) {
        n = parseInt(n, 10);
        if (isNaN(n) || n < 0) {
            return '0000';
        }
        var s = String(n);
        while (s.length < 4) {
            s = '0' + s;
        }
        return s.length > 4 ? s.slice(-4) : s;
    }
    function getForm() {
        return document.getElementById('job-positions-modal-form');
    }
    /** Referències dins del formulari actual (sense dependre d’IDs globals ni camps eliminats com is_catalog). */
    function field(form, dataField) {
        return form ? form.querySelector('[data-field="' + dataField + '"]') : null;
    }
    function hasAutoUnits() {
        return (cfg().autoUnits || []).length > 0;
    }
    function getAssignmentMode() {
        var ex = document.querySelector('input[name="assignment_mode"][value="existing"]');
        if (ex && ex.checked && !ex.disabled) {
            return 'existing';
        }
        return 'new';
    }
    function syncPreviewUnitCodeFromAssignment() {
        var form = getForm();
        if (!form) {
            return;
        }
        var mode = getAssignmentMode();
        var uc = '';
        if (mode === 'existing') {
            var sel = document.querySelector('[data-job-positions-existing-unit-select]');
            if (sel && sel.selectedIndex > 0) {
                var opt = sel.options[sel.selectedIndex];
                uc = (opt.getAttribute('data-unit-code') || '').trim();
            }
        } else {
            var nc = cfg().nextAutoUnitCode;
            uc = nc != null ? String(nc) : '8000';
        }
        form.dataset.previewUnitCode = uc;
        updateCodePreview();
    }
    function syncAssignmentUI() {
        var mode = getAssignmentMode();
        var wrap = document.querySelector('[data-job-positions-existing-unit-wrap]');
        var newHint = document.querySelector('.js-job-positions-new-hint');
        var noAutoHint = document.querySelector('.js-job-positions-no-auto-units-hint');
        var au = hasAutoUnits();
        if (wrap) {
            wrap.hidden = mode !== 'existing' || !au;
        }
        if (newHint) {
            newHint.hidden = mode !== 'new' || !au;
        }
        if (noAutoHint) {
            noAutoHint.hidden = au || mode !== 'new';
        }
    }
    function updateCodePreview() {
        var prev = document.querySelector('[data-job-positions-code-preview]');
        var num = document.querySelector('[data-job-positions-position-input]');
        var form = getForm();
        if (!prev || !num || !form) {
            return;
        }
        var uc = form.dataset.previewUnitCode;
        var pn = (num.value || '').trim();
        if (!uc || pn === '' || !/^\d+$/.test(pn)) {
            prev.textContent = '—';
            return;
        }
        var n = parseInt(pn, 10);
        if (n < 1 || n > 99) {
            prev.textContent = '—';
            return;
        }
        prev.textContent = pad4(parseInt(uc, 10)) + '.' + pad2(n);
    }
    function setModalLayout(mode) {
        var form = getForm();
        var assignBlock = document.querySelector('.js-job-positions-assignment-block');
        var banner = document.querySelector('.js-job-positions-catalog-banner');
        var unitRo = document.querySelector('.js-job-positions-edit-unit-readonly');
        var submitBtn = document.querySelector('[data-job-positions-submit-btn]');
        var pos = form ? field(form, 'position_number') : null;
        var name = form ? field(form, 'name') : null;
        var act = form ? field(form, 'is_active') : null;
        if (form) {
            form.dataset.mode = mode;
        }
        if (assignBlock) {
            assignBlock.hidden = mode !== 'create';
        }
        if (banner) {
            banner.hidden = mode !== 'catalog';
        }
        if (unitRo) {
            unitRo.hidden = mode !== 'edit';
        }
        if (submitBtn) {
            submitBtn.hidden = mode === 'catalog';
        }
        var dis = mode === 'catalog';
        [pos, name, act].forEach(function (el) {
            if (el) {
                el.disabled = dis;
            }
        });
    }
    function openModal() {
        var el = overlay();
        if (!el) {
            return;
        }
        el.removeAttribute('hidden');
        el.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(function () {
            el.classList.add('is-visible');
        });
        lock();
        updateCodePreview();
        var form = getForm();
        var first = form ? form.querySelector('input:not([type="hidden"]):not([disabled])') : null;
        if (first) {
            first.focus();
        }
    }
    function closeModal() {
        var el = overlay();
        if (!el) {
            return;
        }
        var ae = document.activeElement;
        if (ae && el.contains(ae) && ae.blur) {
            ae.blur();
        }
        setTimeout(function () {
            el.classList.remove('is-visible');
            el.setAttribute('hidden', 'hidden');
            el.setAttribute('aria-hidden', 'true');
            unlock();
        }, 0);
    }
    function clearErrors(form) {
        form.querySelectorAll('[data-error-for]').forEach(function (p) {
            p.hidden = true;
            p.textContent = '';
        });
        var w = form.querySelector('.js-job-positions-msg');
        if (w) {
            w.hidden = true;
        }
        var g = form.querySelector('[data-job-positions-form-error]');
        if (g) {
            g.textContent = '';
        }
    }
    function showErrors(form, e) {
        clearErrors(form);
        Object.keys(e || {}).forEach(function (k) {
            if (k === '_general') {
                var w = form.querySelector('.js-job-positions-msg');
                var g = form.querySelector('[data-job-positions-form-error]');
                if (w && g) {
                    w.hidden = false;
                    g.textContent = e[k];
                }
                return;
            }
            var p = form.querySelector('[data-error-for="' + k + '"]');
            if (p) {
                p.hidden = false;
                p.textContent = e[k];
            }
        });
    }
    function setMode(isCreate) {
        var h = $('[data-job-positions-modal-heading]');
        var s = $('[data-job-positions-modal-subheading]');
        if (h) {
            h.textContent = isCreate ? 'Nou lloc de treball' : 'Actualització';
        }
        if (s) {
            s.textContent = isCreate ? 'Introdueix les dades del nou lloc' : 'Modifica la informació del lloc';
        }
    }
    function reset(form) {
        form.reset();
        var idField = field(form, 'id');
        if (idField) {
            idField.value = '';
        }
        delete form.dataset.previewUnitCode;
        form.dataset.mode = 'create';
        var act = field(form, 'is_active');
        if (act) {
            act.checked = true;
            act.disabled = false;
        }
        var pos = field(form, 'position_number');
        var name = field(form, 'name');
        if (pos) {
            pos.disabled = false;
        }
        if (name) {
            name.disabled = false;
        }
        var unitTxt = form.querySelector('[data-job-positions-unit-readonly-text]');
        if (unitTxt) {
            unitTxt.textContent = '';
        }
        clearErrors(form);
        setModalLayout('create');
        syncAssignmentUI();
        syncPreviewUnitCodeFromAssignment();
    }
    function openCreate() {
        if (!cfg().canCreate) {
            return;
        }
        var f = $('#job-positions-modal-form');
        if (!f) {
            return;
        }
        reset(f);
        setMode(true);
        openModal();
    }
    function openEdit(id) {
        if (!cfg().canEdit) {
            return;
        }
        var f = $('#job-positions-modal-form');
        if (!f) {
            return;
        }
        reset(f);
        setMode(false);
        fetch((cfg().apiUrl || '') + '?action=get&id=' + encodeURIComponent(String(id)), { credentials: 'same-origin' })
            .then(function (r) {
                return r.json();
            })
            .then(function (d) {
                if (!d.ok || !d.job_position) {
                    if (window.showAlert) {
                        window.showAlert('error', 'Error', 'No s’ha pogut carregar el lloc de treball.');
                    }
                    return;
                }
                var a = d.job_position;
                var idF = field(f, 'id');
                var posF = field(f, 'position_number');
                var nameF = field(f, 'name');
                var actF = field(f, 'is_active');
                if (idF) {
                    idF.value = String(a.id);
                }
                if (posF) {
                    posF.value = String(a.position_number ?? '');
                }
                if (nameF) {
                    nameF.value = a.name || '';
                }
                if (actF) {
                    actF.checked = !!a.is_active;
                }
                f.dataset.previewUnitCode = String(a.unit_code != null ? a.unit_code : '');
                var unitTxt = document.querySelector('[data-job-positions-unit-readonly-text]');
                if (unitTxt) {
                    var unm = a.unit_name || '';
                    unitTxt.textContent =
                        pad4(parseInt(a.unit_code, 10) || 0) + (unm ? ' — ' + unm : '');
                }
                if (a.is_catalog) {
                    setModalLayout('catalog');
                    var s = $('[data-job-positions-modal-subheading]');
                    if (s) {
                        s.textContent = 'Lloc de catàleg (només lectura)';
                    }
                } else {
                    setModalLayout('edit');
                }
                updateCodePreview();
                openModal();
            })
            .catch(function () {
                if (window.showAlert) {
                    window.showAlert('error', 'Error', 'Error de xarxa.');
                }
            });
    }
    function submit(ev) {
        ev.preventDefault();
        var f = $('#job-positions-modal-form');
        if (!f) {
            return;
        }
        if (f.dataset.mode === 'catalog') {
            return;
        }
        clearErrors(f);
        var c = cfg();
        var csrf = c.csrfToken || '';
        var fd = new FormData(f);
        var id = (fd.get('id') || '').toString().trim();
        var actChk = field(f, 'is_active');
        var payload = {
            action: 'save',
            csrf_token: csrf,
            position_number: (fd.get('position_number') || '').toString(),
            name: (fd.get('name') || '').toString(),
            is_active: actChk && actChk.checked ? '1' : '0'
        };
        if (id !== '') {
            payload.id = parseInt(id, 10);
        } else {
            payload.assignment_mode = getAssignmentMode();
            if (payload.assignment_mode === 'existing') {
                payload.existing_unit_id = (fd.get('existing_unit_id') || '').toString();
            }
        }
        fetch(c.apiUrl || '', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify(payload)
        })
            .then(function (r) {
                return r.json().then(function (j) {
                    return { status: r.status, body: j };
                });
            })
            .then(function (res) {
                if (res.body.ok) {
                    closeModal();
                    if (window.showAlert) {
                        window.showAlert('success', 'Èxit', res.body.message || 'Desat.');
                        setTimeout(function () {
                            window.location.reload();
                        }, 650);
                    } else {
                        window.location.reload();
                    }
                    return;
                }
                if (res.body.errors) {
                    showErrors(f, res.body.errors);
                } else if (window.showAlert) {
                    window.showAlert('error', 'Error', 'No s’ha pogut desar.');
                }
            })
            .catch(function () {
                if (window.showAlert) {
                    window.showAlert('error', 'Error', 'Error de xarxa.');
                }
            });
    }
    function confirmDelete(id) {
        if (!cfg().canDelete || !window.showConfirm) {
            return;
        }
        var csrf = cfg().csrfToken || '';
        window.showConfirm(
            'Eliminar registre',
            'Vols eliminar aquest lloc de treball?',
            function () {
                fetch(cfg().apiUrl || '', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                    body: JSON.stringify({ action: 'delete', id: id, csrf_token: csrf })
                })
                    .then(function (r) {
                        return r.json();
                    })
                    .then(function (d) {
                        if (d.ok) {
                            if (window.showAlert) {
                                window.showAlert('success', 'Èxit', d.message || 'Eliminat.');
                                setTimeout(function () {
                                    window.location.reload();
                                }, 650);
                            } else {
                                window.location.reload();
                            }
                        } else if (d.errors) {
                            if (d.errors._general && window.showAlert) {
                                window.showAlert('error', 'Error', d.errors._general);
                            }
                        }
                    })
                    .catch(function () {
                        if (window.showAlert) {
                            window.showAlert('error', 'Error', 'Error de xarxa.');
                        }
                    });
            },
            { confirmLabel: 'Sí', cancelLabel: 'No' }
        );
    }
    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('click', function (e) {
            if (e.target.closest('[data-job-positions-open-create]')) {
                e.preventDefault();
                openCreate();
            }
            if (e.target.closest('[data-job-positions-edit]')) {
                e.preventDefault();
                var btn = e.target.closest('[data-job-positions-edit]');
                var id = parseInt(btn.getAttribute('data-job-positions-edit'), 10);
                if (id > 0) {
                    openEdit(id);
                }
            }
            if (e.target.closest('[data-job-positions-delete]')) {
                e.preventDefault();
                var b = e.target.closest('[data-job-positions-delete]');
                var idd = parseInt(b.getAttribute('data-job-positions-delete'), 10);
                if (idd > 0) {
                    confirmDelete(idd);
                }
            }
            if (e.target.closest('[data-job-positions-modal-close]')) {
                e.preventDefault();
                closeModal();
            }
        });
        var f = $('#job-positions-modal-form');
        if (f) {
            f.addEventListener('submit', submit);
        }
        var num = document.querySelector('[data-job-positions-position-input]');
        if (num) {
            num.addEventListener('input', updateCodePreview);
        }
        document.querySelectorAll('input[name="assignment_mode"]').forEach(function (r) {
            r.addEventListener('change', function () {
                syncAssignmentUI();
                syncPreviewUnitCodeFromAssignment();
            });
        });
        var selUnit = document.querySelector('[data-job-positions-existing-unit-select]');
        if (selUnit) {
            selUnit.addEventListener('change', syncPreviewUnitCodeFromAssignment);
        }
    });
    window.jobPositionsFormModalIsOpen = function () {
        var el = overlay();
        return !!(el && !el.hasAttribute('hidden'));
    };
})();
