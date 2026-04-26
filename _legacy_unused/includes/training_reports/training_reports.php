<?php
declare(strict_types=1);

function training_reports_list_sort_keys(): array
{
    return ['display_order', 'report_name', 'report_code', 'is_active', 'show_in_general_selector', 'updated_at'];
}

function training_reports_normalize_sort(string $sortBy, string $sortDir): array
{
    $allowed = array_flip(training_reports_list_sort_keys());
    $by = isset($allowed[$sortBy]) ? $sortBy : 'display_order';
    $dir = strtolower($sortDir) === 'desc' ? 'desc' : 'asc';
    return ['by' => $by, 'dir' => $dir];
}

function training_reports_list_filters_clause(array $filters): array
{
    $where = ['1=1'];
    $params = [];

    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(report_name LIKE :q1 OR report_code LIKE :q2 OR report_description LIKE :q3 OR report_explanation LIKE :q4)';
        $like = '%' . $q . '%';
        $params['q1'] = $like;
        $params['q2'] = $like;
        $params['q3'] = $like;
        $params['q4'] = $like;
    }

    $active = trim((string) ($filters['active'] ?? ''));
    if ($active === '1' || $active === '0') {
        $where[] = 'is_active = :active';
        $params['active'] = (int) $active;
    }

    $show = trim((string) ($filters['show_in_general_selector'] ?? ''));
    if ($show === '1' || $show === '0') {
        $where[] = 'show_in_general_selector = :show_in_general_selector';
        $params['show_in_general_selector'] = (int) $show;
    }

    return ['where' => $where, 'params' => $params];
}

function training_reports_list_order_sql(string $sortBy, string $sortDir): string
{
    $n = training_reports_normalize_sort($sortBy, $sortDir);
    $dir = strtoupper($n['dir']) === 'DESC' ? 'DESC' : 'ASC';
    switch ($n['by']) {
        case 'report_name':
            return 'report_name ' . $dir;
        case 'report_code':
            return 'report_code ' . $dir;
        case 'is_active':
            return 'is_active ' . $dir;
        case 'show_in_general_selector':
            return 'show_in_general_selector ' . $dir;
        case 'updated_at':
            return 'updated_at ' . $dir;
        default:
            return 'display_order ' . $dir . ', report_name ASC';
    }
}

function training_reports_count(PDO $db, array $filters): int
{
    $f = training_reports_list_filters_clause($filters);
    $sql = 'SELECT COUNT(*) AS c FROM training_reports WHERE ' . implode(' AND ', $f['where']);
    $st = $db->prepare($sql);
    $st->execute($f['params']);
    $r = $st->fetch();
    return (int) ($r['c'] ?? 0);
}

function training_reports_list(PDO $db, array $filters, string $sortBy, string $sortDir, int $limit, int $offset): array
{
    $f = training_reports_list_filters_clause($filters);
    $sql = 'SELECT *
            FROM training_reports
            WHERE ' . implode(' AND ', $f['where']) . '
            ORDER BY ' . training_reports_list_order_sql($sortBy, $sortDir) . '
            ' . db_sql_limit_offset($limit, $offset);
    $params = $f['params'];

    $st = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $st->bindValue(':' . $k, $v, PDO::PARAM_STR);
    }
    $st->execute();
    return $st->fetchAll() ?: [];
}

function training_reports_normalize_pagination(int $page, int $perPage, int $total): array
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

