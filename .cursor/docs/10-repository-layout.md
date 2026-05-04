# Estructura del repositorio

## Raíz del proyecto (`APP_ROOT`)

Rutas típicas (definidas en bootstrap; `APP_ROOT` = directorio padre de `public/`):

| Ruta | Uso |
|------|-----|
| `public/` | Document root del servidor: `*.php` accesibles por URL, `_init.php`, `assets/` servidos. |
| `includes/` | PHP compartido: `bootstrap.php`, auth, permisos, lógica de mantenimiento (`includes/maintenance/`). |
| `config/` | `app.php`, `database.php`, `env*.php` (no versionar secretos reales). |
| `views/` | Plantillas PHP por pantalla y `views/partials/` (modales, tablas, filtros). |
| `assets/` | **Fuente** de JS/CSS; debe reflejarse en `public/assets/` para lo que sirva el servidor. |

## Duplicado `assets/` y `public/assets/`

`asset_url()` en `includes/functions.php` apunta a `/assets/` bajo la URL base (típicamente `public/assets/`). Convención del proyecto: **tras editar `assets/js` o `assets/css`, copiar a `public/assets/`** (véase `../AGENTS.md`).

## SQL ad hoc

Carpeta `descripcions/` con scripts `.sql` de evolución del esquema y `forms`. Comprobar si está en `.gitignore` antes de asumir que van al repositorio.

## Documentación humana adicional

Existe `docs/` en la raíz (p. ej. `docs/tauler_cataleg_molins.md`), aparte de `.cursor/docs/`.
