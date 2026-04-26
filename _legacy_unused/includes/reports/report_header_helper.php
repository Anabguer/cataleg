<?php
declare(strict_types=1);

/**
 * Cabecera reutilitzable per a informes HTML / impressió.
 *
 * @param array{
 *   report_code: string,
 *   report_title: string,
 *   program_year: int,
 *   generated_at: DateTimeImmutable|DateTimeInterface,
 *   logo_path?: string
 * } $ctx
 */
function report_header_render(array $ctx): void
{
    $logoPath = (string) ($ctx['logo_path'] ?? 'img/logos/color_esquerra.png');
    $abs = APP_ROOT . '/assets/' . ltrim($logoPath, '/');
    $logoUrl = is_readable($abs) ? asset_url($logoPath) : asset_url(page_header_escut_path());

    $gen = $ctx['generated_at'];
    if ($gen instanceof DateTimeImmutable) {
        $generatedAt = $gen;
    } elseif ($gen instanceof DateTimeInterface) {
        $generatedAt = DateTimeImmutable::createFromInterface($gen);
    } else {
        $generatedAt = new DateTimeImmutable('now', new DateTimeZone('Europe/Madrid'));
    }

    $programYear = (int) $ctx['program_year'];
    $report_code = (string) $ctx['report_code'];
    $report_title = (string) $ctx['report_title'];

    require APP_ROOT . '/views/reports/partials/report_header.php';
}
