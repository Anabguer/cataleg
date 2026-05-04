# Visión general

## Qué es el proyecto

Aplicación web interna **cataleg_molins**: catálogos y mantenimientos ligados a un **año de catálogo** (`catalog_year`), con permisos por formulario (`forms` + `role_permissions`).

## Stack

- **Backend:** PHP 7.4+ (declaración `strict_types` en muchos puntos), sin framework MVC.
- **Base de datos:** MySQL/MariaDB vía PDO (`config/database.php`).
- **Frontend:** HTML en vistas PHP, JavaScript y CSS en `assets/` servidos bajo `public/` (véase [10-repository-layout.md](./10-repository-layout.md)).

## Idioma de interfaz

Textos de UI y mensajes al usuario en **catalán** en la mayoría de pantallas (mensajes de API, etiquetas, etc.). La documentación en `.cursor/` está en **español** por convenio con el equipo.

## Principio de trabajo

No inventar patrones nuevos: alinear con módulos ya existentes (`management_positions`, `people`, `job_positions`, `parameters`, `reports` y mantenimientos `maintenance_*`). Las reglas de negocio y UX detalladas están en `../AGENTS.md`.
