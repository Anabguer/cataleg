<?php
declare(strict_types=1);

/**
 * Configuració genèrica de columnes de manteniment jeràrquic:
 * codi i denominació sempre en columnes separades quan ambdós són rellevants;
 * `sort_key` coincideix amb el camp lògic de la BBDD (ordenació per valor real, sense text concatenat).
 */

/** Visualització i cerca LPAD per a `class_id` (Classes i columnes que el mostrin, p. ex. Categories). */
const MAINTENANCE_CLASS_ID_DISPLAY_PAD = 6;

/** Visualització i cerca LPAD per a `category_id` al llistat de Categories. */
const MAINTENANCE_CATEGORY_ID_DISPLAY_PAD = 8;

/** Visualització LPAD per a `subfunction_id` al llistat de Programes. */
const MAINTENANCE_PROGRAM_SUBFUNCTION_PAD = 3;

/** Emmagatzematge i visualització de `subprogram_number` (2 dígits, VARCHAR). */
const MAINTENANCE_SUBPROGRAM_NUMBER_PAD = 2;

/**
 * Codi de lloc (job_position_id) amb 6 dígits i punt: NNNN.DD (p. ex. 0012.34).
 */
function maintenance_format_job_position_code_display(?string $jobPositionId): string
{
    if ($jobPositionId === null) {
        return '';
    }
    $t = trim($jobPositionId);
    if ($t === '') {
        return '';
    }
    if (!preg_match('/^\d+$/', $t)) {
        return $t;
    }
    $p = strlen($t) > 6 ? substr($t, -6) : str_pad($t, 6, '0', STR_PAD_LEFT);

    return substr($p, 0, 4) . '.' . substr($p, 4, 2);
}

/**
 * Normalitza la subfunció a 3 dígits (VARCHAR) sense perdre zeros a l’esquerra.
 * Cal entrada només dígits (1–3) prèvia a la validació /^\d{1,3}$/.
 */
function maintenance_programs_normalize_subfunction_id(string $digits1to3): string
{
    return str_pad($digits1to3, MAINTENANCE_PROGRAM_SUBFUNCTION_PAD, '0', STR_PAD_LEFT);
}

/**
 * Genera `program_id`: subfunció ja normalitzada (3 caràcters) + un sol dígit.
 */
function maintenance_programs_compute_program_id(string $subfunctionIdNormalized3, string $programNumberOneDigit): string
{
    return $subfunctionIdNormalized3 . $programNumberOneDigit;
}

/**
 * Normalitza el número de subprograma a 2 dígits (VARCHAR) sense perdre zeros.
 *
 * @param string $digits1to2 Només dígits (1–2) prèvia a la validació /^\d{1,2}$/
 */
function maintenance_subprograms_normalize_number(string $digits1to2): string
{
    return str_pad($digits1to2, MAINTENANCE_SUBPROGRAM_NUMBER_PAD, '0', STR_PAD_LEFT);
}

/**
 * Genera `subprogram_id`: `program_id` + número ja normalitzat (2 caràcters).
 */
function maintenance_subprograms_compute_subprogram_id(string $programId, string $subprogramNumberNormalized2): string
{
    return trim($programId) . $subprogramNumberNormalized2;
}

/**
 * Visualització del `subprogram_id` al llistat: NNNN.DD (4 dígits + punt + 2 dígits).
 * Només presentació; l’ordenació segueix sent per valor real a la BBDD.
 */
function maintenance_format_subprogram_id_display(string $subprogramId): string
{
    $s = trim($subprogramId);
    if ($s === '' || strlen($s) !== 6 || !ctype_digit($s)) {
        return $s;
    }

    return substr($s, 0, 4) . '.' . substr($s, 4, 2);
}

/** @return list<string> */
function maintenance_subprograms_nature_allowed(): array
{
    return ['Continuació de serveis', 'Nou servei'];
}

/**
 * Claus de ordenació vàlides per a SQL (independents de si el llistat ja està implementat a la UI).
 *
 * @return list<string>
 */
