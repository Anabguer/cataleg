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
/** Longitud canònica del CCC d'empresa (només dígits). */
const MAINTENANCE_COMPANY_CCC_DIGITS = 11;
/** Amplada de visualització de l'epígraf de cotització (3 dígits). */
const MAINTENANCE_SOCIAL_SECURITY_EPIGRAPH_PAD = 3;
/** Amplada de visualització del grup de cotització de bases SS (2 dígits). */
const MAINTENANCE_SOCIAL_SECURITY_BASE_GROUP_PAD = 2;

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
 * Neteja el CCC deixant només dígits.
 */
function maintenance_company_ccc_digits_only(?string $ccc): string
{
    $s = trim((string) $ccc);
    if ($s === '') {
        return '';
    }

    return preg_replace('/\D+/', '', $s) ?? '';
}

/**
 * Mascara de visualització del CCC: `00 0000000 00`.
 */
function maintenance_format_company_ccc_display(?string $ccc): string
{
    $digits = maintenance_company_ccc_digits_only($ccc);
    if ($digits === '') {
        return '';
    }
    if (strlen($digits) !== MAINTENANCE_COMPANY_CCC_DIGITS) {
        return trim((string) $ccc);
    }

    return substr($digits, 0, 2) . ' ' . substr($digits, 2, 7) . ' ' . substr($digits, 9, 2);
}

/**
 * Visualització de decimals amb 4 xifres.
 */
function maintenance_format_decimal_4_display(mixed $value): string
{
    if ($value === null) {
        return '';
    }
    $s = trim((string) $value);
    if ($s === '') {
        return '';
    }
    if (!is_numeric($s)) {
        return $s;
    }

    return number_format((float) $s, 4, '.', '');
}

/**
 * Mostra imports com a moneda europea amb 2 decimals i símbol d'euro a la dreta.
 */
function maintenance_format_currency_eur_2_display(mixed $value): string
{
    if ($value === null) {
        return '';
    }
    $s = trim((string) $value);
    if ($s === '' || !is_numeric($s)) {
        return '';
    }

    return number_format((float) $s, 2, ',', '.') . ' €';
}

/**
 * Coeficients SS (llistat): percentatge tipus Access — valor BBDD × 100, 4 decimals, coma decimal, símbol %.
 */
function maintenance_format_ss_coeff_percent_display(mixed $dbFraction): string
{
    if ($dbFraction === null) {
        return '';
    }
    $s = trim((string) $dbFraction);
    if ($s === '') {
        return '';
    }
    if (!is_numeric($s)) {
        return '';
    }

    return number_format((float) $s * 100.0, 4, ',', '') . '%';
}

/**
 * Parseja un camp opcional de percentatge visible (coma/punt, % opcional) cap al decimal real emmagatzemat (÷100, arrodonit a 6 decimals de fracció).
 *
 * @return array{ok:true, value:?string}|array{ok:false, error:string}
 */