function training_reports_get_by_id(PDO $db, int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    $st = $db->prepare('SELECT * FROM training_reports WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]);
    $r = $st->fetch();
    return $r ?: null;
}

function training_reports_get_by_code(PDO $db, string $reportCode): ?array
{
    $c = trim($reportCode);
    if ($c === '') {
        return null;
    }
    $st = $db->prepare('SELECT * FROM training_reports WHERE LOWER(report_code) = LOWER(:c) LIMIT 1');
    $st->execute(['c' => $c]);
    $r = $st->fetch();
    return $r ?: null;
}

function training_reports_for_general_selector(PDO $db): array
{
    $st = $db->query(
        'SELECT id, report_name, report_code, report_description, report_version
         FROM training_reports
         WHERE show_in_general_selector = 1 AND is_active = 1
         ORDER BY display_order ASC, report_name ASC'
    );
    return $st->fetchAll() ?: [];
}

function training_reports_validate_save(PDO $db, array $data, ?int $id = null): array
{
    $errors = [];

    $code = trim((string) ($data['report_code'] ?? ''));
    if ($code === '') {
        $errors['report_code'] = 'El codi és obligatori.';
    } elseif (mb_strlen($code) > 64) {
        $errors['report_code'] = 'Màxim 64 caràcters.';
    } elseif (!preg_match('/^[A-Za-z0-9._-]+$/', $code)) {
        $errors['report_code'] = 'Només lletres, números, punt, guió i subratllat.';
    }

    $name = trim((string) ($data['report_name'] ?? ''));
    if ($name === '') {
        $errors['report_name'] = 'El nom és obligatori.';
    } elseif (mb_strlen($name) > 200) {
        $errors['report_name'] = 'Màxim 200 caràcters.';
    }

    $desc = trim((string) ($data['report_description'] ?? ''));
    if ($desc !== '' && mb_strlen($desc) > 2000) {
        $errors['report_description'] = 'Màxim 2000 caràcters.';
    }

    $expl = (string) ($data['report_explanation'] ?? '');
    if ($expl !== '' && mb_strlen($expl) > 60000) {
        $errors['report_explanation'] = 'Màxim 60000 caràcters.';
    }

    $version = trim((string) ($data['report_version'] ?? ''));
    if ($version !== '' && mb_strlen($version) > 32) {
        $errors['report_version'] = 'Màxim 32 caràcters.';
    }

    $displayOrderRaw = trim((string) ($data['display_order'] ?? '0'));
    if ($displayOrderRaw === '' || !preg_match('/^\d+$/', $displayOrderRaw)) {
        $errors['display_order'] = 'L’ordre ha de ser numèric.';
    } else {
        $displayOrder = (int) $displayOrderRaw;
        if ($displayOrder < 0 || $displayOrder > 9999) {
            $errors['display_order'] = 'L’ordre ha d’estar entre 0 i 9999.';
        }
    }

    if (!isset($errors['report_code'])) {
        $sql = 'SELECT id FROM training_reports WHERE report_code = :report_code';
        $params = ['report_code' => $code];
        if ($id !== null) {
            $sql .= ' AND id <> :id';
            $params['id'] = $id;
        }
        $st = $db->prepare($sql . ' LIMIT 1');
        $st->execute($params);
        if ($st->fetch()) {
            $errors['report_code'] = 'Aquest codi ja existeix.';
        }
    }

    return $errors;
}

function training_reports_create(PDO $db, array $data): int
{
    $errors = training_reports_validate_save($db, $data, null);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }

    $st = $db->prepare(
        'INSERT INTO training_reports
         (report_name, report_code, report_description, report_explanation, report_version, show_in_general_selector, display_order, is_active)
         VALUES (:report_name, :report_code, :report_description, :report_explanation, :report_version, :show_in_general_selector, :display_order, :is_active)'
    );
    $st->execute([
        'report_name' => trim((string) $data['report_name']),
        'report_code' => trim((string) $data['report_code']),
        'report_description' => trim((string) ($data['report_description'] ?? '')),
        'report_explanation' => trim((string) ($data['report_explanation'] ?? '')),
        'report_version' => trim((string) ($data['report_version'] ?? '')),
        'show_in_general_selector' => isset($data['show_in_general_selector']) && (string) $data['show_in_general_selector'] === '1' ? 1 : 0,
        'display_order' => (int) ($data['display_order'] ?? 0),
        'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0,
    ]);
    return (int) $db->lastInsertId();
}

function training_reports_update(PDO $db, int $id, array $data): void
{
    if ($id < 1 || !training_reports_get_by_id($db, $id)) {
        throw new RuntimeException('Informe no trobat.');
    }
    $errors = training_reports_validate_save($db, $data, $id);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }

    $st = $db->prepare(
        'UPDATE training_reports
         SET report_name = :report_name,
             report_code = :report_code,
             report_description = :report_description,
             report_explanation = :report_explanation,
             report_version = :report_version,
             show_in_general_selector = :show_in_general_selector,
             display_order = :display_order,
             is_active = :is_active
         WHERE id = :id'
    );
    $st->execute([
        'id' => $id,
        'report_name' => trim((string) $data['report_name']),
        'report_code' => trim((string) $data['report_code']),
        'report_description' => trim((string) ($data['report_description'] ?? '')),
        'report_explanation' => trim((string) ($data['report_explanation'] ?? '')),
        'report_version' => trim((string) ($data['report_version'] ?? '')),
        'show_in_general_selector' => isset($data['show_in_general_selector']) && (string) $data['show_in_general_selector'] === '1' ? 1 : 0,
        'display_order' => (int) ($data['display_order'] ?? 0),
        'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0,
    ]);
}

function training_reports_soft_delete(PDO $db, int $id): void
{
    if ($id < 1) {
        throw new InvalidArgumentException('ID invàlid.');
    }
    $st = $db->prepare(
        'UPDATE training_reports
         SET is_active = 0, show_in_general_selector = 0
         WHERE id = :id'
    );
    $st->execute(['id' => $id]);
    if ($st->rowCount() === 0) {
        throw new RuntimeException('Informe no trobat.');
    }
}

function training_reports_parse_validation_exception(Throwable $e): ?array
{
    if (!$e instanceof InvalidArgumentException) {
        return null;
    }
    $d = json_decode($e->getMessage(), true);
    return is_array($d) ? $d : null;
}
