<?php
declare(strict_types=1);

/**
 * Lògica de dades del mòdul permisos per rol (role_permissions).
 */

/**
 * Slug del rol plantilla «Administrator» (UNIQUE a `roles.slug`; veure sql/02_seed.sql).
 */
function permissions_administrator_role_slug(): string
{
    return 'admin';
}

/**
 * L’usuari actual té el rol sistema amb slug `admin` (mateix criteri que permissions_administrator_role_slug).
 */
function permissions_actor_is_system_administrator(): bool
{
    $slug = auth_user_role_slug();
    return $slug !== null && $slug === permissions_administrator_role_slug();
}

/**
 * El rol amb aquest id és el protegit (slug `admin`).
 */
function permissions_target_role_is_system_administrator(PDO $db, int $roleId): bool
{
    if ($roleId < 1) {
        return false;
    }
    $role = permissions_get_role_by_id($db, $roleId);

    return $role !== null && (string) $role['slug'] === permissions_administrator_role_slug();
}

/**
 * @return list<array{id:int,name:string}>
 */
function permissions_roles_for_select(PDO $db): array
{
    $st = $db->query('SELECT id, name FROM roles ORDER BY name ASC');
    return $st->fetchAll() ?: [];
}

/**
 * @return list<array{id:int,name:string,slug:string,description:?string,users_count:int}>
 */
function permissions_roles_overview(PDO $db): array
{
    $sql = 'SELECT r.id, r.name, r.slug, r.description, COUNT(u.id) AS users_count
            FROM roles r
            LEFT JOIN users u ON u.role_id = r.id
            GROUP BY r.id, r.name, r.slug, r.description
            ORDER BY r.name ASC';
    $st = $db->query($sql);
    $rows = $st->fetchAll() ?: [];
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id' => (int) $r['id'],
            'name' => (string) $r['name'],
            'slug' => (string) $r['slug'],
            'description' => $r['description'] !== null ? (string) $r['description'] : null,
            'users_count' => (int) $r['users_count'],
        ];
    }
    return $out;
}

/**
 * Formularis amb permisos del rol (0/1 si no hi ha fila a role_permissions).
 *
 * @return list<array{
 *   id:int,
 *   code:string,
 *   name:string,
 *   form_group:string,
 *   group_sort_order:int,
 *   permissions:array{can_view:int,can_create:int,can_edit:int,can_delete:int}
 * }>
 */
function permissions_forms_with_role(PDO $db, int $roleId): array
{
    $sql = 'SELECT f.id,
                   f.code,
                   f.name,
                   f.form_group,
                   f.group_sort_order,
                   COALESCE(rp.can_view, 0)   AS can_view,
                   COALESCE(rp.can_create, 0) AS can_create,
                   COALESCE(rp.can_edit, 0)   AS can_edit,
                   COALESCE(rp.can_delete, 0) AS can_delete
            FROM forms f
            LEFT JOIN role_permissions rp
                ON rp.form_id = f.id AND rp.role_id = :rid
            ORDER BY CASE f.form_group
                        WHEN \'system\' THEN 1
                        WHEN \'training_management\' THEN 2
                        WHEN \'salary_tables\' THEN 3
                        WHEN \'organization\' THEN 4
                        WHEN \'training_maintenance\' THEN 5
                        WHEN \'social_security_companies\' THEN 6
                        ELSE 9
                     END ASC,
                     f.group_sort_order ASC,
                     f.sort_order ASC,
                     f.name ASC';
    $st = $db->prepare($sql);
    $st->execute(['rid' => $roleId]);
    $rows = $st->fetchAll() ?: [];

    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id' => (int) $r['id'],
            'code' => (string) $r['code'],
            'name' => (string) $r['name'],
            'form_group' => (string) $r['form_group'],
            'group_sort_order' => (int) $r['group_sort_order'],
            'permissions' => [
                'can_view' => (int) $r['can_view'],
                'can_create' => (int) $r['can_create'],
                'can_edit' => (int) $r['can_edit'],
                'can_delete' => (int) $r['can_delete'],
            ],
        ];
    }

    return $out;
}

/**
 * Agrupa formularis per àmbit visual segons forms.form_group.
 *
 * @param list<array{
 *   id:int,
 *   code:string,
 *   name:string,
 *   form_group:string,
 *   group_sort_order:int,
 *   permissions:array{can_view:int,can_create:int,can_edit:int,can_delete:int}
 * }> $forms
 * @return list<array{key:string,label:string,items:list<array<string,mixed>>}>
 */
