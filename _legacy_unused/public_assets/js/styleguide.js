/**
 * Demos de la guia visual (showAlert / showConfirm).
 */
(function () {
    'use strict';

    function onReady(fn) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', fn);
        } else {
            fn();
        }
    }

    onReady(function () {
        document.body.addEventListener('click', function (e) {
            var t = e.target.closest('[data-sg-alert]');
            if (t) {
                e.preventDefault();
                var type = t.getAttribute('data-sg-alert') || 'info';
                var title = t.getAttribute('data-sg-title') || '';
                var msg = t.getAttribute('data-sg-msg') || '';
                if (typeof window.showAlert === 'function') {
                    if (title && msg) {
                        window.showAlert(type, title, msg);
                    } else {
                        window.showAlert(type, msg || title || '');
                    }
                }
            }
        });

        document.body.addEventListener('click', function (e) {
            var t = e.target.closest('[data-sg-confirm]');
            if (t) {
                e.preventDefault();
                var title = t.getAttribute('data-sg-title') || 'Confirmació';
                var msg = t.getAttribute('data-sg-msg') || 'Continuar?';
                if (typeof window.showConfirm === 'function') {
                    var opts = {};
                    var cfy = t.getAttribute('data-sg-confirm-label');
                    var cfn = t.getAttribute('data-sg-cancel-label');
                    if (cfy) {
                        opts.confirmLabel = cfy;
                    }
                    if (cfn) {
                        opts.cancelLabel = cfn;
                    }
                    window.showConfirm(title, msg, function () {
                        if (typeof window.showAlert === 'function') {
                            window.showAlert('success', 'Confirmat', 'Has acceptat.');
                        }
                    }, opts);
                }
            }
        });

        /** Modal registre (guia J): mateix disseny per alta / modificació */
        var modalRoot = document.querySelector('[data-styleguide-users-modal]');
        if (modalRoot) {
            var modalCopy = {
                create: {
                    title: 'Nou registre',
                    subtitle: 'Actualitza la informació',
                    submit: 'Crear'
                },
                edit: {
                    title: 'Actualització',
                    subtitle: 'Modifica la informació del registre',
                    submit: 'Actualitzar'
                }
            };

            function applyModalMode(mode) {
                var cfg = modalCopy[mode];
                if (!cfg) {
                    return;
                }
                var titleEl = modalRoot.querySelector('[data-modal-title]');
                var subEl = modalRoot.querySelector('[data-modal-subtitle]');
                var submitEl = modalRoot.querySelector('[data-modal-submit]');
                if (titleEl) {
                    titleEl.textContent = cfg.title;
                }
                if (subEl) {
                    subEl.textContent = cfg.subtitle;
                }
                if (submitEl) {
                    submitEl.textContent = cfg.submit;
                }
                modalRoot.setAttribute('data-mode', mode);
                modalRoot.querySelectorAll('[data-modal-mode]').forEach(function (btn) {
                    var active = btn.getAttribute('data-modal-mode') === mode;
                    btn.classList.toggle('is-active', active);
                    btn.setAttribute('aria-pressed', active ? 'true' : 'false');
                });
            }

            var initial = modalRoot.getAttribute('data-mode') || 'create';
            applyModalMode(initial === 'edit' ? 'edit' : 'create');

            modalRoot.addEventListener('click', function (e) {
                var btn = e.target.closest('[data-modal-mode]');
                if (!btn || !modalRoot.contains(btn)) {
                    return;
                }
                e.preventDefault();
                var mode = btn.getAttribute('data-modal-mode');
                if (mode === 'create' || mode === 'edit') {
                    applyModalMode(mode);
                }
            });
        }
    });
})();
