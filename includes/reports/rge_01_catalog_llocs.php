<?php
declare(strict_types=1);

require_once __DIR__ . '/report_helpers.php';

/**
 * @return list<array<string, mixed>>
 */
function report_rge01_fetch_base_rows(PDO $db, int $year): array
{
    $sql = <<<'SQL'
SELECT
  COALESCE(NULLIF(TRIM(jp.catalog_code), ''), '') AS catalog_code,
  ou1.org_unit_level_1_id AS area_id,
  ou1.org_unit_level_1_name AS area_name,
  ou3.org_unit_level_3_id AS org3_id,
  ou3.org_unit_level_3_name AS org3_name,
  jp.job_position_id AS job_position_id,
  jp.job_title AS job_title,
  jp.job_number AS job_number,
  jp.organic_level AS jp_organic_level,
  jp.job_type_id AS job_type_id,
  jp.legal_relation_id AS legal_relation_id,
  jp.contribution_epigraph_id AS contribution_epigraph_id,
  jp.contribution_group_id AS contribution_group_id,
  jp.classification_group AS jp_classification_group,
  jp.classification_group_new AS jp_classification_group_new,
  jp.general_specific_compensation_id AS general_specific_compensation_id,
  jp.special_specific_compensation_id AS special_specific_compensation_id,
  jp.special_specific_compensation_amount AS jp_special_amount,
  p.person_id AS person_id,
  p.last_name_1 AS last_name_1,
  p.last_name_2 AS last_name_2,
  p.first_name AS first_name,
  p.position_id AS position_id,
  p.dedication AS dedication,
  p.budgeted_amount AS budgeted_amount,
  p.social_security_contribution_coefficient AS ss_coeff,
  p.productivity_bonus AS productivity_bonus,
  p.personal_transitory_bonus AS personal_transitory_bonus,
  p.annual_budgeted_seniority AS annual_budgeted_seniority,
  p.company_id AS company_id,
  p.status_text AS status_text,
  ast.administrative_status_name AS administrative_status_name,
  pos.classification_group AS pos_classification_group,
  ggen.amount AS ggen_amount,
  ggen.decrease_amount AS ggen_decrease,
  ddest.destination_allowance AS dest_allowance,
  CASE
    WHEN p.position_id IS NOT NULL
      AND p.position_id <> 0
      AND TRIM(CAST(p.position_id AS CHAR)) <> ''
    THEN 1
    ELSE 0
  END AS plaza,
  CASE
    WHEN LOWER(TRIM(COALESCE(p.last_name_1, ''))) = 'vacant'
    THEN 1
    ELSE 0
  END AS vacant
FROM people p
INNER JOIN job_positions jp
  ON jp.catalog_year = p.catalog_year AND jp.job_position_id = p.job_position_id AND jp.deleted_at IS NULL
INNER JOIN org_units_level_3 ou3
  ON ou3.catalog_year = jp.catalog_year AND ou3.org_unit_level_3_id = jp.org_unit_level_3_id
INNER JOIN org_units_level_2 ou2
  ON ou2.catalog_year = ou3.catalog_year AND ou2.org_unit_level_2_id = ou3.org_unit_level_2_id
INNER JOIN org_units_level_1 ou1
  ON ou1.catalog_year = ou2.catalog_year AND ou1.org_unit_level_1_id = ou2.org_unit_level_1_id
LEFT JOIN positions pos
  ON pos.catalog_year = p.catalog_year AND pos.position_id = p.position_id
LEFT JOIN administrative_statuses ast
  ON ast.catalog_year = p.catalog_year AND ast.administrative_status_id = p.administrative_status_id
LEFT JOIN specific_compensation_general ggen
  ON ggen.catalog_year = jp.catalog_year AND ggen.general_specific_compensation_id = jp.general_specific_compensation_id
LEFT JOIN destination_allowances ddest
  ON ddest.catalog_year = jp.catalog_year
  AND UPPER(TRIM(CAST(ddest.organic_level AS CHAR))) = UPPER(TRIM(CAST(jp.organic_level AS CHAR)))
WHERE p.catalog_year = :rge01_year
  AND p.terminated_at IS NULL
  AND p.job_position_id IS NOT NULL AND TRIM(CAST(p.job_position_id AS CHAR)) <> ''
  AND ou1.org_unit_level_1_id <> '9'
ORDER BY catalog_code ASC, area_id ASC, org3_id ASC, job_position_id ASC, vacant ASC, person_id ASC
SQL;

    $st = $db->prepare($sql);
    $st->execute(['rge01_year' => $year]);

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * Mapa catalog_code => catalog_description (taula catalogs, sense any).
 *
 * @return array<string, string>
 */
function report_rge01_catalog_descriptions(PDO $db): array
{
    try {
        $st = $db->query('SELECT catalog_code, catalog_description FROM catalogs');
        if ($st === false) {
            return [];
        }
        $out = [];
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $code = trim((string) ($row['catalog_code'] ?? ''));
            if ($code === '') {
                continue;
            }
            $out[$code] = trim((string) ($row['catalog_description'] ?? ''));
        }

        return $out;
    } catch (Throwable $e) {
        return [];
    }
}

