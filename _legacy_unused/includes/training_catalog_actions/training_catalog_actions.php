<?php
declare(strict_types=1);

function training_catalog_actions_next_action_code(PDO $db): int
{
    $st = $db->query('SELECT COALESCE(MAX(action_code), 0) + 1 AS n FROM training_catalog_actions');
    $r = $st->fetch();
    $n = (int) ($r['n'] ?? 1);
    return $n < 1 ? 1 : $n;
}

/** @param mixed $v */
function training_catalog_actions_null_if_empty($v): ?string
{
    if ($v === null) {
        return null;
    }
    $t = trim((string) $v);
    return $t === '' ? null : $t;
}

/**
 * Totes les àrees (filtre del llistat): actives i inactives.
 *
 * @return list<array{id:int,knowledge_area_code:int,name:string}>
 */
function training_catalog_actions_knowledge_areas_for_filter(PDO $db): array
{
    $st = $db->query('SELECT id, knowledge_area_code, name FROM knowledge_areas ORDER BY knowledge_area_code ASC');
    return $st->fetchAll() ?: [];
}

/**
 * Només àrees actives per al select del modal d’alta (i base per edició; àrea inactiva assignada s’afegeix per JS/API).
 *
 * @return list<array{id:int,knowledge_area_code:int,name:string}>
 */
function training_catalog_actions_knowledge_areas_for_form_modal(PDO $db): array
{
    $st = $db->query('SELECT id, knowledge_area_code, name FROM knowledge_areas WHERE is_active = 1 ORDER BY knowledge_area_code ASC');
    return $st->fetchAll() ?: [];
}

