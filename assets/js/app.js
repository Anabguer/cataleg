/**
 * Modal únic (mides sm/md/lg), alertes i confirmacions sense alert()/confirm() natius.
 *
 * API:
 *   showAlert(type, title, message, detail?)
 *   showAlert(type, message)  — compat: el segon paràmetre és el missatge, títol per defecte
 *   showConfirm(title, message, onConfirm, options?)
 *   showConfirm(message, onConfirm)  — compat: títol "Confirmació"
 *
 * options: { confirmLabel: 'Si', cancelLabel: 'No', size: 'sm'|'md'|'lg' }
 */
(function () {
    'use strict';

    var MODAL_ROOT_ID = 'modal-root';
    var modalEl = null;
    var escHandlerBound = false;
    var bodyScrollLockCount = 0;

    function lockBodyScroll() {
        bodyScrollLockCount++;
        if (bodyScrollLockCount === 1) {
            document.body.classList.add('modal-open');
            document.documentElement.classList.add('modal-open');
        }
    }

    function unlockBodyScroll() {
        if (bodyScrollLockCount > 0) {
            bodyScrollLockCount--;
        }
        if (bodyScrollLockCount === 0) {
            document.body.classList.remove('modal-open');
            document.documentElement.classList.remove('modal-open');
        }
    }

    window.lockModalBodyScroll = lockBodyScroll;
    window.unlockModalBodyScroll = unlockBodyScroll;

    var defaultAlertTitle = {
        success: 'Èxit',
        error: 'Error',
        warning: 'Atenció',
        info: 'Informació'
    };

    function getModalRoot() {
        return document.getElementById(MODAL_ROOT_ID);
    }

    function ensureModalShell() {
        var root = getModalRoot();
        if (!root) {
            return null;
        }
        var stale = root.querySelector('.js-app-modal');
        if (stale && !stale.querySelector('.modal__dialog--classic')) {
            stale.remove();
            modalEl = null;
        }
        if (modalEl && root.contains(modalEl)) {
            return modalEl;
        }
        var wrap = document.createElement('div');
        wrap.className = 'modal js-app-modal';
        wrap.setAttribute('role', 'dialog');
        wrap.setAttribute('aria-modal', 'true');
        wrap.setAttribute('aria-labelledby', 'js-modal-status-label');
        wrap.setAttribute('hidden', 'hidden');
        wrap.innerHTML =
            '<div class="modal__backdrop" data-modal-close></div>' +
            '<div class="modal__dialog modal__dialog--md modal__dialog--classic js-modal-dialog">' +
            '<div class="modal__statusbar">' +
            '<span class="js-modal-title" id="js-modal-status-label"></span>' +
            '</div>' +
            '<div class="modal__body-panel">' +
            '<div class="modal__body-panel-inner">' +
            '<div class="modal__message-box">' +
            '<p class="modal__lead js-modal-lead"></p>' +
            '<p class="modal__secondary js-modal-secondary" hidden></p>' +
            '<div class="js-modal-custom" hidden></div>' +
            '</div>' +
            '<div class="modal__graphic-icon js-modal-type-icon modal__graphic-icon--info" aria-hidden="true">?</div>' +
            '</div>' +
            '</div>' +
            '<footer class="modal__footer js-modal-footer modal__footer--classic"></footer>' +
            '</div>';
        root.appendChild(wrap);
        modalEl = wrap;

        wrap.addEventListener('click', function (e) {
            if (e.target.closest('[data-modal-close]')) {
                e.preventDefault();
                closeModal();
            }
        });

        if (!escHandlerBound) {
            escHandlerBound = true;
            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') {
                    return;
                }
                /* No tancar alertes/confirm amb Escape si hi ha un modal de formulari obert (alta/edició). */
                if (document.querySelector('.modal.modal--form-overlay:not([hidden])')) {
                    return;
                }
                if (modalEl && !modalEl.hasAttribute('hidden')) {
                    closeModal();
                }
            });
        }

        return modalEl;
    }

    function getDialog() {
        return modalEl ? modalEl.querySelector('.js-modal-dialog') : null;
    }

    function setModalSize(size) {
        var d = getDialog();
        if (!d) {
            return;
        }
        ['sm', 'md', 'lg'].forEach(function (s) {
            d.classList.remove('modal__dialog--' + s);
        });
        d.classList.add('modal__dialog--' + (size || 'md'));
    }

    function setModalVariant(el, type) {
        ['success', 'error', 'warning', 'info', 'confirm'].forEach(function (t) {
            el.classList.remove('modal--' + t);
        });
        if (['success', 'error', 'warning', 'info', 'confirm'].indexOf(type) !== -1) {
            el.classList.add('modal--' + type);
        }
    }

    function setTypeIcon(type) {
        var icon = modalEl && modalEl.querySelector('.js-modal-type-icon');
        if (!icon) {
            return;
        }
        var map = {
            success: '✓',
            error: '✕',
            warning: '!',
            info: 'i',
            confirm: '?'
        };
        var t = map[type] ? type : 'info';
        icon.className = 'modal__graphic-icon js-modal-type-icon modal__graphic-icon--' + t;
        icon.textContent = map[t] || map.info;
    }

    function openModal() {
        if (!modalEl) {
            return;
        }
        var root = getModalRoot();
        if (root) {
            root.classList.add('is-open');
            root.setAttribute('aria-hidden', 'false');
        }
        modalEl.removeAttribute('hidden');
        lockBodyScroll();
        requestAnimationFrame(function () {
            modalEl.classList.add('is-visible');
        });
    }

    function closeModal() {
        if (!modalEl) {
            return;
        }
        modalEl.classList.remove('is-visible');
        modalEl.setAttribute('hidden', 'hidden');
        var root = getModalRoot();
        if (root) {
            root.classList.remove('is-open');
            root.setAttribute('aria-hidden', 'true');
        }
        unlockBodyScroll();
    }

    /**
     * @param {string} type success|error|warning|info
     * @param {string} titleOrMsg
     * @param {string} [message]
     * @param {string} [detail]
     */
    window.showAlert = function (type, titleOrMsg, message, detail) {
        var el = ensureModalShell();
        if (!el) {
            return;
        }

        var title;
        var msg;
        var det;
        if (arguments.length <= 2) {
            msg = String(titleOrMsg || '');
            title = defaultAlertTitle[type] || defaultAlertTitle.info;
        } else {
            title = String(titleOrMsg || '');
            msg = String(message || '');
            det = detail !== undefined && detail !== null ? String(detail) : '';
        }

        var t = ['success', 'error', 'warning', 'info'].indexOf(type) !== -1 ? type : 'info';
        setModalVariant(el, t);
        setTypeIcon(t);
        setModalSize('sm');

        el.querySelector('.js-modal-title').textContent = title;
        el.querySelector('.js-modal-lead').textContent = msg;

        var sec = el.querySelector('.js-modal-secondary');
        var custom = el.querySelector('.js-modal-custom');
        if (det) {
            sec.hidden = false;
            sec.textContent = det;
        } else {
            sec.hidden = true;
            sec.textContent = '';
        }
        if (custom) {
            custom.hidden = true;
            custom.innerHTML = '';
        }

        var footEl = el.querySelector('.js-modal-footer');
        footEl.innerHTML = '';
        var ok = document.createElement('button');
        ok.type = 'button';
        ok.className = 'modal__btn modal__btn--classic';
        ok.setAttribute('data-modal-close', '');
        ok.textContent = 'D’acord';
        footEl.appendChild(ok);
        openModal();
        requestAnimationFrame(function () {
            ok.focus();
        });
    };

    /**
     * @param {string} titleOrMsg
     * @param {string|Function} messageOrCb
     * @param {Function} [onConfirm]
     * @param {object} [opts]
     */
    window.showConfirm = function (titleOrMsg, messageOrCb, onConfirm, opts) {
        var title;
        var message;
        var cb;
        var options = {};

        if (typeof messageOrCb === 'function') {
            title = 'Confirmació';
            message = String(titleOrMsg || '');
            cb = messageOrCb;
            options = (onConfirm && typeof onConfirm === 'object') ? onConfirm : {};
        } else {
            title = String(titleOrMsg || '');
            message = String(messageOrCb || '');
            cb = onConfirm;
            options = opts || {};
        }

        var el = ensureModalShell();
        if (!el) {
            return;
        }

        setModalVariant(el, 'confirm');
        setTypeIcon('confirm');
        setModalSize(options.size || 'md');

        el.querySelector('.js-modal-title').textContent = title;
        el.querySelector('.js-modal-lead').textContent = message;

        var sec = el.querySelector('.js-modal-secondary');
        sec.hidden = true;
        var custom = el.querySelector('.js-modal-custom');
        if (custom) {
            custom.hidden = true;
            custom.innerHTML = '';
        }

        var footEl = el.querySelector('.js-modal-footer');
        footEl.innerHTML = '';
        var cancel = document.createElement('button');
        cancel.type = 'button';
        cancel.className = 'modal__btn modal__btn--classic modal__btn--no';
        cancel.setAttribute('data-modal-close', '');
        cancel.textContent = options.cancelLabel || 'No';
        var ok = document.createElement('button');
        ok.type = 'button';
        ok.className = 'modal__btn modal__btn--classic modal__btn--si';
        ok.textContent = options.confirmLabel || 'Si';
        footEl.appendChild(cancel);
        footEl.appendChild(ok);
        ok.addEventListener(
            'click',
            function onOk() {
                ok.removeEventListener('click', onOk);
                closeModal();
                if (typeof cb === 'function') {
                    cb();
                }
            },
            { once: true }
        );
        openModal();
        requestAnimationFrame(function () {
            ok.focus();
        });
    };

    /**
     * Modal d'acció reutilitzant el component visual existent.
     *
     * @param {{
     *   title: string,
     *   message?: string,
     *   detail?: string,
     *   size?: 'sm'|'md'|'lg',
     *   type?: 'confirm'|'info'|'warning'|'error'|'success',
     *   contentHtml?: string,
     *   onOpen?: Function,
     *   buttons?: Array<{label:string,className?:string,closeOnClick?:boolean,onClick?:Function,autofocus?:boolean,dataClose?:boolean}>
     * }} opts
     */
    window.showActionModal = function (opts) {
        var options = opts || {};
        var el = ensureModalShell();
        if (!el) {
            return;
        }
        var type = ['success', 'error', 'warning', 'info', 'confirm'].indexOf(options.type) !== -1 ? options.type : 'confirm';
        setModalVariant(el, type);
        setTypeIcon(type === 'confirm' ? 'confirm' : type);
        setModalSize(options.size || 'md');

        el.querySelector('.js-modal-title').textContent = String(options.title || '');
        el.querySelector('.js-modal-lead').textContent = String(options.message || '');

        var sec = el.querySelector('.js-modal-secondary');
        var detail = options.detail !== undefined && options.detail !== null ? String(options.detail) : '';
        if (detail !== '') {
            sec.hidden = false;
            sec.textContent = detail;
        } else {
            sec.hidden = true;
            sec.textContent = '';
        }

        var custom = el.querySelector('.js-modal-custom');
        if (custom) {
            if (options.contentHtml) {
                custom.hidden = false;
                custom.innerHTML = String(options.contentHtml);
            } else {
                custom.hidden = true;
                custom.innerHTML = '';
            }
        }

        var footEl = el.querySelector('.js-modal-footer');
        footEl.innerHTML = '';
        var buttons = Array.isArray(options.buttons) ? options.buttons : [];
        if (buttons.length === 0) {
            buttons = [{ label: 'D’acord', className: '', dataClose: true, autofocus: true }];
        }
        var focusBtn = null;
        buttons.forEach(function (btn) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'modal__btn modal__btn--classic' + (btn.className ? (' ' + btn.className) : '');
            b.textContent = String(btn.label || '');
            if (btn.dataClose) {
                b.setAttribute('data-modal-close', '');
            }
            b.addEventListener('click', function () {
                if (typeof btn.onClick === 'function') {
                    btn.onClick(closeModal, b);
                }
                if (btn.closeOnClick !== false) {
                    closeModal();
                }
            });
            footEl.appendChild(b);
            if (!focusBtn || btn.autofocus) {
                focusBtn = b;
            }
        });

        openModal();
        requestAnimationFrame(function () {
            if (typeof options.onOpen === 'function') {
                options.onOpen(el);
            } else if (focusBtn) {
                focusBtn.focus();
            }
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.body.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-confirm]');
            if (!btn || btn.classList.contains('js-demo-delete')) {
                return;
            }
            var title = btn.getAttribute('data-confirm-title') || 'Confirmació';
            var msg = btn.getAttribute('data-confirm') || 'Segur que vols continuar?';
            e.preventDefault();
            showConfirm(title, msg, function () {
                var form = btn.closest('form');
                if (form && form.getAttribute('data-confirm-submit') === 'true') {
                    form.submit();
                }
                btn.dispatchEvent(new CustomEvent('app:confirmed', { bubbles: true }));
            });
        });

        document.body.addEventListener('click', function (e) {
            var clr = e.target.closest('.js-filter-clear');
            if (!clr) {
                return;
            }
            var card = clr.closest('.filter-card');
            if (!card) {
                return;
            }
            e.preventDefault();
            card.querySelectorAll('input, select, textarea').forEach(function (field) {
                if (field.hasAttribute('data-preserve-on-filter-clear')) {
                    return;
                }
                if (field.type === 'checkbox' || field.type === 'radio') {
                    field.checked = false;
                } else {
                    field.value = '';
                }
            });
            var form = clr.closest('form');
            if (form) {
                form.submit();
            }
        });

        document.body.addEventListener('click', function (e) {
            var demo = e.target.closest('.js-demo-save');
            if (demo) {
                e.preventDefault();
                showAlert('success', 'Desat', 'Demo: acció de guardat (sense backend).');
            }
        });

        document.body.addEventListener('click', function (e) {
            var del = e.target.closest('.js-demo-delete');
            if (del) {
                e.preventDefault();
                showConfirm(
                    'Eliminar registre',
                    del.getAttribute('data-confirm') || 'Vols eliminar aquest registre de prova?',
                    function () {
                        showAlert('info', 'Informació', 'Aquí s’enviaria la petició al servidor.');
                    }
                );
            }
        });

        document.body.addEventListener('click', function (e) {
            var a = e.target.closest('.js-demo-alert');
            if (a) {
                e.preventDefault();
                showAlert('warning', 'Avís', 'Exemple d’alerta amb títol i missatge.');
            }
        });

        document.body.addEventListener('click', function (e) {
            var c = e.target.closest('.js-demo-confirm');
            if (c) {
                e.preventDefault();
                showConfirm('Acció de prova', 'Vols continuar amb aquesta acció de demostració?', function () {
                    showAlert('success', 'Fet', 'Has confirmat l’acció.');
                });
            }
        });
    });
})();

/**
 * Alçada real de .app-header → --app-header-height al :root (sticky .page-header + padding .app-main).
 * Sense tocar CSS del navbar: només lectura de layout.
 */
(function () {
    'use strict';

    var CSS_VAR = '--app-header-height';

    function applyAppHeaderHeight() {
        var el = document.querySelector('.app-header');
        if (!el) {
            return;
        }
        var h = el.getBoundingClientRect().height;
        if (!h || !isFinite(h)) {
            return;
        }
        document.documentElement.style.setProperty(CSS_VAR, Math.round(h * 1000) / 1000 + 'px');
    }

    function runAfterLayout() {
        applyAppHeaderHeight();
        requestAnimationFrame(function () {
            applyAppHeaderHeight();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', runAfterLayout);
    } else {
        runAfterLayout();
    }

    window.addEventListener('resize', function () {
        applyAppHeaderHeight();
    });

    if (document.fonts && document.fonts.ready) {
        document.fonts.ready.then(function () {
            applyAppHeaderHeight();
        });
    }
})();
