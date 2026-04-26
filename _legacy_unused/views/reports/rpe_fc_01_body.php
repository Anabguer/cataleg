<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $peopleBlocks */
/** @var int $programYear */

?>
<div class="report-rpe-fc-01">
<?php if ($peopleBlocks === []): ?>
    <p class="report-empty">No hi ha persones amb formació realitzada (assistència registrada) per a aquest exercici.</p>
<?php else: ?>
    <?php foreach ($peopleBlocks as $pIdx => $person): ?>
        <?php
        $personCode = (int) ($person['person_code'] ?? 0);
        $codeStr = format_padded_code($personCode, 5);
        $pName = trim((string) ($person['display_name'] ?? ''));
        $personSectionClass = 'report-rpe-fc-01__person';
        if ($pIdx > 0) {
            $personSectionClass .= ' report-rpe-fc-01__person--pagebreak';
        }
        ?>
    <section class="<?= e($personSectionClass) ?>">
        <header class="report-rpe-fc-01__person-banner">
            <span class="report-rpe-fc-01__person-tag">Persona:</span>
            <span class="report-rpe-fc-01__person-code"><?= e($codeStr) ?></span>
            <span class="report-rpe-fc-01__person-name"><?= e($pName) ?></span>
        </header>
        <?php foreach ($person['subprograms'] ?? [] as $sub): ?>
        <div class="report-rpe-fc-01__subprogram">
            <h2 class="report-heading report-heading--subprogram report-rpe-fc-01__subprogram-heading">
                <span class="report-heading__label">Subprograma</span>
                <span class="report-heading__text"><?= e(trim((string) $sub['code_display'] . ' ' . (string) $sub['name'])) ?></span>
            </h2>
            <div class="report-table-wrap report-rpe-fc-01__table-wrap">
                <table class="report-table report-rpe-fc-01__actions-table">
                    <colgroup>
                        <col class="report-rpe-fc-01__col-action">
                        <col class="report-rpe-fc-01__col-dates">
                        <col class="report-rpe-fc-01__col-duration">
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col">Codi i descripció de l’acció</th>
                            <th scope="col">Dates previstes</th>
                            <th scope="col">Durada real</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sub['actions'] ?? [] as $act): ?>
                            <?php
                            $codeAct = (string) ($act['action_display_code'] ?? '');
                            $aname = (string) ($act['action_name'] ?? '');
                            $durF = $act['actual_duration_hours_f'] ?? null;
                            $durCell = $durF === null ? '—' : e(report_rpa_fc_01_format_hours($durF)) . ' hores';
                            $plannedLines = $act['planned_date_lines'] ?? [];
                            ?>
                        <tr class="report-rpe-fc-01__action-row">
                            <td class="report-rpe-fc-01__cell-action">
                                <span class="report-rpe-fc-01__action-code"><?= e($codeAct) ?></span><?= $aname !== '' ? ' ' : '' ?><span class="report-rpe-fc-01__action-name"><?= e($aname) ?></span>
                            </td>
                            <td class="report-rpe-fc-01__cell-dates"><?php
                            $dateLines = report_rpe_fc_01_dates_display_lines(is_array($plannedLines) ? $plannedLines : [], 3);
                            if ($dateLines === []) {
                                echo '—';
                            } else {
                                foreach ($dateLines as $i => $line) {
                                    if ($i > 0) {
                                        echo '<br>';
                                    }
                                    echo e($line);
                                }
                            }
                            ?></td>
                            <td class="report-rpe-fc-01__cell-duration"><?= $durCell ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    </section>
    <?php endforeach; ?>
<?php endif; ?>
</div>
