<?php
declare(strict_types=1);
/** @var int $year */
/** @var string $reportCode */
/** @var string $reportTitle */
/** @var string|null $comentari */
/** @var bool $veureTreballador */
/** @var array{catalogs: array<string, array{areas: array<string, array{rows: list<array<string, mixed>>, totals: array, area_name: string}>, totals: array}>, grand: array} $grouped */
/** @var array<string, string> $catalogDescriptions */
/** @var string $logoPath */
/** @var string $generatedAt */
/** @var bool $rge01SkipHeader si true, només es pinta el cos (capçalera ja dins de report-print-shell) */

$rge01SkipHeader = !empty($rge01SkipHeader);

$rge01TotalCols = 1;

/** @param array<string, mixed> $r */
$row_sit = static function (array $r): string {
    $n = trim((string) ($r['administrative_status_name'] ?? ''));
    if ($n !== '') {
        return mb_substr($n, 0, 3, 'UTF-8');
    }

    return mb_substr(trim((string) ($r['status_text'] ?? '')), 0, 3, 'UTF-8');
};

/** @param array<string, mixed> $r */
$row_epi = static function (array $r): string {
    $e = $r['contribution_epigraph_id'] ?? null;
    if ($e === null || $e === '') {
        return '';
    }

    return str_pad((string) $e, 3, '0', STR_PAD_LEFT);
};

/**
 * @param array<string, float|int> $t Totals from report_rge01_empty_totals()
 * @param ''|'press' $pressVal formatted pressupost or empty
 * @param ''|'coef' $coefVal formatted coef SS or empty
 */
$rge01_render_retrib_money_row = static function (array $t, string $pressVal, string $coefVal, string $rowClass = 'rge-detail__line rge-detail__line--retrib'): void {
    ?>
    <div class="<?= e($rowClass) ?>">
        <div class="rge-money-cell">
            <span class="rge-money-cell__label">Sou base</span>
            <span class="rge-money-cell__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['sou_base'])) ?></span>
        </div>
        <div class="rge-money-cell">
            <span class="rge-money-cell__label">C. destinació</span>
            <span class="rge-money-cell__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['complement_dest'])) ?></span>
        </div>
        <div class="rge-money-cell">
            <span class="rge-money-cell__label">C. espec. gral</span>
            <span class="rge-money-cell__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['complement_gral'])) ?></span>
        </div>
        <div class="rge-money-cell">
            <span class="rge-money-cell__label">C. espec. esp</span>
            <span class="rge-money-cell__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['complement_esp'])) ?></span>
        </div>
        <div class="rge-money-cell">
            <span class="rge-money-cell__label">Suma mensual</span>
            <span class="rge-money-cell__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['suma_mensual'])) ?></span>
        </div>
        <div class="rge-money-cell">
            <span class="rge-money-cell__label">Pressup.</span>
            <span class="rge-money-cell__value"><?= e($pressVal) ?></span>
        </div>
        <div class="rge-money-cell">
            <span class="rge-money-cell__label">Suma anual</span>
            <span class="rge-money-cell__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['ret_any'])) ?></span>
        </div>
        <div class="rge-money-cell">
            <span class="rge-money-cell__label">Antiguitat</span>
            <span class="rge-money-cell__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['ant_any'])) ?></span>
        </div>
        <div class="rge-money-cell">
            <span class="rge-money-cell__label">C.Product.</span>
            <span class="rge-money-cell__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['prod_any'])) ?></span>
        </div>
        <div class="rge-money-cell">
            <span class="rge-money-cell__label">C.P.</span>
            <span class="rge-money-cell__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['cpt_any'])) ?></span>
        </div>
        <div class="rge-money-cell">
            <span class="rge-money-cell__label">Retribució total</span>
            <span class="rge-money-cell__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['ret_total'])) ?></span>
        </div>
        <div class="rge-money-cell">
            <span class="rge-money-cell__label">Coef.SS</span>
            <span class="rge-money-cell__value"><?= e($coefVal) ?></span>
        </div>
        <div class="rge-money-cell">
            <span class="rge-money-cell__label">S.Social</span>
            <span class="rge-money-cell__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['ss'])) ?></span>
        </div>
        <div class="rge-money-cell">
            <span class="rge-money-cell__label">MEI</span>
            <span class="rge-money-cell__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['mei'])) ?></span>
        </div>
    </div>
    <?php
};

/**
 * Bloc de resum (mateixa estructura per catàleg i general).
 *
 * @param array<string, float|int> $t Totals (report_rge01_empty_totals)
 */
