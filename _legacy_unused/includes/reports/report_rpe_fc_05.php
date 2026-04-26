<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/people/people.php';

/**
 * RPEFC-05 — Persones en formació sense dades de correu.
 *
 * Persones amb almenys una fila a `training_action_attendees` vinculada a una acció
 * de l’exercici seleccionat (sense filtrar per preinscripció / inscripció / assistència).
 * Només es llisten persones sense correu efectiu (`email` NULL o buit després de trim).
 * Una persona = una fila (DISTINCT sobre `people.id`).
 * Ordenació: cognoms i nom (`last_name_1`, `last_name_2`, `first_name`).
 */

/**
 * Títol des del catàleg training_reports.
 *
 * @param array<string,mixed> $reportRow
 */
function report_rpe_fc_05_resolved_title(array $reportRow): string
{
    $base = trim((string) ($reportRow['report_description'] ?? ''));
    if ($base === '') {
        $base = trim((string) ($reportRow['report_name'] ?? ''));
    }

    return $base;
}

/**
 * Persones sense correu amb relació a alguna acció formativa de l’any (i filtre d’esborrany).
 *
 * @return list<array<string,mixed>> files amb id, person_code, last_name_*, first_name
 */
function report_rpe_fc_05_fetch_people(PDO $db, int $programYear, bool $includeDraft): array
{
    if ($programYear < 1990 || $programYear > 2100) {
        return [];
    }

    $sql = 'SELECT DISTINCT
                p.id,
                p.person_code,
                p.last_name_1,
                p.last_name_2,
                p.first_name
            FROM people p
            INNER JOIN training_action_attendees taa ON taa.person_id = p.id
            INNER JOIN training_actions ta ON ta.id = taa.training_action_id
            WHERE ta.program_year = :y
              AND (:inc_draft = 1 OR ta.is_active = 1)
              AND (p.email IS NULL OR TRIM(IFNULL(p.email, \'\')) = \'\')
            ORDER BY
                p.last_name_1 ASC,
                COALESCE(p.last_name_2, \'\') ASC,
                p.first_name ASC';

    $st = $db->prepare($sql);
    $st->bindValue(':y', $programYear, PDO::PARAM_INT);
    $st->bindValue(':inc_draft', $includeDraft ? 1 : 0, PDO::PARAM_INT);
    $st->execute();

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

/**
 * @param array<string,mixed> $reportRow
 */
function report_rpe_fc_05_render(PDO $db, array $reportRow, int $programYear, bool $includeDraft): void
{
    require_once APP_ROOT . '/includes/reports/report_header_helper.php';

    $rows = report_rpe_fc_05_fetch_people($db, $programYear, $includeDraft);
    $title = report_rpe_fc_05_resolved_title($reportRow);

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
    require APP_ROOT . '/views/reports/rpe_fc_05_body.php';
    $bodyHtml = ob_get_clean();

    $pageTitle = $title . ' · ' . $programYear;
    $reportLayoutBodyExtraClass = 'report-body--rpe-fc-05';
    $backUrl = app_url('reports.php');
    $excelQ = [
        'code' => (string) $reportRow['report_code'],
        'program_year' => $programYear,
        'optional_include_draft' => $includeDraft ? '1' : '0',
    ];
    $reportExcelUrl = app_url('report_export_excel.php?' . http_build_query($excelQ));
    require APP_ROOT . '/views/reports/layout_report.php';
}
