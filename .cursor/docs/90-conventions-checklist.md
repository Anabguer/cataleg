# Checklist rápido (agente / desarrollador)

Antes de dar por cerrada una tarea, cruzar con `../AGENTS.md` y con los docs `40`–`70` según el área.

## Cualquier cambio

- [ ] ¿Hay un módulo existente que haga algo parecido? Copiar su patrón.
- [ ] ¿Cambias `assets/`? Sincronizar `public/assets/`.
- [ ] ¿Nuevo formulario en menú? Registrar en `forms` + `role_permissions` (admin); no enlazar a mano el menú.

## Mantenimiento / modal

- [ ] HTML específico de módulo: condicionado por `$module` en PHP.
- [ ] JS específico: comprobar `module()` (o equivalente) antes de enganchar eventos.
- [ ] Modo solo lectura: UI bloqueada y handlers que respetan `view`.

## API / servidor

- [ ] CSRF en POST hacia `maintenance_api.php` (u otras APIs).
- [ ] No exponer mensajes PDO/SQL al usuario final; 422 vs 500 según `../AGENTS.md`.

## Datos

- [ ] ¿Listados por ejercicio? Filtro `catalog_year` coherente con el módulo (y excepciones conocidas).
- [ ] Scripts SQL en `descripcions/` si aplica; revisar `.gitignore`.

## Pruebas manuales mínimas (CRUD)

- [ ] Alta, edición, visualización, borrado (según permisos).
- [ ] Otros módulos no muestran bloques ni botones ajenos en el mismo modal.