$rge01_render_summary_block = static function (string $headingText, array $t, bool $withFootnote = false, bool $catalogPageBreak = false): void {
    $extraClass = $withFootnote ? ' report-rge-01__resum-general' : '';
    $catalogClass = $catalogPageBreak ? ' rge-catalog-summary-block rge-catalog-summary-block--page-break' : '';
    ?>
    <div class="report-rge-01__summary-block report-block--total<?= $extraClass ?><?= $catalogClass ?>">
        <h2 class="report-heading report-heading--total"><?= e($headingText) ?></h2>
        <p class="report-summary__list">
            Places: <span class="report-summary__value"><?= (int) $t['places'] ?></span>
            · Vacants: <span class="report-summary__value"><?= (int) $t['vacants'] ?></span><br>
            Sou base: <span class="report-summary__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['sou_base'])) ?></span><br>
            Complement destinació: <span class="report-summary__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['complement_dest'])) ?></span><br>
            Complement específic gral.: <span class="report-summary__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['complement_gral'])) ?></span><br>
            Complement específic esp.: <span class="report-summary__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['complement_esp'])) ?></span><br>
            Suma mensual: <span class="report-summary__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['suma_mensual'])) ?></span><br>
            Retribució anual llocs: <span class="report-summary__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['ret_any'])) ?></span><br>
            Total antiguitats: <span class="report-summary__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['ant_any'])) ?></span><br>
            Total complements productivitat: <span class="report-summary__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['prod_any'])) ?></span><br>
            Total C.P.: <span class="report-summary__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['cpt_any'])) ?></span><br>
            Total retribucions: <span class="report-summary__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['ret_total'])) ?></span><br>
            Total Seguretat Social: <span class="report-summary__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['ss'])) ?></span><br>
            Total MEI: <span class="report-summary__value"><?= e(maintenance_format_currency_eur_2_display((string) $t['mei'])) ?></span>
        </p>
        <?php if ($withFootnote): ?>
            <p class="report-summary__footnote">Coeficient SS i SS anual: càlcul provisional (vegeu <code>report_rge01_social_security_annual_placeholder</code> a <code>includes/reports/report_helpers.php</code>) pendent de validació amb Access.</p>
        <?php endif; ?>
    </div>
    <?php
};

if (!$rge01SkipHeader) {
    $catalogYear = $year;
    require APP_ROOT . '/views/partials/report_print_header.php';
}

