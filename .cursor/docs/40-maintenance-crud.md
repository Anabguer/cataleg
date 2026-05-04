# Sistema de mantenimiento (CRUD genérico)

## Flujo de página

1. **`public/maintenance.php`**: `module` por query string; `maintenance_module_config($module)` en `includes/maintenance/aux_catalog.php`. Si el módulo no existe → redirección al dashboard.
2. **`require_can_view($module)`** — el nombre del módulo es el **code** del formulario.
3. **`catalog_year_current()`** obligatorio para la página de listado; si es `null` → dashboard.
4. Carga filtros, ordenación, paginación y opciones de selects (muchas consultas condicionadas por `$module === 'job_positions'` etc.).
5. Vista **`views/maintenance/index.php`** + layout admin.

## API JSON

- **`public/maintenance_api.php`**: JSON UTF-8.
- GET `action=get` + `id` (+ `module`) para ficha; validación `can_view_form`.
- POST con JSON: `action=save` (permisos create/edit), `delete`, etc. **CSRF**: cabecera `X-CSRF-Token` o campo `csrf_token` igual a `$_SESSION['csrf_token']`.
- Errores en payload `{ ok: false, errors: { ... } }` con códigos HTTP apropiados (403, 404, 422…).

## Registro de módulos

**`maintenance_modules_config()`** en `aux_catalog.php`: array asociativo `module_key => ['title' => ..., 'table' => ..., 'implemented' => true]`.

Listas relacionadas:

- **`maintenance_catalog_list_modules()`** — módulos con listado SQL genérico.
- **`maintenance_catalog_crud_modules()`** — CRUD completo (excluye casos especiales como bonus transitorio solo lectura).

Añadir un módulo nuevo implica: entrada aquí, columnas/listado/save en el mismo archivo o includes relacionados, campos en el POST masivo de `maintenance_api.php`, vistas/modal/JS según el patrón del módulo similar.

## Columnas y presentación de listado

**`includes/maintenance/maintenance_columns.php`**: `maintenance_table_columns()`, constantes de padding (clase, categoría, subprograma, código de lloc, etc.) y helpers de formato (`maintenance_format_job_position_code_display`, dinero, fechas…).

## Vistas del modal

- **`views/partials/maintenance_modal.php`**: formulario compartido; campos genéricos con `data-maintenance-field` y visibilidad por módulo desde JS.
- **Partials por módulo** (inclusión condicionada en PHP):
  - `job_positions` → `maintenance_modal_job_positions.php`
  - `parameters` → `maintenance_modal_parameters.php`
  - `reports` → `maintenance_modal_reports.php`
  - Bloques grandes de `people` inline en el mismo `maintenance_modal.php` dentro de `if ($module === 'people')`.

**Regla:** cualquier HTML específico de un módulo no debe mostrarse en otro (`../AGENTS.md`).

## JavaScript

**`assets/js/maintenance.js`** (y copia en `public/assets/js/`): configuración global del modal, tabla, filtros; `module()` devuelve el código del módulo actual para *guards* por módulo.

## Helpers de vista

**`includes/maintenance/maintenance_view_helpers.php`**: cabeceras de página, filtros, columnas para la tabla HTML.
