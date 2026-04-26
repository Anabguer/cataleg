<?php
declare(strict_types=1);

/**
 * Filtre i etiquetatge de tipus de formació per als informes RPAFC (01–04).
 *
 * Compatibilitat URL: `programmed_training_only=1` es mapa a «programmed» (veure report_training_type_normalize_from_get).
 *
 * Pendent de migrar (fora d’aquest mòdul): el camp `is_programmed_training` encara existeix a BD i es manté
 * sincronitzat des del manteniment de subprogrames (1 només si training_type = programmed); no s’usa als informes RPAFC-01/02/03/04.
 */

/**
 * Valors interns de training_subprograms.training_type (validació backend).
 */
const REPORT_TRAINING_TYPE_PROGRAMMED = 'programmed';
const REPORT_TRAINING_TYPE_NON_PROGRAMMED = 'non_programmed';
const REPORT_TRAINING_TYPE_PERSONAL = 'personal';
const REPORT_TRAINING_TYPE_ALL = 'all';

/** @return list<string> */
function report_training_type_values(): array
{
    return [
        REPORT_TRAINING_TYPE_PROGRAMMED,
        REPORT_TRAINING_TYPE_NON_PROGRAMMED,
        REPORT_TRAINING_TYPE_PERSONAL,
    ];
}

function report_training_type_is_allowed(string $v): bool
{
    return in_array($v, report_training_type_values(), true);
}

/**
 * Llegeix training_type dels GET i manté compatibilitat amb programmed_training_only=1 → programmed.
 */
function report_training_type_normalize_from_get(string $trainingTypeRaw, string $legacyProgrammedOnly): string
{
    $t = strtolower(trim($trainingTypeRaw));
    if (report_training_type_is_allowed($t)) {
        return $t;
    }
    if ($t === REPORT_TRAINING_TYPE_ALL) {
        return REPORT_TRAINING_TYPE_ALL;
    }
    if ($legacyProgrammedOnly === '1') {
        return REPORT_TRAINING_TYPE_PROGRAMMED;
    }

    return REPORT_TRAINING_TYPE_ALL;
}

function report_training_type_label_ca(string $internal): string
{
    switch ($internal) {
        case REPORT_TRAINING_TYPE_PROGRAMMED:
            return 'Programada';
        case REPORT_TRAINING_TYPE_NON_PROGRAMMED:
            return 'No programada';
        case REPORT_TRAINING_TYPE_PERSONAL:
            return 'Personal';
        case REPORT_TRAINING_TYPE_ALL:
            return 'Tots els tipus';
        default:
            return 'Tots els tipus';
    }
}

/**
 * Sufix del títol visible segons el filtre (un espai + literal, o buit si «tots»).
 */
function report_training_type_title_suffix_for_header(string $trainingTypeFilter): string
{
    switch ($trainingTypeFilter) {
        case REPORT_TRAINING_TYPE_PROGRAMMED:
            return ' Programades';
        case REPORT_TRAINING_TYPE_NON_PROGRAMMED:
            return ' No programades';
        case REPORT_TRAINING_TYPE_PERSONAL:
            return ' Caràcter Personal';
        default:
            return '';
    }
}

/**
 * Títol visible per a capçalera HTML / impressió / Excel: descripció base de training_reports + sufix de tipus.
 *
 * Base: `report_description` si no és buit; si no, `report_name` (mateix criteri que la resta d’informes).
 *
 * @param array<string,mixed> $reportRow Fila de training_reports
 */
function report_training_type_resolved_report_title(array $reportRow, string $trainingTypeFilter): string
{
    $base = trim((string) ($reportRow['report_description'] ?? ''));
    if ($base === '') {
        $base = trim((string) ($reportRow['report_name'] ?? ''));
    }

    return $base . report_training_type_title_suffix_for_header($trainingTypeFilter);
}

/**
 * Text de llegenda (peu d’informe) segons el filtre de tipus.
 */
function report_training_type_scope_legend_ca(string $filter): string
{
    if ($filter === REPORT_TRAINING_TYPE_ALL) {
        return 'L’informe inclou totes les accions formatives de l’any seleccionat.';
    }

    return 'L’informe inclou únicament les accions de tipus de formació «'
        . report_training_type_label_ca($filter)
        . '» (segons el subprograma) de l’any seleccionat.';
}

/**
 * JOIN sobre training_subprograms per filtrar per training_type (named params PDO).
 *
 * @return array{join:string, named_binds:array<string,string>}
 */
function report_training_type_subprogram_join_named(string $normalizedFilter): array
{
    if ($normalizedFilter === REPORT_TRAINING_TYPE_ALL) {
        return [
            'join' => 'LEFT JOIN training_subprograms sp ON sp.id = ta.subprogram_id',
            'named_binds' => [],
        ];
    }

    return [
        'join' => 'INNER JOIN training_subprograms sp ON sp.id = ta.subprogram_id AND sp.training_type = :report_training_type',
        'named_binds' => ['report_training_type' => $normalizedFilter],
    ];
}

/**
 * Mateix criteri que join named, però amb ? (ordre: primer el tipus si escau, després la resta que passi execute()).
 *
 * @return array{join:string, leading_positional: list<string>}
 */
function report_training_type_subprogram_join_positional(string $normalizedFilter): array
{
    if ($normalizedFilter === REPORT_TRAINING_TYPE_ALL) {
        return [
            'join' => 'LEFT JOIN training_subprograms sp ON sp.id = ta.subprogram_id',
            'leading_positional' => [],
        ];
    }

    return [
        'join' => 'INNER JOIN training_subprograms sp ON sp.id = ta.subprogram_id AND sp.training_type = ?',
        'leading_positional' => [$normalizedFilter],
    ];
}

/**
 * Valida training_type des de formularis/API (subprograma).
 */
function report_training_type_normalize_input(?string $raw): ?string
{
    $t = strtolower(trim((string) $raw));
    if ($t === '') {
        return null;
    }
    if (report_training_type_is_allowed($t)) {
        return $t;
    }

    return null;
}
