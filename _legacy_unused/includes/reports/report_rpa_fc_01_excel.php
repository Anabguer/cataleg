<?php
declare(strict_types=1);

use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Construeix les línies de text per al bloc de resum (mateixes mètriques que la vista HTML).
 *
 * @param array{action_count:int,places_sum:int,hours_sum:float,avg_duration:?float} $s
 * @return list<array{0: string, 1: string|int|float}>
 */
function report_rpa_fc_01_excel_summary_lines(array $s): array
{
    $avg = $s['avg_duration'];

    return [
        ['Accions formatives previstes', (int) $s['action_count']],
        ['Places totals previstes', (int) $s['places_sum']],
        ['Hores de formació previstes', report_rpa_fc_01_format_hours((float) $s['hours_sum'])],
        ['Durada promig de les accions (h)', $avg === null ? '—' : report_rpa_fc_01_format_hours((float) $avg)],
    ];
}

/**
 * Genera i envia un .xlsx (memòria → php://output, sense desar al disc del projecte).
 * Maquetació: includes/reports/excel/ (builder, styles, layout).
 *
 * @param array<string,mixed> $reportRow Fila de training_reports
 */
function report_rpa_fc_01_export_excel(PDO $db, array $reportRow, int $programYear, bool $includeDraft, bool $includePersonalData, string $trainingTypeFilter = REPORT_TRAINING_TYPE_ALL): void
{
    $vendor = APP_ROOT . '/vendor/autoload.php';
    if (!is_readable($vendor)) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Falta Composer (vendor/autoload.php). Executeu composer install.';
        exit;
    }
    require_once $vendor;
    require_once APP_ROOT . '/includes/reports/report_rpa_fc_01.php';
    require_once APP_ROOT . '/includes/reports/excel/excel.php';

    $theme = report_excel_theme();

    $rows = report_rpa_fc_01_fetch_rows($db, $programYear, $includeDraft, $trainingTypeFilter);
    $built = report_rpa_fc_01_build_blocks($rows);
    $blocks = $built['blocks'];
    $totalSummary = $built['total_summary'];

    $preRegisteredByAction = [];
    if ($includePersonalData && $rows !== []) {
        $ids = [];
        foreach ($rows as $r) {
            $ids[] = (int) ($r['id'] ?? 0);
        }
        $preRegisteredByAction = report_rpa_fc_01_fetch_pre_registered_by_action($db, $ids);
    }

    $title = report_training_type_resolved_report_title($reportRow, $trainingTypeFilter);

    $generatedAt = new DateTimeImmutable('now', new DateTimeZone('Europe/Madrid'));
    $reportCode = (string) ($reportRow['report_code'] ?? '');

    $spreadsheet = new Spreadsheet();
    $b = new ReportExcelBuilder($spreadsheet, 'RPAFC-01', 'F');

    $spreadsheet->getProperties()
        ->setCreator('Formació municipal')
        ->setTitle($reportCode . ' · ' . $programYear);

    $meta = [
        'code' => $reportCode,
        'title' => $title,
        'subtitle' => $title . ' · exportació Excel',
        'year' => $programYear,
        'generated_at' => $generatedAt,
        'include_personal_data' => $includePersonalData,
        'label_persones_preinscrites' => 'Persones preinscrites',
        'training_type_filter' => $trainingTypeFilter,
    ];

    $headerLastRow = report_excel_layout_render_report_header($b, $meta, $theme);

    if ($rows === []) {
        report_excel_layout_render_empty_message(
            $b,
            'No hi ha accions formatives per a aquest exercici amb els criteris seleccionats.',
            $theme
        );
        $b->skip(1);
        report_excel_layout_render_summary_block(
            $b,
            $theme,
            'Resum de l’informe',
            'final',
            report_rpa_fc_01_excel_summary_lines($totalSummary)
        );
        $b->skip(1);
        report_excel_layout_render_merged_detail_line(
            $b,
            report_training_type_scope_legend_ca($trainingTypeFilter),
            $theme
        );
        report_excel_layout_render_merged_detail_line(
            $b,
            $includePersonalData
                ? 'També s’hi visualitzen les persones preinscrites.'
                : 'No s’hi visualitzen les persones preinscrites.',
            $theme
        );
        report_excel_layout_apply_column_widths($b, report_excel_layout_column_widths_rpa_fc_01());
        report_excel_layout_configure_print($b->sheet, $headerLastRow);
        report_excel_layout_freeze_below_header($b->sheet, $headerLastRow);
        report_rpa_fc_01_excel_send_download($spreadsheet, $reportCode, $programYear, $includePersonalData, $trainingTypeFilter);

        return;
    }

    foreach ($blocks as $sub) {
        $subLabel = 'SUBPROGRAMA: ' . trim((string) $sub['code_display'] . ' ' . (string) $sub['name']);
        report_excel_layout_render_subprogram_heading($b, $subLabel, $theme);

        foreach ($sub['areas'] as $area) {
            $areaActions = $area['actions'];
            $areaSummary = report_rpa_fc_01_summary($areaActions);
            $areaLabel = 'ÀREA DE CONEIXEMENT: ' . trim((string) $area['code_display'] . ' ' . (string) $area['name']);
            report_excel_layout_render_area_heading($b, $areaLabel, $theme);

            $headers = ['Codi', 'Acció formativa', 'Dates previstes', 'Durada (h)', 'Places', 'Organitzador'];
            report_excel_layout_render_table_header($b, $headers, $theme);

            $aIdx = 0;
            foreach ($areaActions as $act) {
                $aid = (int) $act['id'];
                $py = (int) $act['program_year'];
                $an = (int) $act['action_number'];
                $displayCode = training_actions_format_display_code($py, $an);
                $orgCode = $act['organizer_code'] ?? null;
                $orgName = trim((string) ($act['organizer_name'] ?? ''));
                if ($orgName === '') {
                    $orgLabel = '—';
                } else {
                    $orgLabel = ($orgCode !== null && $orgCode !== '')
                        ? format_padded_code((int) $orgCode, 3) . ' ' . $orgName
                        : $orgName;
                }
                $dur = $act['planned_duration_hours'] ?? null;
                $durF = $dur !== null && $dur !== '' ? (float) $dur : null;
                $objText = trim((string) ($act['training_objectives'] ?? ''));
                $objDisplay = $objText === '' ? '—' : $objText;
                $destDisplay = trim((string) ($act['target_audience'] ?? '')) === '' ? '—' : (string) $act['target_audience'];

                $sheet = $b->sheet;
                $r = $b->row;
                $sheet->setCellValue('A' . $r, $displayCode);
                $sheet->setCellValue('B' . $r, (string) $act['name']);
                $sheet->setCellValue('C' . $r, report_rpa_fc_01_format_dates_cell($act));
                $sheet->setCellValue('D' . $r, report_rpa_fc_01_format_hours($durF));
                $sheet->setCellValue('E' . $r, (int) ($act['planned_places'] ?? 0));
                $sheet->setCellValue('F' . $r, $orgLabel);
                report_excel_style_apply_action_data_row($sheet, $r, $theme, $aIdx % 2 === 1, $b->lastCol);
                $b->row++;
                $aIdx++;

                report_excel_layout_render_merged_detail_line($b, 'Destinataris: ' . $destDisplay, $theme);
                report_excel_layout_render_merged_detail_line($b, 'Objectius formatius: ' . $objDisplay, $theme);

                if ($includePersonalData) {
                    $names = $preRegisteredByAction[$aid] ?? [];
                    $personesFmt = $names === []
                        ? ['line' => '—', 'more' => 0]
                        : report_rpa_fc_01_truncate_person_names($names, 12);
                    $pText = $personesFmt['line'];
                    if ($personesFmt['more'] > 0) {
                        $pText .= ' + ' . (int) $personesFmt['more'] . ' més';
                    }
                    report_excel_layout_render_merged_detail_line($b, 'Persones preinscrites: ' . $pText, $theme);
                }

                $b->skip(1);
            }

            report_excel_layout_render_summary_block(
                $b,
                $theme,
                'Resum (àrea)',
                'area',
                report_rpa_fc_01_excel_summary_lines($areaSummary)
            );
            $b->skip(2);
        }
    }

    report_excel_layout_render_summary_block(
        $b,
        $theme,
        'Resum de l’informe',
        'final',
        report_rpa_fc_01_excel_summary_lines($totalSummary)
    );
    $b->skip(1);
    $legendProg = report_training_type_scope_legend_ca($trainingTypeFilter);
    $legendPers = $includePersonalData
        ? 'També s’hi visualitzen les persones preinscrites.'
        : 'No s’hi visualitzen les persones preinscrites.';
    report_excel_layout_render_merged_detail_line($b, $legendProg, $theme);
    report_excel_layout_render_merged_detail_line($b, $legendPers, $theme);
    report_excel_layout_apply_column_widths($b, report_excel_layout_column_widths_rpa_fc_01());
    report_excel_layout_configure_print($b->sheet, $headerLastRow);
    report_excel_layout_freeze_below_header($b->sheet, $headerLastRow);

    report_rpa_fc_01_excel_send_download($spreadsheet, $reportCode, $programYear, $includePersonalData, $trainingTypeFilter);
}

function report_rpa_fc_01_excel_send_download(Spreadsheet $spreadsheet, string $reportCode, int $programYear, bool $includePersonalData, string $trainingTypeFilter = REPORT_TRAINING_TYPE_ALL): void
{
    $codePart = preg_replace('/[^A-Za-z0-9_-]+/', '_', $reportCode);
    if ($codePart === '') {
        $codePart = 'informe';
    }
    $filename = $codePart . '_Pla_anual_formacio_' . $programYear;
    if ($includePersonalData) {
        $filename .= '_persones_preinscrites';
    }
    if ($trainingTypeFilter !== REPORT_TRAINING_TYPE_ALL) {
        $filename .= '_tipus_' . preg_replace('/[^a-z0-9_]+/i', '_', $trainingTypeFilter);
    }
    $filename .= '.xlsx';

    report_excel_send_download($spreadsheet, $filename);
}
