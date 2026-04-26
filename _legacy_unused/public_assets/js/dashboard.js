/**
 * Calendari anual del tauler: dades des de dashboard_calendar_api.php;
 * filtres i «només primera data» al frontend.
 */
(function () {
    'use strict';

    var MONTHS_CA = [
        'gener',
        'febrer',
        'març',
        'abril',
        'maig',
        'juny',
        'juliol',
        'agost',
        'setembre',
        'octubre',
        'novembre',
        'desembre',
    ];
    var DOW_CA = ['dl', 'dt', 'dc', 'dj', 'dv', 'ds', 'dg'];

    function pad2(n) {
        return n < 10 ? '0' + n : String(n);
    }

    function isoDate(y, m1, d) {
        return y + '-' + pad2(m1) + '-' + pad2(d);
    }

    function formatDayTitle(iso) {
        var p = iso.split('-');
        if (p.length !== 3) {
            return iso;
        }
        var y = parseInt(p[0], 10);
        var m = parseInt(p[1], 10);
        var d = parseInt(p[2], 10);
        return d + ' de ' + MONTHS_CA[m - 1] + ' de ' + y;
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    function cfg() {
        return window.APP_DASHBOARD || {};
    }

    function trainingActionOpenHref(taId) {
        var base = (cfg().trainingActionsUrl || '').trim();
        if (!base || !(taId > 0)) {
            return '';
        }
        var sep = base.indexOf('?') >= 0 ? '&' : '?';
        return base + sep + 'ta_edit=' + encodeURIComponent(String(taId));
    }

    function setFooterYear(y) {
        var el = document.getElementById('dashboard-footer-year');
        if (el) {
            el.textContent = String(y);
        }
    }

    function updateYearInput(el, y) {
        if (el) {
            el.value = String(y);
        }
    }

    /**
     * Mateixa lògica que training_actions_calendar_day_kind (PHP).
     * @param {Array<{execution_status?: string}>} actions
     */
    function computeDayKind(actions) {
        if (!actions || actions.length === 0) {
            return 'planned';
        }
        var i;
        var allCancelled = true;
        for (i = 0; i < actions.length; i++) {
            if ((actions[i].execution_status || '') !== 'Cancel·lada') {
                allCancelled = false;
                break;
            }
        }
        if (allCancelled) {
            return 'cancelled';
        }
        var allRealized = true;
        for (i = 0; i < actions.length; i++) {
            if ((actions[i].execution_status || '') !== 'Realitzada') {
                allRealized = false;
                break;
            }
        }
        if (allRealized) {
            return 'realized';
        }
        var hasR = false;
        var hasNonR = false;
        for (i = 0; i < actions.length; i++) {
            if ((actions[i].execution_status || '') === 'Realitzada') {
                hasR = true;
            } else {
                hasNonR = true;
            }
        }
        if (hasR && hasNonR) {
            return 'mixed';
        }
        var hasC = false;
        var hasNonC = false;
        for (i = 0; i < actions.length; i++) {
            if ((actions[i].execution_status || '') === 'Cancel·lada') {
                hasC = true;
            } else {
                hasNonC = true;
            }
        }
        if (hasC && hasNonC) {
            return 'mixed';
        }
        return 'planned';
    }

    function filterByStatus(actions, state) {
        if (state === 'all') {
            return actions.slice();
        }
        return actions.filter(function (a) {
            var es = a.execution_status || '';
            if (state === 'previstas') {
                return es !== 'Realitzada' && es !== 'Cancel·lada';
            }
            if (state === 'realizadas') {
                return es === 'Realitzada';
            }
            if (state === 'canceladas') {
                return es === 'Cancel·lada';
            }
            return true;
        });
    }

    function minDateIso(dates) {
        if (!dates || !dates.length) {
            return null;
        }
        return dates.slice().sort()[0];
    }

    /**
     * Mapa de dates per pintar el calendari (i accions per a la modal).
     * @param {Record<string, {actions?: Array}>} rawDates
     */
    function buildVisibleDatesMap(rawDates, filterState, firstDateOnly) {
        var out = {};
        var iso;
        for (iso in rawDates) {
            if (!Object.prototype.hasOwnProperty.call(rawDates, iso)) {
                continue;
            }
            var entry = rawDates[iso];
            var actions = entry.actions || [];
            var forModal = filterByStatus(actions, filterState);
            var forCal = forModal.slice();
            if (firstDateOnly) {
                forCal = forCal.filter(function (a) {
                    return minDateIso(a.all_dates) === iso;
                });
            }
            if (forCal.length === 0) {
                continue;
            }
            out[iso] = {
                day_kind: computeDayKind(forCal),
                actions: forModal,
            };
        }
        return out;
    }

    function openDayModal(dateIso, dayData) {
        var root = document.getElementById('modal-root');
        if (!root) {
            return;
        }
        var existing = root.querySelector('.dashboard-day-modal');
        if (existing) {
            existing.remove();
        }

        var wrap = document.createElement('div');
        wrap.className = 'modal modal--form-overlay dashboard-day-modal is-visible';
        wrap.setAttribute('role', 'dialog');
        wrap.setAttribute('aria-modal', 'true');
        wrap.setAttribute('aria-labelledby', 'dashboard-day-modal-title');

        var actions = (dayData && dayData.actions) || [];

        var html =
            '<div class="modal__backdrop" data-dashboard-day-close tabindex="-1"></div>' +
            '<div class="modal__dialog modal__dialog--md dashboard-day-modal__dialog">' +
            '<div class="dashboard-day-modal__header">' +
            '<h2 class="dashboard-day-modal__title" id="dashboard-day-modal-title">' +
            esc(formatDayTitle(dateIso)) +
            '</h2>' +
            '<button type="button" class="btn btn--secondary btn--sm dashboard-day-modal__close" data-dashboard-day-close>Tancar</button>' +
            '</div>' +
            '<div class="dashboard-day-modal__body">';

        for (var i = 0; i < actions.length; i++) {
            var a = actions[i];
            var trainers = a.trainers_text ? '<p class="dashboard-day-modal__meta">Formador/s: ' + esc(a.trainers_text) + '</p>' : '';
            var loc = a.location_name ? '<p class="dashboard-day-modal__meta">Lloc: ' + esc(a.location_name) + '</p>' : '';
            var dates = Array.isArray(a.all_dates) ? a.all_dates.slice().sort() : [];
            var chips = '';
            for (var j = 0; j < dates.length; j++) {
                chips += '<li class="dashboard-day-modal__date-chip">' + esc(formatDayTitle(dates[j])) + '</li>';
            }
            var taId = parseInt(String(a.training_action_id != null ? a.training_action_id : a.id || ''), 10);
            var openHref = trainingActionOpenHref(taId);
            var openBtn =
                openHref !== ''
                    ? '<div class="dashboard-day-modal__action-footer">' +
                      '<a class="btn btn--outline btn--sm" href="' +
                      esc(openHref) +
                      '">Obrir acció</a>' +
                      '</div>'
                    : '';
            html +=
                '<article class="dashboard-day-modal__action">' +
                '<div><span class="dashboard-day-modal__code">' +
                esc(a.display_code || '') +
                '</span></div>' +
                '<p class="dashboard-day-modal__name">' +
                esc(a.name || '') +
                '</p>' +
                '<p class="dashboard-day-modal__meta">Estat: ' +
                esc(a.execution_status || '') +
                '</p>' +
                trainers +
                loc +
                '<span class="dashboard-day-modal__dates-label">Dates de l’acció</span>' +
                '<ul class="dashboard-day-modal__dates-list">' +
                chips +
                '</ul>' +
                openBtn +
                '</article>';
        }

        html += '</div></div>';
        wrap.innerHTML = html;
        root.appendChild(wrap);
        root.classList.add('is-open');
        root.setAttribute('aria-hidden', 'false');
        if (window.lockModalBodyScroll) {
            window.lockModalBodyScroll();
        }

        function close() {
            wrap.remove();
            if (!root.querySelector('.dashboard-day-modal') && !root.querySelector('.js-app-modal:not([hidden])')) {
                root.classList.remove('is-open');
                root.setAttribute('aria-hidden', 'true');
            }
            if (window.unlockModalBodyScroll) {
                window.unlockModalBodyScroll();
            }
            document.removeEventListener('keydown', onKey);
        }

        function onKey(e) {
            if (e.key === 'Escape') {
                close();
            }
        }

        wrap.addEventListener('click', function (e) {
            if (e.target.closest('[data-dashboard-day-close]')) {
                e.preventDefault();
                close();
            }
        });
        document.addEventListener('keydown', onKey);

        var btn = wrap.querySelector('.dashboard-day-modal__close');
        if (btn) {
            btn.focus();
        }
    }

    function renderMonth(year, month0, datesMap) {
        var first = new Date(year, month0, 1);
        var offset = (first.getDay() + 6) % 7;
        var dim = new Date(year, month0 + 1, 0).getDate();
        var today = new Date();
        var isThisMonth = today.getFullYear() === year && today.getMonth() === month0;

        var el = document.createElement('section');
        el.className = 'dashboard__month';
        el.setAttribute('aria-label', MONTHS_CA[month0] + ' ' + year);

        var h = document.createElement('h3');
        h.className = 'dashboard__month-title';
        h.textContent = MONTHS_CA[month0];
        el.appendChild(h);

        var dow = document.createElement('div');
        dow.className = 'dashboard__dow';
        for (var w = 0; w < 7; w++) {
            var c = document.createElement('span');
            c.textContent = DOW_CA[w];
            dow.appendChild(c);
        }
        el.appendChild(dow);

        var grid = document.createElement('div');
        grid.className = 'dashboard__days';

        var i;
        var cell;
        for (i = 0; i < offset; i++) {
            cell = document.createElement('div');
            cell.className = 'dashboard__day dashboard__day--muted';
            cell.setAttribute('aria-hidden', 'true');
            grid.appendChild(cell);
        }

        for (var d = 1; d <= dim; d++) {
            var iso = isoDate(year, month0 + 1, d);
            var entry = datesMap[iso];
            cell = document.createElement('button');
            cell.type = 'button';
            cell.className = 'dashboard__day';
            cell.textContent = String(d);
            cell.setAttribute('data-date', iso);

            if (isThisMonth && today.getDate() === d) {
                cell.classList.add('dashboard__day--today');
            }

            if (entry) {
                var dk = entry.day_kind || 'planned';
                if (dk === 'realized') {
                    cell.classList.add('dashboard__day--realized');
                } else if (dk === 'mixed') {
                    cell.classList.add('dashboard__day--mixed');
                } else if (dk === 'cancelled') {
                    cell.classList.add('dashboard__day--cancelled');
                } else {
                    cell.classList.add('dashboard__day--planned');
                }
                cell.setAttribute('aria-label', formatDayTitle(iso) + ', accions formatives');
                cell.addEventListener(
                    'click',
                    (function (isoInner, dataInner) {
                        return function () {
                            openDayModal(isoInner, dataInner);
                        };
                    })(iso, entry)
                );
            }

            grid.appendChild(cell);
        }

        el.appendChild(grid);
        return el;
    }

    function renderCalendar(container, year, datesMap) {
        container.innerHTML = '';
        var map = datesMap || {};

        for (var m = 0; m < 12; m++) {
            container.appendChild(renderMonth(year, m, map));
        }
    }

    function parseYear(v) {
        var n = parseInt(String(v), 10);
        if (isNaN(n)) {
            return null;
        }
        return n;
    }

    function init() {
        var grid = document.getElementById('dashboard-year-calendar');
        if (!grid) {
            return;
        }

        var c = cfg();
        var apiUrl = c.apiUrl || '';
        var loading = document.getElementById('dashboard-calendar-loading');
        var hint = document.getElementById('dashboard-restricted-hint');
        var yearInput = document.getElementById('dashboard-year-input');
        var prevBtn = document.querySelector('[data-dashboard-year-prev]');
        var nextBtn = document.querySelector('[data-dashboard-year-next]');
        var filtersEl = document.getElementById('dashboard-filters');
        var filterStatus = document.getElementById('dashboard-filter-status');
        var firstDateOnly = document.getElementById('dashboard-first-date-only');

        var initial = parseInt(grid.getAttribute('data-initial-year') || '', 10);
        if (isNaN(initial) || initial < 1990) {
            initial = new Date().getFullYear();
        }
        var currentYear = initial;

        /** @type {{year:number,dates:Record<string, unknown>}|null} */
        var rawCalendarData = null;

        function showLoading(show) {
            if (loading) {
                loading.hidden = !show;
            }
        }

        function getFilterState() {
            return (filterStatus && filterStatus.value) || 'all';
        }

        function getFirstDateOnly() {
            return !!(firstDateOnly && firstDateOnly.checked);
        }

        function applyFiltersAndRender() {
            if (!rawCalendarData || !rawCalendarData.dates) {
                renderCalendar(grid, currentYear, {});
                return;
            }
            var visible = buildVisibleDatesMap(rawCalendarData.dates, getFilterState(), getFirstDateOnly());
            renderCalendar(grid, currentYear, visible);
        }

        function loadYear(y) {
            if (y < 1990 || y > 2100) {
                if (window.showAlert) {
                    window.showAlert('warning', 'Any no vàlid', 'Introduïu un any entre 1990 i 2100.');
                }
                updateYearInput(yearInput, currentYear);
                return;
            }
            currentYear = y;
            updateYearInput(yearInput, y);
            setFooterYear(y);

            if (!apiUrl) {
                return;
            }

            showLoading(true);
            var url = apiUrl + (apiUrl.indexOf('?') >= 0 ? '&' : '?') + 'year=' + encodeURIComponent(String(y));

            fetch(url, { credentials: 'same-origin' })
                .then(function (r) {
                    return r.json();
                })
                .then(function (data) {
                    showLoading(false);
                    if (!data || !data.ok) {
                        var err = (data && data.errors && data.errors._general) || 'No s’ha pogut carregar el calendari.';
                        if (window.showAlert) {
                            window.showAlert('error', 'Error', err);
                        }
                        rawCalendarData = null;
                        if (filtersEl) {
                            filtersEl.setAttribute('hidden', 'hidden');
                        }
                        return;
                    }
                    var cal = data.calendar || { year: y, dates: {} };
                    if (hint) {
                        if (data.restricted) {
                            hint.removeAttribute('hidden');
                        } else {
                            hint.setAttribute('hidden', 'hidden');
                        }
                    }
                    if (filtersEl) {
                        if (data.restricted) {
                            filtersEl.setAttribute('hidden', 'hidden');
                        } else {
                            filtersEl.removeAttribute('hidden');
                        }
                    }
                    rawCalendarData = cal;
                    applyFiltersAndRender();
                })
                .catch(function () {
                    showLoading(false);
                    rawCalendarData = null;
                    if (filtersEl) {
                        filtersEl.setAttribute('hidden', 'hidden');
                    }
                    if (window.showAlert) {
                        window.showAlert('error', 'Error', 'Error de xarxa en carregar el calendari.');
                    }
                });
        }

        if (filterStatus) {
            filterStatus.addEventListener('change', applyFiltersAndRender);
        }
        if (firstDateOnly) {
            firstDateOnly.addEventListener('change', applyFiltersAndRender);
        }

        if (yearInput) {
            yearInput.addEventListener('change', function () {
                var ny = parseYear(yearInput.value);
                if (ny === null) {
                    updateYearInput(yearInput, currentYear);
                    return;
                }
                loadYear(ny);
            });
            yearInput.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    yearInput.blur();
                }
            });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function () {
                loadYear(currentYear - 1);
            });
        }
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                loadYear(currentYear + 1);
            });
        }

        loadYear(currentYear);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
