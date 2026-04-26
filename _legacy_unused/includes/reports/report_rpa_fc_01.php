<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/training_actions/training_actions.php';
require_once APP_ROOT . '/includes/reports/report_training_type_filter.php';

/**
 * Persones amb preinscripció activa per acció (training_action_attendees.pre_registration_flag = 1).
 *
 * @param list<int> $actionIds
 * @return array<int, list<string>> action_id => llista de noms visibles
 */
function report_rpa_fc_01_fetch_pre_registered_by_action(PDO $db, array $actionIds): array
{
    $clean = [];
    foreach ($actionIds as $id) {
        $n = (int) $id;
        if ($n > 0) {
            $clean[] = $n;
        }
    }
    $actionIds = array_values(array_unique($clean));
    if ($actionIds === []) {
        return [];
    }

    require_once APP_ROOT . '/includes/people/people.php';

    $placeholders = implode(',', array_fill(0, count($actionIds), '?'));
    $sql = "SELECT taa.training_action_id,
                   p.last_name_1, p.last_name_2, p.first_name, p.person_code
            FROM training_action_attendees taa
            INNER JOIN people p ON p.id = taa.person_id
            WHERE taa.pre_registration_flag = 1
              AND taa.training_action_id IN ($placeholders)
            ORDER BY taa.training_action_id ASC, p.last_name_1 ASC, p.last_name_2 ASC, p.first_name ASC";

    $st = $db->prepare($sql);
    $st->execute($actionIds);

    $out = [];
    foreach ($actionIds as $id) {
        $out[$id] = [];
    }

    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $aid = (int) $row['training_action_id'];
        if (!isset($out[$aid])) {
            $out[$aid] = [];
        }
        $out[$aid][] = people_format_surname_first($row);
    }

    return $out;
}

/**
 * Dades per a l’informe RPAFC-01 — Pla anual de formació.
 *
 * @param string $trainingTypeFilter REPORT_TRAINING_TYPE_ALL o un valor de report_training_type_values()
 *
 * @return list<array<string,mixed>>
 */