function training_catalog_actions_knowledge_area_exists(PDO $db, int $id): bool
{
    if ($id < 1) {
        return false;
    }
    $st = $db->prepare('SELECT 1 FROM knowledge_areas WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]);
    return (bool) $st->fetch();
}

/** @return null|bool null si no existeix */
function training_catalog_actions_knowledge_area_is_active_flag(PDO $db, int $id): ?bool
{
    if ($id < 1) {
        return null;
    }
    $st = $db->prepare('SELECT is_active FROM knowledge_areas WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]);
    $r = $st->fetch();
    if (!$r) {
        return null;
    }
    return !empty($r['is_active']);
}

function training_catalog_actions_list_sort_keys(): array
{
    return [
        'action_code',
        'name',
        'knowledge_area',
        'expected_duration_hours',
        'status',
        'is_active',
        'created_at',
    ];
}

function training_catalog_actions_normalize_sort(string $sortBy, string $sortDir): array
{
    $allowed = array_flip(training_catalog_actions_list_sort_keys());
    return [
        'by' => isset($allowed[$sortBy]) ? $sortBy : 'action_code',
        'dir' => strtolower($sortDir) === 'desc' ? 'desc' : 'asc',
    ];
}

function training_catalog_actions_list_from_clause(): string
{
    return 'training_catalog_actions tca
            INNER JOIN knowledge_areas ka ON ka.id = tca.knowledge_area_id';
}

function training_catalog_actions_list_filters_clause(array $filters): array
{
    $where = ['1=1'];
    $params = [];
    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(tca.name LIKE :q1 OR CAST(tca.action_code AS CHAR) LIKE :q2 OR ka.name LIKE :q3
            OR tca.status LIKE :q4 OR tca.target_audience LIKE :q5 OR tca.training_objectives LIKE :q6)';
        $like = '%' . $q . '%';
        $params['q1'] = $like;
        $params['q2'] = $like;
        $params['q3'] = $like;
        $params['q4'] = $like;
        $params['q5'] = $like;
        $params['q6'] = $like;
    }
    $active = trim((string) ($filters['active'] ?? ''));
    if ($active === '1' || $active === '0') {
        $where[] = 'tca.is_active = :active';
        $params['active'] = (int) $active;
    }
    $kaId = null_if_empty_int($filters['knowledge_area_id'] ?? null);
    if ($kaId !== null) {
        $where[] = 'tca.knowledge_area_id = :knowledge_area_id';
        $params['knowledge_area_id'] = $kaId;
    }
    $st = trim((string) ($filters['status'] ?? ''));
    if ($st !== '') {
        $where[] = 'tca.status LIKE :status';
        $params['status'] = '%' . $st . '%';
    }
    return ['where' => $where, 'params' => $params];
}

function training_catalog_actions_list_order_sql(string $sortBy, string $sortDir): string
{
    $n = training_catalog_actions_normalize_sort($sortBy, $sortDir);
    $dir = strtoupper($n['dir']) === 'DESC' ? 'DESC' : 'ASC';
    switch ($n['by']) {
        case 'name':
            return 'tca.name ' . $dir;
        case 'knowledge_area':
            return 'ka.knowledge_area_code ' . $dir . ', ka.name ' . $dir;
        case 'expected_duration_hours':
            return '(tca.expected_duration_hours IS NULL) ASC, tca.expected_duration_hours ' . $dir;
        case 'status':
            return '(tca.status IS NULL OR tca.status = \'\') ASC, tca.status ' . $dir;
        case 'is_active':
            return 'tca.is_active ' . $dir;
        case 'created_at':
            return 'tca.created_at ' . $dir;
        default:
            return 'tca.action_code ' . $dir;
    }
}

function training_catalog_actions_count(PDO $db, array $filters): int
{
    $f = training_catalog_actions_list_filters_clause($filters);
    $sql = 'SELECT COUNT(*) AS c FROM ' . training_catalog_actions_list_from_clause() . '
            WHERE ' . implode(' AND ', $f['where']);
    $st = $db->prepare($sql);
    $st->execute($f['params']);
    $r = $st->fetch();
    return (int) ($r['c'] ?? 0);
}

/**
 * @return list<array<string,mixed>>
 */
function training_catalog_actions_list(PDO $db, array $filters, string $sortBy, string $sortDir, int $limit, int $offset): array
{
    $f = training_catalog_actions_list_filters_clause($filters);
    $sql = 'SELECT tca.*, ka.knowledge_area_code AS ka_code, ka.name AS knowledge_area_name
            FROM ' . training_catalog_actions_list_from_clause() . '
            WHERE ' . implode(' AND ', $f['where']) . '
            ORDER BY ' . training_catalog_actions_list_order_sql($sortBy, $sortDir) . '
            ' . db_sql_limit_offset($limit, $offset);
    $params = $f['params'];
    $st = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $st->bindValue(':' . $k, $v, PDO::PARAM_STR);
    }
    $st->execute();
    return $st->fetchAll() ?: [];
}

function training_catalog_actions_normalize_pagination(int $page, int $perPage, int $total): array
{
    if ($perPage < 1) {
        $perPage = 20;
    }
    if ($perPage > 100) {
        $perPage = 100;
    }
    if ($page < 1) {
        $page = 1;
    }
    $tp = $total > 0 ? (int) ceil($total / $perPage) : 1;
    if ($page > $tp) {
        $page = $tp;
    }
    return ['page' => $page, 'per_page' => $perPage, 'total_pages' => $tp, 'offset' => ($page - 1) * $perPage];
}

function training_catalog_actions_get_by_id(PDO $db, int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    $sql = 'SELECT tca.*, ka.knowledge_area_code AS ka_code, ka.name AS knowledge_area_name,
                ka.is_active AS knowledge_area_is_active
            FROM training_catalog_actions tca
            INNER JOIN knowledge_areas ka ON ka.id = tca.knowledge_area_id
            WHERE tca.id = :id LIMIT 1';
    $st = $db->prepare($sql);
    $st->execute(['id' => $id]);
    $r = $st->fetch();
    return $r ?: null;
}

/**
 * @param array<string,mixed> $row
 * @return array<string,mixed>
 */
