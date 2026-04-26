/**
 * Validació bàsica del formulari de login (sense lògica inline al PHP).
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var form = document.querySelector('.auth-panel form');
        if (!form) {
            return;
        }
        form.addEventListener('submit', function (e) {
            var u = form.querySelector('#username');
            var p = form.querySelector('#password');
            if (!u || !p) {
                return;
            }
            if (!u.value.trim() || !p.value) {
                e.preventDefault();
                if (typeof window.showAlert === 'function') {
                    window.showAlert('warning', 'Dades incompletes', 'Introdueix usuari i contrasenya.');
                }
            }
        });
    });
})();
