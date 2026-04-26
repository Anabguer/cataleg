<?php
declare(strict_types=1);

/**
 * Genera la seqüència de pàgines a mostrar amb marcadors 'ellipsis' on hi ha salts.
 *
 * Inclou sempre 1 i l’última pàgina, el veïnat de la pàgina actual (±1), i blocs inicial/final
 * quan la pàgina actual és a prop del començament o del final (fins a 3 pàgines).
 *
 * @return list<int|string>
 */
function pagination_visible_items(int $current, int $totalPages): array
{
    if ($totalPages < 2) {
        return [1];
    }
    $current = max(1, min($current, $totalPages));

    $set = [];
    $add = static function (int $p) use (&$set, $totalPages): void {
        if ($p >= 1 && $p <= $totalPages) {
            $set[$p] = true;
        }
    };

    $add(1);
    $add($totalPages);
    for ($i = max(1, $current - 1); $i <= min($totalPages, $current + 1); $i++) {
        $add($i);
    }
    if ($current <= 3) {
        for ($i = 1; $i <= min(3, $totalPages); $i++) {
            $add($i);
        }
    }
    if ($current >= $totalPages - 2) {
        for ($i = max(1, $totalPages - 2); $i <= $totalPages; $i++) {
            $add($i);
        }
    }

    $pages = array_keys($set);
    sort($pages, SORT_NUMERIC);

    $out = [];
    $prev = null;
    foreach ($pages as $p) {
        if ($prev !== null && $p - $prev > 1) {
            $out[] = 'ellipsis';
        }
        $out[] = $p;
        $prev = $p;
    }

    return $out;
}