function report_rpa_fc_01_fetch_rows(PDO $db, int $programYear, bool $includeDraft, string $trainingTypeFilter = REPORT_TRAINING_TYPE_ALL): array
{
    if ($programYear < 1990 || $programYear > 2100) {
        return [];
    }

    $parts = report_training_type_subprogram_join_named($trainingTypeFilter);
    $joinSub = $parts['join'];

    $sql = "SELECT
                ta.id,
                ta.program_year,
                ta.action_number,
                ta.name,
                ta.planned_places,
                ta.planned_duration_hours,
                ta.target_audience,
                ta.training_objectives,
                ta.subprogram_id,
                ta.knowledge_area_id,
                sp.subprogram_code AS subprogram_code,
                sp.name AS subprogram_name,
                ka.knowledge_area_code AS knowledge_area_code,
                ka.name AS knowledge_area_name,
                org.organizer_code AS organizer_code,
                org.name AS organizer_name,
                tad.date_from,
                tad.date_to,
                tad.session_count
            FROM training_actions ta
            $joinSub
            LEFT JOIN knowledge_areas ka ON ka.id = ta.knowledge_area_id
            LEFT JOIN training_organizers org ON org.id = ta.organizer_id
            LEFT JOIN (
                SELECT training_action_id,
                       MIN(session_date) AS date_from,
                       MAX(session_date) AS date_to,
                       COUNT(*) AS session_count
                FROM training_action_dates
                GROUP BY training_action_id
            ) tad ON tad.training_action_id = ta.id
            WHERE ta.program_year = :y
              AND (:inc_draft = 1 OR ta.is_active = 1)
            ORDER BY
                COALESCE(sp.subprogram_code, 999999) ASC,
                sp.name ASC,
                COALESCE(ka.knowledge_area_code, 999999) ASC,
                ka.name ASC,
                ta.action_number ASC";

    $st = $db->prepare($sql);
    $st->bindValue(':y', $programYear, PDO::PARAM_INT);
    $st->bindValue(':inc_draft', $includeDraft ? 1 : 0, PDO::PARAM_INT);
    foreach ($parts['named_binds'] as $pname => $pval) {
        $st->bindValue(':' . $pname, $pval, PDO::PARAM_STR);
    }
    $st->execute();

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * @param list<array<string,mixed>> $rows
 * @return array{blocks: list<array<string,mixed>>, total_summary: array<string,mixed>}
 */
function report_rpa_fc_01_build_blocks(array $rows): array
{
    $blocks = [];
    $curSubKey = null;
    $curAreaKey = null;
    /** @var array<string,mixed>|null $subBlock */
    $subBlock = null;
    /** @var array<string,mixed>|null $areaBlock */
    $areaBlock = null;

    $flushArea = static function () use (&$subBlock, &$areaBlock): void {
        if ($subBlock !== null && $areaBlock !== null) {
            $subBlock['areas'][] = $areaBlock;
        }
        $areaBlock = null;
    };

    $flushSub = static function () use (&$blocks, &$subBlock, &$areaBlock, $flushArea): void {
        $flushArea();
        if ($subBlock !== null) {
            $blocks[] = $subBlock;
        }
        $subBlock = null;
    };

    foreach ($rows as $r) {
        $sk = report_rpa_fc_01_subprogram_key($r);
        $ak = report_rpa_fc_01_area_key($r);

        if ($curSubKey !== $sk) {
            $flushSub();
            $curSubKey = $sk;
            $curAreaKey = null;
            $subBlock = report_rpa_fc_01_new_subprogram_block($r);
        }

        if ($curAreaKey !== $ak) {
            $flushArea();
            $curAreaKey = $ak;
            $areaBlock = report_rpa_fc_01_new_area_block($r);
        }

        if ($areaBlock !== null) {
            $areaBlock['actions'][] = $r;
        }
    }

    $flushSub();

    $totalSummary = report_rpa_fc_01_summary($rows);

    return ['blocks' => $blocks, 'total_summary' => $totalSummary];
}

/**
 * @param array<string,mixed> $r
 */
function report_rpa_fc_01_subprogram_key(array $r): string
{
    if (!isset($r['subprogram_id']) || $r['subprogram_id'] === null || $r['subprogram_id'] === '') {
        return 's_none';
    }

    return 's_' . (int) $r['subprogram_id'];
}

/**
 * @param array<string,mixed> $r
 */
function report_rpa_fc_01_area_key(array $r): string
{
    if (!isset($r['knowledge_area_id']) || $r['knowledge_area_id'] === null || $r['knowledge_area_id'] === '') {
        return 'a_none';
    }

    return 'a_' . (int) $r['knowledge_area_id'];
}

/**
 * @param array<string,mixed> $r
 * @return array<string,mixed>
 */
function report_rpa_fc_01_new_subprogram_block(array $r): array
{
    $code = $r['subprogram_code'] ?? null;
    $name = trim((string) ($r['subprogram_name'] ?? ''));
    if ($name === '') {
        $name = 'Sense subprograma';
    }
    $codeDisp = $code !== null && $code !== ''
        ? format_padded_code((int) $code, 3)
        : '—';

    return [
        'subprogram_id' => $r['subprogram_id'],
        'code_display' => $codeDisp,
        'name' => $name,
        'areas' => [],
    ];
}

/**
 * @param array<string,mixed> $r
 * @return array<string,mixed>
 */
function report_rpa_fc_01_new_area_block(array $r): array
{
    $code = $r['knowledge_area_code'] ?? null;
    $name = trim((string) ($r['knowledge_area_name'] ?? ''));
    if ($name === '') {
        $name = 'Sense àrea de coneixement';
    }
    $codeDisp = $code !== null && $code !== ''
        ? format_padded_code((int) $code, 3)
        : '—';

    return [
        'knowledge_area_id' => $r['knowledge_area_id'],
        'code_display' => $codeDisp,
        'name' => $name,
        'actions' => [],
    ];
}

/**
 * Resum numèric sobre una llista d’accions (mateixa lògica per àrea i total).
 *
 * - places: suma de planned_places (NULL com a 0)
 * - hours: suma de planned_duration_hours (NULL s’omet de la suma)
 * - avg_duration: mitjana aritmètica només sobre accions amb durada no null
 *
 * @param list<array<string,mixed>> $actions
 * @return array{action_count:int,places_sum:int,hours_sum:float,avg_duration:?float}
 */
function report_rpa_fc_01_summary(array $actions): array
{
    $actionCount = count($actions);
    $placesSum = 0;
    $hoursSum = 0.0;
    $durations = [];

    foreach ($actions as $a) {
        $placesSum += (int) ($a['planned_places'] ?? 0);
        $pd = $a['planned_duration_hours'] ?? null;
        if ($pd !== null && $pd !== '') {
            $h = (float) $pd;
            $hoursSum += $h;
            $durations[] = $h;
        }
    }

    $avg = count($durations) > 0 ? array_sum($durations) / count($durations) : null;

    return [
        'action_count' => $actionCount,
        'places_sum' => $placesSum,
        'hours_sum' => $hoursSum,
        'avg_duration' => $avg,
    ];
}

function report_rpa_fc_01_format_date_ymd(?string $d): string
{
    if ($d === null || $d === '') {
        return '';
    }
    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $d);
    if ($dt === false) {
        return $d;
    }

    return $dt->format('d/m/Y');
}

/**
 * @param array<string,mixed> $row
 */