function maintenance_parse_ss_coeff_visible_percent_field(string $raw): array
{
    $t = trim($raw);
    if ($t === '') {
        return ['ok' => true, 'value' => null];
    }
    $t = str_replace([' ', "\t", "\n", "\r"], '', $t);
    $t = str_replace('%', '', $t);
    $t = str_replace(',', '.', $t);
    if ($t === '') {
        return ['ok' => true, 'value' => null];
    }
    if (preg_match('/[^0-9.]/', $t)) {
        return ['ok' => false, 'error' => 'Només es permeten xifres, coma o punt i, opcionalment, el símbol %.'];
    }
    if (!preg_match('/^\d+$/', $t) && !preg_match('/^\d+\.\d{1,4}$/', $t)) {
        return ['ok' => false, 'error' => 'Valor numèric invàlid (màxim 4 decimals al percentatge visible).'];
    }
    $pct = (float) $t;
    if (!is_finite($pct)) {
        return ['ok' => false, 'error' => 'Valor numèric invàlid.'];
    }
    if ($pct < 0.0 || $pct > 100.0) {
        return ['ok' => false, 'error' => 'El percentatge ha d’estar entre 0 i 100.'];
    }
    $dec = round($pct / 100.0, 6);

    return ['ok' => true, 'value' => number_format($dec, 6, '.', '')];
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
        'maintenance_social_security_companies' => ['company_id', 'company_description', 'contribution_account_code'],
        'maintenance_social_security_coefficients' => ['contribution_epigraph_id', 'company_1', 'company_2', 'company_3', 'company_4', 'company_5a', 'company_5b', 'company_5c', 'company_5d', 'company_5e', 'temporary_employment_company'],
        'maintenance_social_security_base_limits' => ['contribution_group_id', 'contribution_group_description', 'minimum_base', 'maximum_base', 'period_label'],
        'maintenance_salary_base_by_group' => ['classification_group', 'base_salary', 'base_salary_extra_pay', 'base_salary_new', 'base_salary_extra_pay_new'],
        'maintenance_destination_allowances' => ['organic_level', 'destination_allowance', 'destination_allowance_new'],
        'maintenance_seniority_pay_by_group' => ['classification_group', 'seniority_amount', 'seniority_extra_pay_amount', 'seniority_amount_new', 'seniority_extra_pay_amount_new'],
        'maintenance_specific_compensation_special_prices' => ['special_specific_compensation_id', 'special_specific_compensation_name', 'amount', 'amount_new'],
        'maintenance_specific_compensation_general' => ['general_specific_compensation_id', 'general_specific_compensation_name', 'amount', 'decrease_amount', 'amount_new', 'decrease_amount_new'],
        'maintenance_personal_transitory_bonus' => ['last_name_1', 'last_name_2', 'first_name', 'personal_transitory_bonus', 'personal_transitory_bonus_new'],
        'people' => ['person_id', 'last_name_1', 'last_name_2', 'first_name', 'national_id_number', 'email', 'job_position_id', 'position_id', 'legal_relation_name', 'is_active'],
        'management_positions' => ['position_id', 'position_name', 'position_class_name', 'scale_name', 'subscale_name', 'class_name', 'category_name', 'is_active'],
        'job_positions' => ['job_position_id', 'job_title', 'org_dependency_id', 'scale_name', 'legal_relation_name', 'is_active', 'is_to_be_amortized'],
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
    $sortList = $implemented && in_array($module, ['maintenance_scales', 'maintenance_subscales', 'maintenance_categories', 'maintenance_classes', 'maintenance_administrative_statuses', 'maintenance_position_classes', 'maintenance_legal_relationships', 'maintenance_access_types', 'maintenance_access_systems', 'maintenance_work_centers', 'maintenance_availability_types', 'maintenance_provision_forms', 'maintenance_organic_level_1', 'maintenance_organic_level_2', 'maintenance_organic_level_3', 'maintenance_programs', 'maintenance_social_security_companies', 'maintenance_social_security_coefficients', 'maintenance_social_security_base_limits', 'maintenance_salary_base_by_group', 'maintenance_destination_allowances', 'maintenance_seniority_pay_by_group', 'maintenance_specific_compensation_special_prices', 'maintenance_specific_compensation_general', 'maintenance_personal_transitory_bonus', 'people', 'management_positions', 'job_positions', 'maintenance_subprograms'], true);

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
    if ($module === 'maintenance_social_security_companies') {
        return [
            ['sort_key' => 'company_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'company_id', 'strong' => true]],
            ['sort_key' => 'company_description', 'label' => 'Denominació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'company_description']],
            ['sort_key' => 'contribution_account_code', 'label' => 'CCC núm.', 'sortable' => $sortList, 'cell' => ['type' => 'company_ccc_display', 'field' => 'contribution_account_code']],
        ];
    }
    if ($module === 'maintenance_social_security_coefficients') {
        return [
            ['sort_key' => 'contribution_epigraph_id', 'label' => 'Epígraf', 'sortable' => $sortList, 'cell' => ['type' => 'lpad_digits_varchar', 'field' => 'contribution_epigraph_id', 'pad' => MAINTENANCE_SOCIAL_SECURITY_EPIGRAPH_PAD, 'strong' => true]],
            ['sort_key' => 'company_1', 'label' => 'Emp. 1', 'sortable' => $sortList, 'cell' => ['type' => 'percent_4', 'field' => 'company_1', 'align' => 'right']],
            ['sort_key' => 'company_2', 'label' => 'Emp. 2', 'sortable' => $sortList, 'cell' => ['type' => 'percent_4', 'field' => 'company_2', 'align' => 'right']],
            ['sort_key' => 'company_3', 'label' => 'Emp. 3', 'sortable' => $sortList, 'cell' => ['type' => 'percent_4', 'field' => 'company_3', 'align' => 'right']],
            ['sort_key' => 'company_4', 'label' => 'Emp. 4', 'sortable' => $sortList, 'cell' => ['type' => 'percent_4', 'field' => 'company_4', 'align' => 'right']],
            ['sort_key' => 'company_5a', 'label' => 'Emp. 5A', 'sortable' => $sortList, 'cell' => ['type' => 'percent_4', 'field' => 'company_5a', 'align' => 'right']],
            ['sort_key' => 'company_5b', 'label' => 'Emp. 5B', 'sortable' => $sortList, 'cell' => ['type' => 'percent_4', 'field' => 'company_5b', 'align' => 'right']],
            ['sort_key' => 'company_5c', 'label' => 'Emp. 5C', 'sortable' => $sortList, 'cell' => ['type' => 'percent_4', 'field' => 'company_5c', 'align' => 'right']],
            ['sort_key' => 'company_5d', 'label' => 'Emp. 5D', 'sortable' => $sortList, 'cell' => ['type' => 'percent_4', 'field' => 'company_5d', 'align' => 'right']],
            ['sort_key' => 'company_5e', 'label' => 'Emp. 5E', 'sortable' => $sortList, 'cell' => ['type' => 'percent_4', 'field' => 'company_5e', 'align' => 'right']],
            ['sort_key' => 'temporary_employment_company', 'label' => 'Emp. E.T.', 'sortable' => $sortList, 'cell' => ['type' => 'percent_4', 'field' => 'temporary_employment_company', 'align' => 'right']],
        ];
    }
    if ($module === 'maintenance_social_security_base_limits') {
        return [
            ['sort_key' => 'contribution_group_id', 'label' => 'Grup. Cot.', 'sortable' => $sortList, 'cell' => ['type' => 'lpad_digits_varchar', 'field' => 'contribution_group_id', 'pad' => MAINTENANCE_SOCIAL_SECURITY_BASE_GROUP_PAD, 'strong' => true]],
            ['sort_key' => 'contribution_group_description', 'label' => 'Denominació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'contribution_group_description']],
            ['sort_key' => 'minimum_base', 'label' => 'Base mínima', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'minimum_base', 'align' => 'right']],
            ['sort_key' => 'maximum_base', 'label' => 'Base màxima', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'maximum_base', 'align' => 'right']],
            ['sort_key' => 'period_label', 'label' => 'Període', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'period_label']],
        ];
    }
    if ($module === 'maintenance_salary_base_by_group') {
        return [
            ['sort_key' => 'classification_group', 'label' => 'Grup classificació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'classification_group', 'strong' => true]],
            ['sort_key' => 'base_salary', 'label' => 'Sou base', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'base_salary', 'align' => 'right']],
            ['sort_key' => 'base_salary_extra_pay', 'label' => 'Sou base afectació pagues', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'base_salary_extra_pay', 'align' => 'right']],
            ['sort_key' => 'base_salary_new', 'label' => 'Sou base incrementat', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'base_salary_new', 'align' => 'right']],
            ['sort_key' => 'base_salary_extra_pay_new', 'label' => 'Sou base afectació pagues incrementat', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'base_salary_extra_pay_new', 'align' => 'right']],
        ];
    }
    if ($module === 'maintenance_destination_allowances') {
        return [
            ['sort_key' => 'organic_level', 'label' => 'Nivell orgànic', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'organic_level', 'strong' => true]],
            ['sort_key' => 'destination_allowance', 'label' => 'Complement destinació', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'destination_allowance', 'align' => 'right']],
            ['sort_key' => 'destination_allowance_new', 'label' => 'Complement destinació incrementat', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'destination_allowance_new', 'align' => 'right']],
        ];
    }
    if ($module === 'maintenance_seniority_pay_by_group') {
        return [
            ['sort_key' => 'classification_group', 'label' => 'Grup classificació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'classification_group', 'strong' => true]],
            ['sort_key' => 'seniority_amount', 'label' => 'Trienni', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'seniority_amount', 'align' => 'right']],
            ['sort_key' => 'seniority_extra_pay_amount', 'label' => 'Trienni afectació pagues', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'seniority_extra_pay_amount', 'align' => 'right']],
            ['sort_key' => 'seniority_amount_new', 'label' => 'Trienni incrementat', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'seniority_amount_new', 'align' => 'right']],
            ['sort_key' => 'seniority_extra_pay_amount_new', 'label' => 'Trienni afectació pagues incrementat', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'seniority_extra_pay_amount_new', 'align' => 'right']],
        ];
    }
    if ($module === 'maintenance_specific_compensation_special_prices') {
        return [
            ['sort_key' => 'special_specific_compensation_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'special_specific_compensation_id', 'strong' => true]],
            ['sort_key' => 'special_specific_compensation_name', 'label' => 'Denominació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'special_specific_compensation_name']],
            ['sort_key' => 'amount', 'label' => 'Complement específic especial', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'amount', 'align' => 'right']],
            ['sort_key' => 'amount_new', 'label' => 'Complement específic especial incrementat', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'amount_new', 'align' => 'right']],
        ];
    }
    if ($module === 'maintenance_specific_compensation_general') {
        return [
            ['sort_key' => 'general_specific_compensation_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'general_specific_compensation_id', 'strong' => true]],
            ['sort_key' => 'general_specific_compensation_name', 'label' => 'Descripció Complement', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'general_specific_compensation_name']],
            ['sort_key' => 'amount', 'label' => 'Import complement', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'amount', 'align' => 'right']],
            ['sort_key' => 'decrease_amount', 'label' => 'Import de la disminució Complement Específic de Agents i Caporals (C2-C1)', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'decrease_amount', 'align' => 'right', 'header_class' => 'col-long-text']],
            ['sort_key' => 'amount_new', 'label' => 'Import complement incrementat', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'amount_new', 'align' => 'right']],
            ['sort_key' => 'decrease_amount_new', 'label' => 'Import de la disminució Complement Específic de Agents i Caporals (C2-C1) incrementat', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'decrease_amount_new', 'align' => 'right', 'header_class' => 'col-long-text']],
        ];
    }
    if ($module === 'maintenance_personal_transitory_bonus') {
        return [
            ['sort_key' => 'last_name_1', 'label' => 'Persona', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'person_display_name', 'class' => 'maintenance-ptb-col--name', 'header_class' => 'maintenance-ptb-col--name']],
            ['sort_key' => 'personal_transitory_bonus', 'label' => 'CPT', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'personal_transitory_bonus', 'align' => 'right', 'class' => 'maintenance-ptb-col--amt', 'header_class' => 'maintenance-ptb-col--amt']],
            ['sort_key' => 'personal_transitory_bonus_new', 'label' => 'CPT incrementat', 'sortable' => $sortList, 'cell' => ['type' => 'currency_eur_2', 'field' => 'personal_transitory_bonus_new', 'align' => 'right', 'class' => 'maintenance-ptb-col--new', 'header_class' => 'maintenance-ptb-col--new']],
        ];
    }
    if ($module === 'people') {
        return [
            ['sort_key' => 'person_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'padded_id', 'field' => 'person_id', 'pad' => 5, 'strong' => true]],
            ['sort_key' => 'last_name_1', 'label' => '1r Cognom', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'last_name_1']],
            ['sort_key' => 'last_name_2', 'label' => '2n Cognom', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'last_name_2']],
            ['sort_key' => 'first_name', 'label' => 'Nom', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'first_name']],
            ['sort_key' => 'national_id_number', 'label' => 'DNI', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'national_id_number']],
            ['sort_key' => 'email', 'label' => 'Email', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'email']],
            ['sort_key' => 'job_position_id', 'label' => 'Lloc de treball', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'job_position_name']],
            ['sort_key' => 'position_id', 'label' => 'Plaça', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'position_name']],
            ['sort_key' => 'legal_relation_name', 'label' => 'Relació jurídica', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'legal_relation_name']],
            ['sort_key' => 'is_active', 'label' => 'Activa', 'sortable' => $sortList, 'cell' => ['type' => 'bool_si_no_chip', 'field' => 'is_active', 'align' => 'center']],
        ];
    }
    if ($module === 'management_positions') {
        return [
            ['sort_key' => 'position_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'padded_id', 'field' => 'position_id', 'pad' => 4, 'strong' => true]],
            ['sort_key' => 'position_name', 'label' => 'Denominació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'position_name']],
            ['sort_key' => 'position_class_name', 'label' => 'Classe de plaça', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'position_class_name']],
            ['sort_key' => 'scale_name', 'label' => 'Escala', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'scale_name']],
            ['sort_key' => 'subscale_name', 'label' => 'Subescala', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'subscale_name']],
            ['sort_key' => 'class_name', 'label' => 'Classe', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'class_name']],
            ['sort_key' => 'category_name', 'label' => 'Categoria', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'category_name']],
            ['sort_key' => 'is_active', 'label' => 'Activa', 'sortable' => $sortList, 'cell' => ['type' => 'bool_si_no_chip', 'field' => 'is_active', 'align' => 'center']],
        ];
    }
    if ($module === 'job_positions') {
        return [
            ['sort_key' => 'job_position_id', 'label' => 'Codi', 'sortable' => $sortList, 'cell' => ['type' => 'job_code_dot', 'field' => 'job_position_id', 'strong' => true]],
            ['sort_key' => 'job_title', 'label' => 'Denominació', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'job_title']],
            ['sort_key' => 'org_dependency_id', 'label' => 'Responsable', 'sortable' => $sortList, 'cell' => ['type' => 'job_position_cm_line', 'code_field' => 'responsible_job_code_raw', 'title_field' => 'responsible_job_title']],
            ['sort_key' => 'scale_name', 'label' => 'Escala', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'scale_name']],
            ['sort_key' => 'legal_relation_name', 'label' => 'Relació jurídica', 'sortable' => $sortList, 'cell' => ['type' => 'text', 'field' => 'legal_relation_name']],
            ['sort_key' => 'is_active', 'label' => 'Actiu', 'sortable' => $sortList, 'cell' => ['type' => 'bool_si_no_chip', 'field' => 'is_active', 'align' => 'center']],
            ['sort_key' => 'is_to_be_amortized', 'label' => 'Amortitzat', 'sortable' => $sortList, 'cell' => ['type' => 'bool_si_no_chip', 'field' => 'is_to_be_amortized', 'align' => 'center']],
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
        'maintenance_social_security_companies' => 'company_id',
        'maintenance_social_security_coefficients' => 'contribution_epigraph_id',
        'maintenance_social_security_base_limits' => 'contribution_group_id',
        'maintenance_salary_base_by_group' => 'classification_group',
        'maintenance_destination_allowances' => 'organic_level',
        'maintenance_seniority_pay_by_group' => 'classification_group',
        'maintenance_specific_compensation_special_prices' => 'special_specific_compensation_id',
        'maintenance_specific_compensation_general' => 'general_specific_compensation_id',
        'maintenance_personal_transitory_bonus' => 'last_name_1',
        'people' => 'person_id',
        'management_positions' => 'position_id',
        'job_positions' => 'job_position_id',
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
        'maintenance_social_security_companies' => [
            'id' => 'company_id',
            'name' => 'company_description',
        ],
        'maintenance_social_security_coefficients' => [
            'id' => 'contribution_epigraph_id',
        ],
        'maintenance_social_security_base_limits' => [
            'id' => 'contribution_group_id',
            'name' => 'contribution_group_description',
        ],
        'maintenance_salary_base_by_group' => [
            'id' => 'classification_group',
            'name' => 'classification_group',
        ],
        'maintenance_destination_allowances' => [
            'id' => 'organic_level',
            'name' => 'organic_level',
        ],
        'maintenance_seniority_pay_by_group' => [
            'id' => 'classification_group',
            'name' => 'classification_group',
        ],
        'maintenance_specific_compensation_special_prices' => [
            'id' => 'special_specific_compensation_id',
            'name' => 'special_specific_compensation_name',
        ],
        'maintenance_specific_compensation_general' => [
            'id' => 'general_specific_compensation_id',
            'name' => 'general_specific_compensation_name',
        ],
        'maintenance_personal_transitory_bonus' => [
            'name' => 'last_name_1',
        ],
        'people' => [
            'id' => 'person_id',
            'name' => 'last_name_1',
        ],
        'management_positions' => [
            'id' => 'position_id',
            'name' => 'position_name',
        ],
        'job_positions' => [
            'id' => 'job_position_id',
            'name' => 'job_title',
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

    if ($type === 'job_position_cm_line') {
        $cf = (string) ($cell['code_field'] ?? '');
        $tf = (string) ($cell['title_field'] ?? '');
        $codeRaw = trim((string) ($row[$cf] ?? ''));
        $titleRaw = trim((string) ($row[$tf] ?? ''));
        if ($codeRaw === '' && $titleRaw === '') {
            return '';
        }
        $codeShown = $codeRaw;
        if ($codeRaw !== '' && preg_match('/^\d{6}$/', $codeRaw) === 1) {
            $codeShown = maintenance_format_job_position_code_display($codeRaw);
        }
        $line = $codeShown !== '' ? ($codeShown . ' - ' . $titleRaw) : $titleRaw;

        return e($line);
    }

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

    if ($type === 'company_ccc_display') {
        $s = trim((string) $raw);
        if ($s === '') {
            return '';
        }
        $shown = maintenance_format_company_ccc_display($s);

        return '<span title="' . e($shown) . '">' . e($shown) . '</span>';
    }

    if ($type === 'decimal_4') {
        return e(maintenance_format_decimal_4_display($raw));
    }

    if ($type === 'percent_4') {
        return e(maintenance_format_ss_coeff_percent_display($raw));
    }

    if ($type === 'currency_eur_2') {
        return e(maintenance_format_currency_eur_2_display($raw));
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
    if ($module === 'maintenance_social_security_companies') {
        return 't.' . $field;
    }
    if ($module === 'maintenance_social_security_coefficients') {
        return 't.' . $field;
    }
    if ($module === 'maintenance_social_security_base_limits') {
        return 't.' . $field;
    }
    if ($module === 'maintenance_salary_base_by_group') {
        return 't.' . $field;
    }
    if ($module === 'maintenance_destination_allowances') {
        return 't.' . $field;
    }
    if ($module === 'maintenance_seniority_pay_by_group') {
        return 't.' . $field;
    }
    if ($module === 'maintenance_specific_compensation_special_prices') {
        return 't.' . $field;
    }
    if ($module === 'maintenance_specific_compensation_general') {
        return 't.' . $field;
    }
    if ($module === 'management_positions') {
        return match ($field) {
            'position_class_name' => 'pc.position_class_name',
            'scale_name' => 's.scale_name',
            'subscale_name' => 'ss.subscale_name',
            'class_name' => 'c.class_name',
            'category_name' => 'cat.category_name',
            default => 't.' . $field,
        };
    }
    if ($module === 'people') {
        return match ($field) {
            'job_position_name' => 'jp.job_title',
            'position_name' => 'pos.position_name',
            'legal_relation_name' => 'lr.legal_relation_name',
            default => 'p.' . $field,
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
    if (!in_array($module, ['maintenance_scales', 'maintenance_subscales', 'maintenance_categories', 'maintenance_classes', 'maintenance_administrative_statuses', 'maintenance_position_classes', 'maintenance_legal_relationships', 'maintenance_access_types', 'maintenance_access_systems', 'maintenance_work_centers', 'maintenance_availability_types', 'maintenance_provision_forms', 'maintenance_organic_level_1', 'maintenance_organic_level_2', 'maintenance_organic_level_3', 'maintenance_social_security_companies', 'maintenance_social_security_coefficients', 'maintenance_social_security_base_limits', 'maintenance_salary_base_by_group', 'maintenance_destination_allowances', 'maintenance_seniority_pay_by_group', 'maintenance_specific_compensation_special_prices', 'maintenance_specific_compensation_general', 'people', 'management_positions'], true)) {
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
 * Cerca d'empreses de Seguretat Social: codi, denominació i CCC amb/sense espais.
 *
 * @return array{sql:string, params:array<string, string>}
 */
function maintenance_social_security_companies_list_q_search_clause(string $q): array
{
    $q = trim($q);
    if ($q === '') {
        return ['sql' => '', 'params' => []];
    }
    $qLike = '%' . $q . '%';
    $qDigits = maintenance_company_ccc_digits_only($q);
    $qDigitsLike = '%' . $qDigits . '%';
    $parts = [];
    $params = [];
    $i = 0;

    $n = 'mq_' . $i++;
    $parts[] = '(t.company_id LIKE :' . $n . ')';
    $params[$n] = $qLike;

    $n = 'mq_' . $i++;
    $parts[] = '(t.company_description LIKE :' . $n . ')';
    $params[$n] = $qLike;

    $n = 'mq_' . $i++;
    $parts[] = '(t.contribution_account_code LIKE :' . $n . ')';
    $params[$n] = $qLike;

    $n = 'mq_' . $i++;
    $parts[] = '(REPLACE(t.contribution_account_code, \' \', \'\') LIKE :' . $n . ')';
    $params[$n] = $qDigits !== '' ? $qDigitsLike : $qLike;

    $n = 'mq_' . $i++;
    $parts[] = '(CONCAT(LEFT(REPLACE(t.contribution_account_code, \' \', \'\'), 2), \' \', SUBSTRING(REPLACE(t.contribution_account_code, \' \', \'\'), 3, 7), \' \', RIGHT(REPLACE(t.contribution_account_code, \' \', \'\'), 2)) LIKE :' . $n . ')';
    $params[$n] = $qLike;

    return [
        'sql' => ' AND (' . implode(' OR ', $parts) . ')',
        'params' => $params,
    ];
}

/**
 * Cerca de coeficients SS: epígraf real/padded i camps de coeficients.
 *
 * @return array{sql:string, params:array<string, string>}
 */
function maintenance_social_security_coefficients_list_q_search_clause(string $q): array
{
    $q = trim($q);
    if ($q === '') {
        return ['sql' => '', 'params' => []];
    }
    $qLike = '%' . $q . '%';
    $parts = [];
    $params = [];
    $i = 0;

    $n = 'mq_' . $i++;
    $n2 = 'mq_' . $i++;
    $parts[] = '(TRIM(t.contribution_epigraph_id) LIKE :' . $n . ' OR LPAD(TRIM(t.contribution_epigraph_id), ' . MAINTENANCE_SOCIAL_SECURITY_EPIGRAPH_PAD . ", '0') LIKE :" . $n2 . ')';
    $params[$n] = $qLike;
    $params[$n2] = $qLike;

    foreach (['company_1', 'company_2', 'company_3', 'company_4', 'company_5a', 'company_5b', 'company_5c', 'company_5d', 'company_5e', 'temporary_employment_company'] as $f) {
        $n = 'mq_' . $i++;
        $parts[] = '(CAST(t.' . $f . ' AS CHAR) LIKE :' . $n . ')';
        $params[$n] = $qLike;
    }

    return [
        'sql' => ' AND (' . implode(' OR ', $parts) . ')',
        'params' => $params,
    ];
}

/**
 * Cerca de bases mínimes/màximes SS: codi real/padded, denominació, bases i període.
 *
 * @return array{sql:string, params:array<string, string>}
 */
function maintenance_social_security_base_limits_list_q_search_clause(string $q): array
{
    $q = trim($q);
    if ($q === '') {
        return ['sql' => '', 'params' => []];
    }
    $qLike = '%' . $q . '%';
    $parts = [];
    $params = [];
    $i = 0;

    $n = 'mq_' . $i++;
    $n2 = 'mq_' . $i++;
    $parts[] = '(TRIM(t.contribution_group_id) LIKE :' . $n . ' OR LPAD(TRIM(t.contribution_group_id), ' . MAINTENANCE_SOCIAL_SECURITY_BASE_GROUP_PAD . ", '0') LIKE :" . $n2 . ')';
    $params[$n] = $qLike;
    $params[$n2] = $qLike;

    foreach (['contribution_group_description', 'minimum_base', 'maximum_base', 'period_label'] as $f) {
        $n = 'mq_' . $i++;
        $parts[] = '(CAST(t.' . $f . ' AS CHAR) LIKE :' . $n . ')';
        $params[$n] = $qLike;
    }

    return [
        'sql' => ' AND (' . implode(' OR ', $parts) . ')',
        'params' => $params,
    ];
}

/**
 * Cerca de sous per grup de classificació: grup i imports.
 *
 * @return array{sql:string, params:array<string, string>}
 */
function maintenance_salary_base_by_group_list_q_search_clause(string $q): array
{
    $q = trim($q);
    if ($q === '') {
        return ['sql' => '', 'params' => []];
    }
    $qLike = '%' . $q . '%';
    $parts = [];
    $params = [];
    $i = 0;
    foreach (['classification_group', 'base_salary', 'base_salary_extra_pay', 'base_salary_new', 'base_salary_extra_pay_new'] as $f) {
        $n = 'mq_' . $i++;
        $parts[] = '(CAST(t.' . $f . ' AS CHAR) LIKE :' . $n . ')';
        $params[$n] = $qLike;
    }

    return [
        'sql' => ' AND (' . implode(' OR ', $parts) . ')',
        'params' => $params,
    ];
}

/**
 * Cerca de complements de destinació: nivell orgànic i imports.
 *
 * @return array{sql:string, params:array<string, string>}
 */
function maintenance_destination_allowances_list_q_search_clause(string $q): array
{
    $q = trim($q);
    if ($q === '') {
        return ['sql' => '', 'params' => []];
    }
    $qLike = '%' . $q . '%';
    $parts = [];
    $params = [];
    $i = 0;
    foreach (['organic_level', 'destination_allowance', 'destination_allowance_new'] as $f) {
        $n = 'mq_' . $i++;
        $parts[] = '(CAST(t.' . $f . ' AS CHAR) LIKE :' . $n . ')';
        $params[$n] = $qLike;
    }

    return [
        'sql' => ' AND (' . implode(' OR ', $parts) . ')',
        'params' => $params,
    ];
}

/**
 * Cerca de triennis per grup i imports.
 *
 * @return array{sql:string, params:array<string, string>}
 */
function maintenance_seniority_pay_by_group_list_q_search_clause(string $q): array
{
    $q = trim($q);
    if ($q === '') {
        return ['sql' => '', 'params' => []];
    }
    $qLike = '%' . $q . '%';
    $parts = [];
    $params = [];
    $i = 0;
    foreach (['classification_group', 'seniority_amount', 'seniority_extra_pay_amount', 'seniority_amount_new', 'seniority_extra_pay_amount_new'] as $f) {
        $n = 'mq_' . $i++;
        $parts[] = '(CAST(t.' . $f . ' AS CHAR) LIKE :' . $n . ')';
        $params[$n] = $qLike;
    }

    return [
        'sql' => ' AND (' . implode(' OR ', $parts) . ')',
        'params' => $params,
    ];
}

/**
 * Cerca de preus de complement específic especial.
 *
 * @return array{sql:string, params:array<string, string>}
 */
function maintenance_specific_comp_special_prices_list_q_search_clause(string $q): array
{
    $q = trim($q);
    if ($q === '') {
        return ['sql' => '', 'params' => []];
    }
    $qLike = '%' . $q . '%';
    $parts = [];
    $params = [];
    $i = 0;
    foreach (['special_specific_compensation_id'] as $f) {
        $n = 'mq_' . $i++;
        $parts[] = '(CAST(t.' . $f . ' AS CHAR) LIKE :' . $n . ')';
        $params[$n] = $qLike;
    }
    foreach (['special_specific_compensation_name'] as $f) {
        $n = 'mq_' . $i++;
        $parts[] = '(CAST(t.' . $f . ' AS CHAR) LIKE :' . $n . ')';
        $params[$n] = $qLike;
    }
    foreach (['amount', 'amount_new'] as $f) {
        $n = 'mq_' . $i++;
        $parts[] = '(CAST(t.' . $f . ' AS CHAR) LIKE :' . $n . ')';
        $params[$n] = $qLike;
    }

    return [
        'sql' => ' AND (' . implode(' OR ', $parts) . ')',
        'params' => $params,
    ];
}

/**
 * Cerca de complement específic general.
 *
 * @return array{sql:string, params:array<string, string>}
 */
function maintenance_specific_comp_general_list_q_search_clause(string $q): array
{
    $q = trim($q);
    if ($q === '') {
        return ['sql' => '', 'params' => []];
    }
    $qLike = '%' . $q . '%';
    $parts = [];
    $params = [];
    $i = 0;
    foreach (['general_specific_compensation_id', 'general_specific_compensation_name', 'amount', 'decrease_amount', 'amount_new', 'decrease_amount_new'] as $f) {
        $n = 'mq_' . $i++;
        $parts[] = '(CAST(t.' . $f . ' AS CHAR) LIKE :' . $n . ')';
        $params[$n] = $qLike;
    }
    return [
        'sql' => ' AND (' . implode(' OR ', $parts) . ')',
        'params' => $params,
    ];
}

/**
 * @return array{sql:string, params:array<string, string>}
 */
function maintenance_personal_transitory_bonus_list_q_search_clause(string $q): array
{
    $q = trim($q);
    if ($q === '') {
        return ['sql' => '', 'params' => []];
    }
    $qLike = '%' . $q . '%';
    $nameExpr = "TRIM(CONCAT_WS(', ', NULLIF(TRIM(CONCAT_WS(' ', NULLIF(TRIM(p.last_name_1), ''), NULLIF(TRIM(p.last_name_2), ''))), ''), NULLIF(TRIM(p.first_name), '')))";
    $parts = [];
    $params = [];
    $i = 0;
    $n = 'mq_ptb_' . $i++;
    $parts[] = '(' . $nameExpr . ' LIKE :' . $n . ')';
    $params[$n] = $qLike;
    $n2 = 'mq_ptb_' . $i++;
    $parts[] = '(CAST(p.person_id AS CHAR) LIKE :' . $n2 . ')';
    $params[$n2] = $qLike;
    $n3 = 'mq_ptb_' . $i++;
    $parts[] = '(CAST(p.personal_transitory_bonus AS CHAR) LIKE :' . $n3 . ')';
    $params[$n3] = $qLike;
    $n4 = 'mq_ptb_' . $i++;
    $parts[] = '(CAST(IFNULL(p.personal_transitory_bonus_new, \'\') AS CHAR) LIKE :' . $n4 . ')';
    $params[$n4] = $qLike;

    return [
        'sql' => ' AND (' . implode(' OR ', $parts) . ')',
        'params' => $params,
    ];
}

/**
 * @return array{sql:string, params:array<string, string>}
 */
function management_positions_list_q_search_clause(string $q): array
{
    $q = trim($q);
    if ($q === '') {
        return ['sql' => '', 'params' => []];
    }
    $qLike = '%' . $q . '%';
    $parts = [];
    $params = [];
    $i = 0;
    $n0 = 'mq_mp_' . $i++;
    $n1 = 'mq_mp_' . $i++;
    $parts[] = '(CAST(t.position_id AS CHAR) LIKE :' . $n0 . ' OR LPAD(CAST(t.position_id AS CHAR), 4, \'0\') LIKE :' . $n1 . ')';
    $params[$n0] = $qLike;
    $params[$n1] = $qLike;
    foreach ([
        't.position_name',
        'pc.position_class_name',
        's.scale_name',
        'ss.subscale_name',
        'c.class_name',
        'cat.category_name',
        't.labor_category',
        't.classification_group',
        't.creation_file_reference',
        't.deletion_file_reference',
        't.notes',
    ] as $expr) {
        $n = 'mq_mp_' . $i++;
        $parts[] = '(' . $expr . ' LIKE :' . $n . ')';
        $params[$n] = $qLike;
    }
    return [
        'sql' => ' AND (' . implode(' OR ', $parts) . ')',
        'params' => $params,
    ];
}

/**
 * @return array{sql:string, params:array<string, string>}
 */
function maintenance_job_positions_list_q_search_clause(string $q): array
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
    foreach ([
        't.job_position_id',
        't.catalog_code',
        't.job_title',
        't.org_dependency_id',
        'jp_dep.job_title',
        'jp_dep.catalog_code',
        's.scale_name',
        'lr.legal_relation_name',
        't.labor_category',
        't.notes',
    ] as $expr) {
        $n = 'mq_jp_' . $i++;
        $parts[] = '(' . $expr . ' LIKE :' . $n . ')';
        $params[$n] = $qLike;
    }
    $n = 'mq_jp_' . $i++;
    $parts[] = '(CAST(t.job_position_id AS CHAR) LIKE :' . $n . ')';
    $params[$n] = $qNoDotLike;
    $n = 'mq_jp_' . $i++;
    $parts[] = '(CONCAT(LEFT(LPAD(CAST(CAST(NULLIF(TRIM(t.job_position_id), \'\') AS UNSIGNED) AS CHAR), 6, \'0\'), 4), \'.\', RIGHT(LPAD(CAST(CAST(NULLIF(TRIM(t.job_position_id), \'\') AS UNSIGNED) AS CHAR), 6, \'0\'), 2)) LIKE :' . $n . ')';
    $params[$n] = $qLike;

    return [
        'sql' => ' AND (' . implode(' OR ', $parts) . ')',
        'params' => $params,
    ];
}

/**
 * @return array{sql:string, params:array<string, string>}
 */
function maintenance_people_list_q_search_clause(string $q): array
{
    $q = trim($q);
    if ($q === '') {
        return ['sql' => '', 'params' => []];
    }
    $qLike = '%' . $q . '%';
    $parts = [];
    $params = [];
    $i = 0;
    $n0 = 'mq_ppl_' . $i++;
    $n1 = 'mq_ppl_' . $i++;
    $parts[] = '(CAST(p.person_id AS CHAR) LIKE :' . $n0 . ' OR LPAD(CAST(p.person_id AS CHAR), 5, \'0\') LIKE :' . $n1 . ')';
    $params[$n0] = $qLike;
    $params[$n1] = $qLike;
    foreach ([
        'p.last_name_1',
        'p.last_name_2',
        'p.first_name',
        'p.national_id_number',
        'p.email',
        'p.job_position_id',
        'jp.job_title',
        'p.position_id',
        'pos.position_name',
        'lr.legal_relation_name',
    ] as $expr) {
        $n = 'mq_ppl_' . $i++;
        $parts[] = '(' . $expr . ' LIKE :' . $n . ')';
        $params[$n] = $qLike;
    }

    return ['sql' => ' AND (' . implode(' OR ', $parts) . ')', 'params' => $params];
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
    if ($module === 'maintenance_social_security_companies') {
        return maintenance_social_security_companies_list_q_search_clause($q);
    }
    if ($module === 'maintenance_social_security_coefficients') {
        return maintenance_social_security_coefficients_list_q_search_clause($q);
    }
    if ($module === 'maintenance_social_security_base_limits') {
        return maintenance_social_security_base_limits_list_q_search_clause($q);
    }
    if ($module === 'maintenance_salary_base_by_group') {
        return maintenance_salary_base_by_group_list_q_search_clause($q);
    }
    if ($module === 'maintenance_destination_allowances') {
        return maintenance_destination_allowances_list_q_search_clause($q);
    }
    if ($module === 'maintenance_seniority_pay_by_group') {
        return maintenance_seniority_pay_by_group_list_q_search_clause($q);
    }
    if ($module === 'maintenance_specific_compensation_special_prices') {
        return maintenance_specific_comp_special_prices_list_q_search_clause($q);
    }
    if ($module === 'maintenance_specific_compensation_general') {
        return maintenance_specific_comp_general_list_q_search_clause($q);
    }
    if ($module === 'maintenance_personal_transitory_bonus') {
        return maintenance_personal_transitory_bonus_list_q_search_clause($q);
    }
    if ($module === 'management_positions') {
        return management_positions_list_q_search_clause($q);
    }
    if ($module === 'people') {
        return maintenance_people_list_q_search_clause($q);
    }
    if ($module === 'job_positions') {
        return maintenance_job_positions_list_q_search_clause($q);
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
