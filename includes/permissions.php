<?php
declare(strict_types=1);

/**
 * Carga permisos del rol del usuario en sesión (clave: form code).
 *
 * @return array<string, array{can_view:bool,can_create:bool,can_edit:bool,can_delete:bool}>
 */
function permissions_load_for_session(): array
{
    if (!auth_is_logged_in()) {
        return [];
    }

    $roleId = (int) ($_SESSION['role_id'] ?? 0);
    if ($roleId < 1) {
        $_SESSION['permissions'] = [];
        return [];
    }

    $sql = 'SELECT f.code,
                   rp.can_view, rp.can_create, rp.can_edit, rp.can_delete
            FROM role_permissions rp
            INNER JOIN forms f ON f.id = rp.form_id
            WHERE rp.role_id = :rid';
    $st = db()->prepare($sql);
    $st->execute(['rid' => $roleId]);

    $map = [];
    while ($row = $st->fetch()) {
        $code = (string) $row['code'];
        $map[$code] = [
            'can_view'   => (bool) $row['can_view'],
            'can_create' => (bool) $row['can_create'],
            'can_edit'   => (bool) $row['can_edit'],
            'can_delete' => (bool) $row['can_delete'],
        ];
    }

    $_SESSION['permissions'] = $map;
    return $map;
}

function permissions_invalidate_cache(): void
{
    unset($_SESSION['permissions']);
}

function permissions_get(string $formCode): ?array
{
    $map = permissions_load_for_session();
    return $map[$formCode] ?? null;
}

function can_view_form(string $formCode): bool
{
    $p = permissions_get($formCode);
    return $p !== null && $p['can_view'];
}

function can_create_form(string $formCode): bool
{
    $p = permissions_get($formCode);
    return $p !== null && $p['can_create'];
}

function can_edit_form(string $formCode): bool
{
    $p = permissions_get($formCode);
    return $p !== null && $p['can_edit'];
}

function can_delete_form(string $formCode): bool
{
    $p = permissions_get($formCode);
    return $p !== null && $p['can_delete'];
}

/**
 * Deniega acceso si no puede ver el formulario (redirige al dashboard).
 */
function require_can_view(string $formCode): void
{
    auth_require_login();
    if (!can_view_form($formCode)) {
        redirect(app_url('dashboard.php?denied=1'));
    }
}

function require_can_create(string $formCode): void
{
    require_can_view($formCode);
    if (!can_create_form($formCode)) {
        redirect(app_url('dashboard.php?denied=1'));
    }
}

function require_can_edit(string $formCode): void
{
    require_can_view($formCode);
    if (!can_edit_form($formCode)) {
        redirect(app_url('dashboard.php?denied=1'));
    }
}

function require_can_delete(string $formCode): void
{
    require_can_view($formCode);
    if (!can_delete_form($formCode)) {
        redirect(app_url('dashboard.php?denied=1'));
    }
}

/**
 * Formularis visibles al menú (can_view), ordenats.
 *
 * @return array<int, array{code:string,name:string,route:string,form_group:string,group_sort_order:int,sort_order:int}>
 */
function menu_visible_forms(): array
{
    if (!auth_is_logged_in()) {
        return [];
    }
    $roleId = (int) ($_SESSION['role_id'] ?? 0);
    if ($roleId < 1) {
        return [];
    }

    $sql = 'SELECT f.code, f.name, f.route, f.form_group, f.group_sort_order, f.sort_order
            FROM forms f
            INNER JOIN role_permissions rp ON rp.form_id = f.id AND rp.role_id = :rid
            WHERE rp.can_view = 1
            ORDER BY CASE f.form_group
                        WHEN \'system\' THEN 1
                        WHEN \'organization\' THEN 2
                        WHEN \'training_maintenance\' THEN 3
                        WHEN \'social_security_companies\' THEN 4
                        WHEN \'training_management\' THEN 5
                        ELSE 9
                     END ASC,
                     f.group_sort_order ASC,
                     f.sort_order ASC,
                     f.name ASC';
    $st = db()->prepare($sql);
    $st->execute(['rid' => $roleId]);
    return $st->fetchAll() ?: [];
}
