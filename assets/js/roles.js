/**
 * Mòdul rols: modal d’alta/edició (fetch API) i esborrat amb showConfirm.
 */
(function () {
    'use strict';

    function appRolesCfg() {
        return window.APP_ROLES || {};
    }

    function lockScroll() {
        if (typeof window.lockModalBodyScroll === 'function') {
            window.lockModalBodyScroll();
        }
    }

    function unlockScroll() {
        if (typeof window.unlockModalBodyScroll === 'function') {
            window.unlockModalBodyScroll();
        }
    }

    function getOverlay() {
        return document.getElementById('roles-modal-overlay');
    }

    function openRolesModal() {
        var el = getOverlay();
        if (!el) {
            return;
        }
        el.removeAttribute('hidden');
        el.setAttribute('aria-hidden', 'false');
        requestAnimationFrame(function () {
            el.classList.add('is-visible');
        });
        lockScroll();
        var first = el.querySelector('input:not([type="hidden"]), textarea');
        if (first) {
            first.focus();
        }
    }

    function closeRolesModal() {
        var el = getOverlay();
        if (!el) {
            return;
        }
        var ae = document.activeElement;
        if (ae && el.contains(ae) && typeof ae.blur === 'function') {
            ae.blur();
        }
        window.setTimeout(function () {
            el.classList.remove('is-visible');
            el.setAttribute('hidden', 'hidden');
            el.setAttribute('aria-hidden', 'true');
            unlockScroll();
        }, 0);
    }

    function clearFieldErrors(form) {
        form.querySelectorAll('[data-error-for]').forEach(function (p) {
            p.setAttribute('hidden', 'hidden');
            p.textContent = '';
        });
        var wrap = form.querySelector('.js-roles-msg');
        if (wrap) {
            wrap.setAttribute('hidden', 'hidden');
        }
        var gen = form.querySelector('[data-roles-form-error]');
        if (gen) {
            gen.textContent = '';
        }
    }

    function showFieldErrors(form, errors) {
        clearFieldErrors(form);
        if (!errors) {
            return;
        }
        Object.keys(errors).forEach(function (key) {
            if (key === '_general') {
                var wrap = form.querySelector('.js-roles-msg');
                var gen = form.querySelector('[data-roles-form-error]');
                if (wrap && gen) {
                    wrap.removeAttribute('hidden');
                    gen.textContent = errors[key];
                }
                return;
            }
            var p = form.querySelector('[data-error-for=\"' + key + '\"]');
            if (p) {
                p.removeAttribute('hidden');
                p.textContent = errors[key];
            }
        });
    }

    function setMode(isCreate) {
        var h = document.querySelector('[data-roles-modal-heading]');
        var sub = document.querySelector('[data-roles-modal-subheading]');
        if (h) {
            h.textContent = isCreate ? 'Nou rol' : 'Actualització';
        }
        if (sub) {
            sub.textContent = isCreate
                ? 'Introdueix les dades del nou rol'
                : 'Modifica la informació del rol';
        }
    }

    /** Rol sistema (slug «admin»): el codi no es pot editar (validació també al backend). */
    function applyProtectedSlugUi(form, roleSlug) {
        var slugInput = form.querySelector('[data-field="slug"]');
        var prot = (appRolesCfg().protectedRoleSlug || '');
        if (!slugInput) {
            return;
        }
        if (prot && roleSlug === prot) {
            slugInput.readOnly = true;
            slugInput.setAttribute('aria-readonly', 'true');
            slugInput.title = 'El codi del rol administrador del sistema no es pot canviar.';
        } else {
            slugInput.readOnly = false;
            slugInput.removeAttribute('aria-readonly');
            slugInput.title = '';
        }
    }

    function resetForm(form) {
        form.reset();
        var idField = form.querySelector('[data-field=\"id\"]');
        if (idField) {
            idField.value = '';
        }
        clearFieldErrors(form);
    }

    function openCreate() {
        if (!appRolesCfg().canCreate) {
            return;
        }
        var form = document.getElementById('roles-modal-form');
        if (!form) {
            return;
        }
        resetForm(form);
        setMode(true);
        applyProtectedSlugUi(form, '');
        openRolesModal();
    }

    function openEdit(id) {
        if (!appRolesCfg().canEdit) {
            return;
        }
        var form = document.getElementById('roles-modal-form');
        if (!form) {
            return;
        }
        var apiUrl = appRolesCfg().apiUrl || '';
        resetForm(form);
        setMode(false);
        fetch(apiUrl + '?action=get&id=' + encodeURIComponent(String(id)), { credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok || !data.role) {
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', 'Error', 'No s’ha pogut carregar el rol.');
                    }
                    return;
                }
                var role = data.role;
                form.querySelector('[data-field=\"id\"]').value = String(role.id);
                form.querySelector('[data-field=\"name\"]').value = role.name || '';
                form.querySelector('[data-field=\"slug\"]').value = role.slug || '';
                form.querySelector('[data-field=\"description\"]').value = role.description || '';
                applyProtectedSlugUi(form, role.slug || '');
                openRolesModal();
            })
            .catch(function () {
                if (typeof window.showAlert === 'function') {
                    window.showAlert('error', 'Error', 'Error de xarxa.');
                }
            });
    }

    function submitForm(ev) {
        ev.preventDefault();
        var form = document.getElementById('roles-modal-form');
        if (!form) {
            return;
        }
        clearFieldErrors(form);
        var c = appRolesCfg();
        var csrf = c.csrfToken || '';
        var apiUrl = c.apiUrl || '';
        var fd = new FormData(form);
        var id = (fd.get('id') || '').toString().trim();
        var payload = {
            action: 'save',
            csrf_token: csrf,
            name: (fd.get('name') || '').toString(),
            slug: (fd.get('slug') || '').toString(),
            description: (fd.get('description') || '').toString()
        };
        if (id !== '') {
            payload.id = parseInt(id, 10);
        }
        fetch(apiUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': csrf
            },
            body: JSON.stringify(payload)
        })
            .then(function (r) {
                return r.json().then(function (j) {
                    return { status: r.status, body: j };
                });
            })
            .then(function (res) {
                if (res.body.ok) {
                    closeRolesModal();
                    if (typeof window.showAlert === 'function') {
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
                    showFieldErrors(form, res.body.errors);
                } else if (typeof window.showAlert === 'function') {
                    window.showAlert('error', 'Error', 'No s’ha pogut desar.');
                }
            })
            .catch(function () {
                if (typeof window.showAlert === 'function') {
                    window.showAlert('error', 'Error', 'Error de xarxa.');
                }
            });
    }

    function confirmDelete(id) {
        var c = appRolesCfg();
        if (!c.canDelete) {
            return;
        }
        var apiUrl = c.apiUrl || '';
        var csrf = c.csrfToken || '';
        if (typeof window.showConfirm !== 'function') {
            return;
        }
        window.showConfirm(
            'Registre actiu',
            'Desitja eliminar aquest rol?',
            function () {
                fetch(apiUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': csrf
                    },
                    body: JSON.stringify({ action: 'delete', id: id, csrf_token: csrf })
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (data.ok) {
                            if (typeof window.showAlert === 'function') {
                                window.showAlert('success', 'Èxit', data.message || 'Eliminat.');
                                setTimeout(function () {
                                    window.location.reload();
                                }, 650);
                            } else {
                                window.location.reload();
                            }
                        } else if (data.errors && data.errors._general && typeof window.showAlert === 'function') {
                            window.showAlert('error', 'Error', data.errors._general);
                        }
                    })
                    .catch(function () {
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('error', 'Error', 'Error de xarxa.');
                        }
                    });
            },
            { confirmLabel: 'Si', cancelLabel: 'No' }
        );
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('click', function (e) {
            if (e.target.closest('[data-roles-open-create]')) {
                e.preventDefault();
                openCreate();
            }
            if (e.target.closest('[data-roles-edit]')) {
                e.preventDefault();
                var id = parseInt(e.target.closest('[data-roles-edit]').getAttribute('data-roles-edit'), 10);
                if (id > 0) {
                    openEdit(id);
                }
            }
            if (e.target.closest('[data-roles-delete]')) {
                e.preventDefault();
                var did = parseInt(e.target.closest('[data-roles-delete]').getAttribute('data-roles-delete'), 10);
                if (did > 0) {
                    confirmDelete(did);
                }
            }
            if (e.target.closest('[data-roles-modal-close]')) {
                e.preventDefault();
                closeRolesModal();
            }
        });

        var form = document.getElementById('roles-modal-form');
        if (form) {
            form.addEventListener('submit', submitForm);
        }

    });

    window.rolesFormModalIsOpen = function () {
        var el = getOverlay();
        return !!(el && !el.hasAttribute('hidden'));
    };
})();