function permissions_group_forms(array $forms): array
{
    $groupDefs = permissions_form_group_definitions();
    $groups = [];
    foreach ($groupDefs as $key => $def) {
        $groups[$key] = ['key' => $key, 'label' => $def['label'], 'items' => []];
    }

    foreach ($forms as $f) {
        $groupKey = permissions_normalize_form_group((string) ($f['form_group'] ?? ''));
        if (!isset($groups[$groupKey])) {
            $groups[$groupKey] = ['key' => $groupKey, 'label' => ucfirst(str_replace('_', ' ', $groupKey)), 'items' => []];
        }
        $groups[$groupKey]['items'][] = $f;
    }

    return array_values(array_filter($groups, static function ($g) {
        return $g['items'] !== [];
    }));
}

/**
 * @return array<string,array{label:string,nav:string}>
 */
function permissions_form_group_definitions(): array
{
    return [
        'system' => ['label' => 'Sistema', 'nav' => 'Seguretat'],
        'salary_tables' => ['label' => 'Taules Retribució', 'nav' => 'Taules Retribució'],
        'organization' => ['label' => 'Organització', 'nav' => 'Manteniments'],
        'training_maintenance' => ['label' => 'Manteniment', 'nav' => 'Manteniments'],
        'social_security_companies' => ['label' => 'Empreses i Seguretat Social', 'nav' => 'Manteniments'],
        'training_management' => ['label' => 'Gestió', 'nav' => 'Gestió'],
    ];
}

/**
 * @return list<array{value:string,label:string,nav:string}>
 */
function permissions_form_group_options(): array
{
    $out = [];
    foreach (permissions_form_group_definitions() as $value => $def) {
        $out[] = [
            'value' => $value,
            'label' => $def['label'],
            'nav' => $def['nav'],
        ];
    }
    return $out;
}

function permissions_normalize_form_group(string $group): string
{
    $normalized = trim(strtolower($group));
    $defs = permissions_form_group_definitions();
    if (isset($defs[$normalized])) {
        return $normalized;
    }
    return 'training_maintenance';
}

/**
 * Normalitza role_id escollint un rol vàlid o 0 si no n’hi ha.
 *
 * @param list<array{id:int,name:string}> $roles
 */
function permissions_normalize_role_id(array $roles, int $requestedId): int
{
    if ($roles === []) {
        return 0;
    }
    foreach ($roles as $r) {
        if ((int) $r['id'] === $requestedId) {
            return $requestedId;
        }
    }
    return (int) $roles[0]['id'];
}

/**
 * @return array{id:int,name:string,slug:string,description:?string}|null
 */
function permissions_get_role_by_id(PDO $db, int $roleId): ?array
{
    if ($roleId < 1) {
        return null;
    }
    $st = $db->prepare('SELECT id, name, slug, description FROM roles WHERE id = :id LIMIT 1');
    $st->execute(['id' => $roleId]);
    $row = $st->fetch();
    if (!$row) {
        return null;
    }
    return [
        'id' => (int) $row['id'],
        'name' => (string) $row['name'],
        'slug' => (string) $row['slug'],
        'description' => $row['description'] !== null ? (string) $row['description'] : null,
    ];
}

/**
 * @return list<array{id:int,username:string,full_name:string,email:?string,is_active:int,role_id:int,role_name:string}>
 */
function permissions_users_by_role(PDO $db, int $roleId): array
{
    $sql = 'SELECT u.id, u.username, u.full_name, u.email, u.is_active, u.role_id, r.name AS role_name
            FROM users u
            INNER JOIN roles r ON r.id = u.role_id
            WHERE u.role_id = :rid
            ORDER BY u.full_name ASC, u.username ASC';
    $st = $db->prepare($sql);
    $st->execute(['rid' => $roleId]);
    $rows = $st->fetchAll() ?: [];
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id' => (int) $r['id'],
            'username' => (string) $r['username'],
            'full_name' => (string) $r['full_name'],
            'email' => $r['email'] !== null ? (string) $r['email'] : null,
            'is_active' => (int) $r['is_active'],
            'role_id' => (int) $r['role_id'],
            'role_name' => (string) $r['role_name'],
        ];
    }
    return $out;
}

/**
 * @return list<array{id:int,username:string,full_name:string,email:?string,is_active:int,current_role_id:int,current_role_name:string}>
 */
