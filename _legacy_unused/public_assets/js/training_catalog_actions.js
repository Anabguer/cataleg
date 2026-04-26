(function () {
    'use strict';
    function cfg() { return window.APP_TRAINING_CATALOG_ACTIONS || {}; }
    function $(s, r) { return (r || document).querySelector(s); }
    function overlay() { return document.getElementById('training-catalog-actions-modal-overlay'); }
    function lock() { if (window.lockModalBodyScroll) window.lockModalBodyScroll(); }
    function unlock() { if (window.unlockModalBodyScroll) window.unlockModalBodyScroll(); }
    function openModal() {
        var el = overlay();
        if (!el) return;
        el.removeAttribute('hidden');
        el.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(function () { el.classList.add('is-visible'); });
        lock();
        var f = el.querySelector('#tca_name');
        if (f) f.focus();
    }
    function closeModal() {
        var el = overlay();
        if (!el) return;
        var ae = document.activeElement;
        if (ae && el.contains(ae) && ae.blur) ae.blur();
        setTimeout(function () {
            el.classList.remove('is-visible');
            el.setAttribute('hidden', 'hidden');
            el.setAttribute('aria-hidden', 'true');
            unlock();
        }, 0);
    }
    function clearErrors(form) {
        form.querySelectorAll('[data-error-for]').forEach(function (p) { p.hidden = true; p.textContent = ''; });
        var w = form.querySelector('.js-training-catalog-actions-msg');
        if (w) w.hidden = true;
        var g = form.querySelector('[data-training-catalog-actions-form-error]');
        if (g) g.textContent = '';
    }
    function showErrors(form, e) {
        clearErrors(form);
        Object.keys(e || {}).forEach(function (k) {
            if (k === '_general') {
                var w = form.querySelector('.js-training-catalog-actions-msg'), g = form.querySelector('[data-training-catalog-actions-form-error]');
                if (w && g) { w.hidden = false; g.textContent = typeof e[k] === 'string' ? e[k] : JSON.stringify(e[k]); }
                return;
            }
            var p = form.querySelector('[data-error-for="' + k + '"]');
            if (p) { p.hidden = false; p.textContent = typeof e[k] === 'string' ? e[k] : JSON.stringify(e[k]); }
        });
    }
    function setMode(isCreate) {
        var h = $('[data-training-catalog-actions-modal-heading]'), s = $('[data-training-catalog-actions-modal-subheading]');
        if (h) h.textContent = isCreate ? 'Nova acció al catàleg' : 'Actualització';
        if (s) s.textContent = isCreate ? 'Introdueix les dades de l’acció formativa' : 'Modifica la informació de l’acció';
        var c = $('.js-tca-code-create'), ed = $('.js-tca-code-edit');
        if (c) c.hidden = !isCreate;
        if (ed) ed.hidden = !!isCreate;
    }
    function val(sel, root) {
        var n = $(sel, root);
        return n ? (n.value || '').toString() : '';
    }
    function removeInjectedKnowledgeAreaOptions(select) {
        if (!select) return;
        select.querySelectorAll('option[data-tca-ka-injected="1"]').forEach(function (o) { o.remove(); });
    }
    function ensureKnowledgeAreaOption(select, idStr, label) {
        if (!select || !idStr) return;
        var i;
        for (i = 0; i < select.options.length; i++) {
            if (select.options[i].value === idStr) return;
        }
        var opt = document.createElement('option');
        opt.value = idStr;
        opt.setAttribute('data-tca-ka-injected', '1');
        opt.textContent = label || idStr;
        select.appendChild(opt);
    }
    function applyKnowledgeAreaFromApi(ka, a) {
        if (!ka) return;
        var kid = String(a.knowledge_area_id || '');
        removeInjectedKnowledgeAreaOptions(ka);
        ka.value = kid;
        if (kid && ka.value !== kid) {
            ensureKnowledgeAreaOption(ka, kid, a.knowledge_area_option_label || kid);
            ka.value = kid;
        }
    }
    function reset(f) {
        var kaSel = $('#tca_knowledge_area_id', f);
        removeInjectedKnowledgeAreaOptions(kaSel);
        f.reset();
        var i = $('[data-field="id"]', f);
        if (i) i.value = '';
        $('#tca_is_active').checked = true;
        clearErrors(f);
        var prev = $('#tca_action_code_preview', f);
        if (prev) prev.value = cfg().nextCodeDisplay || '';
        var edIn = $('#tca_action_code_ro', f);
        if (edIn) edIn.value = '';
    }
    function openCreate() {
        if (!cfg().canCreate) return;
        var f = $('#training-catalog-actions-modal-form');
        if (!f) return;
        reset(f);
        setMode(true);
        openModal();
    }
    function openEdit(id) {
        if (!cfg().canEdit) return;
        var f = $('#training-catalog-actions-modal-form');
        if (!f) return;
        reset(f);
        setMode(false);
        fetch((cfg().apiUrl || '') + '?action=get&id=' + encodeURIComponent(String(id)), { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
            if (!d.ok || !d.action) {
                if (window.showAlert) window.showAlert('error', 'Error', 'No s’ha pogut carregar l’acció.');
                return;
            }
            var a = d.action;
            $('[data-field="id"]', f).value = String(a.id);
            var edIn = $('#tca_action_code_ro', f);
            if (edIn) edIn.value = a.action_code_display || String(a.action_code || '');
            $('[data-field="name"]', f).value = a.name || '';
            applyKnowledgeAreaFromApi($('[data-field="knowledge_area_id"]', f), a);
            $('[data-field="target_audience"]', f).value = a.target_audience || '';
            $('[data-field="training_objectives"]', f).value = a.training_objectives || '';
            $('[data-field="conceptual_contents"]', f).value = a.conceptual_contents || '';
            $('[data-field="procedural_contents"]', f).value = a.procedural_contents || '';
            $('[data-field="attitudinal_contents"]', f).value = a.attitudinal_contents || '';
            var dur = $('[data-field="expected_duration_hours"]', f);
            if (dur) {
                if (a.expected_duration_hours != null && String(a.expected_duration_hours) !== '') dur.value = String(a.expected_duration_hours);
                else dur.value = '';
            }
            $('[data-field="status"]', f).value = a.status || '';
            $('#tca_is_active').checked = !!a.is_active;
            openModal();
        }).catch(function () { if (window.showAlert) window.showAlert('error', 'Error', 'Error de xarxa.'); });
    }
    function submit(ev) {
        ev.preventDefault();
        var f = $('#training-catalog-actions-modal-form');
        if (!f) return;
        clearErrors(f);
        var c = cfg(), csrf = c.csrfToken || '';
        var idVal = ($('[data-field="id"]', f).value || '').toString().trim();
        var payload = {
            action: 'save',
            csrf_token: csrf,
            name: val('[data-field="name"]', f),
            knowledge_area_id: val('[data-field="knowledge_area_id"]', f),
            target_audience: val('[data-field="target_audience"]', f),
            training_objectives: val('[data-field="training_objectives"]', f),
            conceptual_contents: val('[data-field="conceptual_contents"]', f),
            procedural_contents: val('[data-field="procedural_contents"]', f),
            attitudinal_contents: val('[data-field="attitudinal_contents"]', f),
            expected_duration_hours: val('[data-field="expected_duration_hours"]', f),
            status: val('[data-field="status"]', f),
            is_active: $('#tca_is_active').checked ? '1' : '0'
        };
        if (idVal !== '') payload.id = parseInt(idVal, 10);
        fetch(c.apiUrl || '', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
            body: JSON.stringify(payload)
        }).then(function (r) { return r.json().then(function (j) { return { status: r.status, body: j }; }); }).then(function (res) {
            if (res.body.ok) {
                closeModal();
                if (window.showAlert) {
                    window.showAlert('success', 'Èxit', res.body.message || 'Desat.');
                    setTimeout(function () { window.location.reload(); }, 650);
                } else window.location.reload();
                return;
            }
            if (res.body.errors) showErrors(f, res.body.errors);
            else if (window.showAlert) window.showAlert('error', 'Error', 'No s’ha pogut desar.');
        }).catch(function () { if (window.showAlert) window.showAlert('error', 'Error', 'Error de xarxa.'); });
    }
    function confirmDelete(id) {
        if (!cfg().canDelete || !window.showConfirm) return;
        var csrf = cfg().csrfToken || '';
        window.showConfirm('Registre actiu', 'Desitja eliminar aquesta acció del catàleg?', function () {
            fetch(cfg().apiUrl || '', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
                body: JSON.stringify({ action: 'delete', id: id, csrf_token: csrf })
            }).then(function (r) { return r.json(); }).then(function (d) {
                if (d.ok) {
                    if (window.showAlert) {
                        window.showAlert('success', 'Èxit', d.message || 'Eliminat.');
                        setTimeout(function () { window.location.reload(); }, 650);
                    } else window.location.reload();
                } else if (d.errors && d.errors._general && window.showAlert) window.showAlert('error', 'Error', d.errors._general);
            }).catch(function () { if (window.showAlert) window.showAlert('error', 'Error', 'Error de xarxa.'); });
        }, { confirmLabel: 'Sí', cancelLabel: 'No' });
    }
    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('click', function (e) {
            if (e.target.closest('[data-training-catalog-actions-open-create]')) { e.preventDefault(); openCreate(); }
            if (e.target.closest('[data-training-catalog-actions-edit]')) {
                e.preventDefault();
                var id = parseInt(e.target.closest('[data-training-catalog-actions-edit]').getAttribute('data-training-catalog-actions-edit'), 10);
                if (id > 0) openEdit(id);
            }
            if (e.target.closest('[data-training-catalog-actions-delete]')) {
                e.preventDefault();
                var idd = parseInt(e.target.closest('[data-training-catalog-actions-delete]').getAttribute('data-training-catalog-actions-delete'), 10);
                if (idd > 0) confirmDelete(idd);
            }
            if (e.target.closest('[data-training-catalog-actions-modal-close]')) { e.preventDefault(); closeModal(); }
        });
        var f = $('#training-catalog-actions-modal-form');
        if (f) f.addEventListener('submit', submit);
    });
    window.trainingCatalogActionsFormModalIsOpen = function () { var el = overlay(); return !!(el && !el.hasAttribute('hidden')); };
})();