function training_catalog_actions_row_for_api(array $row): array
{
    $out = $row;
    $out['action_code_display'] = format_padded_code((int) ($row['action_code'] ?? 0), 5);
    $kaCode = (int) ($row['ka_code'] ?? 0);
    $kaName = (string) ($row['knowledge_area_name'] ?? '');
    $kaActive = !empty($row['knowledge_area_is_active']);
    $out['knowledge_area_is_active'] = $kaActive ? 1 : 0;
    $out['knowledge_area_option_label'] = format_padded_code($kaCode, 3) . ' — ' . $kaName . ($kaActive ? '' : ' (inactiu)');
    return $out;
}

/**
 * @return array<string, string>
 */
function training_catalog_actions_validate_save(PDO $db, array $data, ?int $id = null): array
{
    $errors = [];
    $name = trim((string) ($data['name'] ?? ''));
    if ($name === '') {
        $errors['name'] = 'El nom és obligatori.';
    }
    $kaRaw = $data['knowledge_area_id'] ?? '';
    $kaId = is_numeric($kaRaw) ? (int) $kaRaw : 0;
    if ($kaId < 1 || !training_catalog_actions_knowledge_area_exists($db, $kaId)) {
        $errors['knowledge_area_id'] = 'Seleccioneu una àrea de coneixement vàlida.';
    } else {
        $kaActive = training_catalog_actions_knowledge_area_is_active_flag($db, $kaId);
        if ($kaActive === null) {
            $errors['knowledge_area_id'] = 'Seleccioneu una àrea de coneixement vàlida.';
        } elseif ($id === null || $id < 1) {
            if (!$kaActive) {
                $errors['knowledge_area_id'] = 'Seleccioneu una àrea activa.';
            }
        } else {
            $existing = training_catalog_actions_get_by_id($db, $id);
            $prevKaId = $existing ? (int) ($existing['knowledge_area_id'] ?? 0) : 0;
            if ($kaId !== $prevKaId && !$kaActive) {
                $errors['knowledge_area_id'] = 'Seleccioneu una àrea activa.';
            }
        }
    }
    $durRaw = training_catalog_actions_null_if_empty($data['expected_duration_hours'] ?? null);
    if ($durRaw !== null) {
        if (!is_numeric($durRaw)) {
            $errors['expected_duration_hours'] = 'La durada ha de ser un nombre (hores).';
        } else {
            $d = (float) $durRaw;
            if ($d < 0) {
                $errors['expected_duration_hours'] = 'La durada no pot ser negativa.';
            }
            if ($d > 9999.99) {
                $errors['expected_duration_hours'] = 'La durada és massa gran.';
            }
        }
    }
    return $errors;
}

/**
 * @return array<string, mixed>
 */
function training_catalog_actions_normalize_payload(PDO $db, array $data): array
{
    $durRaw = training_catalog_actions_null_if_empty($data['expected_duration_hours'] ?? null);
    $dur = null;
    if ($durRaw !== null && is_numeric($durRaw)) {
        $dur = round((float) $durRaw, 2);
    }
    $kaRaw = $data['knowledge_area_id'] ?? '';
    $kaId = is_numeric($kaRaw) ? (int) $kaRaw : 0;
    return [
        'name' => trim((string) ($data['name'] ?? '')),
        'knowledge_area_id' => $kaId,
        'target_audience' => training_catalog_actions_null_if_empty($data['target_audience'] ?? null),
        'training_objectives' => training_catalog_actions_null_if_empty($data['training_objectives'] ?? null),
        'conceptual_contents' => training_catalog_actions_null_if_empty($data['conceptual_contents'] ?? null),
        'procedural_contents' => training_catalog_actions_null_if_empty($data['procedural_contents'] ?? null),
        'attitudinal_contents' => training_catalog_actions_null_if_empty($data['attitudinal_contents'] ?? null),
        'expected_duration_hours' => $dur,
        'status' => training_catalog_actions_null_if_empty($data['status'] ?? null),
        'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0,
    ];
}