function maintenance_list_sort_keys(string $module): array
{
    return match ($module) {
        'maintenance_scales' => ['scale_id', 'scale_short_name', 'scale_name', 'scale_full_name'],
        'maintenance_subscales' => ['subscale_id', 'subscale_short_name', 'subscale_name', 'scale_id', 'scale_name'],
        'maintenance_categories' => ['category_id', 'category_short_name', 'category_name', 'scale_id', 'scale_name', 'subscale_id', 'subscale_name', 'class_id', 'class_name'],
        'maintenance_classes' => ['class_id', 'class_short_name', 'class_name', 'scale_id', 'scale_name', 'subscale_id', 'subscale_name'],
        'maintenance_administrative_statuses' => ['administrative_status_id', 'administrative_status_name'],
        'maintenance_position_classes' => ['position_class_id', 'position_class_name'],
        'maintenance_legal_relationships' => ['legal_relation_id', 'legal_relation_name'],
        'maintenance_access_types' => ['access_type_id', 'access_type_name'],
        'maintenance_access_systems' => ['access_system_id', 'access_system_name'],
        'maintenance_work_centers' => ['work_center_id', 'work_center_name', 'city'],
        'maintenance_availability_types' => ['availability_id', 'availability_name', 'sort_order'],
        'maintenance_provision_forms' => ['provision_method_id', 'provision_method_name', 'sort_order'],
        'maintenance_organic_level_1' => ['org_unit_level_1_id', 'org_unit_level_1_name'],
        'maintenance_organic_level_2' => ['org_unit_level_2_id', 'org_unit_level_2_name', 'org_unit_level_1_id', 'org_unit_level_1_name'],
        'maintenance_organic_level_3' => ['org_unit_level_3_id', 'org_unit_level_3_name', 'org_unit_level_2_id', 'org_unit_level_2_name'],
        'maintenance_programs' => ['subfunction_id', 'program_number', 'program_code', 'program_name', 'responsible_person_code', 'responsible_job_title'],
        'maintenance_subprograms' => [
            'subprogram_program_id', 'subprogram_program_name', 'subprogram_number', 'subprogram_code', 'subprogram_name',
            'technical_manager_code', 'technical_job_title',
            'is_mandatory_service', 'has_corporate_agreements',
        ],
        default => ['id', 'name'],
    };
}

/**
 * Definició genèrica de columnes de llistat per manteniments jeràrquics:
 * codi i denominació en columnes separades, sort keys explícits (= camps SQL),
 * sense concatenacions codi+nom com a base d’ordenació.
 *
 * @return list<array{
 *   sort_key: string,
 *   label: string,
 *   sortable: bool,
 *   cell: array<string, mixed>
 * }>
 */
