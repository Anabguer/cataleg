(function () {
    'use strict';

    function cfg() {
        return window.APP_PEOPLE || {};
    }
    function $(s, r) {
        return (r || document).querySelector(s);
    }
    function field(form, dataField) {
        return form ? form.querySelector('[data-field="' + dataField + '"]') : null;
    }
    function overlay() {
        return document.getElementById('people-modal-overlay');
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
    function pad5(n) {
        n = parseInt(n, 10);
        if (isNaN(n) || n < 0) {
            return '00000';
        }
        var s = String(n);
        while (s.length < 5) {
            s = '0' + s;
        }
        return s.length > 5 ? s.slice(-5) : s;
    }
    function updateCreateCodePreview() {
        var prev = document.querySelector('[data-people-code-preview]');
        if (!prev) {
            return;
        }
        var nc = cfg().nextPersonCode;
        if (nc == null || nc === '') {
            prev.textContent = '—';
            return;
        }
        prev.textContent = pad5(parseInt(nc, 10));
    }
    function setModalLayout(mode) {
        var form = $('#people-modal-form');
        var banner = document.querySelector('.js-people-catalog-banner');
        var submitBtn = document.querySelector('[data-people-submit-btn]');
        var createBlock = document.querySelector('.js-people-create-code-block');
        var editRow = document.querySelector('.js-people-edit-code-row');
        var dis = mode === 'catalog';
        if (form) {
            form.dataset.mode = mode;
        }
        if (banner) {
            banner.hidden = mode !== 'catalog';
        }
        if (submitBtn) {
            submitBtn.hidden = mode === 'catalog';
        }
        if (createBlock) {
            createBlock.hidden = mode !== 'create';
        }
        if (editRow) {
            editRow.hidden = mode === 'create';
        }
        [field(form, 'last_name_1'), field(form, 'last_name_2'), field(form, 'first_name'), field(form, 'dni'), field(form, 'email'), field(form, 'job_position_id'), field(form, 'is_active')].forEach(function (el) {
            if (el) {
                el.disabled = dis;
            }
        });
        var pc = document.querySelector('[data-people-person-code-readonly]');
        if (pc) {
            pc.disabled = true;
        }
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
        updateCreateCodePreview();
        var form = $('#people-modal-form');
        var first = form ? form.querySelector('input:not([type="hidden"]):not([disabled]):not([readonly])') : null;
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
        var w = form.querySelector('.js-people-msg');
        if (w) {
            w.hidden = true;
        }
        var g = form.querySelector('[data-people-form-error]');
        if (g) {
            g.textContent = '';
        }
    }
    function showErrors(form, e) {
        clearErrors(form);
        Object.keys(e || {}).forEach(function (k) {
            if (k === '_general') {
                var w = form.querySelector('.js-people-msg');
                var g = form.querySelector('[data-people-form-error]');
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
        var h = $('[data-people-modal-heading]');
        var s = $('[data-people-modal-subheading]');
        if (h) {
            h.textContent = isCreate ? 'Nova persona' : 'Actualització';
        }
        if (s) {
            s.textContent = isCreate ? 'Introdueix les dades de la nova persona' : 'Modifica la informació de la persona';
        }
    }
    function removeInjectedJobPositionOptions(select) {
        if (!select) {
            return;
        }
        select.querySelectorAll('option[data-people-jp-injected="1"]').forEach(function (o) {
            o.remove();
        });
    }
    function ensureJobPositionOption(select, idStr, label) {
        if (!select || !idStr) {
            return;
        }
        var i;
        for (i = 0; i < select.options.length; i++) {
            if (select.options[i].value === idStr) {
                return;
            }
        }
        var opt = document.createElement('option');
        opt.value = idStr;
        opt.setAttribute('data-people-jp-injected', '1');
        opt.textContent = label || idStr;
        select.appendChild(opt);
    }
    function applyJobPositionFromApi(form, a) {
        var jps = field(form, 'job_position_id');
        if (!jps) {
            return;
        }
        removeInjectedJobPositionOptions(jps);
        var jid = a.job_position_id != null && a.job_position_id !== '' ? String(a.job_position_id) : '';
        jps.value = jid;
        if (jid && jps.value !== jid) {
            ensureJobPositionOption(jps, jid, a.job_position_option_label || jid);
            jps.value = jid;
        }
    }
    function reset(form) {
        removeInjectedJobPositionOptions(field(form, 'job_position_id'));
        form.reset();
        var idField = field(form, 'id');
        if (idField) {
            idField.value = '';
        }
        form.dataset.mode = 'create';
        var act = field(form, 'is_active');
        if (act) {
            act.checked = true;
            act.disabled = false;
        }
        clearErrors(form);
        var jps = field(form, 'job_position_id');
        if (jps) {
            jps.selectedIndex = 0;
        }
        setModalLayout('create');
        updateCreateCodePreview();
    }
    function openCreate() {
        if (!cfg().canCreate) {
            return;
        }
        var f = $('#people-modal-form');
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
        var f = $('#people-modal-form');
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
                if (!d.ok || !d.person) {
                    if (window.showAlert) {
                        window.showAlert('error', 'Error', 'No s’ha pogut carregar la persona.');
                    }
                    return;
                }
                var a = d.person;
                var idF = field(f, 'id');
                if (idF) {
                    idF.value = String(a.id);
                }
                if (field(f, 'last_name_1')) {
                    field(f, 'last_name_1').value = a.last_name_1 || '';
                }
                if (field(f, 'last_name_2')) {
                    field(f, 'last_name_2').value = a.last_name_2 || '';
                }
                if (field(f, 'first_name')) {
                    field(f, 'first_name').value = a.first_name || '';
                }
                if (field(f, 'dni')) {
                    field(f, 'dni').value = a.dni || '';
                }
                if (field(f, 'email')) {
                    field(f, 'email').value = a.email || '';
                }
                applyJobPositionFromApi(f, a);
                var actF = field(f, 'is_active');
                if (actF) {
                    actF.checked = !!a.is_active;
                }
                var pcRo = document.querySelector('[data-people-person-code-readonly]');
                if (pcRo) {
                    pcRo.value = pad5(parseInt(a.person_code, 10) || 0);
                }
                if (a.is_catalog) {
                    setModalLayout('catalog');
                    var sub = $('[data-people-modal-subheading]');
                    if (sub) {
                        sub.textContent = 'Registre de catàleg (només lectura)';
                    }
                } else {
                    setModalLayout('edit');
                }
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
        var f = $('#people-modal-form');
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
            last_name_1: (fd.get('last_name_1') || '').toString(),
            last_name_2: (fd.get('last_name_2') || '').toString(),
            first_name: (fd.get('first_name') || '').toString(),
            dni: (fd.get('dni') || '').toString(),
            email: (fd.get('email') || '').toString(),
            is_active: actChk && actChk.checked ? '1' : '0'
        };
        var jpid = (fd.get('job_position_id') || '').toString().trim();
        if (jpid !== '') {
            payload.job_position_id = jpid;
        }
        if (id !== '') {
            payload.id = parseInt(id, 10);
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
            'Vols eliminar aquesta persona?',
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
            if (e.target.closest('[data-people-open-create]')) {
                e.preventDefault();
                openCreate();
            }
            if (e.target.closest('[data-people-edit]')) {
                e.preventDefault();
                var btn = e.target.closest('[data-people-edit]');
                var id = parseInt(btn.getAttribute('data-people-edit'), 10);
                if (id > 0) {
                    openEdit(id);
                }
            }
            if (e.target.closest('[data-people-delete]')) {
                e.preventDefault();
                var b = e.target.closest('[data-people-delete]');
                var idd = parseInt(b.getAttribute('data-people-delete'), 10);
                if (idd > 0) {
                    confirmDelete(idd);
                }
            }
            if (e.target.closest('[data-people-modal-close]')) {
                e.preventDefault();
                closeModal();
            }
        });
        var f = $('#people-modal-form');
        if (f) {
            f.addEventListener('submit', submit);
        }
    });
    window.peopleFormModalIsOpen = function () {
        var el = overlay();
        return !!(el && !el.hasAttribute('hidden'));
    };
})();
