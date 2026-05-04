<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/maintenance/maintenance_columns.php';

/**
 * Informe actiu i visible al selector general.
 *
 * @return array<string, mixed>|null
 */
function report_get_for_selector_run(PDO $db, string $reportCode): ?array
{
    $code = trim($reportCode);
    if ($code === '') {
        return null;
    }
    $st = $db->prepare('SELECT id, report_code, report_name, report_description, report_explanation, report_version
        FROM reports
        WHERE report_code = :c AND is_active = 1 AND show_in_general_selector = 1
        LIMIT 1');
    $st->execute(['c' => $code]);
    $row = $st->fetch(PDO::FETCH_ASSOC);

    return $row ?: null;
}

/**
 * Camí relatiu a asset_url() del logo per capçaleres d’informe impresos (no el de la resta de l’app).
 */
function report_print_logo_path(): string
{
    return 'img/logos/color_esquerra.png';
}

/**
 * Definició de paràmetres per informe (extensible per codi).
 * Per cada codi: 'title' (opcional) i 'fields' (llista de camps).
 * Cada camp: name, type (select|textarea|text), label, required?, default?, options?, rows?.
 *
 * @return array<string, array{title?: string, fields: list<array<string, mixed>}>|list<array<string, mixed>>>
 */
function report_selector_param_fieldsets(): array
{
    return [
        'RGE-01' => [
            'title' => 'Paràmetres de l’informe',
            'fields' => [
                [
                    'name' => 'veure_treballador',
                    'label' => 'Veure treballador',
                    'type' => 'select',
                    'options' => [
                        ['value' => '1', 'label' => 'Sí'],
                        ['value' => '0', 'label' => 'No'],
                    ],
                    'required' => true,
                    'default' => '1',
                ],
                [
                    'name' => 'comentari',
                    'label' => 'Comentari',
                    'type' => 'textarea',
                    'required' => false,
                    'rows' => 4,
                ],
            ],
        ],
    ];
}

/**
 * Títol visible RGE-01 segons paràmetre.
 */
function report_rge01_display_title(bool $veureTreballador): string
{
    return $veureTreballador
        ? 'Catàleg de llocs de treball i personal (amb treballadors)'
        : 'Catàleg de llocs de treball i personal (sense treballadors)';
}

/**
 * MEI: parameters.mei_percentage és el valor real percentual (0,75 = 0,75%), no 75.
 */
function report_rge01_mei_amount(float $retribucioTotalAnual, float $meiPercentageDb): float
{
    return round($retribucioTotalAnual * ($meiPercentageDb / 100.0), 2);
}

/**
 * Cotització SS anual: pendent validació amb la funció Access «SeguretatSocial».
 * Es calcula com retribució total anual × coeficient de persona (BBDD 0..1) fins que es confirmi la fórmula oficial.
 *
 * @param float $retribucioTotalAnual Import anual total (retribució + antiguitat + productivitat + CPT)
 * @param float $contributionCoefficient Coeficient persona (fraction 0..1)
 */
function report_rge01_social_security_annual_placeholder(float $retribucioTotalAnual, float $contributionCoefficient): float
{
    // TODO: reemplaçar per la lògica equivalent a Access SeguretatSocial(pressupostat, retribucio_total, coef) quan estigui documentada.
    return round($retribucioTotalAnual * $contributionCoefficient, 2);
}

/**
 * Carrega mapa grup classificació → sou base / pagues extres.
 *
 * @return array<string, array{base: float, extra: float}>
 */
function report_rge01_salary_map(PDO $db, int $year): array
{
    $st = $db->prepare('SELECT classification_group, base_salary, base_salary_extra_pay
        FROM salary_base_by_group WHERE catalog_year = :y');
    $st->execute(['y' => $year]);
    $out = [];
    while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
        $g = strtoupper(trim((string) ($r['classification_group'] ?? '')));
        if ($g === '') {
            continue;
        }
        $out[$g] = [
            'base' => (float) ($r['base_salary'] ?? 0),
            'extra' => (float) ($r['base_salary_extra_pay'] ?? 0),
        ];
    }

    return $out;
}

/**
 * Determina el grup de classificació efectiu (major sou base entre plaça, lloc i grup nou).
 *
 * @return array{group: string, base: float, extra: float}
 */
function report_rge01_effective_group(
    array $salaryMap,
    ?string $jobGroup,
    ?string $jobGroupNew,
    ?string $positionGroup,
): array {
    $candidates = [];
    foreach ([$jobGroup, $jobGroupNew, $positionGroup] as $g) {
        $k = strtoupper(trim((string) $g));
        if ($k !== '') {
            $candidates[$k] = true;
        }
    }
    $best = '';
    $bestBase = -1.0;
    $bestExtra = 0.0;
    foreach (array_keys($candidates) as $k) {
        $row = $salaryMap[$k] ?? null;
        $base = $row['base'] ?? 0.0;
        if ($base > $bestBase) {
            $bestBase = $base;
            $best = $k;
            $bestExtra = $row['extra'] ?? 0.0;
        }
    }

    return ['group' => $best, 'base' => $bestBase < 0 ? 0.0 : $bestBase, 'extra' => $bestExtra];
}

function report_rge01_format_percent_from_fraction(?float $f): string
{
    if ($f === null) {
        return '';
    }

    return number_format($f * 100.0, 2, ',', '') . '%';
}

function report_rge01_person_display_name(array $row, bool $isVacant): string
{
    if ($isVacant) {
        return 'Vacant ,';
    }
    $a = trim((string) ($row['last_name_1'] ?? ''));
    $b = trim((string) ($row['last_name_2'] ?? ''));
    $c = trim((string) ($row['first_name'] ?? ''));
    $ln = trim($a . ' ' . $b);

    return trim($ln . ', ' . $c);
}
