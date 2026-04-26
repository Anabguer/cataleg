/**
 * Accions formatives: llistat, modal amb pestanyes, dates dinàmiques, catàleg.
 */
(function () {
    'use strict';

    function cfg() {
        return window.APP_TRAINING_ACTIONS || {};
    }

    /** Mateix SVG que ui_icon() (document, pencil-square, trash) per a files generats per JS */
    var TA_ICO_DOCUMENT =
        '<span class="ui-icon"><svg class="ui-icon__svg" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8l-6-6z"/><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/></svg></span>';
    var TA_ICO_PENCIL =
        '<span class="ui-icon"><svg class="ui-icon__svg" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></span>';
    var TA_ICO_TRASH =
        '<span class="ui-icon"><svg class="ui-icon__svg" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></span>';
    function $(s, r) {
        return (r || document).querySelector(s);
    }
    function $all(s, r) {
        return Array.prototype.slice.call((r || document).querySelectorAll(s));
    }
    function overlay() {
        return document.getElementById('training-actions-modal-overlay');
    }
    function catalogOverlay() {
        return document.getElementById('training-actions-catalog-overlay');
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
    function form() {
        return document.getElementById('training-actions-modal-form');
    }

    var currentMode = 'create';

    /** Avaluacions: dades en memòria i ordenació client (mateix patró visual que altres llistats, sense paginació) */
    var evalListCache = [];
    var evalSortBy = 'person';
    var evalSortDir = 'asc';
    var evalListCachedAid = 0;

    function openModalMain() {
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
    }
    function closeModalMain() {
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
    function openCatalogModal() {
        var el = catalogOverlay();
        if (!el) {
            return;
        }
        el.removeAttribute('hidden');
        el.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(function () {
            el.classList.add('is-visible');
        });
        lock();
    }
    function closeCatalogModal() {
        var el = catalogOverlay();
        if (!el) {
            return;
        }
        setTimeout(function () {
            el.classList.remove('is-visible');
            el.setAttribute('hidden', 'hidden');
            el.setAttribute('aria-hidden', 'true');
            unlock();
        }, 0);
    }

    function clearErrors(f) {
        if (!f) {
            return;
        }
        f.querySelectorAll('[data-error-for]').forEach(function (p) {
            p.hidden = true;
            p.textContent = '';
        });
        var w = document.querySelector('.training-actions-modal-form__alert');
        if (w) {
            w.hidden = true;
        }
        var g = document.querySelector('[data-ta-form-error]');
        if (g) {
            g.textContent = '';
        }
    }
    function showErrors(f, err) {
        clearErrors(f);
        if (!err) {
            return;
        }
        Object.keys(err).forEach(function (k) {
            if (k === '_general') {
                var w = document.querySelector('.training-actions-modal-form__alert');
                var ge = document.querySelector('[data-ta-form-error]');
                if (w && ge) {
                    w.hidden = false;
                    ge.textContent = typeof err[k] === 'string' ? err[k] : JSON.stringify(err[k]);
                }
                return;
            }
            var p = f.querySelector('[data-error-for="' + k + '"]');
            if (p) {
                p.hidden = false;
                p.textContent = typeof err[k] === 'string' ? err[k] : JSON.stringify(err[k]);
            }
        });
    }

    function switchTab(key) {
        $all('[data-ta-tab]').forEach(function (btn) {
            var k = btn.getAttribute('data-ta-tab');
            var on = k === key;
            btn.classList.toggle('is-active', on);
            btn.setAttribute('aria-selected', on ? 'true' : 'false');
        });
        $all('[data-ta-panel]').forEach(function (panel) {
            var k = panel.getAttribute('data-ta-panel');
            var on = k === key;
            panel.hidden = !on;
            panel.classList.toggle('is-active', on);
        });
        if (key === 'assist') {
            loadAttendeesList();
        }
        if (key === 'docs') {
            loadDocumentsList();
        }
        if (key === 'eval') {
            loadEvaluationsList();
        }
    }

    function setEditable(editable) {
        var f = form();
        if (!f) {
            return;
        }
        var skip = ['id', 'catalog_action_id'];
        f.querySelectorAll('input, select, textarea, button').forEach(function (el) {
            if (el.type === 'hidden') {
                return;
            }
            if (el.hasAttribute('data-ta-modal-close') || el.hasAttribute('data-ta-submit-btn')) {
                return;
            }
            if (el.closest('#training-actions-catalog-overlay')) {
                return;
            }
            if (el.closest('.modal-tabs')) {
                return;
            }
            if (el.hasAttribute('data-ta-eval-sort')) {
                return;
            }
            if (el.hasAttribute('data-ta-remove-date')) {
                el.disabled = !editable;
                return;
            }
            if (el.hasAttribute('data-ta-add-date')) {
                el.disabled = !editable;
                return;
            }
            if (el.hasAttribute('data-ta-range-start') || el.hasAttribute('data-ta-range-end')) {
                el.disabled = !editable;
                return;
            }
            if (el.hasAttribute('data-ta-generate-weekdays')) {
                el.disabled = !editable;
                return;
            }
            var df = el.getAttribute('data-field');
            if (df === 'planned_municipal_cost') {
                el.readOnly = true;
                el.disabled = !editable;
                return;
            }
            if (df && skip.indexOf(df) >= 0) {
                return;
            }
            if (el.hasAttribute('data-ta-action-number-display') || el.hasAttribute('data-ta-display-code')) {
                el.disabled = true;
                return;
            }
            el.disabled = !editable;
        });
        var py = document.getElementById('ta_program_year');
        if (py) {
            if (currentMode === 'create') {
                py.disabled = !editable;
            } else {
                py.disabled = true;
            }
        }
        var sub = document.querySelector('.modal__dialog--training-actions [data-ta-submit-btn]');
        if (sub) {
            var showSave =
                (currentMode === 'create' && cfg().canCreate) ||
                (currentMode === 'edit' && cfg().canEdit);
            sub.hidden = !showSave;
            sub.setAttribute('aria-hidden', showSave ? 'false' : 'true');
        }
        refreshCatalogBtn();
        refreshAttendeeUi();
        refreshDocumentsUi();
        refreshEvalUi();
        var esReq = document.getElementById('ta_execution_status');
        if (esReq) {
            esReq.required = !!(editable && currentMode !== 'view');
        }
    }

    /**
     * Camps que applyCatalogPick sobreescriu des del catàleg (nom, vincle, àrea, durada prevista, detalls).
     */
    function catalogFormHasRelevantData() {
        var f = form();
        if (!f) {
            return false;
        }
        function trimField(field) {
            var el = f.querySelector('[data-field="' + field + '"]');
            if (!el) {
                return '';
            }
            return (el.value || '').toString().trim();
        }
        if (trimField('catalog_action_id') !== '') {
            return true;
        }
        if (trimField('name') !== '') {
            return true;
        }
        if (trimField('knowledge_area_id') !== '') {
            return true;
        }
        if (trimField('planned_duration_hours') !== '') {
            return true;
        }
        var detailFields = [
            'target_audience',
            'training_objectives',
            'conceptual_contents',
            'procedural_contents',
            'attitudinal_contents'
        ];
        for (var i = 0; i < detailFields.length; i++) {
            if (trimField(detailFields[i]) !== '') {
                return true;
            }
        }
        return false;
    }

    function openCatalogPickerFlow() {
        if (!cfg().canViewCatalog) {
            return;
        }
        var nameEl = document.getElementById('ta_name');
        if (!nameEl || nameEl.disabled) {
            return;
        }

        function proceed() {
            openCatalogModal();
            loadCatalogList('');
        }

        if (!catalogFormHasRelevantData()) {
            proceed();
            return;
        }

        if (typeof window.showConfirm !== 'function') {
            proceed();
            return;
        }

        window.showConfirm(
            'Catàleg d’accions',
            'Si continues, se substituiran les dades de l’acció formativa relacionades amb el catàleg d’accions. Vols continuar?',
            proceed,
            { confirmLabel: 'Sí', cancelLabel: 'No' }
        );
    }

    /**
     * Catàleg: actiu si hi ha permís i el formulari principal és editable (alta o edició), no en visualització.
     */
    function refreshCatalogBtn() {
        var catBtn = document.querySelector('[data-ta-catalog-btn]');
        if (!catBtn) {
            return;
        }
        if (!cfg().canViewCatalog) {
            catBtn.disabled = true;
            return;
        }
        var nameEl = document.getElementById('ta_name');
        var formEditable = nameEl && !nameEl.disabled;
        catBtn.disabled = !formEditable;
    }

    function attendeeOverlay() {
        return document.getElementById('training-actions-attendee-overlay');
    }

    function currentTrainingActionId() {
        var f = form();
        if (!f) {
            return 0;
        }
        var idEl = f.querySelector('[data-field="id"]');
        var v = idEl ? (idEl.value || '').trim() : '';
        var n = parseInt(v, 10);
        return isNaN(n) || n < 1 ? 0 : n;
    }

    function canEditAttendees() {
        return currentMode !== 'view' && cfg().canEdit;
    }

    function canDeleteAttendees() {
        return currentMode !== 'view' && !!cfg().canDelete;
    }

    function refreshAttendeeUi() {
        var aid = currentTrainingActionId();
        var locked = document.querySelector('[data-ta-attendees-locked]');
        var wrap = document.querySelector('[data-ta-attendees-wrap]');
        var addBtn = document.querySelector('[data-ta-attendee-add]');
        if (!locked || !wrap) {
            return;
        }
        if (aid < 1) {
            locked.hidden = false;
            wrap.hidden = true;
        } else {
            locked.hidden = true;
            wrap.hidden = false;
        }
        var ce = canEditAttendees() && aid > 0;
        if (addBtn) {
            addBtn.disabled = !ce;
        }
        $all('[data-ta-attendee-edit]').forEach(function (b) {
            b.disabled = !ce;
        });
        $all('[data-ta-attendee-del]').forEach(function (b) {
            b.disabled = !canDeleteAttendees() || aid < 1;
        });
        var certUp = document.querySelector('[data-ta-attendee-cert-upload]');
        if (certUp) {
            certUp.hidden = !canEditAttendees();
        }
        var sendAllQ = document.querySelector('[data-ta-send-q-all]');
        if (sendAllQ) {
            sendAllQ.hidden = !(canEditAttendees() && aid > 0);
            sendAllQ.disabled = !canEditAttendees() || aid < 1;
        }
    }

    function canEditDocuments() {
        return currentMode !== 'view' && cfg().canEdit;
    }

    function canDeleteDocuments() {
        return currentMode !== 'view' && !!cfg().canDelete;
    }

    function documentOverlay() {
        return document.getElementById('training-actions-document-overlay');
    }

    function refreshDocumentsUi() {
        var aid = currentTrainingActionId();
        var locked = document.querySelector('[data-ta-docs-locked]');
        var wrap = document.querySelector('[data-ta-docs-wrap]');
        var addBtn = document.querySelector('[data-ta-doc-add]');
        if (!locked || !wrap) {
            return;
        }
        if (aid < 1) {
            locked.hidden = false;
            wrap.hidden = true;
        } else {
            locked.hidden = true;
            wrap.hidden = false;
        }
        var ce = canEditDocuments() && aid > 0;
        if (addBtn) {
            addBtn.disabled = !ce;
        }
        $all('[data-ta-doc-edit]').forEach(function (b) {
            b.disabled = !ce;
        });
        $all('[data-ta-doc-del]').forEach(function (b) {
            b.disabled = !canDeleteDocuments() || aid < 1;
        });
    }

    function loadDocumentsList() {
        var aid = currentTrainingActionId();
        var tb = document.getElementById('ta-docs-tbody');
        var empty = document.querySelector('[data-ta-docs-empty]');
        if (!tb || aid < 1) {
            refreshDocumentsUi();
            return;
        }
        var api = cfg().apiUrl || '';
        fetch(api + '?action=documents_list&training_action_id=' + encodeURIComponent(String(aid)), { credentials: 'same-origin' })
            .then(function (r) {
                return r.json();
            })
            .then(function (d) {
                if (!d.ok || !d.items) {
                    return;
                }
                tb.innerHTML = '';
                if (d.items.length === 0) {
                    if (empty) {
                        empty.hidden = false;
                    }
                    refreshDocumentsUi();
                    return;
                }
                if (empty) {
                    empty.hidden = true;
                }
                d.items.forEach(function (it) {
                    tb.appendChild(buildDocumentRow(it));
                });
                refreshDocumentsUi();
            })
            .catch(function () {});
    }

    function docBadgeVisible(v) {
        var on = v === 1;
        var cls = on ? 'badge badge--success' : 'badge badge--neutral';
        var t = on ? 'Sí' : 'No';
        return '<span class="' + cls + '">' + escapeHtml(t) + '</span>';
    }

    function buildDocumentRow(it) {
        var tr = document.createElement('tr');
        tr.setAttribute('data-ta-doc-row-id', String(it.id));
        var notes = it.document_notes ? escapeHtml(String(it.document_notes)) : '<span class="muted">—</span>';
        var typeOrig = escapeHtml(it.origin_label || '—');
        var viewBtn = '';
        if (it.download_url) {
            viewBtn =
                '<a href="' +
                escapeAttr(it.download_url) +
                '" class="btn btn--sm btn--icon-edit ta-attendee-cert-open" title="Visualitzar" target="_blank" rel="noopener">' +
                TA_ICO_DOCUMENT +
                '</a>';
        }
        var editDis = canEditDocuments() ? '' : ' disabled';
        var delDis = canDeleteDocuments() ? '' : ' disabled';
        var actions =
            viewBtn +
            '<button type="button" class="btn btn--sm btn--icon-edit" title="Editar" data-ta-doc-edit="' +
            it.id +
            '"' +
            editDis +
            '>' +
            TA_ICO_PENCIL +
            '</button>' +
            '<button type="button" class="btn btn--sm btn--icon-del" title="Eliminar" data-ta-doc-del="' +
            it.id +
            '"' +
            delDis +
            '>' +
            TA_ICO_TRASH +
            '</button>';
        tr.innerHTML =
            '<td>' +
            escapeHtml(it.file_name || '') +
            '</td><td>' +
            notes +
            '</td><td>' +
            docBadgeVisible(it.is_visible) +
            '</td><td class="ta-docs-orig">' +
            typeOrig +
            '</td><td class="data-table__actions">' +
            actions +
            '</td>';
        return tr;
    }

    function clearDocumentForm() {
        var idEl = document.getElementById('ta_doc_id');
        if (idEl) {
            idEl.value = '';
        }
        var ta = document.getElementById('ta_doc_training_action_id');
        if (ta) {
            ta.value = String(currentTrainingActionId());
        }
        var fn = document.getElementById('ta_doc_file_name');
        if (fn) {
            fn.value = '';
        }
        var notes = document.getElementById('ta_doc_notes');
        if (notes) {
            notes.value = '';
        }
        var vis = document.getElementById('ta_doc_visible');
        if (vis) {
            vis.checked = false;
        }
        var f = document.getElementById('ta_doc_file');
        if (f) {
            f.value = '';
        }
        var fileRow = document.querySelector('[data-ta-doc-file-row]');
        if (fileRow) {
            fileRow.hidden = false;
        }
        var req = document.querySelector('[data-ta-doc-file-req]');
        if (req) {
            req.hidden = false;
        }
        var vr = document.querySelector('[data-ta-doc-view-row]');
        var vl = document.querySelector('[data-ta-doc-view-link]');
        if (vr) {
            vr.hidden = true;
        }
        if (vl) {
            vl.removeAttribute('href');
        }
    }

    function openDocumentModalCreate() {
        if (!canEditDocuments() || currentTrainingActionId() < 1) {
            return;
        }
        clearDocumentForm();
        var title = document.querySelector('[data-ta-doc-modal-title]');
        var sub = document.querySelector('[data-ta-doc-modal-sub]');
        if (title) {
            title.textContent = 'Nou document';
        }
        if (sub) {
            sub.textContent = '';
        }
        var saveBtn = document.querySelector('[data-ta-doc-save]');
        if (saveBtn) {
            saveBtn.hidden = false;
        }
        var fileRow = document.querySelector('[data-ta-doc-file-row]');
        if (fileRow) {
            fileRow.hidden = false;
        }
        var req = document.querySelector('[data-ta-doc-file-req]');
        if (req) {
            req.hidden = false;
        }
        openDocumentOverlay();
    }

    function openDocumentModalEdit(id) {
        if (!canEditDocuments()) {
            return;
        }
        var api = cfg().apiUrl || '';
        var aid = currentTrainingActionId();
        fetch(
            api +
                '?action=document_get&id=' +
                encodeURIComponent(String(id)) +
                '&training_action_id=' +
                encodeURIComponent(String(aid)),
            { credentials: 'same-origin' }
        )
            .then(function (r) {
                return r.json();
            })
            .then(function (d) {
                if (!d.ok || !d.document) {
                    if (window.showAlert) {
                        window.showAlert('error', 'Error', 'No s’ha pogut carregar el document.');
                    }
                    return;
                }
                var doc = d.document;
                clearDocumentForm();
                document.getElementById('ta_doc_id').value = String(doc.id);
                document.getElementById('ta_doc_training_action_id').value = String(aid);
                document.getElementById('ta_doc_file_name').value = doc.file_name || '';
                document.getElementById('ta_doc_notes').value = doc.document_notes || '';
                document.getElementById('ta_doc_visible').checked = doc.is_visible === 1;
                var title = document.querySelector('[data-ta-doc-modal-title]');
                var sub = document.querySelector('[data-ta-doc-modal-sub]');
                if (title) {
                    title.textContent = 'Editar document';
                }
                if (sub) {
                    sub.textContent = doc.origin_label || '';
                }
                var fileRow = document.querySelector('[data-ta-doc-file-row]');
                if (fileRow) {
                    fileRow.hidden = true;
                }
                var req = document.querySelector('[data-ta-doc-file-req]');
                if (req) {
                    req.hidden = true;
                }
                var vr = document.querySelector('[data-ta-doc-view-row]');
                var vl = document.querySelector('[data-ta-doc-view-link]');
                if (doc.download_url && vr && vl) {
                    vr.hidden = false;
                    vl.href = doc.download_url;
                }
                openDocumentOverlay();
            })
            .catch(function () {
                if (window.showAlert) {
                    window.showAlert('error', 'Error', 'Error de xarxa.');
                }
            });
    }

    function openDocumentOverlay() {
        var el = documentOverlay();
        if (!el) {
            return;
        }
        el.removeAttribute('hidden');
        el.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(function () {
            el.classList.add('is-visible');
        });
        lock();
    }

    function closeDocumentOverlay() {
        var el = documentOverlay();
        if (!el) {
            return;
        }
        setTimeout(function () {
            el.classList.remove('is-visible');
            el.setAttribute('hidden', 'hidden');
            el.setAttribute('aria-hidden', 'true');
            unlock();
        }, 0);
    }

    function postDocumentMetadata(payload) {
        var api = cfg().apiUrl || '';
        var c = cfg();
        payload.action = 'document_save';
        payload.csrf_token = c.csrfToken || '';
        payload.training_action_id = String(currentTrainingActionId());
        fetch(api || '', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': c.csrfToken || '' },
            body: JSON.stringify(payload)
        })
            .then(function (r) {
                return r.json().then(function (j) {
                    return { status: r.status, body: j };
                });
            })
            .then(function (res) {
                if (res.body.ok) {
                    closeDocumentOverlay();
                    loadDocumentsList();
                    if (window.showAlert) {
                        window.showAlert('success', 'Èxit', res.body.message || 'Desat.');
                    }
                    return;
                }
                var msg = 'No s’ha pogut desar.';
                if (res.body.errors) {
                    msg = Object.values(res.body.errors).join(' ') || msg;
                }
                if (window.showAlert) {
                    window.showAlert('error', 'Error', msg);
                }
            })
            .catch(function () {
                if (window.showAlert) {
                    window.showAlert('error', 'Error', 'Error de xarxa.');
                }
            });
    }

    function submitDocumentSave() {
        var idEl = document.getElementById('ta_doc_id');
        var id = idEl ? (idEl.value || '').trim() : '';
        var fileEl = document.getElementById('ta_doc_file');
        var hasFile = fileEl && fileEl.files && fileEl.files.length > 0;
        if (!id && !hasFile) {
            if (window.showAlert) {
                window.showAlert('warning', 'Fitxer', 'Seleccioneu un fitxer.');
            }
            return;
        }
        if (!id && hasFile) {
            var uploadUrl = cfg().documentUploadUrl || '';
            var c = cfg();
            if (!uploadUrl) {
                if (window.showAlert) {
                    window.showAlert('error', 'Error', 'URL de pujada no configurada.');
                }
                return;
            }
            var fd = new FormData();
            fd.append('csrf_token', c.csrfToken || '');
            fd.append('training_action_id', String(currentTrainingActionId()));
            fd.append('document', fileEl.files[0]);
            fd.append('file_name', (document.getElementById('ta_doc_file_name').value || '').trim());
            fd.append('document_notes', (document.getElementById('ta_doc_notes').value || '').trim());
            fd.append('is_visible', document.getElementById('ta_doc_visible').checked ? '1' : '0');
            fetch(uploadUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'X-CSRF-Token': c.csrfToken || '' },
                body: fd
            })
                .then(function (r) {
                    return r.json();
                })
                .then(function (d) {
                    if (d && d.ok) {
                        closeDocumentOverlay();
                        loadDocumentsList();
                        if (window.showAlert) {
                            window.showAlert('success', 'Èxit', d.message || 'Document pujat.');
                        }
                        return;
                    }
                    var em =
                        d && d.errors && d.errors._general
                            ? String(d.errors._general)
                            : 'No s’ha pogut pujar el document.';
                    if (window.showAlert) {
                        window.showAlert('error', 'Error', em);
                    }
                })
                .catch(function () {
                    if (window.showAlert) {
                        window.showAlert('error', 'Error', 'Error de xarxa.');
                    }
                });
            return;
        }
        postDocumentMetadata({
            id: id,
            file_name: (document.getElementById('ta_doc_file_name').value || '').trim(),
            document_notes: (document.getElementById('ta_doc_notes').value || '').trim(),
            is_visible: document.getElementById('ta_doc_visible').checked ? '1' : '0'
        });
    }

    function confirmDeleteDocument(id) {
        if (!canDeleteDocuments() || typeof window.showConfirm !== 'function') {
            return;
        }
        window.showConfirm(
            'Eliminar document',
            'Voleu eliminar aquest document? Si és un certificat vinculat a un assistent, se’n treurà l’enllaç.',
            function () {
                var api = cfg().apiUrl || '';
                var c = cfg();
                fetch(api || '', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': c.csrfToken || '' },
                    body: JSON.stringify({
                        action: 'document_delete',
                        id: id,
                        training_action_id: currentTrainingActionId(),
                        csrf_token: c.csrfToken || ''
                    })
                })
                    .then(function (r) {
                        return r.json();
                    })
                    .then(function (d) {
                        if (d.ok) {
                            loadDocumentsList();
                            loadAttendeesList();
                            if (window.showAlert) {
                                window.showAlert('success', 'Èxit', d.message || 'Eliminat.');
                            }
                        } else if (window.showAlert) {
                            window.showAlert('error', 'Error', (d.errors && d.errors._general) || 'Error.');
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

    function evaluationDetailOverlay() {
        return document.getElementById('training-actions-evaluation-detail-overlay');
    }

    function refreshEvalUi() {
        var aid = currentTrainingActionId();
        var locked = document.querySelector('[data-ta-eval-locked]');
        var wrap = document.querySelector('[data-ta-eval-wrap]');
        if (!locked || !wrap) {
            return;
        }
        if (aid < 1) {
            locked.hidden = false;
            wrap.hidden = true;
        } else {
            locked.hidden = true;
            wrap.hidden = false;
        }
        var imp = document.querySelector('[data-ta-eval-import-input]');
        if (imp) {
            imp.disabled = currentMode === 'view' || !cfg().canEdit || aid < 1;
        }
    }

    function renderEvalLegend(legend) {
        var el = document.querySelector('[data-ta-eval-legend]');
        if (!el || !legend || typeof legend !== 'object') {
            return;
        }
        var parts = [];
        Object.keys(legend)
            .sort(function (a, b) {
                return parseInt(a, 10) - parseInt(b, 10);
            })
            .forEach(function (k) {
                parts.push(k + ' — ' + legend[k]);
            });
        el.textContent = 'Escala Likert: ' + parts.join(' · ');
    }

    function evalQValue(row, n) {
        var q = row.q || {};
        var v = q[n];
        if (v == null || v === '') {
            v = q[String(n)];
        }
        return v != null && v !== '' ? v : null;
    }

    function compareEvalNullableNum(av, bv, mult) {
        if (av == null && bv == null) {
            return 0;
        }
        if (av == null) {
            return 1;
        }
        if (bv == null) {
            return -1;
        }
        return mult * (Number(av) - Number(bv));
    }

    function sortEvalItems(items, by, dir) {
        var m = dir === 'desc' ? -1 : 1;
        return items.slice().sort(function (a, b) {
            if (by === 'person') {
                var ca = String(a.person_display || '');
                var cb = String(b.person_display || '');
                return m * ca.localeCompare(cb, 'ca');
            }
            if (by === 'global') {
                return compareEvalNullableNum(a.global_score_1_10, b.global_score_1_10, m);
            }
            var qm = /^q(\d+)$/.exec(by);
            if (qm) {
                var n = parseInt(qm[1], 10);
                return compareEvalNullableNum(evalQValue(a, n), evalQValue(b, n), m);
            }
            return 0;
        });
    }

    function updateEvalSortIndicators() {
        $all('.ta-eval-table [data-ta-eval-sort]').forEach(function (btn) {
            var key = btn.getAttribute('data-ta-eval-sort');
            var span = btn.querySelector('[data-ta-eval-sort-ind]');
            if (key === evalSortBy) {
                btn.classList.add('is-active');
                if (span) {
                    span.textContent = evalSortDir === 'asc' ? '↑' : '↓';
                }
            } else {
                btn.classList.remove('is-active');
                if (span) {
                    span.textContent = '';
                }
            }
        });
    }

    function renderEvaluationsTableBody() {
        var tb = document.getElementById('ta-eval-tbody');
        var empty = document.querySelector('[data-ta-eval-empty]');
        if (!tb) {
            return;
        }
        tb.innerHTML = '';
        if (!evalListCache.length) {
            if (empty) {
                empty.hidden = false;
            }
            updateEvalSortIndicators();
            refreshEvalUi();
            return;
        }
        if (empty) {
            empty.hidden = true;
        }
        var sorted = sortEvalItems(evalListCache, evalSortBy, evalSortDir);
        sorted.forEach(function (it) {
            tb.appendChild(buildEvalRow(it));
        });
        updateEvalSortIndicators();
        refreshEvalUi();
    }

    function loadEvaluationsList() {
        var aid = currentTrainingActionId();
        var tb = document.getElementById('ta-eval-tbody');
        var empty = document.querySelector('[data-ta-eval-empty]');
        if (!tb || aid < 1) {
            refreshEvalUi();
            return;
        }
        var api = cfg().apiUrl || '';
        fetch(api + '?action=evaluations_list&training_action_id=' + encodeURIComponent(String(aid)), { credentials: 'same-origin' })
            .then(function (r) {
                return r.json();
            })
            .then(function (d) {
                if (d.likert_legend) {
                    renderEvalLegend(d.likert_legend);
                }
                if (aid !== evalListCachedAid) {
                    evalSortBy = 'person';
                    evalSortDir = 'asc';
                    evalListCachedAid = aid;
                }
                evalListCache = d.ok && d.items ? d.items : [];
                if (!d.ok || !evalListCache.length) {
                    if (empty) {
                        empty.hidden = false;
                    }
                    tb.innerHTML = '';
                    updateEvalSortIndicators();
                    refreshEvalUi();
                    return;
                }
                renderEvaluationsTableBody();
            })
            .catch(function () {});
    }

    function buildEvalRow(it) {
        var tr = document.createElement('tr');
        tr.setAttribute('data-ta-eval-row-id', String(it.id));
        var cells = '<td>' + escapeHtml(it.person_display || '') + '</td>';
        var n;
        for (n = 1; n <= 20; n++) {
            var v = evalQValue(it, n);
            cells += '<td class="ta-eval-q">' + (v != null && v !== '' ? escapeHtml(String(v)) : '<span class="muted">—</span>') + '</td>';
        }
        var g =
            it.global_score_1_10 != null && it.global_score_1_10 !== ''
                ? escapeHtml(String(it.global_score_1_10))
                : '<span class="muted">—</span>';
        cells += '<td>' + g + '</td>';
        cells +=
            '<td class="data-table__actions">' +
            '<button type="button" class="btn btn--sm btn--icon-edit" title="Visualitzar resta de dades" data-ta-eval-detail="' +
            it.id +
            '">' +
            TA_ICO_DOCUMENT +
            '</button>' +
            '</td>';
        tr.innerHTML = cells;
        return tr;
    }

    function openEvaluationDetailOverlay() {
        var el = evaluationDetailOverlay();
        if (!el) {
            return;
        }
        el.removeAttribute('hidden');
        el.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(function () {
            el.classList.add('is-visible');
        });
        lock();
    }

    function closeEvaluationDetailOverlay() {
        var el = evaluationDetailOverlay();
        if (!el) {
            return;
        }
        setTimeout(function () {
            el.classList.remove('is-visible');
            el.setAttribute('hidden', 'hidden');
            el.setAttribute('aria-hidden', 'true');
            unlock();
        }, 0);
    }

    function fillEvaluationDetailBody(d) {
        var body = document.querySelector('[data-ta-eval-detail-body]');
        var sub = document.querySelector('[data-ta-eval-detail-sub]');
        if (!body) {
            return;
        }
        var ev = d.evaluation || {};
        if (sub) {
            sub.textContent = ev.action_code_snapshot ? String(ev.action_code_snapshot) : '';
        }
        var esc = escapeHtml;
        var line = function (label, val) {
            var v = val != null && String(val).trim() !== '' ? esc(String(val)) : '<span class="muted">—</span>';
            return '<div class="form-group"><span class="form-label">' + esc(label) + '</span><p class="training-actions-eval-detail__p">' + v + '</p></div>';
        };
        var html = '';
        html += line('Codi d’acció', ev.action_code_snapshot);
        html += line('Nom de l’acció', ev.action_name_snapshot);
        html += line('Assistent', d.person_display);
        html += line('Formador', ev.trainer_snapshot);
        html += line('Lloc d’impartició', ev.location_snapshot);
        html += line('Motiu assistència', ev.attendance_reason);
        html += line('Motivació principal', ev.main_motivation);
        html += line('Recomanaries aquest curs?', ev.would_recommend);
        html += line('Punts forts', ev.strengths);
        html += line('Punts febles', ev.weaknesses);
        html += line('Aplicació', ev.application);
        html += line('Altres comentaris', ev.other_comments);
        if (d.received_download_url) {
            html +=
                '<div class="form-group"><a class="btn btn--outline btn--sm" href="' +
                escapeAttr(d.received_download_url) +
                '" target="_blank" rel="noopener">' +
                TA_ICO_DOCUMENT +
                ' Excel rebut</a></div>';
        }
        body.innerHTML = html;
    }

    function loadEvaluationDetail(id) {
        var aid = currentTrainingActionId();
        var api = cfg().apiUrl || '';
        var body = document.querySelector('[data-ta-eval-detail-body]');
        if (body) {
            body.innerHTML = '<p class="muted">Carregant…</p>';
        }
        fetch(api + '?action=evaluation_get&id=' + encodeURIComponent(String(id)) + '&training_action_id=' + encodeURIComponent(String(aid)), {
            credentials: 'same-origin'
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (d) {
                if (!d.ok) {
                    if (body) {
                        body.innerHTML = '<p class="muted">—</p>';
                    }
                    if (window.showAlert) {
                        window.showAlert('error', 'Error', 'No s’ha pogut carregar el detall.');
                    }
                    return;
                }
                fillEvaluationDetailBody(d);
                openEvaluationDetailOverlay();
            })
            .catch(function () {
                if (window.showAlert) {
                    window.showAlert('error', 'Error', 'Error de xarxa.');
                }
            });
    }

    function submitEvalImport(files) {
        if (!files || files.length === 0) {
            return;
        }
        var url = cfg().evaluationImportUrl || '';
        if (!url) {
            return;
        }
        var fd = new FormData();
        fd.append('csrf_token', cfg().csrfToken || '');
        fd.append('training_action_id', String(currentTrainingActionId()));
        for (var i = 0; i < files.length; i++) {
            fd.append('files[]', files[i]);
        }
        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-Token': cfg().csrfToken || '' },
            body: fd
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (d) {
                loadEvaluationsList();
                var msg = d.message || '';
                if (d.results && d.results.length) {
                    var lines = d.results.map(function (x) {
                        return x.file + ': ' + (x.ok ? 'OK' : x.message);
                    });
                    msg = lines.join('\n');
                }
                if (window.showAlert) {
                    window.showAlert(d.imported > 0 ? 'success' : 'warning', 'Importació', msg || 'Fet.');
                }
            })
            .catch(function () {
                if (window.showAlert) {
                    window.showAlert('error', 'Error', 'Error de xarxa.');
                }
            });
    }

    function sendQuestionnaireOne(attendeeId) {
        var url = cfg().questionnaireSendUrl || '';
        if (!url || attendeeId < 1) {
            return;
        }
        fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': cfg().csrfToken || '' },
            body: JSON.stringify({
                training_action_id: currentTrainingActionId(),
                attendee_id: attendeeId,
                csrf_token: cfg().csrfToken || ''
            })
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (d) {
                if (d.ok && window.showAlert) {
                    window.showAlert('success', 'Èxit', d.message || 'Enviat.');
                    loadAttendeesList();
                } else if (window.showAlert) {
                    window.showAlert('error', 'Error', (d.errors && d.errors._general) || d.message || 'Error.');
                }
            })
            .catch(function () {
                if (window.showAlert) {
                    window.showAlert('error', 'Error', 'Error de xarxa.');
                }
            });
    }

    function sendQuestionnaireAll() {
        var url = cfg().questionnaireSendUrl || '';
        if (!url) {
            return;
        }
        if (typeof window.showConfirm !== 'function') {
            return;
        }
        window.showConfirm(
            'Enviar qüestionaris',
            'S’enviarà el qüestionari Excel per correu a tots els assistents amb assistència marcada i correu vàlid. Continuar?',
            function () {
                fetch(url, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': cfg().csrfToken || '' },
                    body: JSON.stringify({
                        training_action_id: currentTrainingActionId(),
                        send_all: true,
                        csrf_token: cfg().csrfToken || ''
                    })
                })
                    .then(function (r) {
                        return r.json();
                    })
                    .then(function (d) {
                        var msg = d.message || '';
                        if (d.errors && d.errors.length) {
                            msg += '\n' + d.errors.join('\n');
                        }
                        if (window.showAlert) {
                            window.showAlert(d.ok ? 'success' : 'warning', 'Enviament', msg);
                        }
                        if (d.sent > 0) {
                            loadAttendeesList();
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

    function openAttendeeOverlay() {
        var el = attendeeOverlay();
        if (!el) {
            return;
        }
        el.removeAttribute('hidden');
        el.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(function () {
            el.classList.add('is-visible');
        });
        lock();
    }

    function closeAttendeeOverlay() {
        var el = attendeeOverlay();
        if (!el) {
            return;
        }
        setAttendeeModalReadOnly(false);
        setTimeout(function () {
            el.classList.remove('is-visible');
            el.setAttribute('hidden', 'hidden');
            el.setAttribute('aria-hidden', 'true');
            unlock();
        }, 0);
    }

    /**
     * Mode només lectura de la modal d’assistent (títol «Veure»): camps desactivats, sense Desar.
     */
    function setAttendeeModalReadOnly(ro) {
        var el = attendeeOverlay();
        if (!el) {
            return;
        }
        if (ro) {
            el.setAttribute('data-attendee-readonly', '1');
        } else {
            el.removeAttribute('data-attendee-readonly');
        }
        var saveBtn = document.querySelector('[data-ta-attendee-save]');
        if (saveBtn) {
            var showSave = !ro && canEditAttendees();
            saveBtn.hidden = !showSave;
            saveBtn.setAttribute('aria-hidden', showSave ? 'false' : 'true');
        }
        var editable = !ro && canEditAttendees();
        $all('#training-actions-attendee-overlay [data-ta-attendee-field]').forEach(function (inp) {
            inp.disabled = !editable;
        });
        var certFile = document.getElementById('ta_attendee_cert_file');
        if (certFile) {
            certFile.disabled = !editable;
        }
        var certClear = document.querySelector('[data-ta-attendee-cert-clear]');
        if (certClear) {
            certClear.disabled = !editable;
            certClear.hidden = !editable;
        }
        var certUp = document.querySelector('[data-ta-attendee-cert-upload]');
        if (certUp) {
            certUp.hidden = !editable;
        }
    }

    function loadAttendeesList() {
        var aid = currentTrainingActionId();
        var tb = document.getElementById('ta-attendees-tbody');
        var empty = document.querySelector('[data-ta-attendees-empty]');
        if (!tb || aid < 1) {
            refreshAttendeeUi();
            return;
        }
        var api = cfg().apiUrl || '';
        fetch(api + '?action=attendees_list&training_action_id=' + encodeURIComponent(String(aid)), { credentials: 'same-origin' })
            .then(function (r) {
                return r.json();
            })
            .then(function (d) {
                if (!d.ok || !d.items) {
                    return;
                }
                tb.innerHTML = '';
                if (d.items.length === 0) {
                    if (empty) {
                        empty.hidden = false;
                    }
                    return;
                }
                if (empty) {
                    empty.hidden = true;
                }
                d.items.forEach(function (it) {
                    tb.appendChild(buildAttendeeRow(it));
                });
                refreshAttendeeUi();
            })
            .catch(function () {});
    }

    function attendeeBoolBadge(v) {
        var on = v === 1;
        var cls = on ? 'badge badge--success' : 'badge badge--neutral';
        var t = on ? 'Sí' : 'No';
        return '<span class="' + cls + '">' + escapeHtml(t) + '</span>';
    }

    function buildAttendeeRow(it) {
        var tr = document.createElement('tr');
        tr.setAttribute('data-ta-attendee-row-id', String(it.id));
        var jobLabel =
            it.job_position_label != null && String(it.job_position_label).trim() !== ''
                ? escapeHtml(String(it.job_position_label))
                : '<span class="muted">—</span>';
        var certTd = '';
        if (it.certificate_download_url) {
            certTd =
                '<a href="' +
                escapeAttr(it.certificate_download_url) +
                '" class="btn btn--sm btn--icon-edit ta-attendee-cert-open" title="Veure certificat" target="_blank" rel="noopener">' +
                TA_ICO_DOCUMENT +
                '</a>';
        } else {
            certTd = '<span class="muted">—</span>';
        }
        var editDis = canEditAttendees() ? '' : ' disabled';
        var delDis = canDeleteAttendees() ? '' : ' disabled';
        var qDis =
            canEditAttendees() && (it.attendance_flag === 1 || it.attendance_flag === true) && it.email
                ? ''
                : ' disabled';
        var qSentSlot = '';
        if (it.questionnaire_sent_download_url) {
            qSentSlot =
                '<a href="' +
                escapeAttr(it.questionnaire_sent_download_url) +
                '" class="btn btn--sm btn--icon-edit ta-attendee-q-sent-link" title="Descarregar Excel enviat" target="_blank" rel="noopener">' +
                TA_ICO_DOCUMENT +
                '</a>';
        } else {
            qSentSlot = '<span class="muted ta-attendee-q-dash">—</span>';
        }
        var qBtn =
            '<button type="button" class="btn btn--sm btn--icon-edit" title="Enviar qüestionari" data-ta-send-q-one="' +
            it.id +
            '"' +
            qDis +
            '>' +
            TA_ICO_DOCUMENT +
            '</button>';
        var actions =
            '<button type="button" class="btn btn--sm btn--icon-edit" title="Visualitzar" data-ta-attendee-view="' +
            it.id +
            '">' +
            TA_ICO_DOCUMENT +
            '</button>' +
            '<button type="button" class="btn btn--sm btn--icon-edit" title="Editar" data-ta-attendee-edit="' +
            it.id +
            '"' +
            editDis +
            '>' +
            TA_ICO_PENCIL +
            '</button>' +
            '<button type="button" class="btn btn--sm btn--icon-del" title="Eliminar" data-ta-attendee-del="' +
            it.id +
            '"' +
            delDis +
            '>' +
            TA_ICO_TRASH +
            '</button>';
        tr.innerHTML =
            '<td>' +
            escapeHtml(it.person_display || '') +
            '</td><td>' +
            jobLabel +
            '</td><td>' +
            escapeHtml(it.email || '—') +
            '</td><td class="ta-attendees-ic">' +
            attendeeBoolBadge(it.request_flag) +
            '</td><td class="ta-attendees-ic">' +
            attendeeBoolBadge(it.pre_registration_flag) +
            '</td><td class="ta-attendees-ic">' +
            attendeeBoolBadge(it.registration_flag) +
            '</td><td class="ta-attendees-ic">' +
            attendeeBoolBadge(it.attendance_flag) +
            '</td><td>' +
            certTd +
            '</td><td class="ta-attendees-ic ta-attendee-qcell">' +
            '<div class="ta-attendee-q-sent">' +
            qSentSlot +
            '</div><div class="ta-attendee-q-send">' +
            qBtn +
            '</div></td><td class="data-table__actions">' +
            actions +
            '</td>';
        return tr;
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function escapeAttr(s) {
        return escapeHtml(s).replace(/"/g, '&quot;');
    }

    function clearAttendeeForm() {
        setAttendeeModalReadOnly(false);
        $all('#training-actions-attendee-overlay [data-ta-attendee-field]').forEach(function (el) {
            if (el.type === 'checkbox') {
                el.checked = false;
            } else {
                el.value = '';
            }
        });
        var lk = document.querySelector('[data-ta-attendee-cert-linked]');
        var nm = document.querySelector('[data-ta-attendee-cert-name]');
        var al = document.querySelector('[data-ta-attendee-cert-link]');
        if (lk) {
            lk.hidden = true;
        }
        if (nm) {
            nm.textContent = '';
        }
        if (al) {
            al.removeAttribute('href');
        }
        var certFile = document.getElementById('ta_attendee_cert_file');
        if (certFile) {
            certFile.value = '';
            certFile.removeAttribute('data-ta-keep-file');
        }
        var block = document.querySelector('[data-ta-attendee-person-block]');
        if (block) {
            block.hidden = false;
        }
        var sel = document.getElementById('ta_attendee_person_select');
        if (sel) {
            sel.disabled = false;
            sel.removeAttribute('hidden');
            sel.innerHTML = '';
            var opt0 = document.createElement('option');
            opt0.value = '';
            opt0.textContent = '— Seleccioneu una persona —';
            sel.appendChild(opt0);
            sel.value = '';
        }
    }

    function syncCertDisplay(it) {
        var docId = it.attendance_certificate_document_id;
        var fname = it.certificate_file_name;
        var url = it.certificate_download_url;
        var lk = document.querySelector('[data-ta-attendee-cert-linked]');
        var nm = document.querySelector('[data-ta-attendee-cert-name]');
        var al = document.querySelector('[data-ta-attendee-cert-link]');
        var up = document.querySelector('[data-ta-attendee-cert-upload]');
        var hid = document.getElementById('ta_attendee_certificate_doc_id');
        if (hid) {
            hid.value = docId != null && docId !== '' ? String(docId) : '';
        }
        if (up) {
            up.hidden = !canEditAttendees();
        }
        if (docId && fname && url && lk && nm && al) {
            lk.hidden = false;
            nm.textContent = fname;
            al.href = url;
        } else if (lk) {
            lk.hidden = true;
        }
    }

    function loadPeoplePickerOptions(done) {
        var api = cfg().apiUrl || '';
        var sel = document.getElementById('ta_attendee_person_select');
        if (!sel) {
            if (typeof done === 'function') {
                done();
            }
            return;
        }
        sel.innerHTML = '';
        var loading = document.createElement('option');
        loading.value = '';
        loading.textContent = 'Carregant persones…';
        sel.appendChild(loading);
        sel.disabled = true;
        fetch(api + '?action=people_picker_list', { credentials: 'same-origin' })
            .then(function (r) {
                return r.json();
            })
            .then(function (d) {
                sel.innerHTML = '';
                var opt0 = document.createElement('option');
                opt0.value = '';
                opt0.textContent = '— Seleccioneu una persona —';
                sel.appendChild(opt0);
                if (!d || d.ok === false) {
                    var bad = document.createElement('option');
                    bad.value = '';
                    bad.disabled = true;
                    bad.textContent = 'No s’ha pogut carregar la llista';
                    sel.appendChild(bad);
                    sel.disabled = false;
                    var errMsg =
                        d && d.errors && d.errors._general
                            ? String(d.errors._general)
                            : 'Resposta del servidor no vàlida.';
                    if (window.showAlert) {
                        window.showAlert('error', 'Persones', errMsg);
                    }
                    if (typeof done === 'function') {
                        done();
                    }
                    return;
                }
                if (!d.items || !d.items.length) {
                    var empty = document.createElement('option');
                    empty.value = '';
                    empty.disabled = true;
                    empty.textContent =
                        'No hi ha persones actives (people.is_active = 1).';
                    sel.appendChild(empty);
                    sel.disabled = false;
                    if (window.showAlert) {
                        window.showAlert(
                            'warning',
                            'Persones',
                            'No s’ha trobat cap persona activa. Reviseu el mòdul de persones.'
                        );
                    }
                    if (typeof done === 'function') {
                        done();
                    }
                    return;
                }
                d.items.forEach(function (it) {
                    var opt = document.createElement('option');
                    opt.value = String(it.id);
                    opt.setAttribute('data-person-label', it.label != null ? String(it.label) : '');
                    opt.textContent = it.label != null ? String(it.label) : String(it.id);
                    sel.appendChild(opt);
                });
                sel.value = '';
                sel.disabled = false;
                if (typeof done === 'function') {
                    done();
                }
            })
            .catch(function () {
                sel.innerHTML = '';
                var err = document.createElement('option');
                err.value = '';
                err.textContent = 'Error en carregar persones';
                sel.appendChild(err);
                sel.disabled = false;
                if (window.showAlert) {
                    window.showAlert('error', 'Error', 'Error de xarxa en carregar la llista de persones.');
                }
                if (typeof done === 'function') {
                    done();
                }
            });
    }

    function openAttendeeModalCreate() {
        if (!canEditAttendees() || currentTrainingActionId() < 1) {
            return;
        }
        clearAttendeeForm();
        var title = document.querySelector('[data-ta-attendee-modal-title]');
        var sub = document.querySelector('[data-ta-attendee-modal-sub]');
        if (title) {
            title.textContent = 'Nou assistent';
        }
        if (sub) {
            sub.textContent = '';
        }
        var ta = document.getElementById('ta_attendee_training_action_id');
        if (ta) {
            ta.value = String(currentTrainingActionId());
        }
        var idEl = document.getElementById('ta_attendee_id');
        if (idEl) {
            idEl.value = '';
        }
        syncCertDisplay({});
        loadPeoplePickerOptions(function () {
            openAttendeeOverlay();
        });
    }

    function openAttendeeModalEdit(id) {
        if (!canEditAttendees()) {
            return;
        }
        var api = cfg().apiUrl || '';
        fetch(api + '?action=attendee_get&id=' + encodeURIComponent(String(id)), { credentials: 'same-origin' })
            .then(function (r) {
                return r.json();
            })
            .then(function (d) {
                if (!d.ok || !d.attendee) {
                    if (window.showAlert) {
                        window.showAlert('error', 'Error', 'No s’ha pogut carregar l’assistent.');
                    }
                    return;
                }
                var a = d.attendee;
                clearAttendeeForm();
                var title = document.querySelector('[data-ta-attendee-modal-title]');
                var sub = document.querySelector('[data-ta-attendee-modal-sub]');
                if (title) {
                    title.textContent = 'Editar assistent';
                }
                if (sub) {
                    sub.textContent = '';
                }
                document.getElementById('ta_attendee_id').value = String(a.id);
                document.getElementById('ta_attendee_training_action_id').value = String(a.training_action_id);
                document.getElementById('ta_attendee_request').checked = a.request_flag === 1;
                document.getElementById('ta_attendee_pre_registration').checked = a.pre_registration_flag === 1;
                document.getElementById('ta_attendee_registration').checked = a.registration_flag === 1;
                document.getElementById('ta_attendee_attendance').checked = a.attendance_flag === 1;
                document.getElementById('ta_attendee_non_attendance').value =
                    a.non_attendance_reason != null ? String(a.non_attendance_reason) : '';
                var block = document.querySelector('[data-ta-attendee-person-block]');
                if (block) {
                    block.hidden = false;
                }
                var sel = document.getElementById('ta_attendee_person_select');
                if (sel) {
                    sel.innerHTML = '';
                    var opt = document.createElement('option');
                    opt.value = String(a.person_id);
                    opt.textContent = a.person_display || '#' + String(a.person_id);
                    sel.appendChild(opt);
                    sel.value = String(a.person_id);
                    sel.disabled = true;
                }
                syncCertDisplay(a);
                setAttendeeModalReadOnly(false);
                openAttendeeOverlay();
            })
            .catch(function () {
                if (window.showAlert) {
                    window.showAlert('error', 'Error', 'Error de xarxa.');
                }
            });
    }

    function openAttendeeModalView(id) {
        var api = cfg().apiUrl || '';
        fetch(api + '?action=attendee_get&id=' + encodeURIComponent(String(id)), { credentials: 'same-origin' })
            .then(function (r) {
                return r.json();
            })
            .then(function (d) {
                if (!d.ok || !d.attendee) {
                    if (window.showAlert) {
                        window.showAlert('error', 'Error', 'No s’ha pogut carregar l’assistent.');
                    }
                    return;
                }
                var a = d.attendee;
                clearAttendeeForm();
                var title = document.querySelector('[data-ta-attendee-modal-title]');
                var sub = document.querySelector('[data-ta-attendee-modal-sub]');
                if (title) {
                    title.textContent = 'Veure assistent';
                }
                if (sub) {
                    sub.textContent = '';
                }
                document.getElementById('ta_attendee_id').value = String(a.id);
                document.getElementById('ta_attendee_training_action_id').value = String(a.training_action_id);
                document.getElementById('ta_attendee_request').checked = a.request_flag === 1;
                document.getElementById('ta_attendee_pre_registration').checked = a.pre_registration_flag === 1;
                document.getElementById('ta_attendee_registration').checked = a.registration_flag === 1;
                document.getElementById('ta_attendee_attendance').checked = a.attendance_flag === 1;
                document.getElementById('ta_attendee_non_attendance').value =
                    a.non_attendance_reason != null ? String(a.non_attendance_reason) : '';
                var block = document.querySelector('[data-ta-attendee-person-block]');
                if (block) {
                    block.hidden = false;
                }
                var sel = document.getElementById('ta_attendee_person_select');
                if (sel) {
                    sel.innerHTML = '';
                    var opt = document.createElement('option');
                    opt.value = String(a.person_id);
                    opt.textContent = a.person_display || '#' + String(a.person_id);
                    sel.appendChild(opt);
                    sel.value = String(a.person_id);
                    sel.disabled = true;
                }
                syncCertDisplay(a);
                setAttendeeModalReadOnly(true);
                openAttendeeOverlay();
            })
            .catch(function () {
                if (window.showAlert) {
                    window.showAlert('error', 'Error', 'Error de xarxa.');
                }
            });
    }

    function collectAttendeePayload() {
        var o = {};
        $all('#training-actions-attendee-overlay [data-ta-attendee-field]').forEach(function (el) {
            var k = el.getAttribute('data-ta-attendee-field');
            if (!k) {
                return;
            }
            if (el.type === 'checkbox') {
                o[k] = el.checked ? '1' : '0';
            } else {
                o[k] = (el.value || '').trim();
            }
        });
        return o;
    }

    function postAttendeeSavePayload(payload) {
        var api = cfg().apiUrl || '';
        var c = cfg();
        payload.action = 'attendee_save';
        payload.csrf_token = c.csrfToken || '';
        if (!payload.training_action_id) {
            payload.training_action_id = String(currentTrainingActionId());
        }
        fetch(api || '', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': c.csrfToken || '' },
            body: JSON.stringify(payload)
        })
            .then(function (r) {
                return r.json().then(function (j) {
                    return { status: r.status, body: j };
                });
            })
            .then(function (res) {
                if (res.body.ok) {
                    closeAttendeeOverlay();
                    loadAttendeesList();
                    if (window.showAlert) {
                        window.showAlert('success', 'Èxit', res.body.message || 'Desat.');
                    }
                    return;
                }
                var msg = 'No s’ha pogut desar.';
                if (res.body.errors) {
                    msg = Object.values(res.body.errors).join(' ') || msg;
                }
                if (window.showAlert) {
                    window.showAlert('error', 'Error', msg);
                }
            })
            .catch(function () {
                if (window.showAlert) {
                    window.showAlert('error', 'Error', 'Error de xarxa.');
                }
            });
    }

    function submitAttendeeSave() {
        var ao = attendeeOverlay();
        if (ao && ao.getAttribute('data-attendee-readonly') === '1') {
            return;
        }
        var c = cfg();
        var payload = collectAttendeePayload();
        if (!(payload.id && String(payload.id).trim() !== '') && !(payload.person_id && String(payload.person_id).trim() !== '')) {
            if (window.showAlert) {
                window.showAlert('warning', 'Persona', 'Seleccioneu una persona de la llista.');
            }
            return;
        }
        var uploadUrl = c.certificateUploadUrl || '';
        var fileEl = document.getElementById('ta_attendee_cert_file');
        var needUpload =
            canEditAttendees() &&
            uploadUrl &&
            fileEl &&
            fileEl.files &&
            fileEl.files.length > 0;
        if (!needUpload) {
            postAttendeeSavePayload(payload);
            return;
        }
        var fd = new FormData();
        fd.append('csrf_token', c.csrfToken || '');
        fd.append('training_action_id', String(currentTrainingActionId()));
        fd.append('certificate', fileEl.files[0]);
        fetch(uploadUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-Token': c.csrfToken || '' },
            body: fd
        })
            .then(function (r) {
                return r.json();
            })
            .then(function (d) {
                if (!d || !d.ok) {
                    var em =
                        d && d.errors && d.errors._general
                            ? String(d.errors._general)
                            : 'No s’ha pogut pujar el certificat.';
                    if (window.showAlert) {
                        window.showAlert('error', 'Certificat', em);
                    }
                    return;
                }
                var hid = document.getElementById('ta_attendee_certificate_doc_id');
                if (hid && d.document_id) {
                    hid.value = String(d.document_id);
                }
                if (fileEl) {
                    fileEl.value = '';
                }
                payload = collectAttendeePayload();
                postAttendeeSavePayload(payload);
            })
            .catch(function () {
                if (window.showAlert) {
                    window.showAlert('error', 'Error', 'Error de xarxa en pujar el certificat.');
                }
            });
    }

    function confirmDeleteAttendee(id) {
        if (!canDeleteAttendees() || typeof window.showConfirm !== 'function') {
            return;
        }
        window.showConfirm(
            'Eliminar assistent',
            'Voleu eliminar aquest assistent de l’acció?',
            function () {
                var api = cfg().apiUrl || '';
                var c = cfg();
                fetch(api || '', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': c.csrfToken || '' },
                    body: JSON.stringify({
                        action: 'attendee_delete',
                        id: id,
                        training_action_id: currentTrainingActionId(),
                        csrf_token: c.csrfToken || ''
                    })
                })
                    .then(function (r) {
                        return r.json();
                    })
                    .then(function (d) {
                        if (d.ok) {
                            loadAttendeesList();
                            if (window.showAlert) {
                                window.showAlert('success', 'Èxit', d.message || 'Eliminat.');
                            }
                        } else if (window.showAlert) {
                            window.showAlert('error', 'Error', (d.errors && d.errors._general) || 'Error.');
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

    function clearDateRows() {
        var tb = document.getElementById('ta-dates-tbody');
        if (tb) {
            tb.innerHTML = '';
        }
    }
    function addDateRow(data) {
        var tpl = document.getElementById('ta-date-row-template');
        var tb = document.getElementById('ta-dates-tbody');
        if (!tpl || !tb || !tpl.content) {
            return;
        }
        var row = tpl.content.firstElementChild.cloneNode(true);
        if (data) {
            var d = row.querySelector('[data-ta-date-field="session_date"]');
            var st = row.querySelector('[data-ta-date-field="start_time"]');
            var en = row.querySelector('[data-ta-date-field="end_time"]');
            if (d && data.session_date) {
                d.value = data.session_date;
            }
            if (st && data.start_time) {
                st.value = (data.start_time + '').substring(0, 5);
            }
            if (en && data.end_time) {
                en.value = (data.end_time + '').substring(0, 5);
            }
        }
        tb.appendChild(row);
    }

    function collectDatesPayload() {
        var rows = $all('#ta-dates-tbody .ta-date-row');
        var out = [];
        rows.forEach(function (row) {
            var sd = row.querySelector('[data-ta-date-field="session_date"]');
            var st = row.querySelector('[data-ta-date-field="start_time"]');
            var en = row.querySelector('[data-ta-date-field="end_time"]');
            var v = sd ? (sd.value || '').trim() : '';
            if (!v) {
                return;
            }
            out.push({
                session_date: v,
                start_time: st && st.value ? st.value : '',
                end_time: en && en.value ? en.value : ''
            });
        });
        return out;
    }

    function parseNumField(v) {
        var s = (v || '').toString().trim().replace(',', '.');
        if (s === '') {
            return null;
        }
        var n = parseFloat(s);
        return isNaN(n) ? null : n;
    }

    function recalcPlannedMunicipalCost() {
        var totalEl = document.querySelector('[data-field="planned_total_cost"]');
        var pctEl = document.querySelector('[data-field="municipal_funding_percent"]');
        var muniEl = document.querySelector('[data-field="planned_municipal_cost"]');
        if (!muniEl) {
            return;
        }
        var total = totalEl ? parseNumField(totalEl.value) : null;
        var pct = pctEl ? parseNumField(pctEl.value) : null;
        if (total === null || pct === null) {
            muniEl.value = '';
            return;
        }
        var computed = Math.round(total * (pct / 100) * 100) / 100;
        muniEl.value = String(computed);
    }

    function updateKaPreview(areaIdStr, serverImageUrl) {
        var wrap = document.querySelector('[data-ta-ka-preview]');
        var img = document.querySelector('[data-ta-ka-preview-img]');
        if (!wrap || !img) {
            return;
        }
        var id = (areaIdStr || '').trim();
        var url = null;
        if (id) {
            var row = (cfg().knowledgeAreaAssets || {})[id];
            if (row && row.imageUrl) {
                url = row.imageUrl;
            } else if (serverImageUrl) {
                url = serverImageUrl;
            }
        }
        if (url) {
            img.src = url;
            wrap.removeAttribute('hidden');
        } else {
            img.removeAttribute('src');
            wrap.setAttribute('hidden', 'hidden');
        }
    }

    function formatLocalYMD(d) {
        return (
            d.getFullYear() +
            '-' +
            padNum(d.getMonth() + 1, 2) +
            '-' +
            padNum(d.getDate(), 2)
        );
    }

    function existingSessionDatesSet() {
        var set = {};
        $all('#ta-dates-tbody .ta-date-row').forEach(function (row) {
            var inp = row.querySelector('[data-ta-date-field="session_date"]');
            var v = inp ? (inp.value || '').trim() : '';
            if (v) {
                set[v] = true;
            }
        });
        return set;
    }

    function appendWeekdayDatesFromRange() {
        var startEl = document.querySelector('[data-ta-range-start]');
        var endEl = document.querySelector('[data-ta-range-end]');
        if (!startEl || !endEl) {
            return;
        }
        var startS = (startEl.value || '').trim();
        var endS = (endEl.value || '').trim();
        if (!startS || !endS) {
            if (window.showAlert) {
                window.showAlert('warning', 'Dates', 'Indica data d’inici i data de fi.');
            }
            return;
        }
        var partsS = startS.split('-');
        var partsE = endS.split('-');
        if (partsS.length !== 3 || partsE.length !== 3) {
            return;
        }
        var d0 = new Date(
            parseInt(partsS[0], 10),
            parseInt(partsS[1], 10) - 1,
            parseInt(partsS[2], 10)
        );
        var d1 = new Date(
            parseInt(partsE[0], 10),
            parseInt(partsE[1], 10) - 1,
            parseInt(partsE[2], 10)
        );
        if (d0 > d1) {
            if (window.showAlert) {
                window.showAlert('warning', 'Dates', 'La data d’inici no pot ser posterior a la data de fi.');
            }
            return;
        }
        var seen = existingSessionDatesSet();
        var added = 0;
        var cur = new Date(d0.getTime());
        while (cur <= d1) {
            var wd = cur.getDay();
            if (wd !== 0 && wd !== 6) {
                var iso = formatLocalYMD(cur);
                if (!seen[iso]) {
                    addDateRow({ session_date: iso, start_time: '', end_time: '' });
                    seen[iso] = true;
                    added++;
                }
            }
            cur.setDate(cur.getDate() + 1);
        }
        if (added === 0 && window.showAlert) {
            window.showAlert(
                'info',
                'Dates',
                'No s’han afegit dates noves (ja existien o el rang no té dies laborables).'
            );
        }
    }

    function padNum(n, len) {
        var s = String(Math.max(0, parseInt(n, 10) || 0));
        while (s.length < len) {
            s = '0' + s;
        }
        return s.length > len ? s.slice(-len) : s;
    }

    function ensureKaOption(select, id, label) {
        if (!select || !id) {
            return;
        }
        var idStr = String(id);
        for (var i = 0; i < select.options.length; i++) {
            if (select.options[i].value === idStr) {
                return;
            }
        }
        var opt = document.createElement('option');
        opt.value = idStr;
        opt.setAttribute('data-ta-injected', '1');
        opt.textContent = label || idStr;
        select.appendChild(opt);
    }
    function removeInjectedKa() {
        var sel = document.getElementById('ta_knowledge_area_id');
        if (!sel) {
            return;
        }
        sel.querySelectorAll('option[data-ta-injected="1"]').forEach(function (o) {
            o.remove();
        });
    }

    function fillFromAction(a) {
        var f = form();
        if (!f || !a) {
            return;
        }
        f.querySelector('[data-field="id"]').value = a.id ? String(a.id) : '';
        f.querySelector('[data-field="catalog_action_id"]').value =
            a.catalog_action_id != null && a.catalog_action_id !== '' ? String(a.catalog_action_id) : '';

        var py = document.getElementById('ta_program_year');
        if (py) {
            py.value = String(a.program_year || cfg().defaultProgramYear || new Date().getFullYear());
        }
        var numRo = document.querySelector('[data-ta-action-number-display]');
        if (numRo) {
            numRo.value = a.action_number != null ? String(a.action_number) : '';
        }
        var dc = document.querySelector('[data-ta-display-code]');
        if (dc) {
            dc.value = a.display_code || '';
        }

        function setVal(field, v) {
            var el = f.querySelector('[data-field="' + field + '"]');
            if (!el) {
                return;
            }
            if (el.type === 'checkbox') {
                el.checked = v === 1 || v === true || v === '1';
            } else {
                el.value = v != null && v !== '' ? String(v) : '';
            }
        }

        setVal('name', a.name);
        setVal('subprogram_id', a.subprogram_id);
        removeInjectedKa();
        var ka = document.getElementById('ta_knowledge_area_id');
        if (ka) {
            ka.value = a.knowledge_area_id != null ? String(a.knowledge_area_id) : '';
            if (a.knowledge_area_id && ka.value !== String(a.knowledge_area_id)) {
                var lab =
                    a.ka_code != null
                        ? padNum(a.ka_code, 3) + ' — ' + (a.ka_name || '')
                        : String(a.knowledge_area_id);
                ensureKaOption(ka, a.knowledge_area_id, lab);
                ka.value = String(a.knowledge_area_id);
            }
        }
        setVal('organizer_id', a.organizer_id);
        setVal('trainer_type_id', a.trainer_type_id);
        setVal('trainers_text', a.trainers_text);
        setVal('planned_places', a.planned_places);
        setVal('training_location_id', a.training_location_id);
        setVal('planned_duration_hours', a.planned_duration_hours);
        setVal('planned_total_cost', a.planned_total_cost);
        setVal('municipal_funding_percent', a.municipal_funding_percent);
        setVal('planned_municipal_cost', a.planned_municipal_cost);
        setVal('training_authorizer_id', a.training_authorizer_id);
        setVal('funding_id', a.funding_id);
        setVal('grouped_plan_code', a.grouped_plan_code);
        setVal('planned_schedule', a.planned_schedule);
        setVal('notes', a.notes);
        setVal('target_audience', a.target_audience);
        setVal('training_objectives', a.training_objectives);
        setVal('conceptual_contents', a.conceptual_contents);
        setVal('procedural_contents', a.procedural_contents);
        setVal('attitudinal_contents', a.attitudinal_contents);
        setVal('execution_status', a.execution_status != null && a.execution_status !== '' ? a.execution_status : 'Pendent');
        setVal('actual_cost', a.actual_cost);
        setVal('actual_duration_hours', a.actual_duration_hours);
        setVal('execution_notes', a.execution_notes);
        setVal('is_active', a.is_active);

        clearDateRows();
        if (a.dates && a.dates.length) {
            a.dates.forEach(function (d) {
                addDateRow(d);
            });
        }
        refreshCatalogBtn();
        recalcPlannedMunicipalCost();
        var kaSel = document.getElementById('ta_knowledge_area_id');
        updateKaPreview(kaSel ? kaSel.value : '', a.ka_image_url || null);
        refreshAttendeeUi();
        loadAttendeesList();
        refreshDocumentsUi();
        loadDocumentsList();
        refreshEvalUi();
        loadEvaluationsList();
    }

    function resetForCreate() {
        var f = form();
        if (!f) {
            return;
        }
        clearErrors(f);
        f.reset();
        f.querySelector('[data-field="id"]').value = '';
        f.querySelector('[data-field="catalog_action_id"]').value = '';
        removeInjectedKa();
        clearDateRows();
        var tbAtt = document.getElementById('ta-attendees-tbody');
        if (tbAtt) {
            tbAtt.innerHTML = '';
        }
        var attEmpty = document.querySelector('[data-ta-attendees-empty]');
        if (attEmpty) {
            attEmpty.hidden = true;
        }
        var tbDocs = document.getElementById('ta-docs-tbody');
        if (tbDocs) {
            tbDocs.innerHTML = '';
        }
        var docsEmpty = document.querySelector('[data-ta-docs-empty]');
        if (docsEmpty) {
            docsEmpty.hidden = true;
        }
        var tbEv = document.getElementById('ta-eval-tbody');
        if (tbEv) {
            tbEv.innerHTML = '';
        }
        var evEmpty = document.querySelector('[data-ta-eval-empty]');
        if (evEmpty) {
            evEmpty.hidden = true;
        }
        refreshAttendeeUi();
        refreshDocumentsUi();
        refreshEvalUi();
        var py = document.getElementById('ta_program_year');
        if (py) {
            py.value = String(cfg().defaultProgramYear || new Date().getFullYear());
        }
        var chk = document.getElementById('ta_is_active');
        if (chk) {
            chk.checked = true;
        }
        var esStat = document.getElementById('ta_execution_status');
        if (esStat) {
            esStat.value = 'Pendent';
        }
        updateNextPreview();
        switchTab('prog');
        recalcPlannedMunicipalCost();
        updateKaPreview('', null);
    }

    function updateNextPreview() {
        var pyEl = document.getElementById('ta_program_year');
        if (!pyEl || currentMode !== 'create') {
            return;
        }
        var py = parseInt(pyEl.value, 10);
        if (isNaN(py)) {
            return;
        }
        var api = cfg().apiUrl || '';
        fetch(api + '?action=next_preview&program_year=' + encodeURIComponent(String(py)), { credentials: 'same-origin' })
            .then(function (r) {
                return r.json();
            })
            .then(function (d) {
                if (!d.ok) {
                    return;
                }
                var numRo = document.querySelector('[data-ta-action-number-display]');
                var dc = document.querySelector('[data-ta-display-code]');
                if (numRo) {
                    numRo.value = String(d.next_action_number);
                }
                if (dc) {
                    dc.value = d.display_code || '';
                }
            })
            .catch(function () {});
    }

    function setHeaders(mode) {
        var h = document.querySelector('[data-ta-modal-heading]');
        var s = document.querySelector('[data-ta-modal-subheading]');
        if (mode === 'create') {
            if (h) {
                h.textContent = 'Nova acció formativa';
            }
            if (s) {
                s.textContent = 'Programació, detalls i execució';
            }
        } else if (mode === 'edit') {
            if (h) {
                h.textContent = 'Editar acció formativa';
            }
            if (s) {
                s.textContent = 'Modifica les dades';
            }
        } else {
            if (h) {
                h.textContent = 'Visualitzar acció formativa';
            }
            if (s) {
                s.textContent = 'Només lectura';
            }
        }
    }

    function openCreate() {
        if (!cfg().canCreate) {
            return;
        }
        currentMode = 'create';
        setHeaders('create');
        resetForCreate();
        setEditable(true);
        openModalMain();
        refreshCatalogBtn();
    }

    function loadAndOpen(id, mode) {
        var api = cfg().apiUrl || '';
        fetch(api + '?action=get&id=' + encodeURIComponent(String(id)), { credentials: 'same-origin' })
            .then(function (r) {
                return r.json();
            })
            .then(function (d) {
                if (!d.ok || !d.action) {
                    if (window.showAlert) {
                        window.showAlert('error', 'Error', 'No s’ha pogut carregar l’acció.');
                    }
                    return;
                }
                currentMode = mode;
                setHeaders(mode);
                fillFromAction(d.action);
                var editable = mode === 'edit' && cfg().canEdit;
                setEditable(editable);
                switchTab('prog');
                openModalMain();
            })
            .catch(function () {
                if (window.showAlert) {
                    window.showAlert('error', 'Error', 'Error de xarxa.');
                }
            });
    }

    function submitForm(ev) {
        ev.preventDefault();
        var f = form();
        if (!f || currentMode === 'view') {
            return;
        }
        clearErrors(f);
        recalcPlannedMunicipalCost();
        var esSel = f.querySelector('[data-field="execution_status"]');
        if (esSel && (!esSel.value || String(esSel.value).trim() === '')) {
            showErrors(f, { execution_status: 'L’estat d’execució és obligatori.' });
            if (window.showAlert) {
                window.showAlert('warning', 'Validació', 'Seleccioneu l’estat d’execució.');
            }
            switchTab('exec');
            return;
        }
        var c = cfg();
        var payload = {
            action: 'save',
            csrf_token: c.csrfToken || '',
            id: (f.querySelector('[data-field="id"]').value || '').trim(),
            program_year: (document.getElementById('ta_program_year') || {}).value,
            catalog_action_id: (f.querySelector('[data-field="catalog_action_id"]').value || '').trim(),
            name: (f.querySelector('[data-field="name"]').value || '').trim(),
            subprogram_id: f.querySelector('[data-field="subprogram_id"]').value,
            knowledge_area_id: f.querySelector('[data-field="knowledge_area_id"]').value,
            organizer_id: f.querySelector('[data-field="organizer_id"]').value,
            trainer_type_id: f.querySelector('[data-field="trainer_type_id"]').value,
            trainers_text: (f.querySelector('[data-field="trainers_text"]').value || '').trim(),
            planned_places: (f.querySelector('[data-field="planned_places"]').value || '').trim(),
            training_location_id: f.querySelector('[data-field="training_location_id"]').value,
            planned_duration_hours: (f.querySelector('[data-field="planned_duration_hours"]').value || '').trim(),
            planned_total_cost: (f.querySelector('[data-field="planned_total_cost"]').value || '').trim(),
            municipal_funding_percent: (f.querySelector('[data-field="municipal_funding_percent"]').value || '').trim(),
            planned_municipal_cost: (f.querySelector('[data-field="planned_municipal_cost"]').value || '').trim(),
            training_authorizer_id: f.querySelector('[data-field="training_authorizer_id"]').value,
            funding_id: f.querySelector('[data-field="funding_id"]').value,
            grouped_plan_code: (f.querySelector('[data-field="grouped_plan_code"]').value || '').trim(),
            planned_schedule: (f.querySelector('[data-field="planned_schedule"]').value || '').trim(),
            notes: (f.querySelector('[data-field="notes"]').value || '').trim(),
            target_audience: (f.querySelector('[data-field="target_audience"]').value || '').trim(),
            training_objectives: (f.querySelector('[data-field="training_objectives"]').value || '').trim(),
            conceptual_contents: (f.querySelector('[data-field="conceptual_contents"]').value || '').trim(),
            procedural_contents: (f.querySelector('[data-field="procedural_contents"]').value || '').trim(),
            attitudinal_contents: (f.querySelector('[data-field="attitudinal_contents"]').value || '').trim(),
            execution_status: (f.querySelector('[data-field="execution_status"]').value || '').trim(),
            actual_cost: (f.querySelector('[data-field="actual_cost"]').value || '').trim(),
            actual_duration_hours: (f.querySelector('[data-field="actual_duration_hours"]').value || '').trim(),
            execution_notes: (f.querySelector('[data-field="execution_notes"]').value || '').trim(),
            is_active: document.getElementById('ta_is_active').checked ? '1' : '0',
            dates: collectDatesPayload()
        };
        if (!payload.id) {
            delete payload.id;
        }
        if (!payload.catalog_action_id) {
            delete payload.catalog_action_id;
        }
        fetch(c.apiUrl || '', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': c.csrfToken || '' },
            body: JSON.stringify(payload)
        })
            .then(function (r) {
                return r.json().then(function (j) {
                    return { status: r.status, body: j };
                });
            })
            .then(function (res) {
                if (res.body.ok) {
                    closeModalMain();
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
        if (!cfg().canDelete || typeof window.showConfirm !== 'function') {
            return;
        }
        var c = cfg();
        window.showConfirm(
            'Eliminar acció',
            'Voleu eliminar aquesta acció formativa?',
            function () {
                fetch(c.apiUrl || '', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': c.csrfToken || '' },
                    body: JSON.stringify({ action: 'delete', id: id, csrf_token: c.csrfToken })
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
                        } else if (d.errors && d.errors._general && window.showAlert) {
                            window.showAlert('error', 'Error', d.errors._general);
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

    var catalogSearchTimer = null;
    function loadCatalogList(q) {
        var api = cfg().apiUrl || '';
        fetch(api + '?action=catalog_list&q=' + encodeURIComponent(q || ''), { credentials: 'same-origin' })
            .then(function (r) {
                return r.json();
            })
            .then(function (d) {
                var ul = document.getElementById('ta-catalog-list');
                if (!ul || !d.ok || !d.items) {
                    return;
                }
                ul.innerHTML = '';
                d.items.forEach(function (it) {
                    var li = document.createElement('li');
                    li.className = 'ta-catalog-list__item';
                    li.setAttribute('role', 'option');
                    li.setAttribute('data-catalog-id', String(it.id));
                    li.textContent = it.label || it.name;
                    ul.appendChild(li);
                });
            })
            .catch(function () {});
    }

    function applyCatalogPick(id) {
        var api = cfg().apiUrl || '';
        fetch(api + '?action=catalog_pick&id=' + encodeURIComponent(String(id)), { credentials: 'same-origin' })
            .then(function (r) {
                return r.json();
            })
            .then(function (d) {
                if (!d.ok || !d.pick) {
                    return;
                }
                var p = d.pick;
                var f = form();
                if (!f) {
                    return;
                }
                f.querySelector('[data-field="catalog_action_id"]').value = String(p.catalog_action_id || '');
                var nameEl = document.getElementById('ta_name');
                if (nameEl) {
                    nameEl.value = p.name || '';
                }
                removeInjectedKa();
                var ka = document.getElementById('ta_knowledge_area_id');
                if (ka && p.knowledge_area_id) {
                    ka.value = String(p.knowledge_area_id);
                    if (ka.value !== String(p.knowledge_area_id)) {
                        ensureKaOption(ka, p.knowledge_area_id, String(p.knowledge_area_id));
                        ka.value = String(p.knowledge_area_id);
                    }
                }
                function setTxt(field, v) {
                    var el = f.querySelector('[data-field="' + field + '"]');
                    if (el) {
                        el.value = v != null ? String(v) : '';
                    }
                }
                setTxt('target_audience', p.target_audience);
                setTxt('training_objectives', p.training_objectives);
                setTxt('conceptual_contents', p.conceptual_contents);
                setTxt('procedural_contents', p.procedural_contents);
                setTxt('attitudinal_contents', p.attitudinal_contents);
                setTxt('planned_duration_hours', p.planned_duration_hours);
                closeCatalogModal();
                refreshCatalogBtn();
                updateKaPreview(ka ? ka.value : '', null);
            })
            .catch(function () {});
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('click', function (e) {
            if (e.target.closest('[data-ta-open-create]')) {
                e.preventDefault();
                openCreate();
            }
            if (e.target.closest('[data-ta-view]')) {
                e.preventDefault();
                var id = parseInt(e.target.closest('[data-ta-view]').getAttribute('data-ta-view'), 10);
                if (id > 0) {
                    loadAndOpen(id, 'view');
                }
            }
            if (e.target.closest('[data-ta-edit]')) {
                e.preventDefault();
                var id2 = parseInt(e.target.closest('[data-ta-edit]').getAttribute('data-ta-edit'), 10);
                if (id2 > 0) {
                    loadAndOpen(id2, 'edit');
                }
            }
            if (e.target.closest('[data-ta-delete]')) {
                e.preventDefault();
                var id3 = parseInt(e.target.closest('[data-ta-delete]').getAttribute('data-ta-delete'), 10);
                if (id3 > 0) {
                    confirmDelete(id3);
                }
            }
            if (e.target.closest('[data-ta-modal-close]')) {
                e.preventDefault();
                closeModalMain();
            }
            if (e.target.closest('[data-ta-tab]') && !e.target.closest('[data-ta-tab]').disabled) {
                e.preventDefault();
                var tab = e.target.closest('[data-ta-tab]');
                var k = tab.getAttribute('data-ta-tab');
                if (k) {
                    switchTab(k);
                }
            }
            if (e.target.closest('[data-ta-add-date]')) {
                e.preventDefault();
                addDateRow(null);
            }
            if (e.target.closest('[data-ta-generate-weekdays]')) {
                e.preventDefault();
                appendWeekdayDatesFromRange();
            }
            if (e.target.closest('[data-ta-remove-date]')) {
                e.preventDefault();
                var row = e.target.closest('.ta-date-row');
                if (row && row.parentNode) {
                    row.parentNode.removeChild(row);
                }
            }
            if (e.target.closest('[data-ta-catalog-btn]')) {
                e.preventDefault();
                openCatalogPickerFlow();
            }
            if (e.target.closest('[data-ta-catalog-close]')) {
                e.preventDefault();
                closeCatalogModal();
            }
            if (e.target.closest('[data-ta-attendee-view]')) {
                e.preventDefault();
                var vid = parseInt(e.target.closest('[data-ta-attendee-view]').getAttribute('data-ta-attendee-view'), 10);
                if (vid > 0) {
                    openAttendeeModalView(vid);
                }
            }
            if (e.target.closest('[data-ta-attendee-edit]')) {
                e.preventDefault();
                var eid = parseInt(e.target.closest('[data-ta-attendee-edit]').getAttribute('data-ta-attendee-edit'), 10);
                if (eid > 0) {
                    openAttendeeModalEdit(eid);
                }
            }
            if (e.target.closest('[data-ta-attendee-del]')) {
                e.preventDefault();
                var did = parseInt(e.target.closest('[data-ta-attendee-del]').getAttribute('data-ta-attendee-del'), 10);
                if (did > 0) {
                    confirmDeleteAttendee(did);
                }
            }
            if (e.target.closest('[data-ta-doc-edit]')) {
                e.preventDefault();
                var de = e.target.closest('[data-ta-doc-edit]');
                var docId = parseInt(de.getAttribute('data-ta-doc-edit'), 10);
                if (docId > 0) {
                    openDocumentModalEdit(docId);
                }
            }
            if (e.target.closest('[data-ta-doc-del]')) {
                e.preventDefault();
                var dd = e.target.closest('[data-ta-doc-del]');
                var docDelId = parseInt(dd.getAttribute('data-ta-doc-del'), 10);
                if (docDelId > 0) {
                    confirmDeleteDocument(docDelId);
                }
            }
            if (e.target.closest('[data-ta-eval-sort]')) {
                e.preventDefault();
                var sb = e.target.closest('[data-ta-eval-sort]');
                var sortKey = sb && sb.getAttribute('data-ta-eval-sort');
                if (sortKey) {
                    if (sortKey === evalSortBy) {
                        evalSortDir = evalSortDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        evalSortBy = sortKey;
                        evalSortDir = 'asc';
                    }
                    renderEvaluationsTableBody();
                }
            }
            if (e.target.closest('[data-ta-eval-detail]')) {
                e.preventDefault();
                var evBtn = e.target.closest('[data-ta-eval-detail]');
                var evId = parseInt(evBtn.getAttribute('data-ta-eval-detail'), 10);
                if (evId > 0) {
                    loadEvaluationDetail(evId);
                }
            }
            if (e.target.closest('#ta-catalog-list .ta-catalog-list__item')) {
                e.preventDefault();
                var item = e.target.closest('.ta-catalog-list__item');
                var cid = item.getAttribute('data-catalog-id');
                if (cid) {
                    applyCatalogPick(parseInt(cid, 10));
                }
            }
        });

        var nameInput = document.getElementById('ta_name');
        if (nameInput) {
            nameInput.addEventListener('input', refreshCatalogBtn);
            nameInput.addEventListener('change', refreshCatalogBtn);
        }
        var pyEl = document.getElementById('ta_program_year');
        if (pyEl) {
            pyEl.addEventListener('change', function () {
                if (currentMode === 'create') {
                    updateNextPreview();
                }
            });
        }
        var totalCost = document.querySelector('[data-field="planned_total_cost"]');
        var muniPct = document.querySelector('[data-field="municipal_funding_percent"]');
        if (totalCost) {
            totalCost.addEventListener('input', recalcPlannedMunicipalCost);
            totalCost.addEventListener('change', recalcPlannedMunicipalCost);
        }
        if (muniPct) {
            muniPct.addEventListener('input', recalcPlannedMunicipalCost);
            muniPct.addEventListener('change', recalcPlannedMunicipalCost);
        }
        var kaSelInit = document.getElementById('ta_knowledge_area_id');
        if (kaSelInit) {
            kaSelInit.addEventListener('change', function () {
                updateKaPreview(kaSelInit.value, null);
            });
        }
        var catSearch = document.getElementById('ta_catalog_search');
        if (catSearch) {
            catSearch.addEventListener('input', function () {
                clearTimeout(catalogSearchTimer);
                var v = catSearch.value || '';
                catalogSearchTimer = setTimeout(function () {
                    loadCatalogList(v);
                }, 300);
            });
        }

        var f = form();
        if (f) {
            f.addEventListener('submit', submitForm);
        }

        var attSave = document.querySelector('[data-ta-attendee-save]');
        if (attSave) {
            attSave.addEventListener('click', function (e) {
                e.preventDefault();
                submitAttendeeSave();
            });
        }
        var attClose = document.querySelector('[data-ta-attendee-close]');
        if (attClose) {
            attClose.addEventListener('click', function (e) {
                e.preventDefault();
                closeAttendeeOverlay();
            });
        }
        var attAdd = document.querySelector('[data-ta-attendee-add]');
        if (attAdd) {
            attAdd.addEventListener('click', function (e) {
                e.preventDefault();
                openAttendeeModalCreate();
            });
        }
        var attChk = document.getElementById('ta_attendee_attendance');
        if (attChk) {
            attChk.addEventListener('change', function () {
                if (attChk.checked) {
                    var ta = document.getElementById('ta_attendee_non_attendance');
                    if (ta) {
                        ta.value = '';
                    }
                }
            });
        }
        var attCertClear = document.querySelector('[data-ta-attendee-cert-clear]');
        if (attCertClear) {
            attCertClear.addEventListener('click', function (e) {
                e.preventDefault();
                var hid = document.getElementById('ta_attendee_certificate_doc_id');
                if (hid) {
                    hid.value = '';
                }
                var cf = document.getElementById('ta_attendee_cert_file');
                if (cf) {
                    cf.value = '';
                }
                syncCertDisplay({});
            });
        }
        var attOv = attendeeOverlay();
        if (attOv) {
            attOv.addEventListener('click', function (e) {
                if (e.target === attOv || (e.target.classList && e.target.classList.contains('modal__backdrop'))) {
                    e.preventDefault();
                    closeAttendeeOverlay();
                }
            });
        }

        var sendQAll = document.querySelector('[data-ta-send-q-all]');
        if (sendQAll) {
            sendQAll.addEventListener('click', function (e) {
                e.preventDefault();
                sendQuestionnaireAll();
            });
        }
        var evImp = document.querySelector('[data-ta-eval-import-input]');
        if (evImp) {
            evImp.addEventListener('change', function () {
                if (evImp.files && evImp.files.length) {
                    submitEvalImport(evImp.files);
                    evImp.value = '';
                }
            });
        }
        var evDetailClose = document.querySelector('[data-ta-eval-detail-close]');
        if (evDetailClose) {
            evDetailClose.addEventListener('click', function (e) {
                e.preventDefault();
                closeEvaluationDetailOverlay();
            });
        }
        var evDetailOv = evaluationDetailOverlay();
        if (evDetailOv) {
            evDetailOv.addEventListener('click', function (e) {
                if (e.target === evDetailOv || (e.target.classList && e.target.classList.contains('modal__backdrop'))) {
                    e.preventDefault();
                    closeEvaluationDetailOverlay();
                }
            });
        }
        var docAdd = document.querySelector('[data-ta-doc-add]');
        if (docAdd) {
            docAdd.addEventListener('click', function (e) {
                e.preventDefault();
                openDocumentModalCreate();
            });
        }
        var docSave = document.querySelector('[data-ta-doc-save]');
        if (docSave) {
            docSave.addEventListener('click', function (e) {
                e.preventDefault();
                submitDocumentSave();
            });
        }
        var docClose = document.querySelector('[data-ta-doc-close]');
        if (docClose) {
            docClose.addEventListener('click', function (e) {
                e.preventDefault();
                closeDocumentOverlay();
            });
        }
        var docOv = documentOverlay();
        if (docOv) {
            docOv.addEventListener('click', function (e) {
                if (e.target === docOv || (e.target.classList && e.target.classList.contains('modal__backdrop'))) {
                    e.preventDefault();
                    closeDocumentOverlay();
                }
            });
        }
        var docFileInp = document.getElementById('ta_doc_file');
        if (docFileInp) {
            docFileInp.addEventListener('change', function () {
                var fnEl = document.getElementById('ta_doc_file_name');
                if (!fnEl || (fnEl.value || '').trim() !== '') {
                    return;
                }
                var f = docFileInp.files && docFileInp.files[0];
                if (f && f.name) {
                    fnEl.value = f.name;
                }
            });
        }

        /* Deep link des del tauler (calendari): training_actions.php?ta_edit=ID */
        var sp = new URLSearchParams(window.location.search);
        var qid = parseInt(String(sp.get('ta_edit') || ''), 10);
        if (qid > 0) {
            var cOpen = cfg();
            var mdOpen = cOpen.canEdit ? 'edit' : 'view';
            loadAndOpen(qid, mdOpen);
            try {
                var clean = new URL(window.location.href);
                clean.searchParams.delete('ta_edit');
                var qs = clean.searchParams.toString();
                history.replaceState({}, '', clean.pathname + (qs ? '?' + qs : '') + clean.hash);
            } catch (errOpen) {}
        }

    });

    window.trainingActionsFormModalIsOpen = function () {
        var m = overlay();
        var c = catalogOverlay();
        var a = attendeeOverlay();
        var d = documentOverlay();
        var evd = evaluationDetailOverlay();
        return !!(
            (m && !m.hasAttribute('hidden')) ||
            (c && !c.hasAttribute('hidden')) ||
            (a && !a.hasAttribute('hidden')) ||
            (d && !d.hasAttribute('hidden')) ||
            (evd && !evd.hasAttribute('hidden'))
        );
    };
})();