function training_catalog_actions_create(PDO $db, array $data): int
{
    $norm = training_catalog_actions_normalize_payload($db, $data);
    $errors = training_catalog_actions_validate_save($db, array_merge($data, [
        'knowledge_area_id' => $norm['knowledge_area_id'],
        'expected_duration_hours' => $data['expected_duration_hours'] ?? '',
    ]), null);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }
    $db->beginTransaction();
    try {
        $code = training_catalog_actions_next_action_code($db);
        $st = $db->prepare('INSERT INTO training_catalog_actions (
                action_code, name, knowledge_area_id,
                target_audience, training_objectives, conceptual_contents, procedural_contents, attitudinal_contents,
                expected_duration_hours, status, is_active
            ) VALUES (
                :action_code, :name, :knowledge_area_id,
                :target_audience, :training_objectives, :conceptual_contents, :procedural_contents, :attitudinal_contents,
                :expected_duration_hours, :status, :is_active
            )');
        $st->execute([
            'action_code' => $code,
            'name' => $norm['name'],
            'knowledge_area_id' => $norm['knowledge_area_id'],
            'target_audience' => $norm['target_audience'],
            'training_objectives' => $norm['training_objectives'],
            'conceptual_contents' => $norm['conceptual_contents'],
            'procedural_contents' => $norm['procedural_contents'],
            'attitudinal_contents' => $norm['attitudinal_contents'],
            'expected_duration_hours' => $norm['expected_duration_hours'],
            'status' => $norm['status'],
            'is_active' => $norm['is_active'],
        ]);
        $id = (int) $db->lastInsertId();
        $db->commit();
        return $id;
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

function training_catalog_actions_update(PDO $db, int $id, array $data): void
{
    if ($id < 1 || !training_catalog_actions_get_by_id($db, $id)) {
        throw new RuntimeException('Acció del catàleg no trobada');
    }
    $norm = training_catalog_actions_normalize_payload($db, $data);
    $errors = training_catalog_actions_validate_save($db, array_merge($data, [
        'knowledge_area_id' => $norm['knowledge_area_id'],
        'expected_duration_hours' => $data['expected_duration_hours'] ?? '',
    ]), $id);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }
    $st = $db->prepare('UPDATE training_catalog_actions SET
            name = :name,
            knowledge_area_id = :knowledge_area_id,
            target_audience = :target_audience,
            training_objectives = :training_objectives,
            conceptual_contents = :conceptual_contents,
            procedural_contents = :procedural_contents,
            attitudinal_contents = :attitudinal_contents,
            expected_duration_hours = :expected_duration_hours,
            status = :status,
            is_active = :is_active
            WHERE id = :id');
    $st->execute([
        'id' => $id,
        'name' => $norm['name'],
        'knowledge_area_id' => $norm['knowledge_area_id'],
        'target_audience' => $norm['target_audience'],
        'training_objectives' => $norm['training_objectives'],
        'conceptual_contents' => $norm['conceptual_contents'],
        'procedural_contents' => $norm['procedural_contents'],
        'attitudinal_contents' => $norm['attitudinal_contents'],
        'expected_duration_hours' => $norm['expected_duration_hours'],
        'status' => $norm['status'],
        'is_active' => $norm['is_active'],
    ]);
}

function training_catalog_actions_delete(PDO $db, int $id): void
{
    if ($id < 1) {
        throw new InvalidArgumentException(json_encode(['_general' => 'ID invàlid'], JSON_THROW_ON_ERROR));
    }
    $st = $db->prepare('DELETE FROM training_catalog_actions WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]);
    if ($st->rowCount() === 0) {
        throw new RuntimeException('Acció del catàleg no trobada');
    }
}

function training_catalog_actions_parse_validation_exception(Throwable $e): ?array
{
    if (!$e instanceof InvalidArgumentException) {
        return null;
    }
    $d = json_decode($e->getMessage(), true);
    return is_array($d) ? $d : null;
}
