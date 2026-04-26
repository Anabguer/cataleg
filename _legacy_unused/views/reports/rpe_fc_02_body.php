<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $actions */
/** @var float $totalHours */
/** @var bool $withAttendees */

$totalFormatted = $totalHours > 0.0
    ? e(report_rpa_fc_01_format_hours($totalHours)) . ' hores'
    : '0,00 hores';

?>
<div class="report-rpe-fc-02">
<?php
$reportRpeFc02EmptyMessage = $reportRpeFc02EmptyMessage ?? 'No hi ha accions formatives amb assistència registrada per a aquest exercici.';
?>
<?php if ($actions === []): ?>
    <p class="report-empty"><?= e($reportRpeFc02EmptyMessage) ?></p>
<?php else: ?>
    <div class="report-table-wrap report-rpe-fc-02__wrap">
        <table class="report-table report-rpe-fc-02__grid">
            <colgroup>
                <col class="report-rpe-fc-02__col-main">
                <col class="report-rpe-fc-02__col-dur">
            </colgroup>
            <?php
            $reportRpeFc02FirstTh = $reportRpeFc02FirstTh ?? 'Acció formativa / Assistents';
            $reportRpeFc02SecondTh = $reportRpeFc02SecondTh ?? 'Durada';
            ?>
            <thead>
                <tr>
                    <th scope="col"><?= e($reportRpeFc02FirstTh) ?></th>
                    <th scope="col" class="report-rpe-fc-02__th-dur"><?= e($reportRpeFc02SecondTh) ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($actions as $act): ?>
                    <?php
                    $codeAct = (string) ($act['action_display_code'] ?? '');
                    $aname = (string) ($act['action_name'] ?? '');
                    $durParts = $act['duration_parts'] ?? null;
                    $durPartsOk = is_array($durParts) && isset($durParts['expr'], $durParts['result']);
                    $useSimpleDur = array_key_exists('duration_simple', $act);
                    $durSimple = $useSimpleDur ? (string) ($act['duration_simple'] ?? '') : '';
                    $attendees = $act['attendees'] ?? [];
                    $isArr = is_array($attendees);
                    $durCellClass = 'report-rpe-fc-02__cell-dur' . ($useSimpleDur ? '' : ' report-rpe-fc-02__cell-dur--split');
                    ?>
                <tr class="report-rpe-fc-02__action-row">
                    <td class="report-rpe-fc-02__cell-main">
                        <span class="report-rpe-fc-02__action-code"><?= e($codeAct) ?></span><?= $aname !== '' ? ' ' : '' ?><span class="report-rpe-fc-02__action-name"><?= e($aname) ?></span>
                    </td>
                    <td class="<?= e($durCellClass) ?>">
                        <?php if ($useSimpleDur): ?>
                            <?= e($durSimple) ?>
                        <?php elseif (!$durPartsOk): ?>
                            —
                        <?php else: ?>
                            <span class="report-rpe-fc-02__dur-line">
                                <span class="report-rpe-fc-02__dur-expr"><?= e((string) $durParts['expr']) ?></span>
                                <span class="report-rpe-fc-02__dur-result"><?= e((string) $durParts['result']) ?></span>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
                    <?php if ($withAttendees && $isArr): ?>
                        <?php foreach ($attendees as $att): ?>
                            <?php $pname = trim((string) ($att['display_name'] ?? '')); ?>
                <tr class="report-rpe-fc-02__attendee-row">
                    <td class="report-rpe-fc-02__cell-attendee">
                        <div class="report-rpe-fc-02__attendee-inner"><?= e($pname) ?></div>
                    </td>
                    <td class="report-rpe-fc-02__cell-dur report-rpe-fc-02__cell-dur--empty"></td>
                </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <tr class="report-rpe-fc-02__sep-row" aria-hidden="true">
                    <td colspan="2" class="report-rpe-fc-02__sep-cell"><div class="report-rpe-fc-02__sep-line"></div></td>
                </tr>
                <?php endforeach; ?>
                <tr class="report-rpe-fc-02__total-row">
                    <th scope="row" class="report-rpe-fc-02__cell-total-label">Total hores realitzades</th>
                    <td class="report-rpe-fc-02__cell-dur report-rpe-fc-02__cell-total-val"><?= $totalFormatted ?></td>
                </tr>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</div>
