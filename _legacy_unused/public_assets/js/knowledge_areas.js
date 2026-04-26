(function () {
    'use strict';
    var kaPreviewBlobUrl = null;
    function cfg() { return window.APP_KNOWLEDGE_AREAS || {}; }
    function $(s, r) { return (r || document).querySelector(s); }
    function overlay() { return document.getElementById('knowledge-areas-modal-overlay'); }
    var KA_MSG_DISALLOWED = "Només es permeten imatges JPG, PNG o WEBP vàlides.";
    var KA_MSG_NOT_VALID = "El fitxer seleccionat no és una imatge JPG, PNG o WEBP vàlida.";

    function revokeKaBlobPreview() {
        if (kaPreviewBlobUrl) {
            try { URL.revokeObjectURL(kaPreviewBlobUrl); } catch (e) { /* ignore */ }
            kaPreviewBlobUrl = null;
        }
    }
    function kaLower(s) { return String(s || '').toLowerCase(); }
    function clearImageFieldError(f) {
        var p = f.querySelector('[data-error-for="image"]');
        if (p) { p.hidden = true; p.textContent = ''; }
    }
    function setImageFieldError(f, msg) {
        var p = f.querySelector('[data-error-for="image"]');
        if (p) { p.hidden = false; p.textContent = msg; }
    }
    function kaIsSvgFile(file) {
        if (kaLower(file.type) === 'image/svg+xml') return true;
        return kaLower(file.name || '').endsWith('.svg');
    }
    function kaSniffRasterKind(buf) {
        var u = new Uint8Array(buf);
        if (u.length < 12) return null;
        if (u[0] === 0xFF && u[1] === 0xD8 && u[2] === 0xFF) return 'jpeg';
        var png = [0x89, 0x50, 0x4E, 0x47, 0x0D, 0x0A, 0x1A, 0x0A];
        var i;
        for (i = 0; i < 8; i++) { if (u[i] !== png[i]) break; }
        if (i === 8) return 'png';
        if (u[0] === 0x52 && u[1] === 0x49 && u[2] === 0x46 && u[3] === 0x46 && u[8] === 0x57 && u[9] === 0x45 && u[10] === 0x42 && u[11] === 0x50) return 'webp';
        return null;
    }
    function kaDecodeCheck(file) {
        return new Promise(function (resolve) {
            if (typeof createImageBitmap === 'function') {
                createImageBitmap(file).then(function () { resolve(true); }).catch(function () { resolve(false); });
                return;
            }
            var u = URL.createObjectURL(file);
            var im = new Image();
            im.onload = function () { URL.revokeObjectURL(u); resolve(true); };
            im.onerror = function () { URL.revokeObjectURL(u); resolve(false); };
            im.src = u;
        });
    }
    /** Mateix criteri que PHP: JPEG/PNG/WEBP raster vàlid (capçalera + decodificació). */
    function kaValidateImageFile(file) {
        if (!file) return Promise.resolve({ ok: true });
        if (kaIsSvgFile(file)) return Promise.resolve({ ok: false, message: KA_MSG_DISALLOWED });
        var mime = kaLower(file.type);
        var blocked = ['image/gif', 'image/bmp', 'image/x-ms-bmp', 'image/x-icon', 'image/vnd.microsoft.icon', 'image/tiff', 'image/heic', 'image/heif', 'image/avif', 'image/svg+xml'];
        if (blocked.indexOf(mime) !== -1) return Promise.resolve({ ok: false, message: KA_MSG_DISALLOWED });
        return file.slice(0, 64).arrayBuffer().then(function (buf) {
            var kind = kaSniffRasterKind(buf);
            var u = new Uint8Array(buf);
            if (kind === null) {
                if (u.length >= 3 && u[0] === 0x47 && u[1] === 0x49 && u[2] === 0x46) {
                    return { ok: false, message: KA_MSG_DISALLOWED };
                }
                if (mime === 'image/jpeg' || mime === 'image/jpg' || mime === 'image/pjpeg' || mime === 'image/png' || mime === 'image/webp') {
                    return kaDecodeCheck(file).then(function (ok) {
                        return ok ? { ok: true } : { ok: false, message: KA_MSG_NOT_VALID };
                    });
                }
                if (mime !== '' && mime.indexOf('image/') === 0) {
                    return { ok: false, message: KA_MSG_DISALLOWED };
                }
                return { ok: false, message: KA_MSG_DISALLOWED };
            }
            return kaDecodeCheck(file).then(function (ok) {
                return ok ? { ok: true } : { ok: false, message: KA_MSG_NOT_VALID };
            });
        });
    }
    function lock() { if (window.lockModalBodyScroll) window.lockModalBodyScroll(); }
    function unlock() { if (window.unlockModalBodyScroll) window.unlockModalBodyScroll(); }
    function openModal() {
        var el = overlay();
        if (!el) return;
        el.removeAttribute('hidden');
        el.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(function () { el.classList.add('is-visible'); });
        lock();
        var f = el.querySelector('input:not([type="hidden"]):not([type="file"])');
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
            var f = $('#knowledge-areas-modal-form');
            if (f) revokeKaBlobPreview();
        }, 0);
    }
    function clearErrors(form) {
        form.querySelectorAll('[data-error-for]').forEach(function (p) { p.hidden = true; p.textContent = ''; });
        var w = form.querySelector('.js-knowledge-areas-msg');
        if (w) w.hidden = true;
        var g = form.querySelector('[data-knowledge-areas-form-error]');
        if (g) g.textContent = '';
    }
    function showErrors(form, e) {
        clearErrors(form);
        Object.keys(e || {}).forEach(function (k) {
            if (k === '_general') {
                var w = form.querySelector('.js-knowledge-areas-msg'), g = form.querySelector('[data-knowledge-areas-form-error]');
                if (w && g) {
                    w.hidden = false;
                    g.textContent = '';
                    g.appendChild(document.createTextNode(e[k]));
                }
                return;
            }
            if (k === '_upload_debug' || k === '_files_image_debug' || k === '_request_debug') {
                return;
            }
            var p = form.querySelector('[data-error-for="' + k + '"]');
            if (p) { p.hidden = false; p.textContent = typeof e[k] === 'string' ? e[k] : JSON.stringify(e[k]); }
        });
        var dbg = { _upload_debug: e._upload_debug, _files_image_debug: e._files_image_debug, _request_debug: e._request_debug };
        var hasDbg = dbg._upload_debug != null || dbg._files_image_debug != null || dbg._request_debug != null;
        if (hasDbg) {
            var w2 = form.querySelector('.js-knowledge-areas-msg'), g2 = form.querySelector('[data-knowledge-areas-form-error]');
            if (w2 && g2) {
                w2.hidden = false;
                var pre = document.createElement('pre');
                pre.className = 'knowledge-areas-debug-json';
                pre.setAttribute('tabindex', '0');
                pre.textContent = JSON.stringify(dbg, null, 2);
                g2.appendChild(pre);
            }
        }
    }
    function setMode(isCreate) {
        var h = $('[data-knowledge-areas-modal-heading]'), s = $('[data-knowledge-areas-modal-subheading]');
        if (h) h.textContent = isCreate ? 'Nova àrea' : 'Actualització';
        if (s) s.textContent = isCreate ? 'Introdueix les dades de la nova àrea' : 'Modifica la informació de l’àrea';
        var rw = $('[data-ka-remove-wrap]');
        if (rw) rw.hidden = !!isCreate;
    }
    function resetPreview(f) {
        revokeKaBlobPreview();
        f.removeAttribute('data-ka-server-image-url');
        f.removeAttribute('data-ka-server-image-name');
        var wrap = $('[data-ka-preview-wrap]', f), img = $('[data-ka-preview-img]', f), lbl = $('[data-ka-image-label]', f), cap = $('[data-ka-preview-caption]', f);
        if (wrap) wrap.hidden = true;
        if (img) { img.removeAttribute('src'); }
        if (lbl) { lbl.textContent = ''; lbl.hidden = true; }
        if (cap) cap.textContent = 'Vista prèvia';
        var rm = $('#ka_remove_image', f);
        if (rm) rm.checked = false;
        f.removeAttribute('data-ka-upload-valid');
    }
    function reset(f) {
        f.reset();
        var i = $('[data-field="id"]', f);
        if (i) i.value = '';
        var fileIn = $('#ka_image', f);
        if (fileIn) fileIn.value = '';
        $('#ka_is_active').checked = true;
        clearErrors(f);
        resetPreview(f);
        var cd = $('[data-field="code_display"]', f);
        if (cd) cd.value = (cfg().nextCodeDisplay || '');
    }
    /** Actualitza la zona de previsualització segons fitxer nou, imatge del servidor o eliminació. */
    function refreshKnowledgeAreaImagePreview(f) {
        var wrap = $('[data-ka-preview-wrap]', f), img = $('[data-ka-preview-img]', f), lbl = $('[data-ka-image-label]', f), cap = $('[data-ka-preview-caption]', f);
        var fileIn = $('#ka_image', f), rm = $('#ka_remove_image', f);
        if (!wrap || !img) return;
        if (rm && rm.checked) {
            revokeKaBlobPreview();
            img.removeAttribute('src');
            wrap.hidden = false;
            if (cap) cap.textContent = "La imatge s'eliminarà en desar";
            if (lbl) { lbl.hidden = true; lbl.textContent = ''; }
            return;
        }
        if (fileIn && fileIn.files && fileIn.files[0]) {
            var file = fileIn.files[0];
            if (f.getAttribute('data-ka-upload-valid') !== '1') {
                revokeKaBlobPreview();
                img.removeAttribute('src');
                wrap.hidden = true;
                if (lbl) { lbl.textContent = ''; lbl.hidden = true; }
                if (cap) cap.textContent = 'Vista prèvia';
                return;
            }
            revokeKaBlobPreview();
            kaPreviewBlobUrl = URL.createObjectURL(file);
            img.src = kaPreviewBlobUrl;
            wrap.hidden = false;
            if (cap) cap.textContent = 'Vista prèvia (JPG, PNG o WEBP)';
            if (lbl) { lbl.textContent = file.name || ''; lbl.hidden = false; }
            return;
        }
        f.removeAttribute('data-ka-upload-valid');
        var serverUrl = f.getAttribute('data-ka-server-image-url') || '';
        if (serverUrl) {
            revokeKaBlobPreview();
            img.src = serverUrl;
            wrap.hidden = false;
            if (cap) cap.textContent = 'Imatge desada';
            var sn = f.getAttribute('data-ka-server-image-name') || '';
            if (lbl) { lbl.textContent = sn; lbl.hidden = !sn; }
            return;
        }
        revokeKaBlobPreview();
        img.removeAttribute('src');
        wrap.hidden = true;
        if (lbl) { lbl.textContent = ''; lbl.hidden = true; }
        if (cap) cap.textContent = 'Vista prèvia';
    }
    function kaOnImageFileChanged(f) {
        clearImageFieldError(f);
        f.removeAttribute('data-ka-upload-valid');
        var fileIn = $('#ka_image', f);
        if (!fileIn || !fileIn.files || !fileIn.files[0]) {
            refreshKnowledgeAreaImagePreview(f);
            return;
        }
        kaValidateImageFile(fileIn.files[0]).then(function (r) {
            if (!r.ok) {
                f.removeAttribute('data-ka-upload-valid');
                setImageFieldError(f, r.message);
                refreshKnowledgeAreaImagePreview(f);
                return;
            }
            f.setAttribute('data-ka-upload-valid', '1');
            clearImageFieldError(f);
            refreshKnowledgeAreaImagePreview(f);
        });
    }
    function openCreate() {
        if (!cfg().canCreate) return;
        var f = $('#knowledge-areas-modal-form');
        if (!f) return;
        reset(f);
        setMode(true);
        openModal();
    }
    function openEdit(id) {
        if (!cfg().canEdit) return;
        var f = $('#knowledge-areas-modal-form');
        if (!f) return;
        reset(f);
        setMode(false);
        fetch((cfg().apiUrl || '') + '?action=get&id=' + encodeURIComponent(String(id)), { credentials: 'same-origin' }).then(function (r) { return r.json(); }).then(function (d) {
            if (!d.ok || !d.knowledge_area) {
                if (window.showAlert) window.showAlert('error', 'Error', 'No s’ha pogut carregar l’àrea.');
                return;
            }
            var a = d.knowledge_area;
            $('[data-field="id"]', f).value = String(a.id);
            $('[data-field="code_display"]', f).value = a.knowledge_area_code_display || String(a.knowledge_area_code || '');
            $('[data-field="name"]', f).value = a.name || '';
            $('#ka_is_active').checked = !!a.is_active;
            var fileIn = $('#ka_image', f);
            if (fileIn) fileIn.value = '';
            if (a.image_url) {
                f.setAttribute('data-ka-server-image-url', a.image_url);
                f.setAttribute('data-ka-server-image-name', a.image_name || '');
            } else {
                f.removeAttribute('data-ka-server-image-url');
                f.removeAttribute('data-ka-server-image-name');
            }
            refreshKnowledgeAreaImagePreview(f);
            openModal();
        }).catch(function () { if (window.showAlert) window.showAlert('error', 'Error', 'Error de xarxa.'); });
    }
    function submit(ev) {
        ev.preventDefault();
        var f = $('#knowledge-areas-modal-form');
        if (!f) return;
        clearErrors(f);
        var c = cfg(), csrf = c.csrfToken || '';
        var idVal = ($('[data-field="id"]', f).value || '').toString().trim();
        var imgInput = $('#ka_image', f);
        var selFile = imgInput && imgInput.files && imgInput.files[0] ? imgInput.files[0] : null;
        var removeImg = idVal !== '' && $('#ka_remove_image') && $('#ka_remove_image').checked;
        var willSendImage = !!(selFile && !removeImg);
        function doSave() {
            var fd = new FormData();
            fd.append('action', 'save');
            fd.append('csrf_token', csrf);
            if (idVal !== '') fd.append('id', idVal);
            fd.append('name', ($('[data-field="name"]', f).value || '').toString());
            fd.append('is_active', $('#ka_is_active').checked ? '1' : '0');
            if (willSendImage) fd.append('image', selFile);
            if (removeImg) fd.append('remove_image', '1');
            fetch(c.apiUrl || '', { method: 'POST', credentials: 'same-origin', headers: { 'X-CSRF-Token': csrf }, body: fd }).then(function (r) {
                return r.json().then(function (j) { return { status: r.status, body: j }; });
            }).then(function (res) {
                if (res.body.ok) {
                    closeModal();
                    if (window.showAlert) { window.showAlert('success', 'Èxit', res.body.message || 'Desat.'); setTimeout(function () { window.location.reload(); }, 650); }
                    else window.location.reload();
                    return;
                }
                if (res.body.errors) showErrors(f, res.body.errors);
                else if (window.showAlert) window.showAlert('error', 'Error', 'No s’ha pogut desar.');
            }).catch(function () { if (window.showAlert) window.showAlert('error', 'Error', 'Error de xarxa.'); });
        }
        if (willSendImage) {
            kaValidateImageFile(selFile).then(function (r) {
                if (!r.ok) {
                    showErrors(f, { image: r.message });
                    return;
                }
                doSave();
            });
            return;
        }
        doSave();
    }
    function confirmDelete(id) {
        if (!cfg().canDelete || !window.showConfirm) return;
        var csrf = cfg().csrfToken || '';
        window.showConfirm('Registre actiu', 'Desitja eliminar aquesta àrea de coneixement?', function () {
            fetch(cfg().apiUrl || '', { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf }, body: JSON.stringify({ action: 'delete', id: id, csrf_token: csrf }) }).then(function (r) { return r.json(); }).then(function (d) {
                if (d.ok) {
                    if (window.showAlert) { window.showAlert('success', 'Èxit', d.message || 'Eliminat.'); setTimeout(function () { window.location.reload(); }, 650); }
                    else window.location.reload();
                } else if (d.errors && d.errors._general && window.showAlert) window.showAlert('error', 'Error', d.errors._general);
            }).catch(function () { if (window.showAlert) window.showAlert('error', 'Error', 'Error de xarxa.'); });
        }, { confirmLabel: 'Sí', cancelLabel: 'No' });
    }
    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('click', function (e) {
            if (e.target.closest('[data-knowledge-areas-open-create]')) { e.preventDefault(); openCreate(); }
            if (e.target.closest('[data-knowledge-areas-edit]')) {
                e.preventDefault();
                var id = parseInt(e.target.closest('[data-knowledge-areas-edit]').getAttribute('data-knowledge-areas-edit'), 10);
                if (id > 0) openEdit(id);
            }
            if (e.target.closest('[data-knowledge-areas-delete]')) {
                e.preventDefault();
                var idd = parseInt(e.target.closest('[data-knowledge-areas-delete]').getAttribute('data-knowledge-areas-delete'), 10);
                if (idd > 0) confirmDelete(idd);
            }
            if (e.target.closest('[data-knowledge-areas-modal-close]')) { e.preventDefault(); closeModal(); }
        });
        var f = $('#knowledge-areas-modal-form');
        if (f) {
            f.addEventListener('submit', submit);
            var fi = $('#ka_image', f);
            if (fi) fi.addEventListener('change', function () { kaOnImageFileChanged(f); });
            var rmi = $('#ka_remove_image', f);
            if (rmi) rmi.addEventListener('change', function () { refreshKnowledgeAreaImagePreview(f); });
        }
    });
    window.knowledgeAreasFormModalIsOpen = function () { var el = overlay(); return !!(el && !el.hasAttribute('hidden')); };
})();
