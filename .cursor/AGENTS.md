# AGENTS.md — Normas del proyecto cataleg_molins

Documentación de normas para agentes y desarrollo en Cursor. Seguir estos criterios al tocar el código de este repositorio.

## Documentación complementaria (arquitectura del repo)

Este archivo concentra **normas de producto y convenciones**. La **descripción técnica** del repositorio (rutas, bootstrap, permisos, mantenimiento, año de catálogo, checklist) está en:

- [`.cursor/README.md`](README.md) — índice del directorio `.cursor/`
- [`.cursor/docs/README.md`](docs/README.md) — índice de guías de arquitectura

Conviene leer `docs/40-maintenance-crud.md` y `docs/30-auth-permissions-menu.md` al trabajar en `maintenance.php` / `maintenance_api.php` / modales.

## Índice

1. [Contexto general](#contexto-general)
2. [Regla principal](#regla-principal)
3. [Estructura principal](#estructura-principal)
4. [Forms y permisos](#forms-y-permisos)
5. [Grupos de menú](#grupos-de-menú)
6. [catalog_year](#catalog_year)
7. [Modales compartidas](#modales-compartidas)
8. [JS por módulo](#js-por-módulo)
9. [Readonly / Visualización](#readonly--visualización)
10. [Booleanos](#booleanos)
11. [Moneda](#moneda)
12. [Porcentajes](#porcentajes)
13. [management_positions / Catàleg de places](#management_positions--catàleg-de-places)
14. [people / Catàleg de persones](#people--catàleg-de-persones)
15. [job_positions / Catàleg de llocs](#job_positions--catàleg-de-llocs)
16. [parameters / Paràmetres](#parameters--paràmetres)
17. [reports / Informes](#reports--informes)
18. [report_selector / Selector d’informes](#report_selector--selector-dinformes)
19. [report_run.php](#report_runphp)
20. [SQL y migraciones](#sql-y-migraciones)
21. [Git](#git)
22. [Seguridad](#seguridad)
23. [Validación mínima por mantenimiento](#validación-mínima-por-mantenimiento)

---

## Contexto general

Aplicación interna `cataleg_molins`.

**Stack:**

- PHP vanilla, sin framework.
- MySQL / MariaDB.
- Frontend con JavaScript vanilla.
- Sistema de mantenimiento genérico basado en `maintenance.php`.
- UI y permisos gestionados desde `forms`.

No improvisar arquitectura nueva: integrar siempre en los patrones existentes.

---

## Regla principal

Antes de modificar, revisar cómo lo hace un módulo existente similar.

**Módulos de referencia:**

| Módulo | Descripción |
|--------|-------------|
| `management_positions` | Catàleg de places |
| `people` | Catàleg de persones |
| `job_positions` | Catàleg de llocs |
| `parameters` | Paràmetres |
| `reports` | Informes |

Si algo ya existe en un patrón común, reutilizarlo.

---

## Estructura principal

**Archivos clave:**

```text
includes/maintenance/aux_catalog.php
includes/maintenance/maintenance_columns.php
public/maintenance.php
public/maintenance_api.php
views/maintenance/index.php
views/partials/maintenance_modal.php
views/partials/maintenance_modal_*.php
assets/js/maintenance.js
public/assets/js/maintenance.js
assets/css/module-users.css
public/assets/css/module-users.css
```

**Regla:** al tocar JS o CSS en `assets/`, copiar también a `public/assets/`.

---

## Forms y permisos

- Todo formulario debe registrarse en `forms`.
- No añadir enlaces manuales al menú salvo pantalla especial que lo requiera y el patrón existente lo haga así.

**Campos habituales del formulario:**

```text
code
name
route
form_group
```

**Permisos:**

```text
can_view
can_create
can_edit
can_delete
```

- Mantenimiento CRUD → registrar permisos CRUD.
- Pantalla solo consulta/uso → normalmente solo `can_view`.

El menú se genera desde `forms` y permisos.

---

## Grupos de menú

Usar grupos existentes. Ejemplos:

```text
training_management  -> Gestió
security             -> Seguretat
salary_tables        -> Taules Retribució
parameters           -> Paràmetres
```

No meter un formulario en `Manteniments` si debe ir como pestaña propia.

---

## catalog_year

**Regla general:** la mayoría de mantenimientos filtran siempre por `catalog_year`.

**Excepciones:**

- `parameters`: NO filtrar por año activo en listado; la clave es `catalog_year` y debe listar todos los años.
- `reports`: NO tiene `catalog_year`.

**Módulos con datos por ejercicio:**

- Todas las consultas filtran por `catalog_year`.
- No modificar registros de otros años.
- Acciones masivas limitadas al año activo.

---

## Modales compartidas

`views/partials/maintenance_modal.php` es compartida.

**Regla crítica:** todo bloque específico de un módulo debe ir condicionado por módulo.

```php
<?php if (($module ?? '') === 'people'): ?>
    ...
<?php endif; ?>
```

Nunca dejar bloques de un módulo renderizados en otros.

**Errores ya vistos (evitar):**

- Bloques de `people` en `management_positions`.
- Bloques de trienios en plazas.
- Botón «Copiar plaça» fuera de `management_positions`.

Siempre blindar por módulo.

---

## JS por módulo

- Toda lógica específica debe tener guard, por ejemplo:

```js
if (module() !== 'people') return;
```

- No romper lógica global.

**Funciones por módulo (estilo recomendado):** `setupPeopleFields()`, `setupJobPositionFields()`, `setupReportsFields()`, `sync...`.

**No usar:** `window.prompt()`, `alert()`, `confirm()`.

**Usar modales del sistema:** `showAlert(...)`, `showConfirm(...)`, `showActionModal(...)`.

---

## Readonly / Visualización

En modo visualización:

- Todos los inputs/selects/textarea bloqueados.
- Botón «Desar» oculto.
- Acciones de añadir/eliminar filas ocultas o deshabilitadas.
- Los handlers JS deben comprobar modo readonly (no basta el bloqueo visual).

```js
if (currentMaintenanceModalMode === 'view') return;
```

---

## Booleanos

- **En modales:** checkboxes normales.
- **En listados:** Sí/No con píldoras tipo subprogramas: Sí verde suave, No gris suave.
- No añadir chips Sí/No dentro de modales.

---

## Moneda

**Formato visual:** `1.234,56 €`

**En BBDD:** `1234.56`

- Alinear a la derecha.
- Aceptar coma, punto, separador de miles y símbolo €.
- Reutilizar funciones existentes tipo `formatMoneyForInput()` y `normalizeMoneyInput()`.

---

## Porcentajes

No mezclar tipos.

### Porcentaje fracción 0..1

Ejemplos en `people`: `dedication`, `budgeted_amount`, `social_security_contribution_coefficient`.

| BBDD | UI |
|------|-----|
| `1` | `100,00 %` |
| `0.5` | `50,00 %` |
| `0.3184` | `31,8400 %` |

### Porcentaje real 0..100

Ejemplo: `subprogram_people.dedication`.

| BBDD | UI |
|------|-----|
| `100` | `100,00 %` |
| `50` | `50,00 %` |

No dividir ni multiplicar si ya está en 0..100.

### Parámetros MEI (`parameters.mei_percentage`)

- `0.75` = `0,75%` (no significa 75%).
- Guardar valor real, p. ej. `0.7500`; no multiplicar por 100.

---

## management_positions / Catàleg de places

- `position_id` numérico, 1 a 4 dígitos.
- Visualización con padding a 4 dígitos: `1` → `0001`, `12` → `0012`.
- Buscar con ceros y sin ceros.
- `budgeted_amount`: UI como porcentaje visual; BBDD guarda fracción.
- `is_active`: solo depende de `deleted_at`; **no editable**. Vacío → activa sí; con fecha → activa no.
- `classification_group`: tabla de sueldos, no depende de escala/categoría.
- `category_id` depende de `class_id`, no de `subscale_id`.

### Classe de plaça (`position_class_id`)

- **`1` funcionari:** activar escala, subescala, classe, categoria; desactivar categoria laboral.
- **`2` laboral:** desactivar escala, subescala, classe, categoria; activar categoria laboral.
- **Nuevo registro:** dependientes deshabilitados hasta elegir tipo.

### Copiar plaça

Solo en visualización real de `management_positions`. No en nuevo, edición ni otros módulos.

---

## people / Catàleg de persones

**Listado (columnas):** Codi, 1r Cognom, 2n Cognom, Nom, DNI, Email, Lloc de treball, Plaça, Relació jurídica, Activa.

- `person_id` visual: 5 dígitos con ceros a la izquierda.
- `is_active`: derivado de `terminated_at`; no editable manualmente.

### Subprogramas (`subprogram_people`)

Campos: `catalog_year`, `subprogram_id`, `person_id`, `dedication`, `legacy_person_id`.

- `dedication` en `subprogram_people` es 0..100.
- No subprogramas repetidos misma persona/año.
- Visualización: no añadir/eliminar; edición: sí.
- Guardar en transacción: guardar persona → borrar subprogramas año/persona → insertar enviados.
- Recomendado en BBDD: `UNIQUE (catalog_year, person_id, subprogram_id)`.

### Grau personal

- Select con código; importe en campo readonly aparte, formato moneda.
- El importe no lleva `name` (no va al POST).

### Antiguitat / Triennis

Bloque solo para `people`; no en otros módulos.

---

## job_positions / Catàleg de llocs

**Pestañas:** Identificació, Dedicació, Funcions, Provisió, Condicions, Observacions.

Lo específico en `views/partials/maintenance_modal_job_positions.php`, condicionado a `job_positions`.

### Codi complet del lloc

- Cálculo: departament 4 dígitos + número 2 dígitos.
- Visual: `1000.01`; guardado compacto si la BBDD lo usa: `100001`.
- Campo visible readonly; no editable manualmente.

### Relació jurídica

Texto, **no** numérico. Valores:

```text
E - Eventual
F - Funcionari/a
I - Funcionari/a Interí/na per programa temporal
L - Laboral
P - Funcionari/a Pràctiques
T - Laboral temporal
D - Directiu
```

No castear a `int`.

**Comportamiento por relación:**

- `D`, `E`, `I`, `P`: escala/subescala/classe/categoria/laboral desactivados y limpios.
- `F`: activar escala/subescala/classe/categoria; desactivar laboral.
- `L`, `T`: desactivar escala/subescala/classe/categoria; activar laboral.

### Cascadas

escala → subescala → classe → categoria (categoría depende de classe).

### Responsable

- Select de `job_positions` con `job_type_id = 'CM'`.
- Mostrar p. ej. `9000.01 — Alcalde`; value real `900001`.
- Ordenar por código si se pide; no excluir por baja si no se pide.

### Ocupants del lloc

Columnas: Codi, Nom, %Dedicació, %Pressupostat, Situació, Coef. Cotització.

- Visualización: readonly.
- Edición: permite añadir/eliminar.

---

## parameters / Paràmetres

Tabla `parameters`: campos `catalog_year`, `mei_percentage`.

- Lista **todos** los años, no solo el activo.
- `catalog_year` es clave primaria.
- Acciones view/edit/delete usan `catalog_year` como id.
- `mei_percentage`: valor real (`0.75` = `0,75%`, no 75%).

Pestaña propia **Paràmetres**, no dentro de Manteniments.

---

## reports / Informes

Mantenimiento CRUD. Tabla `reports`.

**Campos principales:** `id`, `report_group`, `report_group_order`, `report_name`, `report_code`, `report_description`, `report_explanation`, `report_version`, `show_in_general_selector`, `display_order`, `is_active`, `created_at`, `updated_at`.

- `report_code` único.
- Mantenimiento en Gestió; nombre visible: **Informes**.
- No confundir con `report_selector`.

**Orden:**

```sql
report_group_order, report_group, report_code
```

Booleanos en listado con píldora Sí/No.

---

## report_selector / Selector d’informes

**No** es mantenimiento; pantalla de uso.

**Archivos:**

```text
public/report_selector.php
views/report_selector/index.php
assets/js/report_selector.js
public/assets/js/report_selector.js
assets/css/reports.css
public/assets/css/reports.css
```

**Reglas:**

- Sin listado CRUD ni alta/edición/borrado.
- Lee tabla `reports`: solo `is_active = 1` y `show_in_general_selector = 1`.
- Agrupa por `report_group`; orden grupos por `report_group_order`, `report_group`; informes por `report_code`.
- Muestra `report_name`.
- Año activo del catálogo sin selector de año en UI.

**UI:** radio por informe; «Acceptar»; «Cancel·lar» limpia selección (no navega); sin selección + aceptar → aviso; cabecera `page_header_with_escut`; envolver en `.module-users` para mismo diseño que mantenimientos.

---

## report_run.php

Rep `code`, `catalog_year` i paràmetres per informe; valida any actiu, permís de consulta al selector i existència de l’informe. Executa l’informe implementat (p. ex. RGE-01). No hardcodejar la llista d’informes al selector.

**Maquetació d’informes impresos/PDF:** una sola **plantilla PHP** compartida per a la capçalera (com el modal de manteniment) + CSS compartit `report_print.css`; cada informe només inclou la plantilla i el cos. El **PDF** no es genera amb llibreries PHP: es fa amb vista standalone + `window.print()` (mateix criteri que Formació). Detall i checklist: [`.cursor/rules/reports-generation.mdc`](rules/reports-generation.mdc).

---

## SQL y migraciones

Scripts nuevos de módulo en `descripcions/`. Comprobar si la carpeta está en `.gitignore`; si está ignorada, los SQL no irán al repo.

Incluir cuando aplique: creación de tabla, insert/update en `forms`, permisos admin, updates para bases existentes.

---

## Git

Antes de commit: `git status`.

**No subir:** `logs`, `exports`, temporales, `~$*.docx`, docs sin revisar, credenciales.

Commits con mensaje claro, por ejemplo:

```bash
git add .
git commit -m "Descripción clara del cambio"
git push origin main
```

---

## Seguridad

- No mostrar errores PDO/SQL al usuario.
- Backend: errores funcionales → 422 con mensaje claro; errores técnicos → 500 genérico; logs técnicos solo en servidor.
- No dejar `error_log` masivos en producción salvo modo debug.

---

## Validación mínima por mantenimiento

Para módulo nuevo o cambio importante, comprobar:

- Crear registro, visualizar readonly, editar, borrar.
- Filtros y ordenación.
- Permisos.
- No afecta otros módulos; no aparecen bloques de otros módulos.
- Filtro por `catalog_year` solo cuando corresponda al dominio del módulo.