function report_rge01_load_mei_percentage(PDO $db, int $year): float
{
    $st = $db->prepare('SELECT mei_percentage FROM parameters WHERE catalog_year = :y LIMIT 1');
    $st->execute(['y' => $year]);
    $r = $st->fetch(PDO::FETCH_ASSOC);
    if (!$r || !isset($r['mei_percentage']) || !is_numeric($r['mei_percentage'])) {
        return 0.0;
    }

    return (float) $r['mei_percentage'];
}

/**
 * @param list<array<string, mixed>> $rawRows
 * @return list<array<string, mixed>>
 */
function report_rge01_compute_rows(array $rawRows, array $salaryMap, float $meiPct): array
{
    $out = [];
    foreach ($rawRows as $row) {
        $dedication = (float) ($row['dedication'] ?? 0);
        $press = (float) ($row['budgeted_amount'] ?? 0);
        $ssCoeff = (float) ($row['ss_coeff'] ?? 0);

        $eff = report_rge01_effective_group(
            $salaryMap,
            $row['jp_classification_group'] ?? null,
            $row['jp_classification_group_new'] ?? null,
            $row['pos_classification_group'] ?? null,
        );
        $effGroup = $eff['group'];
        $souBaseMensual = $eff['base'] * $dedication;
        $souBasePagues = $eff['extra'] * $dedication;

        $destRaw = $row['dest_allowance'];
        $complementDest = ((float) ($destRaw ?? 0)) * $dedication;

        $ggenAmount = (float) ($row['ggen_amount'] ?? 0);
        $ggenDec = (float) ($row['ggen_decrease'] ?? 0);
        $newNorm = strtoupper(trim((string) ($row['jp_classification_group_new'] ?? '')));
        $effNorm = strtoupper(trim($effGroup));
        if ($effNorm !== '' && $newNorm !== '' && $effNorm === $newNorm) {
            $genUnit = $ggenAmount;
        } else {
            $genUnit = $ggenAmount - $ggenDec;
        }
        $complementGral = $genUnit * $dedication;

        $specAmt = $row['jp_special_amount'];
        $complementEsp = ((float) ($specAmt ?? 0)) * $dedication;

        $sumaMensual = $souBaseMensual + $complementDest + $complementGral + $complementEsp;

        $retAny = (
            $sumaMensual * 12.0
            + $souBasePagues * 2.0
            + ($complementDest + $complementGral + $complementEsp) * 2.0
        ) * $press;

        $antAny = (float) ($row['annual_budgeted_seniority'] ?? 0) * $dedication * $press;
        $prodBonus = (float) ($row['productivity_bonus'] ?? 0);
        $prodAny = $prodBonus * 14.0 * $dedication * $press;
        $cptBonus = (float) ($row['personal_transitory_bonus'] ?? 0);
        $cptAny = $cptBonus * 14.0 * $dedication * $press;

        $complementsSum = $antAny + $cptAny;

        $retTotal = $retAny + $antAny + $prodAny + $cptAny;

        $ssAnnual = report_rge01_social_security_annual_placeholder($retTotal, $ssCoeff);
        $mei = report_rge01_mei_amount($retTotal, $meiPct);

        $row['calc_eff_group'] = $effGroup;
        $row['calc_sou_base_real'] = $souBaseMensual;
        $row['calc_sou_base_pagues'] = $souBasePagues;
        $row['calc_complement_dest'] = $complementDest;
        $row['calc_complement_gral'] = $complementGral;
        $row['calc_complement_esp'] = $complementEsp;
        $row['calc_suma_mensual'] = $sumaMensual;
        $row['calc_ret_any'] = $retAny;
        $row['calc_ant_any'] = $antAny;
        $row['calc_prod_any'] = $prodAny;
        $row['calc_cpt_any'] = $cptAny;
        $row['calc_complements'] = $complementsSum;
        $row['calc_ret_total'] = $retTotal;
        $row['calc_ss_annual'] = $ssAnnual;
        $row['calc_mei'] = $mei;
        $row['calc_is_vacant'] = false;
        $row['calc_dedication'] = $dedication;
        $row['calc_press'] = $press;
        $row['plaza'] = (int) ($row['plaza'] ?? 0);
        $row['vacant'] = (int) ($row['vacant'] ?? 0);

        $out[] = $row;
    }

    return $out;
}

/** @param list<array<string, mixed>> $rows */
function report_rge01_empty_totals(): array
{
    return [
        'places' => 0,
        'vacants' => 0,
        'sou_base' => 0.0,
        'complement_dest' => 0.0,
        'complement_gral' => 0.0,
        'complement_esp' => 0.0,
        'suma_mensual' => 0.0,
        'ret_any' => 0.0,
        'ant_any' => 0.0,
        'prod_any' => 0.0,
        'cpt_any' => 0.0,
        'ret_total' => 0.0,
        'ss' => 0.0,
        'mei' => 0.0,
    ];
}

