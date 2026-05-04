# Pantallas fuera del CRUD genérico

## Dashboard

- **`public/dashboard.php`**: permiso `can_view_form('dashboard')`; KPIs y enlaces; año de catálogo para contexto.

## Seguridad y administración de acceso

- **`public/users.php`**, **`users_api.php`**, vistas en `views/users/`, JS `users.js`.
- **`public/roles.php`**, **`roles_api.php`**, `views/roles/`, `roles.js`.
- **`public/permissions.php`**, **`permissions_api.php`**, `views/permissions/`, `permissions.js`.
- **`public/change_password.php`**, vista en `views/security/`.

APIs dedicadas con el mismo patrón: bootstrap, auth, JSON, CSRF donde aplique.

## Selector y ejecución de informes

- **`public/report_selector.php`**: lectura de tabla `reports`; sin CRUD; UI con radios (véase normas en `../AGENTS.md`).
- **`public/report_run.php`**: entrada con `code`, `catalog_year` y parámetros por informe (p. ej. RGE-01). La salida «PDF» sigue el patrón HTML + `report_print.css` + `window.print()` (sin librerías PHP); véase `../rules/reports-generation.mdc`.
- **Cabecera de informes impresos/PDF:** un único partial PHP reutilizable + `assets/css/report_print.css` (y copia en `public/assets/`); no duplicar markup por informe. Norma completa (PDF sin Composer, vistas standalone): `../rules/reports-generation.mdc`.

## Otros módulos “training” en assets

Hay JS/CSS para acciones formativas, subprogramas, ubicaciones, etc. (`training_*.js`). Siguen el estilo del proyecto; revisar la pantalla PHP que los encola antes de modificar.

## Layout

- **`views/layouts/admin_page.php`**: encadena `page_header`, `action_bar`, `filter_card`, `data_table` y contenido extra.
- **`views/layouts/auth.php`**: login.
