/**
 * Mòdul usuaris: modal d’alta/edició (fetch API) i esborrat amb showConfirm.
 */
(function () {
    'use strict';

    /** Llegeix sempre en temps d’execució (els scripts defer s’executen després de window.APP_USERS al footer). */
    function appUsersCfg() {
        return window.APP_USERS || {};
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

    function $(sel, root) {
        return (root || document).querySelector(sel);
    }

    function getOverlay() {
        return document.getElementById('users-modal-overlay');
    }

    function openUsersModal() {
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
        var first = el.querySelector('input:not([type="hidden"]), select, textarea');
        if (first) {
            first.focus();
        }
    }

    function closeUsersModal() {
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
        var wrap = form.querySelector('.js-users-msg');
        if (wrap) {
            wrap.setAttribute('hidden', 'hidden');
        }
        var gen = form.querySelector('[data-users-form-error]');
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
                var wrap = form.querySelector('.js-users-msg');
                var gen = form.querySelector('[data-users-form-error]');
                if (wrap && gen) {
                    wrap.removeAttribute('hidden');
                    gen.textContent = errors[key];
                }
                return;
            }
            var p = form.querySelector('[data-error-for="' + key + '"]');
            if (p) {
                p.removeAttribute('hidden');
                p.textContent = errors[key];
            }
        });
    }

    function setMode(isCreate) {
        var h = document.querySelector('[data-users-modal-heading]');
        var sub = document.querySelector('[data-users-modal-subheading]');
        var pwReq = document.querySelector('[data-users-password-req]');
        var pw2Req = document.querySelector('[data-users-password2-req]');
        var pwLabel = document.querySelector('[data-users-password-label]');
        var pw = document.getElementById('users_password');
        var pw2 = document.getElementById('users_password_confirm');
        if (h) {
            h.textContent = isCreate ? 'Nou usuari' : 'Actualització';
        }
        if (sub) {
            sub.textContent = isCreate
                ? 'Introdueix les dades del nou usuari'
                : 'Modifica la informació de l’usuari';
        }
        if (pwReq) {
            pwReq.style.display = isCreate ? '' : 'none';
        }
        if (pw2Req) {
            pw2Req.style.display = isCreate ? '' : 'none';
        }
        if (pwLabel) {
            pwLabel.textContent = isCreate ? 'Contrasenya' : 'Nova contrasenya (opcional)';
        }
        if (pw) {
            pw.required = !!isCreate;
            pw.value = '';
        }
        if (pw2) {
            pw2.required = !!isCreate;
            pw2.value = '';
        }
    }

    function resetForm(form) {
        form.reset();
        var idField = form.querySelector('[data-field="id"]');
        if (idField) {
            idField.value = '';
        }
        var activeChk = document.getElementById('users_is_active');
        if (activeChk) {
            activeChk.checked = true;
        }
        clearFieldErrors(form);
    }

    function openCreate() {
        if (!appUsersCfg().canCreate) {
            return;
        }
        var form = document.getElementById('users-modal-form');
        if (!form) {
            return;
        }
        resetForm(form);
        setMode(true);
        openUsersModal();
    }

    function openEdit(id) {
        if (!appUsersCfg().canEdit) {
            return;
        }
        var form = document.getElementById('users-modal-form');
        if (!form) {
            return;
        }
        var apiUrl = appUsersCfg().apiUrl || '';
        resetForm(form);
        setMode(false);
        fetch(apiUrl + '?action=get&id=' + encodeURIComponent(String(id)), { credentials: 'same-origin' })
            .then(function (r) {
                return r.json();
            })
            .then(function (data) {
                if (!data.ok || !data.user) {
                    if (typeof window.showAlert === 'function') {
                        window.showAlert('error', 'Error', 'No s’ha pogut carregar l’usuari.');
                    }
                    return;
                }
                var u = data.user;
                form.querySelector('[data-field="id"]').value = String(u.id);
                form.querySelector('[data-field="username"]').value = u.username || '';
                form.querySelector('[data-field="full_name"]').value = u.full_name || '';
                form.querySelector('[data-field="email"]').value = u.email || '';
                form.querySelector('[data-field="role_id"]').value = u.role_id != null ? String(u.role_id) : '';
                document.getElementById('users_is_active').checked = !!u.is_active;
                openUsersModal();
            })
            .catch(function () {
                if (typeof window.showAlert === 'function') {
                    window.showAlert('error', 'Error', 'Error de xarxa.');
                }
            });
    }

    function submitForm(ev) {
        ev.preventDefault();
        var form = document.getElementById('users-modal-form');
        if (!form) {
            return;
        }
        clearFieldErrors(form);
        var c = appUsersCfg();
        var csrf = c.csrfToken || '';
        var apiUrl = c.apiUrl || '';
        var fd = new FormData(form);
        var id = (fd.get('id') || '').toString().trim();
        var payload = {
            action: 'save',
            csrf_token: csrf,
            username: (fd.get('username') || '').toString(),
            full_name: (fd.get('full_name') || '').toString(),
            email: (fd.get('email') || '').toString(),
            role_id: fd.get('role_id'),
            password: (fd.get('password') || '').toString(),
            password_confirm: (fd.get('password_confirm') || '').toString(),
            is_active: document.getElementById('users_is_active').checked ? '1' : '0'
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
                    closeUsersModal();
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
        var c = appUsersCfg();
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
            'Desitja eliminar aquest usuari?',
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
                    .then(function (r) {
                        return r.json();
                    })
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
            if (e.target.closest('[data-users-open-create]')) {
                e.preventDefault();
                openCreate();
            }
            if (e.target.closest('[data-users-edit]')) {
                e.preventDefault();
                var id = parseInt(e.target.closest('[data-users-edit]').getAttribute('data-users-edit'), 10);
                if (id > 0) {
                    openEdit(id);
                }
            }
            if (e.target.closest('[data-users-delete]')) {
                e.preventDefault();
                var btn = e.target.closest('[data-users-delete]');
                if (btn.disabled) {
                    return;
                }
                var did = parseInt(btn.getAttribute('data-users-delete'), 10);
                if (did > 0) {
                    confirmDelete(did);
                }
            }
            if (e.target.closest('[data-users-modal-close]')) {
                e.preventDefault();
                closeUsersModal();
            }
        });

        var form = document.getElementById('users-modal-form');
        if (form) {
            form.addEventListener('submit', submitForm);
        }

    });

    /**
     * Indica si el modal de formulari d’usuaris està obert (per coordinar Escape amb app.js).
     */
    window.usersFormModalIsOpen = function () {
        var el = getOverlay();
        return !!(el && !el.hasAttribute('hidden'));
    };
})();
