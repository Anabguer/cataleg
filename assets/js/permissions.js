(function () {
    'use strict';

    function appPermissionsCfg() { return window.APP_PERMISSIONS || {}; }
    function $(sel, root) { return (root || document).querySelector(sel); }
    function $all(sel, root) { return Array.prototype.slice.call((root || document).querySelectorAll(sel)); }

    var isDirty = false;
    var lastEffectiveEdit = false;
    var groupOptions = [];

    function markDirty(saveBtn) {
        if (!isDirty) {
            isDirty = true;
            if (saveBtn) { saveBtn.disabled = !lastEffectiveEdit; }
        }
    }

    function getRowPermInputs(row) {
        return {
            view: row.querySelector('input[type="checkbox"][data-perm="view"]'),
            create: row.querySelector('input[type="checkbox"][data-perm="create"]'),
            edit: row.querySelector('input[type="checkbox"][data-perm="edit"]'),
            del: row.querySelector('input[type="checkbox"][data-perm="delete"]')
        };
    }

    function applyRowRulesFrom(changedInput) {
        if (!changedInput) { return; }
        var row = changedInput.closest('tr');
        if (!row) { return; }
        var inputs = getRowPermInputs(row);
        var perm = changedInput.getAttribute('data-perm');
        var checked = changedInput.checked;
        if (perm === 'view' && !checked) {
            if (inputs.create) { inputs.create.checked = false; }
            if (inputs.edit) { inputs.edit.checked = false; }
            if (inputs.del) { inputs.del.checked = false; }
        } else if (perm !== 'view' && checked && inputs.view) {
            inputs.view.checked = true;
        }
    }

    function collectPermissions(formEl) {
        var rows = $all('.permissions-table tbody tr', formEl);
        var out = [];
        rows.forEach(function (row) {
            var formId = row.dataset.formId ? parseInt(row.dataset.formId, 10) || 0 : 0;
            if (!formId) { return; }
            var i = getRowPermInputs(row);
            var p = {
                form_id: formId,
                can_view: i.view && i.view.checked ? 1 : 0,
                can_create: i.create && i.create.checked ? 1 : 0,
                can_edit: i.edit && i.edit.checked ? 1 : 0,
                can_delete: i.del && i.del.checked ? 1 : 0
            };
            if (!p.can_view) {
                p.can_create = 0;
                p.can_edit = 0;
                p.can_delete = 0;
            }
            out.push(p);
        });
        return out;
    }

    function collectFormsConfig(formEl) {
        var rows = $all('.permissions-table tbody tr', formEl);
        var out = [];
        rows.forEach(function (row) {
            var formId = row.dataset.formId ? parseInt(row.dataset.formId, 10) || 0 : 0;
            if (!formId) { return; }
            var groupSelect = row.querySelector('select[data-field="form_group"]');
            var orderInput = row.querySelector('input[data-field="group_sort_order"]');
            var groupSortOrder = orderInput ? parseInt(orderInput.value, 10) || 0 : 0;
            if (groupSortOrder < 0) { groupSortOrder = 0; }
            out.push({
                form_id: formId,
                form_group: groupSelect ? String(groupSelect.value || '') : '',
                group_sort_order: groupSortOrder
            });
        });
        return out;
    }

    function apiCall(payload, method, query) {
        var cfg = appPermissionsCfg();
        var url = cfg.apiUrl || 'permissions_api.php';
        if (query) { url += '?' + query; }
        var opts = {
            method: method || 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json' }
        };
        if (payload) { opts.body = JSON.stringify(payload); }
        return fetch(url, opts).then(function (r) {
            return r.text().then(function (text) {
                var body;
                try { body = text ? JSON.parse(text) : {}; } catch (e) { body = { success: false }; }
                return body;
            });
        });
    }

    function esc(v) {
        return String(v || '').replace(/[&<>"']/g, function (m) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
        });
    }

    function renderGroupSelect(current) {
        var html = '<select class="form-select form-select--sm permissions-group-select" data-field="form_group">';
        (groupOptions || []).forEach(function (opt) {
            var selected = String(opt.value) === String(current) ? ' selected' : '';
            html += '<option value="' + esc(opt.value) + '"' + selected + '>' + esc(opt.label) + '</option>';
        });
        html += '</select>';
        return html;
    }

    function renderGroups(groupsRoot, groups) {
        groupsRoot.innerHTML = '';
        if (!groups || !groups.length) {
            groupsRoot.innerHTML = '<p class="muted">No hi ha formularis configurats.</p>';
            return;
        }
        groups.forEach(function (group) {
            var box = document.createElement('div');
            box.className = 'permissions-group';
            box.innerHTML = '<h4 class="permissions-group__title">' + esc(group.label) + '</h4>' +
                '<div class="permissions-table-scroll"><table class="data-table permissions-table">' +
                '<colgroup>' +
                '<col class="permissions-col permissions-col--screen">' +
                '<col class="permissions-col permissions-col--group">' +
                '<col class="permissions-col permissions-col--order">' +
                '<col class="permissions-col permissions-col--perm">' +
                '<col class="permissions-col permissions-col--perm">' +
                '<col class="permissions-col permissions-col--perm">' +
                '<col class="permissions-col permissions-col--perm">' +
                '<col class="permissions-col permissions-col--quick">' +
                '</colgroup>' +
                '<thead><tr><th>Pantalla</th><th>Grup</th><th>Ordre</th><th>Veure</th><th>Crear</th><th>Editar</th><th>Esborrar</th><th>Accio rapida</th></tr></thead><tbody></tbody></table></div>';
            var tbody = $('tbody', box);
            (group.items || []).forEach(function (f) {
                var p = f.permissions || {};
                var tr = document.createElement('tr');
                tr.setAttribute('data-form-id', String(f.id));
                tr.setAttribute('data-form-code', String(f.code || ''));
                tr.innerHTML = '<th scope="row"><div class="permissions-form-name"><span>' + esc(f.name) + '</span><span class="permissions-form-code">' + esc(f.code) + '</span></div></th>' +
                    '<td class="permissions-cell">' + renderGroupSelect(f.form_group || '') + '</td>' +
                    '<td class="permissions-cell permissions-cell--order"><input type="number" min="0" step="1" class="form-input form-input--sm permissions-order-input" data-field="group_sort_order" value="' + esc(f.group_sort_order || 0) + '"></td>' +
                    '<td class="permissions-cell permissions-cell--perm"><label class="permissions-switch"><input type="checkbox" data-perm="view"' + (p.can_view ? ' checked' : '') + '><span class="permissions-switch__slider"></span></label></td>' +
                    '<td class="permissions-cell permissions-cell--perm"><label class="permissions-switch"><input type="checkbox" data-perm="create"' + (p.can_create ? ' checked' : '') + '><span class="permissions-switch__slider"></span></label></td>' +
                    '<td class="permissions-cell permissions-cell--perm"><label class="permissions-switch"><input type="checkbox" data-perm="edit"' + (p.can_edit ? ' checked' : '') + '><span class="permissions-switch__slider"></span></label></td>' +
                    '<td class="permissions-cell permissions-cell--perm"><label class="permissions-switch"><input type="checkbox" data-perm="delete"' + (p.can_delete ? ' checked' : '') + '><span class="permissions-switch__slider"></span></label></td>' +
                    '<td class="permissions-cell permissions-cell--quick"><button type="button" class="btn btn--ghost btn--sm permissions-row-toggle">Tot/Nada</button></td>';
                tbody.appendChild(tr);
            });
            groupsRoot.appendChild(box);
        });
    }

    function renderRoleUsers(root, users, canEdit, rolesOverview, currentRoleId) {
        root.innerHTML = '';
        if (!users || !users.length) {
            root.innerHTML = '<p class="muted">No hi ha usuaris assignats a aquest rol.</p>';
            return;
        }
        var roleOptions = (rolesOverview || []).filter(function (r) {
            return parseInt(r.id, 10) !== parseInt(currentRoleId, 10);
        }).map(function (r) {
            return '<option value="' + esc(r.id) + '">' + esc(r.name) + '</option>';
        }).join('');
        users.forEach(function (u) {
            var card = document.createElement('article');
            card.className = 'permissions-user-card';
            card.setAttribute('data-user-id', String(u.id));
            card.innerHTML = '<div><h4>' + esc(u.full_name) + '</h4><p class="muted">' + esc(u.email || u.username) + '</p>' +
                '<span class="badge ' + (u.is_active ? 'badge--success' : 'badge--neutral') + '">' + (u.is_active ? 'Actiu' : 'Inactiu') + '</span></div>' +
                (canEdit ? '<div class="permissions-user-actions">' +
                '<button type="button" class="btn btn--ghost btn--sm permissions-change-user-role" data-user-id="' + String(u.id) + '" data-user-name="' + esc(u.full_name) + '">Canviar rol</button>' +
                '<button type="button" class="btn btn--ghost btn--sm permissions-remove-user" data-user-id="' + String(u.id) + '" data-user-name="' + esc(u.full_name) + '">Quitar</button>' +
                '</div><div class="permissions-user-change-box" data-user-change-box="' + String(u.id) + '" hidden>' +
                '<label class="form-label" for="permissions-change-role-' + String(u.id) + '">Nou rol</label>' +
                '<select class="form-select permissions-change-role-select" id="permissions-change-role-' + String(u.id) + '" data-user-id="' + String(u.id) + '">' + roleOptions + '</select>' +
                '<button type="button" class="btn btn--primary btn--sm permissions-apply-role-change" data-user-id="' + String(u.id) + '" data-user-name="' + esc(u.full_name) + '">Aplicar canvi</button>' +
                '</div>' : '');
            root.appendChild(card);
        });
    }

    function renderUsersPool(select, users) {
        if (!select) { return; }
        select.innerHTML = '';
        (users || []).forEach(function (u) {
            var op = document.createElement('option');
            op.value = String(u.id);
            op.textContent = (u.full_name || '') + ' (' + (u.username || '') + ')';
            op.setAttribute('data-current-role-id', String(u.current_role_id || 0));
            op.setAttribute('data-current-role-name', String(u.current_role_name || ''));
            select.appendChild(op);
        });
    }

    function markRoleActive(roleId) {
        $all('.permissions-role-card').forEach(function (card) {
            var id = parseInt(card.getAttribute('data-role-id') || '0', 10) || 0;
            card.classList.toggle('is-active', id === roleId);
        });
    }

    function loadRole(roleId) {
        var groupsRoot = $('#permissions-groups-root');
        var usersRoot = $('#permissions-user-list');
        var roleTitle = $('#permissions-role-title');
        var roleDescription = $('#permissions-role-description');
        var roleInput = $('#permissions-role-id');
        var usersSel = $('#permissions-user-select');
        var saveBtn = $('#permissions-save-btn');

        apiCall(null, 'GET', 'action=get&role_id=' + encodeURIComponent(String(roleId))).then(function (res) {
            if (!res || !res.success) {
                if (typeof window.showAlert === 'function') { window.showAlert('error', 'Error', (res && res.errors && res.errors._general) || 'No s\'han pogut carregar dades.'); }
                return;
            }
            var cfg2 = appPermissionsCfg();
            var canEdit2 = !!cfg2.canEdit;
            var adm = cfg2.adminRoleSlug || 'admin';
            var actorAdm = !!cfg2.actorIsSystemAdmin;
            var isAdmTgt = res.role && String(res.role.slug || '') === adm;
            var effectiveEdit = canEdit2 && (!isAdmTgt || actorAdm);
            lastEffectiveEdit = effectiveEdit;
            roleInput.value = String(roleId);
            roleTitle.textContent = (res.role && res.role.name) || 'Rol';
            roleDescription.textContent = (res.role && res.role.description) || '';
            groupOptions = res.group_options || [];
            renderGroups(groupsRoot, res.groups || []);
            renderRoleUsers(usersRoot, res.role_users || [], effectiveEdit, res.roles_overview || [], roleId);
            renderUsersPool(usersSel, res.users_pool || []);
            markRoleActive(roleId);
            isDirty = false;
            if (saveBtn) { saveBtn.disabled = true; }
            if (!effectiveEdit) {
                $all('select[data-field="form_group"], input[data-field="group_sort_order"], input[data-perm]', groupsRoot).forEach(function (el) {
                    el.disabled = true;
                });
                $all('.permissions-row-toggle', groupsRoot).forEach(function (btn) {
                    btn.disabled = true;
                });
            } else {
                $all('select[data-field="form_group"], input[data-field="group_sort_order"], input[data-perm]', groupsRoot).forEach(function (el) {
                    el.disabled = false;
                });
                $all('.permissions-row-toggle', groupsRoot).forEach(function (btn) {
                    btn.disabled = false;
                });
            }
            var assignBox = $('#permissions-assign-box');
            if (assignBox) {
                assignBox.hidden = !effectiveEdit;
            }
        }).catch(function () {
            if (typeof window.showAlert === 'function') { window.showAlert('error', 'Error', 'Error de xarxa carregant el rol.'); }
        });
    }

    function handleSave() {
        if (!lastEffectiveEdit) { return; }
        var form = $('#permissions-matrix-form');
        if (!form || !isDirty) { return; }
        var roleId = parseInt(($('#permissions-role-id') || {}).value || '0', 10) || 0;
        var saveBtn = $('#permissions-save-btn');
        if (saveBtn) { saveBtn.disabled = true; }
        var payload = {
            action: 'save',
            role_id: roleId,
            permissions: collectPermissions(form),
            forms_config: collectFormsConfig(form)
        };
        if (!payload.permissions.length) {
            if (saveBtn) { saveBtn.disabled = !(lastEffectiveEdit && isDirty); }
            return;
        }
        apiCall(payload, 'POST').then(function (res) {
            if (res && res.success) {
                isDirty = false;
                if (saveBtn) { saveBtn.disabled = true; }
                if (typeof window.showAlert === 'function') { window.showAlert('success', 'Permisos desats', 'Els permisos s\'han actualitzat correctament.'); }
                loadRole(roleId);
            } else {
                if (saveBtn) { saveBtn.disabled = !(lastEffectiveEdit && isDirty); }
                if (typeof window.showAlert === 'function') { window.showAlert('error', 'Error', (res && res.errors && res.errors._general) || 'Error guardant permisos.'); }
            }
        }).catch(function () {
            if (saveBtn) { saveBtn.disabled = !(lastEffectiveEdit && isDirty); }
            if (typeof window.showAlert === 'function') { window.showAlert('error', 'Error', 'Error de xarxa en desar els permisos.'); }
        });
    }

    function handleAssign() {
        if (!lastEffectiveEdit) { return; }
        var roleId = parseInt(($('#permissions-role-id') || {}).value || '0', 10) || 0;
        var sel = $('#permissions-user-select');
        if (!sel || !sel.value || !roleId) { return; }
        var btn = $('#permissions-assign-btn');
        var userId = parseInt(sel.value, 10) || 0;
        var op = sel.options[sel.selectedIndex];
        var currRoleId = parseInt(op.getAttribute('data-current-role-id') || '0', 10) || 0;
        var currRoleName = op.getAttribute('data-current-role-name') || '';
        if (btn) { btn.disabled = true; }
        if (currRoleId === roleId) {
            if (typeof window.showAlert === 'function') { window.showAlert('info', 'Sense canvis', 'Aquest usuari ja pertany al rol seleccionat.'); }
            if (btn) { btn.disabled = false; }
            return;
        }
        var doAssign = function () {
            apiCall({ action: 'assign_user', role_id: roleId, user_id: userId }, 'POST').then(function (res) {
                if (res && res.success) {
                    if (typeof window.showAlert === 'function') { window.showAlert('success', 'Usuari assignat', 'Assignacio completada.'); }
                    loadRole(roleId);
                } else if (typeof window.showAlert === 'function') {
                    window.showAlert('error', 'Error', (res && res.errors && res.errors._general) || 'No s\'ha pogut assignar.');
                }
                if (btn) { btn.disabled = false; }
            }).catch(function () {
                if (btn) { btn.disabled = false; }
            });
        };
        if (currRoleId > 0 && currRoleId !== roleId && typeof window.showConfirm === 'function') {
            window.showConfirm('Canvi de rol', 'Aquest usuari ja te el rol "' + currRoleName + '". Vols substituir-lo pel rol seleccionat?', doAssign);
        } else {
            doAssign();
        }
    }

    function handleRemove(userId, userName) {
        if (!lastEffectiveEdit) { return; }
        var roleId = parseInt(($('#permissions-role-id') || {}).value || '0', 10) || 0;
        if (!userId || !roleId) { return; }
        var doRemove = function () {
            apiCall({ action: 'remove_user', role_id: roleId, user_id: userId }, 'POST').then(function (res) {
                if (res && res.success) {
                    if (typeof window.showAlert === 'function') { window.showAlert('success', 'Usuari actualitzat', 'L\'usuari ha quedat sense rol assignat.'); }
                    loadRole(roleId);
                } else if (typeof window.showAlert === 'function') {
                    window.showAlert('error', 'Error', (res && res.errors && res.errors._general) || 'No s\'ha pogut quitar el rol.');
                }
            });
        };
        if (typeof window.showConfirm === 'function') {
            window.showConfirm('Quitar usuari del rol', 'Aquest usuari (' + (userName || 'usuari') + ') es quedara sense rol assignat. Continuar?', doRemove);
        } else {
            doRemove();
        }
    }

    function handleChangeRole(userId, userName) {
        if (!lastEffectiveEdit) { return; }
        var roleId = parseInt(($('#permissions-role-id') || {}).value || '0', 10) || 0;
        if (!roleId || !userId) { return; }
        var sel = $('.permissions-change-role-select[data-user-id="' + String(userId) + '"]');
        if (!sel || !sel.value) { return; }
        var newRoleId = parseInt(sel.value, 10) || 0;
        var newRoleName = sel.options[sel.selectedIndex] ? sel.options[sel.selectedIndex].textContent : '';
        if (!newRoleId || newRoleId === roleId) {
            if (typeof window.showAlert === 'function') { window.showAlert('info', 'Sense canvis', 'Selecciona un rol diferent de l\'actual.'); }
            return;
        }
        var doChange = function () {
            apiCall({ action: 'assign_user', role_id: newRoleId, user_id: userId }, 'POST').then(function (res) {
                if (res && res.success) {
                    if (typeof window.showAlert === 'function') { window.showAlert('success', 'Rol actualitzat', 'S\'ha canviat el rol correctament.'); }
                    loadRole(roleId);
                } else if (typeof window.showAlert === 'function') {
                    window.showAlert('error', 'Error', (res && res.errors && res.errors._general) || 'No s\'ha pogut canviar el rol.');
                }
            });
        };
        if (typeof window.showConfirm === 'function') {
            window.showConfirm('Canviar rol d\'usuari', 'Vols canviar el rol de ' + (userName || 'aquest usuari') + ' de l\'actual a "' + newRoleName + '"?', doChange);
        } else {
            doChange();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        var screen = $('#permissions-screen');
        if (!screen) { return; }
        var cfg0 = appPermissionsCfg();
        var canEditEff = cfg0.canEditEffective !== undefined ? !!cfg0.canEditEffective : !!cfg0.canEdit;
        lastEffectiveEdit = canEditEff;
        var saveBtn = $('#permissions-save-btn');
        if (saveBtn) { saveBtn.disabled = !canEditEff; }
        isDirty = false;

        document.body.addEventListener('change', function (e) {
            var input = e.target.closest('input[type="checkbox"][data-perm]');
            if (!input) { return; }
            if (!lastEffectiveEdit) { return; }
            applyRowRulesFrom(input);
            var row = input.closest('tr');
            if (row) { row.classList.add('is-modified'); }
            markDirty(saveBtn);
        });

        document.body.addEventListener('input', function (e) {
            var orderInput = e.target.closest('input[data-field="group_sort_order"]');
            if (!orderInput) { return; }
            if (!lastEffectiveEdit) { return; }
            var rowOrder = orderInput.closest('tr');
            if (rowOrder) { rowOrder.classList.add('is-modified'); }
            markDirty(saveBtn);
        });

        document.body.addEventListener('change', function (e) {
            var groupSelect = e.target.closest('select[data-field="form_group"]');
            if (!groupSelect) { return; }
            if (!lastEffectiveEdit) { return; }
            var rowGroup = groupSelect.closest('tr');
            if (rowGroup) { rowGroup.classList.add('is-modified'); }
            markDirty(saveBtn);
        });

        document.body.addEventListener('click', function (e) {
            var roleCard = e.target.closest('.permissions-role-card');
            if (roleCard) {
                var rid = parseInt(roleCard.getAttribute('data-role-id') || '0', 10) || 0;
                if (rid) { loadRole(rid); }
                return;
            }
            var quick = e.target.closest('.permissions-row-toggle');
            if (quick) {
                if (!lastEffectiveEdit) { return; }
                var row = quick.closest('tr');
                if (!row) { return; }
                var i = getRowPermInputs(row);
                var allOn = !!(i.view && i.view.checked && i.create && i.create.checked && i.edit && i.edit.checked && i.del && i.del.checked);
                if (i.view) { i.view.checked = !allOn; }
                if (i.create) { i.create.checked = !allOn; }
                if (i.edit) { i.edit.checked = !allOn; }
                if (i.del) { i.del.checked = !allOn; }
                applyRowRulesFrom(i.view || i.create || i.edit || i.del);
                row.classList.add('is-modified');
                markDirty(saveBtn);
                return;
            }
            if (e.target.closest('#permissions-save-btn')) {
                e.preventDefault();
                handleSave();
                return;
            }
            if (e.target.closest('#permissions-assign-btn')) {
                handleAssign();
                return;
            }
            var openChange = e.target.closest('.permissions-change-user-role');
            if (openChange) {
                var uidOpen = parseInt(openChange.getAttribute('data-user-id') || '0', 10) || 0;
                var box = $('[data-user-change-box="' + String(uidOpen) + '"]');
                if (box) { box.hidden = !box.hidden; }
                return;
            }
            var applyChange = e.target.closest('.permissions-apply-role-change');
            if (applyChange) {
                handleChangeRole(
                    parseInt(applyChange.getAttribute('data-user-id') || '0', 10) || 0,
                    applyChange.getAttribute('data-user-name') || ''
                );
                return;
            }
            var rem = e.target.closest('.permissions-remove-user');
            if (rem) {
                handleRemove(
                    parseInt(rem.getAttribute('data-user-id') || '0', 10) || 0,
                    rem.getAttribute('data-user-name') || ''
                );
            }
        });

        var userSelect = $('#permissions-user-select');
        var assignBtn = $('#permissions-assign-btn');
        if (userSelect && assignBtn) {
            assignBtn.disabled = !userSelect.value;
            userSelect.addEventListener('change', function () {
                assignBtn.disabled = !userSelect.value;
            });
        }
    });
})();
