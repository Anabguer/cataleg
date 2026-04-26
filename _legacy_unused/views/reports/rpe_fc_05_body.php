<?php
declare(strict_types=1);
/** @var list<array<string,mixed>> $rows */
?>
<div class="report-rpe-fc-05">
    <div class="report-rpe-fc-05__container">
        <?php if ($rows === []): ?>
            <p class="report-empty report-rpe-fc-05__empty"><?= e('No hi ha persones sense correu vinculades a accions formatives per a aquest exercici.') ?></p>
        <?php else: ?>
            <div class="report-table-wrap report-rpe-fc-05__wrap">
                <table class="report-table report-rpe-fc-05__table">
                    <colgroup>
                        <col class="report-rpe-fc-05__col-code">
                        <col class="report-rpe-fc-05__col-name">
                    </colgroup>
                    <thead>
                        <tr>
                            <th scope="col">Codi</th>
                            <th scope="col">Nom</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td class="report-rpe-fc-05__cell-code"><?= e(format_padded_code((int) ($r['person_code'] ?? 0), 5)) ?></td>
                                <td><?= e(people_format_surname_first($r)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