function permissions_users_pool(PDO $db): array
{
    $sql = 'SELECT u.id, u.username, u.full_name, u.email, u.is_active,
                   COALESCE(r.id, 0) AS current_role_id, COALESCE(r.name, \'\') AS current_role_name
            FROM users u
            LEFT JOIN roles r ON r.id = u.role_id
            ORDER BY u.full_name ASC, u.username ASC';
    $st = $db->query($sql);
    $rows = $st->fetchAll() ?: [];
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id' => (int) $r['id'],
            'username' => (string) $r['username'],
            'full_name' => (string) $r['full_name'],
            'email' => $r['email'] !== null ? (string) $r['email'] : null,
            'is_active' => (int) $r['is_active'],
            'current_role_id' => (int) $r['current_role_id'],
            'current_role_name' => (string) $r['current_role_name'],
        ];
    }
    return $out;
}

/**
 * Quita el rol actual a un usuario (role_id = NULL).
 */
function permissions_unassign_user_role(PDO $db, int $userId, int $fromRoleId): void
{
    if ($userId < 1 || $fromRoleId < 1) {
        throw new InvalidArgumentException('Dades no vàlides');
    }
    $st = $db->prepare('SELECT role_id FROM users WHERE id = :uid LIMIT 1');
    $st->execute(['uid' => $userId]);
    $row = $st->fetch();
    if (!$row) {
        throw new RuntimeException('Usuari no trobat');
    }
    if ((int) $row['role_id'] !== $fromRoleId) {
        throw new InvalidArgumentException('L’usuari ja no té aquest rol');
    }
    $up = $db->prepare('UPDATE users SET role_id = NULL WHERE id = :uid');
    $up->execute(['uid' => $userId]);
    permissions_invalidate_cache();
}

/**
 * Assigna/canvia el rol d’un usuari.
 */
function permissions_assign_user_to_role(PDO $db, int $userId, int $targetRoleId): void
{
    if ($userId < 1 || $targetRoleId < 1) {
        throw new InvalidArgumentException('Dades no vàlides');
    }
    $st = $db->prepare('SELECT id FROM roles WHERE id = :id LIMIT 1');
    $st->execute(['id' => $targetRoleId]);
    if (!$st->fetch()) {
        throw new InvalidArgumentException('Rol no vàlid');
    }
    $up = $db->prepare('UPDATE users SET role_id = :rid WHERE id = :uid');
    $up->execute(['rid' => $targetRoleId, 'uid' => $userId]);
    if ($up->rowCount() === 0) {
        $chk = $db->prepare('SELECT id FROM users WHERE id = :uid LIMIT 1');
        $chk->execute(['uid' => $userId]);
        if (!$chk->fetch()) {
            throw new RuntimeException('Usuari no trobat');
        }
    }
    permissions_invalidate_cache();
}

/**
 * Mou un usuari del rol actual a un rol de destí (equivalent a "quitar del rol").
 */
function permissions_move_user_from_role(PDO $db, int $userId, int $fromRoleId, int $targetRoleId): void
{
    if ($fromRoleId < 1 || $targetRoleId < 1 || $userId < 1) {
        throw new InvalidArgumentException('Dades no vàlides');
    }
    $st = $db->prepare('SELECT role_id FROM users WHERE id = :uid LIMIT 1');
    $st->execute(['uid' => $userId]);
    $row = $st->fetch();
    if (!$row) {
        throw new RuntimeException('Usuari no trobat');
    }
    if ((int) $row['role_id'] !== $fromRoleId) {
        throw new InvalidArgumentException('L’usuari ja no té aquest rol');
    }
    permissions_assign_user_to_role($db, $userId, $targetRoleId);
}

/**
 * Mateixa normalització que a permissions_save_matrix per una fila concreta (form_id).
 *
 * @param list<array<string,mixed>> $matrix
 */
function permissions_matrix_will_can_view_form(array $matrix, int $formId): bool
{
    foreach ($matrix as $row) {
        if ((int) ($row['form_id'] ?? 0) !== $formId) {
            continue;
        }
        $v = !empty($row['can_view']) ? 1 : 0;
        $c = !empty($row['can_create']) ? 1 : 0;
        $e = !empty($row['can_edit']) ? 1 : 0;
        $d = !empty($row['can_delete']) ? 1 : 0;
        if ($v === 0) {
            $c = 0;
            $e = 0;
            $d = 0;
        } elseif ($c || $e || $d) {
            $v = 1;
        }
        if ($v === 0 && $c === 0 && $e === 0 && $d === 0) {
            continue;
        }
        return $v === 1;
    }
    return false;
}

/**
 * El rol Administrator (slug `admin`) no pot quedar sense can_view al formulari `permissions`, independentment de qui editi.
 *
 * @param list<array<string,mixed>> $matrix
 */