function report_rpa_fc_01_format_dates_cell(array $row): string
{
    $from = $row['date_from'] ?? null;
    $to = $row['date_to'] ?? null;
    $from = is_string($from) ? $from : null;
    $to = is_string($to) ? $to : null;

    if (($from === null || $from === '') && ($to === null || $to === '')) {
        return '—';
    }

    $df = report_rpa_fc_01_format_date_ymd($from);
    $dt = report_rpa_fc_01_format_date_ymd($to);
    if ($df !== '' && $dt !== '') {
        if ($df === $dt) {
            return $df;
        }

        return $df . ' – ' . $dt;
    }

    return $df !== '' ? $df : $dt;
}

function report_rpa_fc_01_format_hours(?float $h): string
{
    if ($h === null) {
        return '—';
    }

    return number_format($h, 2, ',', '.');
}

/**
 * Llista de noms amb límit per a impressió (evita blocs massa llargs).
 *
 * @param list<string> $names
 * @return array{line: string, more: int}
 */
function report_rpa_fc_01_truncate_person_names(array $names, int $maxShow = 12): array
{
    $clean = [];
    foreach ($names as $n) {
        $t = trim((string) $n);
        if ($t !== '') {
            $clean[] = $t;
        }
    }
    $n = count($clean);
    if ($n <= $maxShow) {
        return ['line' => implode(' - ', $clean), 'more' => 0];
    }
    $slice = array_slice($clean, 0, $maxShow);

    return ['line' => implode(' - ', $slice), 'more' => $n - $maxShow];
}

/**
 * @param array{action_count:int,places_sum:int,hours_sum:float,avg_duration:?float} $s
 */
function report_rpa_fc_01_render_summary_block(array $s): void
{
    ?>
    <div class="report-summary">
        <div class="report-summary__title">Resum</div>
        <ul class="report-summary__list">
            <li><span class="report-summary__label">Accions formatives previstes</span> <span class="report-summary__value"><?= (int) $s['action_count'] ?></span></li>
            <li><span class="report-summary__label">Places totals previstes</span> <span class="report-summary__value"><?= (int) $s['places_sum'] ?></span></li>
            <li><span class="report-summary__label">Hores de formació previstes</span> <span class="report-summary__value"><?= e(report_rpa_fc_01_format_hours((float) $s['hours_sum'])) ?></span></li>
            <li><span class="report-summary__label">Durada promig de les accions (h)</span> <span class="report-summary__value"><?= $s['avg_duration'] === null ? '—' : e(report_rpa_fc_01_format_hours((float) $s['avg_duration'])) ?></span></li>
        </ul>
    </div>
    <?php
}

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpa_fc_01_render(PDO $db, array $reportRow, int $programYear, bool $includeDraft, bool $includePersonalData = false, string $trainingTypeFilter = REPORT_TRAINING_TYPE_ALL): void
{
    require_once APP_ROOT . '/includes/reports/report_header_helper.php';

    $rows = report_rpa_fc_01_fetch_rows($db, $programYear, $includeDraft, $trainingTypeFilter);
    $built = report_rpa_fc_01_build_blocks($rows);
    $blocks = $built['blocks'];
    $totalSummary = $built['total_summary'];

    $preRegisteredByAction = [];
    if ($includePersonalData) {
        $ids = [];
        foreach ($rows as $r) {
            $ids[] = (int) ($r['id'] ?? 0);
        }
        $preRegisteredByAction = report_rpa_fc_01_fetch_pre_registered_by_action($db, $ids);
    }

    $title = report_training_type_resolved_report_title($reportRow, $trainingTypeFilter);

    $generatedAt = new DateTimeImmutable('now', new DateTimeZone('Europe/Madrid'));

    $ctxHeader = [
        'report_code' => (string) $reportRow['report_code'],
        'report_title' => $title,
        'program_year' => $programYear,
        'generated_at' => $generatedAt,
    ];

    ob_start();
    report_header_render($ctxHeader);
    $headerHtml = ob_get_clean();

    ob_start();
    /** @var bool $includePersonalData */
    /** @var array<int, list<string>> $preRegisteredByAction */
    require APP_ROOT . '/views/reports/rpa_fc_01_body.php';
    $bodyHtml = ob_get_clean();

    $pageTitle = $title . ' · ' . $programYear;
    $backUrl = app_url('reports.php');
    $excelQ = [
        'code' => (string) $reportRow['report_code'],
        'program_year' => $programYear,
        'include_personal_data' => $includePersonalData ? '1' : '0',
        'optional_include_draft' => $includeDraft ? '1' : '0',
    ];
    if ($trainingTypeFilter !== REPORT_TRAINING_TYPE_ALL) {
        $excelQ['training_type'] = $trainingTypeFilter;
    }
    $reportExcelUrl = app_url('report_export_excel.php?' . http_build_query($excelQ));
    require APP_ROOT . '/views/reports/layout_report.php';
}