/** @param array<string, mixed> $r */
function report_rge01_add_row_to_totals(array &$tot, array $r): void
{
    $tot['places'] += (int) ($r['plaza'] ?? 0);
    $tot['vacants'] += (int) ($r['vacant'] ?? 0);
    $tot['sou_base'] += (float) ($r['calc_sou_base_real'] ?? 0);
    $tot['complement_dest'] += (float) ($r['calc_complement_dest'] ?? 0);
    $tot['complement_gral'] += (float) ($r['calc_complement_gral'] ?? 0);
    $tot['complement_esp'] += (float) ($r['calc_complement_esp'] ?? 0);
    $tot['suma_mensual'] += (float) ($r['calc_suma_mensual'] ?? 0);
    $tot['ret_any'] += (float) ($r['calc_ret_any'] ?? 0);
    $tot['ant_any'] += (float) ($r['calc_ant_any'] ?? 0);
    $tot['prod_any'] += (float) ($r['calc_prod_any'] ?? 0);
    $tot['cpt_any'] += (float) ($r['calc_cpt_any'] ?? 0);
    $tot['ret_total'] += (float) ($r['calc_ret_total'] ?? 0);
    $tot['ss'] += (float) ($r['calc_ss_annual'] ?? 0);
    $tot['mei'] += (float) ($r['calc_mei'] ?? 0);
}

/**
 * @param list<array<string, mixed>> $computedRows
 * @return array{catalogs: array<string, array{areas: array<string, array{rows: list<array<string, mixed>>, totals: array, area_name: string}>, totals: array}>, grand: array}
 */
function report_rge01_group_by_catalog_area(array $computedRows): array
{
    $catalogs = [];
    $grand = report_rge01_empty_totals();

    foreach ($computedRows as $r) {
        $cat = (string) ($r['catalog_code'] ?? '');
        $area = (string) ($r['area_id'] ?? '');
        if (!isset($catalogs[$cat])) {
            $catalogs[$cat] = ['areas' => [], 'totals' => report_rge01_empty_totals()];
        }
        if (!isset($catalogs[$cat]['areas'][$area])) {
            $catalogs[$cat]['areas'][$area] = [
                'rows' => [],
                'totals' => report_rge01_empty_totals(),
                'area_name' => trim((string) ($r['area_name'] ?? '')),
            ];
        } else {
            $nm = trim((string) ($r['area_name'] ?? ''));
            if ($nm !== '' && $catalogs[$cat]['areas'][$area]['area_name'] === '') {
                $catalogs[$cat]['areas'][$area]['area_name'] = $nm;
            }
        }
        $catalogs[$cat]['areas'][$area]['rows'][] = $r;
        report_rge01_add_row_to_totals($catalogs[$cat]['areas'][$area]['totals'], $r);
        report_rge01_add_row_to_totals($catalogs[$cat]['totals'], $r);
        report_rge01_add_row_to_totals($grand, $r);
    }

    return ['catalogs' => $catalogs, 'grand' => $grand];
}

/**
 * @return array{
 *   year: int,
 *   reportCode: string,
 *   reportTitle: string,
 *   comentari: string|null,
 *   veureTreballador: bool,
 *   grouped: array,
 *   logoPath: string,
 *   generatedAt: string,
 *   catalogDescriptions: array<string, string>
 * }
 */
function report_rge01_build_view_data(PDO $db, int $year, string $reportCode, string $reportTitle, ?string $comentari, bool $veureTreballador): array
{
    $raw = report_rge01_fetch_base_rows($db, $year);
    $salaryMap = report_rge01_salary_map($db, $year);
    $mei = report_rge01_load_mei_percentage($db, $year);
    $computed = report_rge01_compute_rows($raw, $salaryMap, $mei);
    $grouped = report_rge01_group_by_catalog_area($computed);
    $catalogDescriptions = report_rge01_catalog_descriptions($db);

    return [
        'year' => $year,
        'reportCode' => $reportCode,
        'reportTitle' => $reportTitle,
        'comentari' => $comentari,
        'veureTreballador' => $veureTreballador,
        'grouped' => $grouped,
        'logoPath' => report_print_logo_path(),
        'generatedAt' => date('d/m/Y H:i'),
        'catalogDescriptions' => $catalogDescriptions,
    ];
}

function report_rge01_run(PDO $db, int $year, string $reportCode, string $reportTitle, ?string $comentari, bool $veureTreballador): void
{
    $data = report_rge01_build_view_data($db, $year, $reportCode, $reportTitle, $comentari, $veureTreballador);
    extract($data, EXTR_OVERWRITE);
    $rge01SkipHeader = false;

    require APP_ROOT . '/views/reports/rge_01_print.php';
}

/**
 * Document HTML complet per impressió / «Desar com a PDF» (mateix patró que Formació: layout_report + window.print).
 */
function report_rge01_run_print_view(PDO $db, int $year, string $reportCode, string $reportTitle, ?string $comentari, bool $veureTreballador, bool $autoPrint = false): void
{
    $data = report_rge01_build_view_data($db, $year, $reportCode, $reportTitle, $comentari, $veureTreballador);
    extract($data, EXTR_OVERWRITE);
    $reportAutoPrint = $autoPrint;

    require APP_ROOT . '/views/reports/rge_01_print_standalone.php';
}
