# Año de catálogo (`catalog_year`)

## Sesión

- **`catalog_year_current()`** (`includes/catalog_year.php`): lee `$_SESSION['catalog_year']`.
- **`catalog_year_init($db)`** (llamado desde `bootstrap.php`): si no hay año en sesión o no es válido, asigna el **máximo** año disponible entre un conjunto de tablas maestras (`catalog_year_tables()`).
- **`public/set_catalog_year.php`**: permite cambiar el año activo (debe existir en los años derivados de esas tablas).

## Uso en mantenimiento

La página `maintenance.php` y la API usan el año activo para listados y guardados en la mayoría de módulos.

**Excepciones y matices** documentados en normas de producto (`../AGENTS.md`): p. ej. `parameters` lista todos los años; `reports` no filtra por `catalog_year` en el modelo de datos.

## Implicaciones para desarrollo

- Consultas de datos de ejercicio deben incluir `catalog_year = :year` coherente con el resto del módulo.
- No mezclar registros de otros años en updates masivos salvo lógica explícita y aprobada.