function maintenance_table_columns(string $module, bool $implemented): array
{
    $sortList = $implemented && in_array($module, ['maintenance_scales', 'maintenance_subscales', 'maintenance_categories', 'maintenance_classes', 'maintenance_administrative_statuses', 'maintenance_position_classes', 'maintenance_legal_relationships', 'maintenance_access_types', 'maintenance_access_systems', 'maintenance_work_centers', 'maintenance_availability_types', 'maintenance_provision_forms', 'maintenance_organic_level_1', 'maintenance_organic_level_2', 'maintenance_organic_level_3', 'maintenance_programs', 'maintenance_subprograms'], true);

    if ($module === 'maintenance_scales') {
        return [
            ['sort_key' => 'scale_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'padded_id', 'field' => 'scale_id', 'pad' => 2, 'strong' => true]],
            ['sort_key' => 'scale_short_name', 'label' => 'Abreviatura', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'scale_short_name']],
            ['sort_key' => 'scale_name', 'label' => 'Denominació Escala', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'scale_name']],
            ['sort_key' => 'scale_full_name', 'label' => 'Denominació Completa Escala', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'scale_full_name']],
        ];
    }
    if ($module === 'maintenance_subscales') {
        return [
            ['sort_key' => 'subscale_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'padded_id', 'field' => 'subscale_id', 'pad' => 4, 'strong' => true]],
            ['sort_key' => 'subscale_short_name', 'label' => 'Abreviatura', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'subscale_short_name']],
            ['sort_key' => 'subscale_name', 'label' => 'Denominació Sub Escala', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'subscale_name']],
            ['sort_key' => 'scale_id', 'label' => 'Codi Escala', 'sortable' => $sortList, 'cell' => ['type' => 'padded_id', 'field' => 'scale_id', 'pad' => 2, 'strong' => false]],
            ['sort_key' => 'scale_name', 'label' => 'Escala', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'scale_name']],
        ];
    }
    if ($module === 'maintenance_categories') {
        return [
            ['sort_key' => 'category_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'padded_id', 'field' => 'category_id', 'pad' => MAINTENANCE_CATEGORY_ID_DISPLAY_PAD, 'strong' => true]],
            ['sort_key' => 'category_short_name', 'label' => 'Abreviatura', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'category_short_name']],
            ['sort_key' => 'category_name', 'label' => 'Denominació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'category_name']],
            ['sort_key' => 'scale_id', 'label' => 'Codi Escala', 'sortable' => $sortList, 'cell' => ['type' => 'padded_id', 'field' => 'scale_id', 'pad' => 2, 'strong' => false]],
            ['sort_key' => 'scale_name', 'label' => 'Escala', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'scale_name']],
            ['sort_key' => 'subscale_id', 'label' => 'Codi Subescala', 'sortable' => $sortList, 'cell' => ['type' => 'padded_id', 'field' => 'subscale_id', 'pad' => 4, 'strong' => false]],
            ['sort_key' => 'subscale_name', 'label' => 'Subescala', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'subscale_name']],
            ['sort_key' => 'class_id', 'label' => 'Codi Classe', 'sortable' => $sortList, 'cell' => ['type' => 'padded_id', 'field' => 'class_id', 'pad' => MAINTENANCE_CLASS_ID_DISPLAY_PAD, 'strong' => false]],
            ['sort_key' => 'class_name', 'label' => 'Classe', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'class_name']],
        ];
    }
    if ($module === 'maintenance_classes') {
        return [
            ['sort_key' => 'class_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'padded_id', 'field' => 'class_id', 'pad' => MAINTENANCE_CLASS_ID_DISPLAY_PAD, 'strong' => true]],
            ['sort_key' => 'class_short_name', 'label' => 'Abreviatura', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'class_short_name']],
            ['sort_key' => 'class_name', 'label' => 'Denominació Classe', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'class_name']],
            ['sort_key' => 'scale_id', 'label' => 'Codi Escala', 'sortable' => $sortList, 'cell' => ['type' => 'padded_id', 'field' => 'scale_id', 'pad' => 2, 'strong' => false]],
            ['sort_key' => 'scale_name', 'label' => 'Escala', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'scale_name']],
            ['sort_key' => 'subscale_id', 'label' => 'Codi Subescala', 'sortable' => $sortList, 'cell' => ['type' => 'padded_id', 'field' => 'subscale_id', 'pad' => 4, 'strong' => false]],
            ['sort_key' => 'subscale_name', 'label' => 'Subescala', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'subscale_name']],
        ];
    }
    if ($module === 'maintenance_administrative_statuses') {
        return [
            ['sort_key' => 'administrative_status_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'raw_id', 'field' => 'administrative_status_id', 'strong' => true]],
            ['sort_key' => 'administrative_status_name', 'label' => 'Denominació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'administrative_status_name']],
        ];
    }
    if ($module === 'maintenance_position_classes') {
        return [
            ['sort_key' => 'position_class_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'raw_id', 'field' => 'position_class_id', 'strong' => true]],
            ['sort_key' => 'position_class_name', 'label' => 'Denominació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'position_class_name']],
        ];
    }
    if ($module === 'maintenance_legal_relationships') {
        return [
            ['sort_key' => 'legal_relation_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'raw_id', 'field' => 'legal_relation_id', 'strong' => true]],
            ['sort_key' => 'legal_relation_name', 'label' => 'Denominació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'legal_relation_name']],
        ];
    }
    if ($module === 'maintenance_access_types') {
        return [
            ['sort_key' => 'access_type_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'raw_id', 'field' => 'access_type_id', 'strong' => true]],
            ['sort_key' => 'access_type_name', 'label' => 'Denominació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'access_type_name']],
        ];
    }
    if ($module === 'maintenance_access_systems') {
        return [
            ['sort_key' => 'access_system_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'raw_id', 'field' => 'access_system_id', 'strong' => true]],
            ['sort_key' => 'access_system_name', 'label' => 'Denominació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'access_system_name']],
        ];
    }
    if ($module === 'maintenance_work_centers') {
        return [
            ['sort_key' => 'work_center_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'raw_id', 'field' => 'work_center_id', 'strong' => true, 'align' => 'right']],
            ['sort_key' => 'work_center_name', 'label' => 'Denominació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'work_center_name']],
            ['sort_key' => 'address', 'label' => 'Domicili', 'sortable' => false, 'cell' => ['type' => 'text', 'field' => 'address', 'class' => 'table-cell--wrap']],
            ['sort_key' => 'postal_code', 'label' => 'C.P.', 'sortable' => false, 'cell' => ['type' => 'text', 'field' => 'postal_code']],
            ['sort_key' => 'city', 'label' => 'Població', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'city']],
            ['sort_key' => 'phone', 'label' => 'Telèfon', 'sortable' => false, 'cell' => ['type' => 'text', 'field' => 'phone']],
            ['sort_key' => 'fax', 'label' => 'Fax', 'sortable' => false, 'cell' => ['type' => 'text', 'field' => 'fax']],
        ];
    }
    if ($module === 'maintenance_availability_types') {
        return [
            ['sort_key' => 'availability_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'availability_id', 'align' => 'center', 'class' => 'table__code-cell', 'header_class' => 'table__code-header']],
            ['sort_key' => 'availability_name', 'label' => 'Denominació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'availability_name']],
            ['sort_key' => 'sort_order', 'label' => 'Ordre', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'sort_order', 'align' => 'right', 'class' => 'table__order-cell', 'header_class' => 'table__order-header']],
        ];
    }
    if ($module === 'maintenance_provision_forms') {
        return [
            ['sort_key' => 'provision_method_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'provision_method_id', 'align' => 'center', 'class' => 'table__code-cell', 'header_class' => 'table__code-header']],
            ['sort_key' => 'provision_method_name', 'label' => 'Denominació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'provision_method_name']],
            ['sort_key' => 'sort_order', 'label' => 'Ordre', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'sort_order', 'align' => 'right', 'class' => 'table__order-cell', 'header_class' => 'table__order-header']],
        ];
    }
    if ($module === 'maintenance_organic_level_1') {
        return [
            ['sort_key' => 'org_unit_level_1_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'raw_id', 'field' => 'org_unit_level_1_id', 'strong' => true]],
            ['sort_key' => 'org_unit_level_1_name', 'label' => 'Denominació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'org_unit_level_1_name']],
        ];
    }
    if ($module === 'maintenance_organic_level_2') {
        return [
            ['sort_key' => 'org_unit_level_2_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'padded_id', 'field' => 'org_unit_level_2_id', 'pad' => 2, 'strong' => true]],
            ['sort_key' => 'org_unit_level_2_name', 'label' => 'Denominació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'org_unit_level_2_name']],
            ['sort_key' => 'org_unit_level_1_id', 'label' => 'Codi orgànic 1 dígit', 'sortable' => $sortList, 'cell' => ['type' => 'raw_id', 'field' => 'org_unit_level_1_id', 'strong' => false]],
            ['sort_key' => 'org_unit_level_1_name', 'label' => 'Orgànic 1 dígit', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'org_unit_level_1_name']],
        ];
    }
    if ($module === 'maintenance_organic_level_3') {
        return [
            ['sort_key' => 'org_unit_level_3_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'padded_id', 'field' => 'org_unit_level_3_id', 'pad' => 4, 'strong' => true]],
            ['sort_key' => 'org_unit_level_3_name', 'label' => 'Denominació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'org_unit_level_3_name']],
            ['sort_key' => 'org_unit_level_2_id', 'label' => 'Codi Orgànic 2', 'sortable' => $sortList, 'cell' => ['type' => 'padded_id', 'field' => 'org_unit_level_2_id', 'pad' => 2, 'strong' => false]],
            ['sort_key' => 'org_unit_level_2_name', 'label' => 'Orgànic 2', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'org_unit_level_2_name']],
        ];
    }
    if ($module === 'maintenance_programs') {
        return [
            ['sort_key' => 'subfunction_id', 'label' => 'Subfunció', 'sortable' => $sortList, 'cell' => ['type' => 'lpad_digits_varchar', 'field' => 'subfunction_id', 'pad' => MAINTENANCE_PROGRAM_SUBFUNCTION_PAD, 'strong' => true]],
            ['sort_key' => 'program_number', 'label' => 'Número', 'sortable' => $sortList, 'cell' => ['type' => 'raw_id', 'field' => 'program_number', 'strong' => false]],
            ['sort_key' => 'program_code', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'program_id', 'strong' => true]],
            ['sort_key' => 'program_name', 'label' => 'Nom programa', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'program_name']],
            ['sort_key' => 'responsible_person_code', 'label' => 'Codi responsable', 'sortable' => $sortList, 'cell' => ['type' => 'job_code_dot', 'field' => 'responsible_person_code']],
            ['sort_key' => 'responsible_job_title', 'label' => 'Responsable', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'responsible_job_title']],
            ['sort_key' => 'description', 'label' => 'Descripció', 'sortable' => false, 'cell' => ['type' => 'program_description', 'field' => 'description']],
        ];
    }
    if ($module === 'maintenance_subprograms') {
        $subCompact = 'table-col--maint-sub-compact';

        return [
            ['sort_key' => 'subprogram_program_id', 'label' => 'Programa', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'program_id', 'strong' => true, 'class' => $subCompact, 'header_class' => $subCompact]],
            ['sort_key' => 'subprogram_program_name', 'label' => 'Nom programa', 'sortable' => $sortList, 'cell' => ['type' => 'text_truncate', 'field' => 'program_name', 'truncate_class' => 'table__text-truncate--maint-sub']],
            ['sort_key' => 'subprogram_number', 'label' => 'Número', 'sortable' => $sortList, 'cell' => ['type' => 'lpad_digits_varchar', 'field' => 'subprogram_number', 'pad' => MAINTENANCE_SUBPROGRAM_NUMBER_PAD, 'strong' => false, 'class' => $subCompact, 'header_class' => $subCompact]],
            ['sort_key' => 'subprogram_code', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'subprogram_id_display', 'field' => 'subprogram_id', 'strong' => true, 'class' => 'table-col--code-subprogram', 'header_class' => 'table-col--code-subprogram']],
            ['sort_key' => 'subprogram_name', 'label' => 'Nom subprograma', 'sortable' => $sortList, 'cell' => ['type' => 'text_truncate', 'field' => 'subprogram_name', 'truncate_class' => 'table__text-truncate--maint-sub']],
            ['sort_key' => 'technical_manager_code', 'label' => 'Codi resp. tècnic', 'sortable' => $sortList, 'header_title' => 'Codi responsable tècnic', 'cell' => ['type' => 'job_code_dot', 'field' => 'technical_manager_code', 'class' => $subCompact, 'header_class' => $subCompact]],
            ['sort_key' => 'technical_job_title', 'label' => 'Responsable tècnic', 'sortable' => $sortList, 'cell' => ['type' => 'text_truncate', 'field' => 'technical_job_title', 'truncate_class' => 'table__text-truncate--maint-sub', 'class' => 'table-col--responsable-tecnic', 'header_class' => 'table-col--responsable-tecnic']],
            ['sort_key' => 'is_mandatory_service', 'label' => 'Obligatori', 'sortable' => $sortList, 'header_title' => 'Servei obligatori', 'cell' => ['type' => 'bool_si_no_chip', 'field' => 'is_mandatory_service', 'class' => $subCompact, 'header_class' => $subCompact]],
            ['sort_key' => 'has_corporate_agreements', 'label' => 'Acords', 'sortable' => $sortList, 'header_title' => 'Acords corporatius', 'cell' => ['type' => 'bool_si_no_chip', 'field' => 'has_corporate_agreements', 'class' => $subCompact, 'header_class' => $subCompact]],
        ];
    }

    return [
        ['sort_key' => 'id', 'label' => 'Codi', 'sortable' => false, 'cell' => ['type' => 'text', 'field' => '_empty']],
        ['sort_key' => 'name', 'label' => 'Nom', 'sortable' => false, 'cell' => ['type' => 'text', 'field' => '_empty']],
        ['sort_key' => 'short_name', 'label' => 'Nom curt', 'sortable' => false, 'cell' => ['type' => 'text', 'field' => '_empty']],
    ];
}

function maintenance_default_sort_key(string $module): string
{
    return match ($module) {
        'maintenance_scales' => 'scale_id',
        'maintenance_subscales' => 'subscale_id',
        'maintenance_categories' => 'category_id',
        'maintenance_classes' => 'class_id',
        'maintenance_administrative_statuses' => 'administrative_status_id',
        'maintenance_position_classes' => 'position_class_id',
        'maintenance_legal_relationships' => 'legal_relation_id',
        'maintenance_access_types' => 'access_type_id',
        'maintenance_access_systems' => 'access_system_id',
        'maintenance_work_centers' => 'work_center_id',
        'maintenance_availability_types' => 'sort_order',
        'maintenance_provision_forms' => 'sort_order',
        'maintenance_organic_level_1' => 'org_unit_level_1_id',
        'maintenance_organic_level_2' => 'org_unit_level_2_id',
        'maintenance_organic_level_3' => 'org_unit_level_3_id',
        'maintenance_programs' => 'subfunction_id',
        'maintenance_subprograms' => 'subprogram_program_id',
        default => 'id',
    };
}

/**
 * @return array<string, string> mapa sort_by antic (URL) → clau canònica
 */
function maintenance_sort_key_legacy_map(string $module): array
{
    return match ($module) {
        'maintenance_scales' => [
            'id' => 'scale_id',
            'short_name' => 'scale_short_name',
            'name' => 'scale_name',
            'full_name' => 'scale_full_name',
        ],
        'maintenance_subscales' => [
            'id' => 'subscale_id',
            'short_name' => 'subscale_short_name',
            'name' => 'subscale_name',
            'parent' => 'scale_name',
            'parent_id' => 'scale_id',
        ],
        'maintenance_categories' => [
            'id' => 'category_id',
            'short_name' => 'category_short_name',
            'name' => 'category_name',
            'parent' => 'class_name',
        ],
        'maintenance_classes' => [
            'id' => 'class_id',
            'short_name' => 'class_short_name',
            'name' => 'class_name',
        ],
        'maintenance_administrative_statuses' => [
            'id' => 'administrative_status_id',
            'name' => 'administrative_status_name',
        ],
        'maintenance_position_classes' => [
            'id' => 'position_class_id',
            'name' => 'position_class_name',
        ],
        'maintenance_legal_relationships' => [
            'id' => 'legal_relation_id',
            'name' => 'legal_relation_name',
        ],
        'maintenance_access_types' => [
            'id' => 'access_type_id',
            'name' => 'access_type_name',
        ],
        'maintenance_access_systems' => [
            'id' => 'access_system_id',
            'name' => 'access_system_name',
        ],
        'maintenance_work_centers' => [
            'id' => 'work_center_id',
            'name' => 'work_center_name',
        ],
        'maintenance_availability_types' => [
            'id' => 'availability_id',
            'name' => 'availability_name',
        ],
        'maintenance_provision_forms' => [
            'id' => 'provision_method_id',
            'name' => 'provision_method_name',
        ],
        'maintenance_organic_level_1' => [
            'id' => 'org_unit_level_1_id',
            'name' => 'org_unit_level_1_name',
        ],
        'maintenance_organic_level_2' => [
            'id' => 'org_unit_level_2_id',
            'name' => 'org_unit_level_2_name',
        ],
        'maintenance_organic_level_3' => [
            'id' => 'org_unit_level_3_id',
            'name' => 'org_unit_level_3_name',
        ],
        'maintenance_programs' => [
            'id' => 'program_code',
            'program_id' => 'program_code',
            'name' => 'program_name',
        ],
        'maintenance_subprograms' => [
            'id' => 'subprogram_code',
            'subprogram_id' => 'subprogram_code',
            'name' => 'subprogram_name',
        ],
        default => ['id' => 'id', 'name' => 'name'],
    };
}

function maintenance_sort_key_canonical(string $module, string $sortBy): string
{
    $sortBy = trim($sortBy);
    if ($sortBy === '') {
        return maintenance_default_sort_key($module);
    }
    $legacy = maintenance_sort_key_legacy_map($module);
    if (isset($legacy[$sortBy])) {
        $sortBy = $legacy[$sortBy];
    }
    $allowed = maintenance_list_sort_keys($module);
    return in_array($sortBy, $allowed, true) ? $sortBy : maintenance_default_sort_key($module);
}

/**
 * Contingut HTML d’una cel·la de dades (sense <td>).
 *
 * @param array<string, mixed> $row
 */
function maintenance_column_cell_html(array $colDef, array $row): string
{
    $cell = $colDef['cell'];
    $type = (string) ($cell['type'] ?? 'text');
    $field = (string) ($cell['field'] ?? '');
    if ($field === '' || $field === '_unknown' || $field === '_empty') {
        return '';
    }
    $raw = $row[$field] ?? '';

    if ($type === 'padded_id') {
        $pad = (int) ($cell['pad'] ?? 2);
        $n = (int) $raw;
        $inner = e(format_padded_code($n, $pad));
        if (!empty($cell['strong'])) {
            return '<strong>' . $inner . '</strong>';
        }
        return $inner;
    }

    if ($type === 'lpad_digits_varchar') {
        $pad = max(1, min(20, (int) ($cell['pad'] ?? 3)));
        $t = trim((string) $raw);
        if ($t === '') {
            return '';
        }
        if (preg_match('/^\d+$/', $t) && strlen($t) <= $pad) {
            $inner = e(str_pad($t, $pad, '0', STR_PAD_LEFT));
        } else {
            $inner = e($t);
        }
        if (!empty($cell['strong'])) {
            return '<strong>' . $inner . '</strong>';
        }
        return $inner;
    }

    if ($type === 'raw_id') {
        $n = (int) $raw;
        $inner = e((string) $n);
        if (!empty($cell['strong'])) {
            return '<strong>' . $inner . '</strong>';
        }
        return $inner;
    }

    if ($type === 'job_code_dot') {
        $inner = e(maintenance_format_job_position_code_display((string) $raw));

        return $inner === '' ? '' : $inner;
    }

    if ($type === 'subprogram_id_display') {
        $s = trim((string) $raw);
        if ($s === '') {
            return '';
        }
        $shown = maintenance_format_subprogram_id_display($s);
        $inner = e($shown);
        if (!empty($cell['strong'])) {
            $inner = '<strong>' . $inner . '</strong>';
        }

        return '<span title="' . e($s) . '">' . $inner . '</span>';
    }

    if ($type === 'program_description') {
        $s = (string) $raw;
        if ($s === '') {
            return '';
        }
        $cls = trim((string) ($cell['class'] ?? 'table-cell--wrap'));

        return '<span class="' . e($cls) . '">' . e($s) . '</span>';
    }

    if ($type === 'bool_si_no') {
        $v = (int) $raw;

        return e($v === 1 ? 'Sí' : 'No');
    }

    if ($type === 'bool_si_no_chip') {
        $v = (int) $raw;
        $lbl = $v === 1 ? 'Sí' : 'No';
        $cls = $v === 1 ? 'maintenance-chip maintenance-chip--bool maintenance-chip--bool-yes' : 'maintenance-chip maintenance-chip--bool maintenance-chip--bool-no';

        return '<span class="' . e($cls) . '" title="' . e($lbl) . '"><span class="maintenance-chip__text">' . e($lbl) . '</span></span>';
    }

    if ($type === 'text_truncate') {
        $s = (string) $raw;
        $tcls = trim('table__text-truncate ' . (string) ($cell['truncate_class'] ?? ''));
        $inner = e($s);
        if (!empty($cell['strong'])) {
            $inner = '<strong>' . $inner . '</strong>';
        }

        return '<span class="' . e($tcls) . '" title="' . e($s) . '">' . $inner . '</span>';
    }

    if ($type === 'text') {
        $s = (string) $raw;
        $inner = e($s);
        if (!empty($cell['strong'])) {
            $inner = '<strong>' . $inner . '</strong>';
        }

        return $inner;
    }

    return e((string) $raw);
}

function maintenance_table_data_column_count(string $module, bool $implemented): int
{
    return count(maintenance_table_columns($module, $implemented));
}

/**
 * Camp qualificat amb àlies de taula per a la clàusula WHERE de cerca (q).
 */
function maintenance_search_qualified_field(string $module, string $field): ?string
{
    if ($field === '' || $field === '_empty') {
        return null;
    }
    if ($module === 'maintenance_scales') {
        return 't.' . $field;
    }
    if ($module === 'maintenance_subscales') {
        return match ($field) {
            'scale_name' => 's.scale_name',
            default => 't.' . $field,
        };
    }
    if ($module === 'maintenance_categories') {
        return match ($field) {
            'scale_name' => 's.scale_name',
            'subscale_name' => 'ss.subscale_name',
            'class_name' => 'c.class_name',
            default => 't.' . $field,
        };
    }
    if ($module === 'maintenance_classes') {
        return match ($field) {
            'scale_name' => 's.scale_name',
            'subscale_name' => 'ss.subscale_name',
            default => 't.' . $field,
        };
    }
    if ($module === 'maintenance_access_types') {
        return 't.' . $field;
    }
    if ($module === 'maintenance_position_classes') {
        return 't.' . $field;
    }
    if ($module === 'maintenance_legal_relationships') {
        return 't.' . $field;
    }
    if ($module === 'maintenance_administrative_statuses') {
        return 't.' . $field;
    }
    if ($module === 'maintenance_access_systems') {
        return 't.' . $field;
    }
    if ($module === 'maintenance_work_centers') {
        return 't.' . $field;
    }
    if ($module === 'maintenance_availability_types') {
        return 't.' . $field;
    }
    if ($module === 'maintenance_provision_forms') {
        return 't.' . $field;
    }
    if ($module === 'maintenance_organic_level_1') {
        return 't.' . $field;
    }
    if ($module === 'maintenance_organic_level_2') {
        return match ($field) {
            'org_unit_level_1_name' => 'o1.org_unit_level_1_name',
            default => 't.' . $field,
        };
    }
    if ($module === 'maintenance_organic_level_3') {
        return match ($field) {
            'org_unit_level_2_name' => 'o2.org_unit_level_2_name',
            default => 't.' . $field,
        };
    }

    return null;
}

/**
 * Especificacions de cerca (sense placeholders) derivades de les columnes de llistat.
 *
 * @return list<array<int|string,mixed>>
 */
function maintenance_search_specs_from_columns(string $module): array
{
    if (!in_array($module, ['maintenance_scales', 'maintenance_subscales', 'maintenance_categories', 'maintenance_classes', 'maintenance_administrative_statuses', 'maintenance_position_classes', 'maintenance_legal_relationships', 'maintenance_access_types', 'maintenance_access_systems', 'maintenance_work_centers', 'maintenance_availability_types', 'maintenance_provision_forms', 'maintenance_organic_level_1', 'maintenance_organic_level_2', 'maintenance_organic_level_3'], true)) {
        return [];
    }
    $cols = maintenance_table_columns($module, true);
    $seen = [];
    $specs = [];
    foreach ($cols as $col) {
        $cell = $col['cell'];
        $field = (string) ($cell['field'] ?? '');
        $type = (string) ($cell['type'] ?? 'text');
        $qual = maintenance_search_qualified_field($module, $field);
        if ($qual === null) {
            continue;
        }
        if ($type === 'text') {
            $key = 't|' . $qual;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $specs[] = ['text', $qual];
        } elseif ($type === 'padded_id') {
            $pad = max(1, min(20, (int) ($cell['pad'] ?? 2)));
            $key = 'p|' . $qual . '|' . $pad;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $specs[] = ['padded', $qual, $pad];
        } elseif ($type === 'raw_id') {
            $searchPad = isset($cell['search_pad']) ? max(0, min(20, (int) $cell['search_pad'])) : 0;
            $key = 'r|' . $qual . '|' . $searchPad;
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $specs[] = ['raw', $qual, $searchPad];
        }
    }

    return $specs;
}

/**
 * Cerca específica per programes (subfunció, número, codi calculat, nom, responsable formatat, títol lloc, descripció).
 *
 * @return array{sql:string, params:array<string, string>}
 */
function maintenance_programs_list_q_search_clause(string $q): array
{
    $q = trim($q);
    if ($q === '') {
        return ['sql' => '', 'params' => []];
    }
    $qLike = '%' . $q . '%';
    $qNoDot = str_replace('.', '', $q);
    $qNoDotLike = '%' . $qNoDot . '%';
    $parts = [];
    $params = [];
    $i = 0;
    $sfPad = MAINTENANCE_PROGRAM_SUBFUNCTION_PAD;
    $n = 'mq_' . $i++;
    $n2 = 'mq_' . $i++;
    $parts[] = '(TRIM(t.subfunction_id) LIKE :' . $n . ' OR LPAD(TRIM(t.subfunction_id), ' . $sfPad . ", '0') LIKE :" . $n2 . ')';
    $params[$n] = $qLike;
    $params[$n2] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(CAST(t.program_number AS CHAR) LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(t.program_id LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(CONCAT(LPAD(TRIM(t.subfunction_id), ' . $sfPad . ", '0'), CAST(t.program_number AS CHAR)) LIKE :" . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(t.program_name LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(t.responsible_person_code LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(CAST(t.responsible_person_code AS CHAR) LIKE :' . $n . ')';
    $params[$n] = $qNoDotLike;
    $n = 'mq_' . $i++;
    $parts[] = '(CONCAT(LEFT(LPAD(CAST(CAST(NULLIF(TRIM(t.responsible_person_code), \'\') AS UNSIGNED) AS CHAR), 6, \'0\'), 4), \'.\', RIGHT(LPAD(CAST(CAST(NULLIF(TRIM(t.responsible_person_code), \'\') AS UNSIGNED) AS CHAR), 6, \'0\'), 2)) LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(jp.job_title LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(t.description LIKE :' . $n . ')';
    $params[$n] = $qLike;

    return [
        'sql' => ' AND (' . implode(' OR ', $parts) . ')',
        'params' => $params,
    ];
}

/**
 * Cerca de subprogrames: programa, número (amb LPAD), codi, noms, responsables formatats, naturalesa, booleans.
 *
 * @return array{sql:string, params:array<string, string>}
 */
function maintenance_subprograms_list_q_search_clause(string $q): array
{
    $q = trim($q);
    if ($q === '') {
        return ['sql' => '', 'params' => []];
    }
    $qLike = '%' . $q . '%';
    $qNoDot = str_replace('.', '', $q);
    $qNoDotLike = '%' . $qNoDot . '%';
    $parts = [];
    $params = [];
    $i = 0;
    $nPad = MAINTENANCE_SUBPROGRAM_NUMBER_PAD;

    $n = 'mq_' . $i++;
    $parts[] = '(TRIM(t.program_id) LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(p.program_name LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $n2 = 'mq_' . $i++;
    $parts[] = '(TRIM(t.subprogram_number) LIKE :' . $n . ' OR LPAD(TRIM(t.subprogram_number), ' . $nPad . ", '0') LIKE :" . $n2 . ')';
    $params[$n] = $qLike;
    $params[$n2] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(t.subprogram_id LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(t.subprogram_name LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(TRIM(t.technical_manager_code) LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(CAST(t.technical_manager_code AS CHAR) LIKE :' . $n . ')';
    $params[$n] = $qNoDotLike;
    $n = 'mq_' . $i++;
    $parts[] = '(CONCAT(LEFT(LPAD(CAST(CAST(NULLIF(TRIM(t.technical_manager_code), \'\') AS UNSIGNED) AS CHAR), 6, \'0\'), 4), \'.\', RIGHT(LPAD(CAST(CAST(NULLIF(TRIM(t.technical_manager_code), \'\') AS UNSIGNED) AS CHAR), 6, \'0\'), 2)) LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(jp_t.job_title LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(TRIM(t.elected_manager_code) LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(CAST(t.elected_manager_code AS CHAR) LIKE :' . $n . ')';
    $params[$n] = $qNoDotLike;
    $n = 'mq_' . $i++;
    $parts[] = '(CONCAT(LEFT(LPAD(CAST(CAST(NULLIF(TRIM(t.elected_manager_code), \'\') AS UNSIGNED) AS CHAR), 6, \'0\'), 4), \'.\', RIGHT(LPAD(CAST(CAST(NULLIF(TRIM(t.elected_manager_code), \'\') AS UNSIGNED) AS CHAR), 6, \'0\'), 2)) LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(jp_e.job_title LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(t.nature LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(CAST(t.is_mandatory_service AS CHAR) LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(CASE WHEN t.is_mandatory_service = 1 THEN \'Sí\' ELSE \'No\' END LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(CAST(t.has_corporate_agreements AS CHAR) LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n = 'mq_' . $i++;
    $parts[] = '(CASE WHEN t.has_corporate_agreements = 1 THEN \'Sí\' ELSE \'No\' END LIKE :' . $n . ')';
    $params[$n] = $qLike;

    return [
        'sql' => ' AND (' . implode(' OR ', $parts) . ')',
        'params' => $params,
    ];
}

/**
 * Fragment SQL AND (...) per cerca global q, o cadena buida si no aplica.
 * Cada LIKE/LPAD usa un placeholder PDO únic (compatibilitat sense emulació de prepared statements).
 *
 * @return array{sql:string, params:array<string, string>}
 */
function maintenance_list_q_search_clause(string $module, string $q): array
{
    $q = trim($q);
    if ($q === '') {
        return ['sql' => '', 'params' => []];
    }
    if ($module === 'maintenance_programs') {
        return maintenance_programs_list_q_search_clause($q);
    }
    if ($module === 'maintenance_subprograms') {
        return maintenance_subprograms_list_q_search_clause($q);
    }
    $specs = maintenance_search_specs_from_columns($module);
    if ($specs === []) {
        return ['sql' => '', 'params' => []];
    }

    $qLike = '%' . $q . '%';
    $parts = [];
    $params = [];
    $i = 0;
    foreach ($specs as $spec) {
        $kind = $spec[0];
        if ($kind === 'text') {
            $qual = $spec[1];
            $name = 'mq_' . $i++;
            $parts[] = '(' . $qual . ' LIKE :' . $name . ')';
            $params[$name] = $qLike;
        } elseif ($kind === 'padded') {
            $qual = $spec[1];
            $pad = (int) $spec[2];
            $n1 = 'mq_' . $i++;
            $n2 = 'mq_' . $i++;
            $parts[] = '(CAST(' . $qual . ' AS CHAR) LIKE :' . $n1 . ' OR LPAD(CAST(' . $qual . ' AS CHAR), ' . $pad . ", '0') LIKE :" . $n2 . ')';
            $params[$n1] = $qLike;
            $params[$n2] = $qLike;
        } elseif ($kind === 'raw') {
            $qual = $spec[1];
            $searchPad = (int) $spec[2];
            if ($searchPad > 0) {
                $n1 = 'mq_' . $i++;
                $n2 = 'mq_' . $i++;
                $parts[] = '(CAST(' . $qual . ' AS CHAR) LIKE :' . $n1 . ' OR LPAD(CAST(' . $qual . ' AS CHAR), ' . $searchPad . ", '0') LIKE :" . $n2 . ')';
                $params[$n1] = $qLike;
                $params[$n2] = $qLike;
            } else {
                $n1 = 'mq_' . $i++;
                $parts[] = '(CAST(' . $qual . ' AS CHAR) LIKE :' . $n1 . ')';
                $params[$n1] = $qLike;
            }
        }
    }

    if ($parts === []) {
        return ['sql' => '', 'params' => []];
    }

    return [
        'sql' => ' AND (' . implode(' OR ', $parts) . ')',
        'params' => $params,
    ];
}