function permissions_assert_administrator_keeps_permissions_view_on_form(PDO $db, array $matrix): void
{
    $st = $db->prepare('SELECT id FROM forms WHERE code = :c LIMIT 1');
    $st->execute(['c' => 'permissions']);
    $permForm = $st->fetch();
    if (!$permForm) {
        return;
    }
    $permFormId = (int) $permForm['id'];
    if (!permissions_matrix_will_can_view_form($matrix, $permFormId)) {
        throw new InvalidArgumentException(
            'El rol Administrator ha de conservar sempre el permís de veure la pantalla de permisos. No es pot desactivar «Veure» en el formulari Permisos per a aquest rol.'
        );
    }
}

/**
 * Desa la matriu completa de permisos per a un rol (delete + insert en transacció).
 *
 * @param list<array{form_id:int,can_view:int,can_create:int,can_edit:int,can_delete:int}> $matrix
 */
function permissions_save_matrix(PDO $db, int $roleId, array $matrix): void
{
    if ($roleId < 1) {
        throw new InvalidArgumentException('Rol no vàlid');
    }

    $st = $db->prepare('SELECT id, slug FROM roles WHERE id = :id LIMIT 1');
    $st->execute(['id' => $roleId]);
    $roleRow = $st->fetch();
    if (!$roleRow) {
        throw new InvalidArgumentException('Rol no trobat');
    }

    if ((string) $roleRow['slug'] === permissions_administrator_role_slug()) {
        permissions_assert_administrator_keeps_permissions_view_on_form($db, $matrix);
    }

    // Carregar map de forms vàlids
    $fSt = $db->query('SELECT id FROM forms');
    $fRows = $fSt->fetchAll() ?: [];
    $validForms = [];
    foreach ($fRows as $fr) {
        $validForms[(int) $fr['id']] = true;
    }

    $del = $db->prepare('DELETE FROM role_permissions WHERE role_id = :rid');
    $del->execute(['rid' => $roleId]);

    $ins = $db->prepare(
        'INSERT INTO role_permissions (role_id, form_id, can_view, can_create, can_edit, can_delete)
         VALUES (:role_id, :form_id, :can_view, :can_create, :can_edit, :can_delete)'
    );

    foreach ($matrix as $row) {
        $formId = (int) ($row['form_id'] ?? 0);
        if ($formId < 1 || !isset($validForms[$formId])) {
            continue;
        }
        $v = !empty($row['can_view']) ? 1 : 0;
        $c = !empty($row['can_create']) ? 1 : 0;
        $e = !empty($row['can_edit']) ? 1 : 0;
        $d = !empty($row['can_delete']) ? 1 : 0;

        if ($v === 0) {
            $c = 0;
            $e = 0;
            $d = 0;
        } elseif ($c || $e || $d) {
            $v = 1;
        }

        // Si tots són 0, podem ometre la fila (sense permisos).
        if ($v === 0 && $c === 0 && $e === 0 && $d === 0) {
            continue;
        }

        $ins->execute([
            'role_id' => $roleId,
            'form_id' => $formId,
            'can_view' => $v,
            'can_create' => $c,
            'can_edit' => $e,
            'can_delete' => $d,
        ]);
    }
    permissions_invalidate_cache();
}

/**
 * @param list<array{form_id:int,form_group:string,group_sort_order:int}> $formsConfig
 */
function permissions_save_forms_configuration(PDO $db, array $formsConfig): void
{
    if ($formsConfig === []) {
        return;
    }

    $fSt = $db->query('SELECT id FROM forms');
    $validRows = $fSt->fetchAll() ?: [];
    $validFormIds = [];
    foreach ($validRows as $row) {
        $validFormIds[(int) $row['id']] = true;
    }

    $up = $db->prepare(
        'UPDATE forms
         SET form_group = :form_group,
             group_sort_order = :group_sort_order
         WHERE id = :id'
    );

    foreach ($formsConfig as $row) {
        $formId = (int) ($row['form_id'] ?? 0);
        if ($formId < 1 || !isset($validFormIds[$formId])) {
            continue;
        }

        $formGroup = permissions_normalize_form_group((string) ($row['form_group'] ?? ''));
        $groupSortOrder = (int) ($row['group_sort_order'] ?? 0);
        if ($groupSortOrder < 0) {
            $groupSortOrder = 0;
        }

        $up->execute([
            'id' => $formId,
            'form_group' => $formGroup,
            'group_sort_order' => $groupSortOrder,
        ]);
    }
}

