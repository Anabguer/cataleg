(function () {
    'use strict';

    function esc(value) {
        return String(value == null ? '' : value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function formatPersonName(row) {
        var p1 = String(row.last_name_1 || '').trim();
        var p2 = String(row.last_name_2 || '').trim();
        var fn = String(row.first_name || '').trim();
        var surnames = [p1, p2].filter(Boolean).join(' ');
        if (surnames !== '' && fn !== '') {
            return surnames + ', ' + fn;
        }
        return surnames || fn || '-';
    }

    function formatBool(v) {
        return Number(v) === 1 ? 'Sí' : 'No';
    }

    function formatDate(value) {
        var raw = String(value || '').trim();
        if (raw === '' || raw.indexOf('0000-00-00') === 0) {
            return '';
        }
        var d = raw.slice(0, 10).split('-');
        if (d.length !== 3) {
            return raw;
        }
        return d[2] + '/' + d[1] + '/' + d[0];
    }

    function formatPersonId(value) {
        var n = Number(value || 0);
        if (!isFinite(n) || n < 0) {
            n = 0;
        }
        var s = String(Math.floor(n));
        while (s.length < 5) {
            s = '0' + s;
        }
        return s;
    }

    function closePeopleModal(wrap) {
        var root = document.getElementById('modal-root');
        if (wrap && wrap.parentNode) {
            wrap.parentNode.removeChild(wrap);
        }
        if (root && !root.querySelector('.dashboard-people-modal') && !root.querySelector('.js-app-modal:not([hidden])')) {
            root.classList.remove('is-open');
            root.setAttribute('aria-hidden', 'true');
        }
        if (window.unlockModalBodyScroll) {
            window.unlockModalBodyScroll();
        }
    }

    function renderRows(rows) {
        if (!Array.isArray(rows) || rows.length === 0) {
            return '<div class="dashboard-people-modal__empty">No hi ha registres per a aquesta revisió.</div>';
        }
        var html =
            '<div class="dashboard-people-modal__table-wrap">' +
            '<table class="dashboard-people-modal__table">' +
            '<thead><tr>' +
            '<th>ID</th>' +
            '<th>Cognoms i nom</th>' +
            '<th>DNI</th>' +
            '<th>Email</th>' +
            '<th>Lloc</th>' +
            '<th>Plaça</th>' +
            '<th>Actiu</th>' +
            '<th>Data baixa</th>' +
            '</tr></thead><tbody>';
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i] || {};
            html +=
                '<tr>' +
                '<td>' + esc(formatPersonId(r.person_id)) + '</td>' +
                '<td>' + esc(formatPersonName(r)) + '</td>' +
                '<td>' + esc(r.national_id_number || '') + '</td>' +
                '<td>' + esc(r.email || '') + '</td>' +
                '<td>' + esc(r.job_position_id != null ? r.job_position_id : '') + '</td>' +
                '<td>' + esc(r.position_id != null ? r.position_id : '') + '</td>' +
                '<td>' + esc(formatBool(r.is_active)) + '</td>' +
                '<td>' + esc(formatDate(r.terminated_at)) + '</td>' +
                '</tr>';
        }
        html += '</tbody></table></div>';
        return html;
    }

    function openPeopleModal(payload) {
        var root = document.getElementById('modal-root');
        if (!root) {
            return;
        }
        var existing = root.querySelector('.dashboard-people-modal');
        if (existing) {
            existing.remove();
        }
        var wrap = document.createElement('div');
        wrap.className = 'modal dashboard-people-modal is-visible';
        wrap.setAttribute('role', 'dialog');
        wrap.setAttribute('aria-modal', 'true');
        wrap.setAttribute('aria-labelledby', 'dashboard-people-modal-title');
        wrap.innerHTML =
            '<div class="modal__backdrop" aria-hidden="true"></div>' +
            '<div class="modal__dialog dashboard-people-modal__dialog">' +
            '<div class="dashboard-people-modal__header">' +
            '<div class="dashboard-people-modal__head-main">' +
            '<h2 id="dashboard-people-modal-title" class="dashboard-people-modal__title">' + esc(payload.title || 'Revisió de persones') + '</h2>' +
            '<div class="dashboard-people-modal__meta">Total: <strong>' + esc(payload.total != null ? payload.total : 0) + '</strong></div>' +
            '</div>' +
            '<button type="button" class="btn btn--secondary btn--sm" data-people-checks-close>Tancar</button>' +
            '</div>' +
            '<div class="dashboard-people-modal__body">' +
            renderRows(payload.rows || []) +
            '</div>' +
            '</div>';
        root.appendChild(wrap);
        root.classList.add('is-open');
        root.setAttribute('aria-hidden', 'false');
        if (window.lockModalBodyScroll) {
            window.lockModalBodyScroll();
        }

        function onKey(e) {
            if (e.key === 'Escape') {
                closePeopleModal(wrap);
                document.removeEventListener('keydown', onKey);
            }
        }
        document.addEventListener('keydown', onKey);

        wrap.addEventListener('click', function (e) {
            if (e.target.closest('[data-people-checks-close]')) {
                e.preventDefault();
                closePeopleModal(wrap);
                document.removeEventListener('keydown', onKey);
            }
        });
    }

    function handleClick(btn, apiUrl) {
        var type = btn.getAttribute('data-check-type') || '';
        var title = btn.getAttribute('data-check-title') || '';
        if (!type || !apiUrl) {
            return;
        }
        btn.disabled = true;
        fetch(apiUrl + '?type=' + encodeURIComponent(type), {
            method: 'GET',
            headers: { Accept: 'application/json' },
            credentials: 'same-origin'
        })
            .then(function (res) { return res.json().catch(function () { return null; }); })
            .then(function (json) {
                if (!json || json.ok !== true) {
                    if (window.showAlert) {
                        var msg = (json && json.errors && json.errors._general) ? json.errors._general : 'No s\'ha pogut carregar el detall.';
                        window.showAlert('error', 'Revisions de persones', msg);
                    }
                    return;
                }
                openPeopleModal({
                    title: json.title || title,
                    total: Number(json.total || 0),
                    rows: Array.isArray(json.rows) ? json.rows : []
                });
            })
            .catch(function () {
                if (window.showAlert) {
                    window.showAlert('error', 'Revisions de persones', 'No s\'ha pogut carregar el detall.');
                }
            })
            .finally(function () {
                btn.disabled = false;
            });
    }

    function init() {
        var root = document.querySelector('[data-people-checks-root]');
        if (!root) {
            return;
        }
        var apiUrl = root.getAttribute('data-api-url') || '';
        root.addEventListener('click', function (e) {
            var btn = e.target.closest('.dashboard__people-checks-btn');
            if (!btn || !root.contains(btn)) {
                return;
            }
            e.preventDefault();
            handleClick(btn, apiUrl);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

