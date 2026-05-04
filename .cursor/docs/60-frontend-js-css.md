# Frontend: JS y CSS

## Ubicación

- **Origen:** `assets/js/`, `assets/css/`.
- **Servidos:** `public/assets/js/`, `public/assets/css/` (copia manual o proceso del equipo).

## Mantenimiento

- **`maintenance.js`**: modal CRUD, ordenación, filtros, llamadas a `maintenance_api.php` con token CSRF.
- Módulos con lógica extra en archivos dedicados en el mismo directorio (p. ej. `job_positions.js`, `people.js`) según la pantalla; seguir el patrón de *guard* por módulo (`module() !== 'x'` return).

## Modales y feedback

Usar las funciones del propio `maintenance.js` / stack común (`showAlert`, `showConfirm`, `showActionModal`, etc. — detalle en `../AGENTS.md`). Evitar `alert`/`confirm`/`prompt` nativos.

## Estilos globales de módulo administrativo

- **`module-users.css`**: aspecto unificado de mantenimientos y formularios tipo “users modal”.
- **`components.css`**, **`app.css`**: piezas reutilizables y layout.

## Selector de informes

`assets/js/report_selector.js` + `assets/css/reports.css` (y copias en `public/assets/`). Pantalla independiente de `maintenance.php`.
