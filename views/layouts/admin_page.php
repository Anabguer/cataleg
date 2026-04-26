<?php
declare(strict_types=1);
/**
 * Layout administratiu: encadena els parcials page_header, action_bar, filter_card i data_table,
 * i després imprimeix contingut extra (cards, formularis, alertes, etc.).
 *
 * Variables (totes opcionals excepte que $pageHeader hagi de tenir almenys title per a UX):
 *
 * @var array<string,mixed> $pageHeader        Títol, subtítol, icon, help_url…
 * @var string               $actionBarInner   HTML dels botons de la barra d’accions (buit = no es renderitza la barra)
 * @var string               $filterCardInner  HTML dels camps de filtre (buit = s’omet el bloc de filtres)
 * @var string               $dataTableInner   HTML de la <table> (buit = s’omet la taula)
 * @var string               $pageContentExtra HTML sota la taula (alertes, form-card, etc.)
 *
 * Opcionals només si hi ha filtre:
 * @var bool|null            $filterExpanded
 * @var string|null          $filterSummaryLabel
 * @var bool|null            $filterShowClear
 *
 * Opcionals només si hi ha taula:
 * @var string|null          $dataTableCaption
 * @var string|null          $dataTableToolbar
 */

$pageHeader = $pageHeader ?? [];
require APP_ROOT . '/views/partials/page_header.php';

$actionBarInner = $actionBarInner ?? '';
require APP_ROOT . '/views/partials/action_bar.php';

$filterCardInner = trim((string) ($filterCardInner ?? ''));
if ($filterCardInner !== '') {
    $filterExpanded = (bool) ($filterExpanded ?? true);
    $filterSummaryLabel = (string) ($filterSummaryLabel ?? 'Filtres');
    $filterShowClear = (bool) ($filterShowClear ?? true);
    require APP_ROOT . '/views/partials/filter_card.php';
}

$dataTableInner = trim((string) ($dataTableInner ?? ''));
if ($dataTableInner !== '') {
    $dataTableCaption = $dataTableCaption ?? null;
    $dataTableToolbar = $dataTableToolbar ?? null;
    require APP_ROOT . '/views/partials/data_table.php';
}

$pageContentExtra = $pageContentExtra ?? '';
if ($pageContentExtra !== '') {
    echo $pageContentExtra;
}
