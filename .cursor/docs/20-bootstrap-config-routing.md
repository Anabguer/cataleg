# Bootstrap, configuración y rutas

## Arranque PHP

- **`includes/bootstrap.php`**: define `APP_ROOT`, charset UTF-8, carga `config/app.php`, `config/database.php`, `includes/functions.php`, `catalog_year.php`, `auth.php`, `permissions.php` e inicia sesión con cookies seguras según HTTPS.
- **`public/_init.php`**: patrón corto para páginas autenticadas: `bootstrap` + `auth_require_login()` + `permissions_load_for_session()`.
- **`public/maintenance_api.php`**: carga `bootstrap` directamente (sin `_init`) pero exige login y permisos dentro del propio script.

## Configuración

- **`config/env.php`**: cargador; debe existir.
- **`config/env.local.php`** / **`config/env.production.php`**: definen `APP_ENV`, `BASE_URL`, `SITE_BASE_URL` (plantillas en `env.example.php`).
- **`config/app.php`**: depende del entorno cargado; en producción sin debug suele ocultar errores al cliente.

## URLs

- **`app_url($path)`**: URLs de páginas y redirecciones bajo `BASE_URL`.
- **`asset_url($path)`**: estáticos bajo `/assets/...`.

## Punto de entrada web

El servidor debe apuntar el **document root** a `public/`. Los enlaces del menú usan rutas relativas al dominio configurado (campo `route` en `forms`).
