# Autenticación, permisos y menú

## Sesión

Tras login, sesión PHP con `role_id`, datos de usuario, `csrf_token` y `catalog_year` (véase [50-catalog-year.md](./50-catalog-year.md)).

## Tablas clave

- **`forms`**: cada pantalla lógica tiene `code` (identificador único usado en código), `name`, `route`, `form_group`, ordenación (`sort_order`, `group_sort_order`).
- **`role_permissions`**: por `role_id` y `form_id`, flags `can_view`, `can_create`, `can_edit`, `can_delete`.

El **código del formulario** (`code`) coincide con el identificador de módulo en mantenimiento (p. ej. `people`, `management_positions`) cuando la pantalla es `maintenance.php?module=...`.

## Funciones principales (`includes/permissions.php`)

- `permissions_load_for_session()` — rellena `$_SESSION['permissions']` desde BD.
- `can_view_form($code)`, `can_create_form`, `can_edit_form`, `can_delete_form`.
- `require_can_view($code)` — redirige al dashboard con `denied=1` si no hay vista.

## Menú lateral (`includes/header.php`)

- `menu_visible_forms()` lista formularios con `can_view` para el rol actual.
- `permissions_form_group_definitions()` en `includes/permissions/permissions.php` define grupos: `system`, `salary_tables`, `organization`, `training_maintenance`, `social_security_companies`, `training_management`, `parameters`.
- El header clasifica cada ítem en **Seguretat**, **Taules Retribució**, **Manteniments**, **Gestió**, **Paràmetres** según `form_group`. Grupos desconocidos se normalizan a `training_maintenance` (`permissions_normalize_form_group`).

**No hardcodear ítems de menú** salvo excepciones ya existentes: el menú sale de `forms` + permisos.

## Pantallas de seguridad

Códigos reservados en menú como grupo Seguretat: `users`, `roles`, `permissions`, `password_change`.