$catalogs = $grouped['catalogs'];
ksort($catalogs, SORT_STRING);
if ($catalogs === []) {
    echo '<p class="report-empty">No hi ha dades per als criteris seleccionats (persones actives amb lloc, llocs actius, àrea diferent de 9).</p>';

    return;
}
?>
<div class="report-table-scroll report-table-wrap report-table-wrap--rge-01">
    <div class="rge-report-wide">
        <?php foreach ($catalogs as $catKey => $catBlock): ?>
            <table class="report-table report-table--rge-01">
                <tbody class="rge-report-body">
                <?php
                $areas = $catBlock['areas'];
                ksort($areas, SORT_STRING);
                foreach ($areas as $areaId => $areaBlock) {
                    $areaRows = $areaBlock['rows'];
                    $areaTot = $areaBlock['totals'];
                    $areaHead = (string) ($areaBlock['area_name'] ?? '');

                    foreach ($areaRows as $r) {
                        $isV = !empty($r['calc_is_vacant']);
                        $rge01CPlz = '';
                        if ($r['position_id'] !== null && $r['position_id'] !== '') {
                            $rge01CPlz = trim(format_padded_code((int) $r['position_id'], 4));
                            if ($rge01CPlz === '0' || $rge01CPlz === '0000') {
                                $rge01CPlz = '';
                            }
                        }
                        [$org, $codi] = (static function (?string $jid): array {
                            $disp = maintenance_format_job_position_code_display($jid);
                            if ($disp === '' || strpos($disp, '.') === false) {
                                return ['', ''];
                            }
                            $parts = explode('.', $disp, 2);

                            return [$parts[0] ?? '', $parts[1] ?? ''];
                        })($r['job_position_id'] ?? null);
                        $cPer = $isV ? '—' : format_padded_code((int) ($r['person_id'] ?? 0), 5);
                        $personLine = $veureTreballador
                            ? ($isV ? '—' : $cPer . ' ' . report_rge01_person_display_name($r, $isV))
                            : '';
                        ?>
                        <tr class="rge-detail-block">
                            <td colspan="<?= (int) $rge01TotalCols ?>">
                                <div class="rge-detail">
                                    <div class="rge-detail__line rge-detail__line--ident<?= $veureTreballador ? '' : ' rge-detail__line--ident-no-worker' ?>">
                                        <div><strong>Org.</strong><span><?= e($org) ?></span></div>
                                        <div><strong>Codi</strong><span><?= e($codi) ?></span></div>
                                        <div class="rge-detail__job-col">
                                            <strong>Nom del lloc</strong>
                                            <span class="rge-detail__job-name"><?= e((string) ($r['job_title'] ?? '')) ?></span>
                                        </div>
                                        <div><strong>C.Plç</strong><span><?= e($rge01CPlz) ?></span></div>
                                        <?php if ($veureTreballador): ?>
                                            <div class="rge-detail__person-col">
                                                <strong>Treballador/a</strong>
                                                <span class="rge-detail__worker-name"><?= e($personLine) ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div><strong>C.Per</strong><span><?= e($cPer) ?></span></div>
                                        <?php endif; ?>
                                        <div><strong>Sit.</strong><span><?= e($row_sit($r)) ?></span></div>
                                        <div><strong>Epi</strong><span><?= e($row_epi($r)) ?></span></div>
                                        <div><strong>Emp</strong><span><?= e((string) ($r['company_id'] ?? '')) ?></span></div>
                                        <div><strong>Rel.</strong><span><?= e(trim((string) ($r['legal_relation_id'] ?? ''))) ?></span></div>
                                        <div><strong>Tipus</strong><span><?= e(trim((string) ($r['job_type_id'] ?? ''))) ?></span></div>
                                        <div><strong>Grup</strong><span><?= e((string) ($r['calc_eff_group'] ?? '')) ?></span></div>
                                        <div><strong>Nivell</strong><span><?= e(trim((string) ($r['jp_organic_level'] ?? ''))) ?></span></div>
                                        <div><strong>Dedicació</strong><span><?= e(report_rge01_format_percent_from_fraction($r['calc_dedication'] ?? null)) ?></span></div>
                                    </div>
                                    <?php
                                    $rge01_render_retrib_money_row(
                                        [
                                            'sou_base' => (float) ($r['calc_sou_base_real'] ?? 0),
                                            'complement_dest' => (float) ($r['calc_complement_dest'] ?? 0),
                                            'complement_gral' => (float) ($r['calc_complement_gral'] ?? 0),
                                            'complement_esp' => (float) ($r['calc_complement_esp'] ?? 0),
                                            'suma_mensual' => (float) ($r['calc_suma_mensual'] ?? 0),
                                            'ret_any' => (float) ($r['calc_ret_any'] ?? 0),
                                            'ant_any' => (float) ($r['calc_ant_any'] ?? 0),
                                            'prod_any' => (float) ($r['calc_prod_any'] ?? 0),
                                            'cpt_any' => (float) ($r['calc_cpt_any'] ?? 0),
                                            'ret_total' => (float) ($r['calc_ret_total'] ?? 0),
                                            'ss' => (float) ($r['calc_ss_annual'] ?? 0),
                                            'mei' => (float) ($r['calc_mei'] ?? 0),
                                        ],
                                        report_rge01_format_percent_from_fraction($r['calc_press'] ?? null),
                                        $isV ? '' : (string) maintenance_format_ss_coeff_percent_display($r['ss_coeff'] ?? null)
                                    );
                        ?>
                                </div>
                            </td>
                        </tr>
                        <?php
                    }

                    $sumaAreaTitle = 'Suma àrea ' . trim(trim((string) $areaId) . ' ' . $areaHead);
                    ?>
                    <tr class="rge-area-subtotal-row">
                        <td colspan="<?= (int) $rge01TotalCols ?>">
                            <div class="rge-area-subtotal-block">
                                <div class="rge-area-subtotal-header">
                                    <span><?= e($sumaAreaTitle) ?></span>
                                    <span>Places: <?= (int) $areaTot['places'] ?></span>
                                    <span>Vacants: <?= (int) $areaTot['vacants'] ?></span>
                                </div>
                                <?php $rge01_render_retrib_money_row($areaTot, '', '', 'rge-area-subtotal-values rge-area-subtotal-values--bold'); ?>
                            </div>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
            <?php
            $catalogDescription = trim((string) ($catalogDescriptions[$catKey] ?? ''));
            $catHeading = 'Resum catàleg' . ($catKey === '' ? '' : ' ' . $catKey);
            if ($catalogDescription !== '') {
                $catHeading .= ' - ' . $catalogDescription;
            }
            $rge01_render_summary_block($catHeading, $catBlock['totals'], false, true);
            ?>
        <?php endforeach; ?>
    </div>
</div>
<?php $rge01_render_summary_block('Resum general', $grouped['grand'], true); ?>
