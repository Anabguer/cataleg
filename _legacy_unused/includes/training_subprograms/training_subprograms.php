<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/reports/report_training_type_filter.php';

function training_subprograms_list_sort_keys(): array { return ['subprogram_code', 'name', 'is_active', 'created_at']; }
function training_subprograms_normalize_sort(string $sortBy, string $sortDir): array
{
    $allowed = array_flip(training_subprograms_list_sort_keys());
    return ['by' => isset($allowed[$sortBy]) ? $sortBy : 'subprogram_code', 'dir' => strtolower($sortDir) === 'desc' ? 'desc' : 'asc'];
}
function training_subprograms_list_filters_clause(array $filters): array
{
    $where = ['1=1']; $params = [];
    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(name LIKE :q1 OR CAST(subprogram_code AS CHAR) LIKE :q2)';
        $like = '%' . $q . '%';
        $params['q1'] = $like; $params['q2'] = $like;
    }
    $active = trim((string) ($filters['active'] ?? ''));
    if ($active === '1' || $active === '0') { $where[] = 'is_active = :active'; $params['active'] = (int) $active; }
    return ['where' => $where, 'params' => $params];
}
function training_subprograms_list_order_sql(string $sortBy, string $sortDir): string
{
    $n = training_subprograms_normalize_sort($sortBy, $sortDir); $dir = strtoupper($n['dir']) === 'DESC' ? 'DESC' : 'ASC';
    switch ($n['by']) {
        case 'name':
            return 'name ' . $dir;
        case 'is_active':
            return 'is_active ' . $dir;
        case 'created_at':
            return 'created_at ' . $dir;
        default:
            return 'subprogram_code ' . $dir;
    }
}
function training_subprograms_count(PDO $db, array $filters): int
{
    $f = training_subprograms_list_filters_clause($filters);
    $sql = 'SELECT COUNT(*) AS c FROM training_subprograms WHERE ' . implode(' AND ', $f['where']);
    $st = $db->prepare($sql); $st->execute($f['params']); $r = $st->fetch(); return (int) ($r['c'] ?? 0);
}
function training_subprograms_list(PDO $db, array $filters, string $sortBy, string $sortDir, int $limit, int $offset): array
{
    $f = training_subprograms_list_filters_clause($filters);
    $sql = 'SELECT * FROM training_subprograms
            WHERE ' . implode(' AND ', $f['where']) . '
            ORDER BY ' . training_subprograms_list_order_sql($sortBy, $sortDir) . '
            ' . db_sql_limit_offset($limit, $offset);
    $params = $f['params'];
    $st = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $st->bindValue(':' . $k, $v, PDO::PARAM_STR);
    }
    $st->execute();
    return $st->fetchAll() ?: [];
}
function training_subprograms_normalize_pagination(int $page, int $perPage, int $total): array
{
    if ($perPage < 1) $perPage = 20; if ($perPage > 100) $perPage = 100; if ($page < 1) $page = 1;
    $tp = $total > 0 ? (int) ceil($total / $perPage) : 1; if ($page > $tp) $page = $tp;
    return ['page' => $page, 'per_page' => $perPage, 'total_pages' => $tp, 'offset' => ($page - 1) * $perPage];
}
function training_subprograms_get_by_id(PDO $db, int $id): ?array
{
    if ($id < 1) return null;
    $st = $db->prepare('SELECT * FROM training_subprograms WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]); $r = $st->fetch(); return $r ?: null;
}
function training_subprograms_validate_save(PDO $db, array $data, ?int $id = null): array
{
    $errors = [];
    $codeRaw = trim((string) ($data['subprogram_code'] ?? ''));
    if ($codeRaw === '' || !ctype_digit($codeRaw)) $errors['subprogram_code'] = 'El codi és obligatori i numèric.';
    $name = trim((string) ($data['name'] ?? ''));
    if ($name === '') $errors['name'] = 'El nom és obligatori.';
    if (!isset($errors['subprogram_code'])) {
        $sql = 'SELECT id FROM training_subprograms WHERE subprogram_code = :code';
        $params = ['code' => (int) $codeRaw];
        if ($id !== null) { $sql .= ' AND id <> :id'; $params['id'] = $id; }
        $st = $db->prepare($sql . ' LIMIT 1'); $st->execute($params);
        if ($st->fetch()) $errors['subprogram_code'] = 'Aquest codi ja existeix.';
    }
    if (array_key_exists('training_type', $data)) {
        $rawT = trim((string) $data['training_type']);
        if ($rawT !== '' && report_training_type_normalize_input($rawT) === null) {
            $errors['training_type'] = 'Tipus de formació no vàlid.';
        }
    }
    return $errors;
}
/**
 * Compatibilitat: només s’usa si el payload encara envia el booleà antic sense training_type vàlid.
 *
 * @deprecated Preferir training_type al formulari i a l’API.
 */
function training_subprograms_is_programmed_training_value(array $data): int
{
    if (!array_key_exists('is_programmed_training', $data)) {
        return 1;
    }

    return isset($data['is_programmed_training']) && (string) $data['is_programmed_training'] === '1' ? 1 : 0;
}

/**
 * Resol el tipus de formació des del payload (training_type o, en fallback, is_programmed_training).
 */
function training_subprograms_resolve_training_type(array $data): string
{
    if (array_key_exists('training_type', $data)) {
        $fromField = report_training_type_normalize_input((string) $data['training_type']);
        if ($fromField !== null) {
            return $fromField;
        }
    }

    return training_subprograms_is_programmed_training_value($data) === 1
        ? REPORT_TRAINING_TYPE_PROGRAMMED
        : REPORT_TRAINING_TYPE_NON_PROGRAMMED;
}

function training_subprograms_sync_programmed_flag_from_type(string $trainingType): int
{
    return $trainingType === REPORT_TRAINING_TYPE_PROGRAMMED ? 1 : 0;
}

/** Etiqueta en català per al llistat de manteniment (reutilitza el mateix mapa que els informes). */
function training_subprograms_training_type_label_ca(?string $trainingType): string
{
    $t = strtolower(trim((string) ($trainingType ?? '')));
    if ($t === '' || !report_training_type_is_allowed($t)) {
        return '—';
    }

    return report_training_type_label_ca($t);
}

function training_subprograms_create(PDO $db, array $data): int
{
    $errors = training_subprograms_validate_save($db, $data, null); if ($errors !== []) throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    $trainingType = training_subprograms_resolve_training_type($data);
    $ipt = training_subprograms_sync_programmed_flag_from_type($trainingType);
    $st = $db->prepare('INSERT INTO training_subprograms (subprogram_code, name, is_active, is_programmed_training, training_type) VALUES (:code, :name, :is_active, :is_programmed_training, :training_type)');
    $st->execute([
        'code' => (int) $data['subprogram_code'],
        'name' => trim((string) $data['name']),
        'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0,
        'is_programmed_training' => $ipt,
        'training_type' => $trainingType,
    ]);
    return (int) $db->lastInsertId();
}
function training_subprograms_update(PDO $db, int $id, array $data): void
{
    if ($id < 1 || !training_subprograms_get_by_id($db, $id)) throw new RuntimeException('Subprograma no trobat');
    $errors = training_subprograms_validate_save($db, $data, $id); if ($errors !== []) throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    $trainingType = training_subprograms_resolve_training_type($data);
    $ipt = training_subprograms_sync_programmed_flag_from_type($trainingType);
    $st = $db->prepare('UPDATE training_subprograms SET subprogram_code = :code, name = :name, is_active = :is_active, is_programmed_training = :is_programmed_training, training_type = :training_type WHERE id = :id');
    $st->execute([
        'id' => $id,
        'code' => (int) $data['subprogram_code'],
        'name' => trim((string) $data['name']),
        'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0,
        'is_programmed_training' => $ipt,
        'training_type' => $trainingType,
    ]);
}
function training_subprograms_delete(PDO $db, int $id): void
{
    if ($id < 1) throw new InvalidArgumentException('ID invàlid');
    $st = $db->prepare('DELETE FROM training_subprograms WHERE id = :id LIMIT 1'); $st->execute(['id' => $id]);
    if ($st->rowCount() === 0) throw new RuntimeException('Subprograma no trobat');
}
function training_subprograms_parse_validation_exception(Throwable $e): ?array
{
    if (!$e instanceof InvalidArgumentException) return null;
    $d = json_decode($e->getMessage(), true); return is_array($d) ? $d : null;
}
