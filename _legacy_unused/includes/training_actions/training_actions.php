<?php
declare(strict_types=1);

require_once APP_ROOT . '/includes/knowledge_areas/knowledge_areas.php';
require_once APP_ROOT . '/includes/training_actions/training_actions_attendees.php';

/**
 * Accions formatives: programació, detalls i execució (Fase 1).
 */

/** @return list<string> */
function training_actions_execution_status_allowed_values(): array
{
    return ['Pendent', 'En curs', 'Realitzada', 'Cancel·lada'];
}

/**
 * Converteix qualsevol entrada a un dels valors permesos o NULL (no es desa text lliure invàlid).
 */
function training_actions_normalize_execution_status(?string $raw): ?string
{
    if ($raw === null) {
        return null;
    }
    $s = trim(preg_replace('/\s+/u', ' ', $raw));
    if ($s === '') {
        return null;
    }
    foreach (training_actions_execution_status_allowed_values() as $allowed) {
        if ($s === $allowed) {
            return $allowed;
        }
    }
    $lower = mb_strtolower($s, 'UTF-8');
    foreach (training_actions_execution_status_allowed_values() as $allowed) {
        if (mb_strtolower($allowed, 'UTF-8') === $lower) {
            return $allowed;
        }
    }
    $noDot = str_replace('·', '', $lower);
    $mapCompact = [
        'pendent' => 'Pendent',
        'encurs' => 'En curs',
        'realitzada' => 'Realitzada',
        'cancellada' => 'Cancel·lada',
    ];
    if (isset($mapCompact[$noDot])) {
        return $mapCompact[$noDot];
    }
    if ($lower === 'en curs') {
        return 'En curs';
    }

    return null;
}

/**
 * Cost municipal previst = cost total × (% municipal / 100).
 */
function training_actions_compute_planned_municipal_cost(?float $plannedTotal, ?float $municipalPercent): ?float
{
    if ($plannedTotal === null || $municipalPercent === null) {
        return null;
    }

    return round($plannedTotal * ($municipalPercent / 100.0), 2);
}

function training_actions_format_display_code(int $programYear, int $actionNumber): string
{
    return (string) $programYear . '.' . format_padded_code($actionNumber, 3);
}

function training_actions_next_action_number(PDO $db, int $programYear): int
{
    if ($programYear < 1900 || $programYear > 2100) {
        return 1;
    }
    $st = $db->prepare('SELECT COALESCE(MAX(action_number), 0) + 1 AS n FROM training_actions WHERE program_year = :y');
    $st->execute(['y' => $programYear]);
    $r = $st->fetch();
    $n = (int) ($r['n'] ?? 1);

    return $n < 1 ? 1 : $n;
}

/**
 * @return list<string>
 */
function training_actions_list_sort_keys(): array
{
    return [
        'program_year',
        'action_number',
        'display_code',
        'name',
        'subprogram_name',
        'organizer_name',
        'knowledge_area',
        'nearest_session_date',
        'execution_status',
        'is_active',
    ];
}

/**
 * @return array{by:string,dir:string}
 */
function training_actions_normalize_sort(string $sortBy, string $sortDir): array
{
    $allowed = array_flip(training_actions_list_sort_keys());

    return [
        'by' => isset($allowed[$sortBy]) ? $sortBy : 'display_code',
        'dir' => strtolower($sortDir) === 'desc' ? 'desc' : 'asc',
    ];
}

function training_actions_list_from_clause(): string
{
    return 'training_actions ta
            LEFT JOIN training_subprograms sp ON sp.id = ta.subprogram_id
            LEFT JOIN knowledge_areas ka ON ka.id = ta.knowledge_area_id
            LEFT JOIN training_organizers org ON org.id = ta.organizer_id
            LEFT JOIN training_trainer_types ttt ON ttt.id = ta.trainer_type_id
            LEFT JOIN training_locations tl ON tl.id = ta.training_location_id
            LEFT JOIN training_funding tf ON tf.id = ta.funding_id
            LEFT JOIN training_authorizers tauth ON tauth.id = ta.training_authorizer_id
            LEFT JOIN org_areas oa_auth ON oa_auth.id = tauth.area_id
            LEFT JOIN training_catalog_actions tca ON tca.id = ta.catalog_action_id
            LEFT JOIN (
                SELECT training_action_id, MIN(session_date) AS first_session_date
                FROM training_action_dates
                GROUP BY training_action_id
            ) tad ON tad.training_action_id = ta.id';
}

/**
 * @param array{
 *   q?:string,
 *   program_year?:string,
 *   subprogram_id?:string,
 *   organizer_id?:string,
 *   date_from?:string,
 *   training_location_id?:string,
 *   knowledge_area_id?:string,
 *   trainer_type_id?:string,
 *   execution_status?:string,
 *   active?:string
 * } $filters
 * @return array{where:list<string>,params:array<string,mixed>}
 */
function training_actions_list_filters_clause(array $filters): array
{
    $where = ['1=1'];
    $params = [];

    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(ta.name LIKE :q1 OR ta.trainers_text LIKE :q2 OR ta.notes LIKE :q3
            OR ta.grouped_plan_code LIKE :q4 OR ta.execution_status LIKE :q5
            OR CAST(ta.program_year AS CHAR) LIKE :q6 OR CAST(ta.action_number AS CHAR) LIKE :q7)';
        $like = '%' . $q . '%';
        $params['q1'] = $like;
        $params['q2'] = $like;
        $params['q3'] = $like;
        $params['q4'] = $like;
        $params['q5'] = $like;
        $params['q6'] = $like;
        $params['q7'] = $like;
    }

    $py = null_if_empty_int($filters['program_year'] ?? null);
    if ($py !== null) {
        $where[] = 'ta.program_year = :program_year';
        $params['program_year'] = $py;
    }

    $sid = null_if_empty_int($filters['subprogram_id'] ?? null);
    if ($sid !== null) {
        $where[] = 'ta.subprogram_id = :subprogram_id';
        $params['subprogram_id'] = $sid;
    }

    $oid = null_if_empty_int($filters['organizer_id'] ?? null);
    if ($oid !== null) {
        $where[] = 'ta.organizer_id = :organizer_id';
        $params['organizer_id'] = $oid;
    }

    $df = null_if_empty((string) ($filters['date_from'] ?? ''));
    if ($df !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $df)) {
        $where[] = 'EXISTS (
            SELECT 1 FROM training_action_dates x
            WHERE x.training_action_id = ta.id AND x.session_date >= :date_from
        )';
        $params['date_from'] = $df;
    }

    $lid = null_if_empty_int($filters['training_location_id'] ?? null);
    if ($lid !== null) {
        $where[] = 'ta.training_location_id = :training_location_id';
        $params['training_location_id'] = $lid;
    }

    $kaid = null_if_empty_int($filters['knowledge_area_id'] ?? null);
    if ($kaid !== null) {
        $where[] = 'ta.knowledge_area_id = :knowledge_area_id';
        $params['knowledge_area_id'] = $kaid;
    }

    $ttid = null_if_empty_int($filters['trainer_type_id'] ?? null);
    if ($ttid !== null) {
        $where[] = 'ta.trainer_type_id = :trainer_type_id';
        $params['trainer_type_id'] = $ttid;
    }

    $esFilter = null_if_empty(trim((string) ($filters['execution_status'] ?? '')));
    if ($esFilter !== null) {
        $esNorm = training_actions_normalize_execution_status($esFilter);
        if ($esNorm !== null) {
            $where[] = 'ta.execution_status = :execution_status_filter';
            $params['execution_status_filter'] = $esNorm;
        }
    }

    $active = trim((string) ($filters['active'] ?? ''));
    if ($active === '1' || $active === '0') {
        $where[] = 'ta.is_active = :active';
        $params['active'] = (int) $active;
    }

    return ['where' => $where, 'params' => $params];
}

function training_actions_list_order_sql(string $sortBy, string $sortDir): string
{
    $n = training_actions_normalize_sort($sortBy, $sortDir);
    $dir = strtoupper($n['dir']) === 'DESC' ? 'DESC' : 'ASC';

    switch ($n['by']) {
        case 'program_year':
            return 'ta.program_year ' . $dir . ', ta.action_number ASC';
        case 'action_number':
            return 'ta.action_number ' . $dir . ', ta.program_year ASC';
        case 'name':
            return 'ta.name ' . $dir;
        case 'subprogram_name':
            return '(sp.name IS NULL) ASC, sp.name ' . $dir;
        case 'organizer_name':
            return '(org.name IS NULL) ASC, org.name ' . $dir;
        case 'knowledge_area':
            return '(ka.knowledge_area_code IS NULL) ASC, ka.knowledge_area_code ' . $dir . ', ka.name ' . $dir;
        case 'nearest_session_date':
            return '(tad.first_session_date IS NULL) ASC, tad.first_session_date ' . $dir;
        case 'execution_status':
            return '(ta.execution_status IS NULL OR ta.execution_status = \'\') ASC, ta.execution_status ' . $dir;
        case 'is_active':
            return 'ta.is_active ' . $dir;
        default:
            return 'ta.program_year ' . $dir . ', ta.action_number ' . $dir;
    }
}

function training_actions_count(PDO $db, array $filters): int
{
    $f = training_actions_list_filters_clause($filters);
    $sql = 'SELECT COUNT(*) AS c FROM ' . training_actions_list_from_clause() . '
            WHERE ' . implode(' AND ', $f['where']);
    $st = $db->prepare($sql);
    $st->execute($f['params']);
    $r = $st->fetch();

    return (int) ($r['c'] ?? 0);
}

/**
 * @return list<array<string,mixed>>
 */
function training_actions_list(PDO $db, array $filters, string $sortBy, string $sortDir, int $limit, int $offset): array
{
    $f = training_actions_list_filters_clause($filters);
    $sql = 'SELECT ta.*,
                   sp.name AS subprogram_name,
                   ka.knowledge_area_code AS ka_code,
                   ka.name AS ka_name,
                   ka.image_name AS ka_image_name,
                   org.name AS organizer_name,
                   tad.first_session_date AS nearest_session_date
            FROM ' . training_actions_list_from_clause() . '
            WHERE ' . implode(' AND ', $f['where']) . '
            ORDER BY ' . training_actions_list_order_sql($sortBy, $sortDir) . '
            ' . db_sql_limit_offset($limit, $offset);
    $params = $f['params'];
    $st = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $st->bindValue(':' . $k, $v, PDO::PARAM_STR);
    }
    $st->execute();

    $rows = $st->fetchAll() ?: [];
    foreach ($rows as &$r) {
        $r['execution_status'] = training_actions_normalize_execution_status(
            isset($r['execution_status']) ? (string) $r['execution_status'] : null
        ) ?? 'Pendent';
    }
    unset($r);

    return $rows;
}

/**
 * @return array{page:int,per_page:int,total_pages:int,offset:int}
 */
function training_actions_normalize_pagination(int $page, int $perPage, int $total): array
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

    return [
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $tp,
        'offset' => ($page - 1) * $perPage,
    ];
}

/**
 * @return list<array{id:int,session_date:string,start_time:?string,end_time:?string,sort_order:int}>
 */
function training_actions_get_dates(PDO $db, int $trainingActionId): array
{
    if ($trainingActionId < 1) {
        return [];
    }
    $st = $db->prepare('SELECT id, session_date, start_time, end_time, sort_order
        FROM training_action_dates WHERE training_action_id = :id ORDER BY sort_order ASC, session_date ASC, id ASC');
    $st->execute(['id' => $trainingActionId]);
    $rows = $st->fetchAll() ?: [];
    $out = [];
    foreach ($rows as $r) {
        $out[] = [
            'id' => (int) $r['id'],
            'session_date' => (string) $r['session_date'],
            'start_time' => $r['start_time'] !== null && $r['start_time'] !== '' ? (string) $r['start_time'] : null,
            'end_time' => $r['end_time'] !== null && $r['end_time'] !== '' ? (string) $r['end_time'] : null,
            'sort_order' => (int) $r['sort_order'],
        ];
    }

    return $out;
}

/**
 * @return array<string,mixed>|null
 */
function training_actions_get_by_id(PDO $db, int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    $sql = 'SELECT ta.*,
                   sp.name AS subprogram_name,
                   ka.knowledge_area_code AS ka_code,
                   ka.name AS ka_name,
                   ka.image_name AS ka_image_name,
                   org.name AS organizer_name,
                   ttt.trainer_type_code AS tt_code,
                   ttt.name AS trainer_type_name,
                   tl.location_code AS loc_code,
                   tl.name AS location_name,
                   tf.funding_code AS funding_code,
                   tf.name AS funding_name,
                   tauth.full_name AS authorizer_name,
                   oa_auth.area_code AS authorizer_area_code,
                   oa_auth.name AS authorizer_area_name,
                   tca.action_code AS catalog_action_code,
                   tca.name AS catalog_action_name
            FROM training_actions ta
            LEFT JOIN training_subprograms sp ON sp.id = ta.subprogram_id
            LEFT JOIN knowledge_areas ka ON ka.id = ta.knowledge_area_id
            LEFT JOIN training_organizers org ON org.id = ta.organizer_id
            LEFT JOIN training_trainer_types ttt ON ttt.id = ta.trainer_type_id
            LEFT JOIN training_locations tl ON tl.id = ta.training_location_id
            LEFT JOIN training_funding tf ON tf.id = ta.funding_id
            LEFT JOIN training_authorizers tauth ON tauth.id = ta.training_authorizer_id
            LEFT JOIN org_areas oa_auth ON oa_auth.id = tauth.area_id
            LEFT JOIN training_catalog_actions tca ON tca.id = ta.catalog_action_id
            WHERE ta.id = :id LIMIT 1';
    $st = $db->prepare($sql);
    $st->execute(['id' => $id]);
    $row = $st->fetch();

    return $row ?: null;
}

/**
 * @param array<string,mixed> $row
 * @return array<string,mixed>
 */
function training_actions_row_for_api(PDO $db, array $row): array
{
    $id = (int) ($row['id'] ?? 0);
    $py = (int) ($row['program_year'] ?? 0);
    $an = (int) ($row['action_number'] ?? 0);
    $out = $row;
    $esApi = training_actions_normalize_execution_status(
        isset($row['execution_status']) && $row['execution_status'] !== null && (string) $row['execution_status'] !== ''
            ? (string) $row['execution_status']
            : null
    );
    $out['execution_status'] = $esApi ?? 'Pendent';
    $out['display_code'] = training_actions_format_display_code($py, $an);
    $out['dates'] = training_actions_get_dates($db, $id);
    $kaImg = isset($row['ka_image_name']) && (string) $row['ka_image_name'] !== '' ? (string) $row['ka_image_name'] : null;
    $out['ka_image_url'] = knowledge_areas_public_image_url($kaImg);
    unset($out['ka_image_name']);

    return $out;
}

function training_actions_row_exists(PDO $db, string $table, int $id): bool
{
    $allowed = [
        'training_catalog_actions',
        'training_subprograms',
        'knowledge_areas',
        'training_organizers',
        'training_trainer_types',
        'training_locations',
        'training_funding',
        'training_authorizers',
    ];
    if ($id < 1 || !in_array($table, $allowed, true)) {
        return false;
    }
    $st = $db->prepare('SELECT 1 FROM `' . str_replace('`', '', $table) . '` WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]);

    return (bool) $st->fetch();
}

/**
 * @param list<array<string,mixed>>|mixed $rawDates
 * @return array{0:list<array{session_date:string,start_time:?string,end_time:?string,sort_order:int}>,1:array<string,string>}
 */
/** @param mixed $rawDates */
function training_actions_parse_dates_input($rawDates): array
{
    $errors = [];
    if (!is_array($rawDates)) {
        return [[], []];
    }
    $out = [];
    $i = 0;
    foreach ($rawDates as $item) {
        if (!is_array($item)) {
            continue;
        }
        $sd = trim((string) ($item['session_date'] ?? ''));
        if ($sd === '') {
            continue;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sd)) {
            $errors['dates_' . $i] = 'Data no vàlida a la fila ' . ($i + 1) . '.';

            continue;
        }
        $stT = null_if_empty(trim((string) ($item['start_time'] ?? '')));
        $enT = null_if_empty(trim((string) ($item['end_time'] ?? '')));
        if ($stT !== null && !preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $stT)) {
            $errors['dates_' . $i] = 'Hora d’inici no vàlida (fila ' . ($i + 1) . ').';
            ++$i;

            continue;
        }
        if ($enT !== null && !preg_match('/^\d{1,2}:\d{2}(:\d{2})?$/', $enT)) {
            $errors['dates_' . $i] = 'Hora de fi no vàlida (fila ' . ($i + 1) . ').';
            ++$i;

            continue;
        }
        if ($stT !== null && strlen($stT) === 5) {
            $stT .= ':00';
        }
        if ($enT !== null && strlen($enT) === 5) {
            $enT .= ':00';
        }
        if ($stT !== null && $enT !== null) {
            $t1 = strtotime('1970-01-01 ' . $stT);
            $t2 = strtotime('1970-01-01 ' . $enT);
            if ($t1 !== false && $t2 !== false && $t2 < $t1) {
                $errors['dates_' . $i] = 'L’hora de fi ha de ser posterior a la d’inici (fila ' . ($i + 1) . ').';
                ++$i;

                continue;
            }
        }
        $out[] = [
            'session_date' => $sd,
            'start_time' => $stT,
            'end_time' => $enT,
            'sort_order' => (int) ($item['sort_order'] ?? $i),
        ];
        ++$i;
    }

    return [$out, $errors];
}

/**
 * @param array<string,mixed> $data
 * @return array<string,string>
 */
function training_actions_validate_save(PDO $db, array $data, ?int $id): array
{
    $errors = [];

    if ($id !== null && $id > 0 && trim((string) ($data['program_year'] ?? '')) === '') {
        $existing = training_actions_get_by_id($db, $id);
        if ($existing) {
            $data['program_year'] = (string) (int) ($existing['program_year'] ?? 0);
        }
    }

    [, $dateErrs] = training_actions_parse_dates_input($data['dates'] ?? []);
    $errors = array_merge($errors, $dateErrs);

    $pyRaw = $data['program_year'] ?? '';
    if (!is_numeric($pyRaw)) {
        $errors['program_year'] = 'L’any de programa és obligatori i ha de ser numèric.';
    } else {
        $py = (int) $pyRaw;
        if ($py < 1990 || $py > 2100) {
            $errors['program_year'] = 'L’any de programa no és vàlid.';
        }
    }

    $name = trim((string) ($data['name'] ?? ''));
    if ($name === '') {
        $errors['name'] = 'El nom de l’acció és obligatori.';
    } elseif (strlen($name) > 255) {
        $errors['name'] = 'El nom és massa llarg.';
    }

    $catId = null_if_empty_int($data['catalog_action_id'] ?? null);
    if ($catId !== null) {
        if (!training_actions_row_exists($db, 'training_catalog_actions', $catId)) {
            $errors['catalog_action_id'] = 'L’acció del catàleg no existeix.';
        }
    }

    $optionalFks = [
        'subprogram_id' => 'training_subprograms',
        'knowledge_area_id' => 'knowledge_areas',
        'organizer_id' => 'training_organizers',
        'trainer_type_id' => 'training_trainer_types',
        'training_location_id' => 'training_locations',
        'funding_id' => 'training_funding',
        'training_authorizer_id' => 'training_authorizers',
    ];
    foreach ($optionalFks as $field => $table) {
        $fk = null_if_empty_int($data[$field] ?? null);
        if ($fk !== null && !training_actions_row_exists($db, $table, $fk)) {
            $errors[$field] = 'Valor no vàlid.';
        }
    }

    foreach (['planned_duration_hours' => 'Durada prevista', 'actual_duration_hours' => 'Durada real'] as $f => $label) {
        $raw = null_if_empty(trim((string) ($data[$f] ?? '')));
        if ($raw === null) {
            continue;
        }
        if (!is_numeric($raw)) {
            $errors[$f] = $label . ': ha de ser un nombre.';
        } else {
            $v = round((float) $raw, 2);
            if ($v < 0) {
                $errors[$f] = $label . ': no pot ser negatiu.';
            }
            if ($v > 9999.99) {
                $errors[$f] = $label . ': valor massa gran.';
            }
        }
    }

    foreach (['planned_total_cost' => 'Cost total previst', 'actual_cost' => 'Cost real'] as $f => $label) {
        $raw = null_if_empty(trim((string) ($data[$f] ?? '')));
        if ($raw === null) {
            continue;
        }
        if (!is_numeric($raw)) {
            $errors[$f] = $label . ': ha de ser un nombre.';
        } else {
            $v = round((float) $raw, 2);
            if ($v < 0) {
                $errors[$f] = $label . ': no pot ser negatiu.';
            }
            if ($v > 99999999.99) {
                $errors[$f] = $label . ': valor massa gran.';
            }
        }
    }

    $pct = null_if_empty(trim((string) ($data['municipal_funding_percent'] ?? '')));
    if ($pct !== null) {
        if (!is_numeric($pct)) {
            $errors['municipal_funding_percent'] = 'El percentatge ha de ser numèric.';
        } else {
            $v = round((float) $pct, 2);
            if ($v < 0 || $v > 100) {
                $errors['municipal_funding_percent'] = 'El percentatge ha d’estar entre 0 i 100.';
            }
        }
    }

    $gpc = null_if_empty((string) ($data['grouped_plan_code'] ?? ''));
    if ($gpc !== null && strlen($gpc) > 50) {
        $errors['grouped_plan_code'] = 'Màxim 50 caràcters.';
    }

    $pp = null_if_empty(trim((string) ($data['planned_places'] ?? '')));
    if ($pp !== null) {
        if (!ctype_digit($pp)) {
            $errors['planned_places'] = 'Les places han de ser un enter no negatiu.';
        } elseif ((int) $pp > 4294967295) {
            $errors['planned_places'] = 'Valor massa gran.';
        }
    }

    foreach (['planned_schedule' => 255, 'trainers_text' => 500] as $f => $max) {
        $t = (string) ($data[$f] ?? '');
        if (strlen($t) > $max) {
            $errors[$f] = 'Màxim ' . $max . ' caràcters.';
        }
    }

    $esRaw = trim((string) ($data['execution_status'] ?? ''));
    if ($esRaw === '') {
        $errors['execution_status'] = 'L’estat d’execució és obligatori.';
    } else {
        $esNorm = training_actions_normalize_execution_status(
            isset($data['execution_status']) ? (string) $data['execution_status'] : null
        );
        if ($esNorm === null) {
            $errors['execution_status'] = 'Seleccioneu un estat d’execució vàlid (Pendent, En curs, Realitzada o Cancel·lada).';
        }
    }

    return $errors;
}

/**
 * @param array<string,mixed> $data
 * @return array<string,mixed>
 */
function training_actions_normalize_payload(PDO $db, array $data, bool $isCreate): array
{
    [$dates] = training_actions_parse_dates_input($data['dates'] ?? []);

    $py = (int) ($data['program_year'] ?? 0);

    $executionStatus = training_actions_normalize_execution_status(
        isset($data['execution_status']) ? (string) $data['execution_status'] : null
    );
    if ($executionStatus === null) {
        throw new InvalidArgumentException(json_encode(['execution_status' => 'L’estat d’execució és obligatori o no és vàlid.'], JSON_THROW_ON_ERROR));
    }

    $norm = [
        'program_year' => $py,
        'catalog_action_id' => null_if_empty_int($data['catalog_action_id'] ?? null),
        'name' => trim((string) ($data['name'] ?? '')),
        'subprogram_id' => null_if_empty_int($data['subprogram_id'] ?? null),
        'knowledge_area_id' => null_if_empty_int($data['knowledge_area_id'] ?? null),
        'organizer_id' => null_if_empty_int($data['organizer_id'] ?? null),
        'trainer_type_id' => null_if_empty_int($data['trainer_type_id'] ?? null),
        'training_location_id' => null_if_empty_int($data['training_location_id'] ?? null),
        'funding_id' => null_if_empty_int($data['funding_id'] ?? null),
        'training_authorizer_id' => null_if_empty_int($data['training_authorizer_id'] ?? null),
        'grouped_plan_code' => null_if_empty((string) ($data['grouped_plan_code'] ?? '')),
        'trainers_text' => null_if_empty((string) ($data['trainers_text'] ?? '')),
        'planned_places' => null_if_empty_int($data['planned_places'] ?? null),
        'planned_schedule' => null_if_empty((string) ($data['planned_schedule'] ?? '')),
        'execution_status' => $executionStatus,
        'target_audience' => null_if_empty((string) ($data['target_audience'] ?? '')),
        'training_objectives' => null_if_empty((string) ($data['training_objectives'] ?? '')),
        'conceptual_contents' => null_if_empty((string) ($data['conceptual_contents'] ?? '')),
        'procedural_contents' => null_if_empty((string) ($data['procedural_contents'] ?? '')),
        'attitudinal_contents' => null_if_empty((string) ($data['attitudinal_contents'] ?? '')),
        'execution_notes' => null_if_empty((string) ($data['execution_notes'] ?? '')),
        'notes' => null_if_empty((string) ($data['notes'] ?? '')),
        'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0,
        'dates' => $dates,
    ];

    foreach (['planned_duration_hours', 'actual_duration_hours'] as $f) {
        $raw = null_if_empty(trim((string) ($data[$f] ?? '')));
        $norm[$f] = ($raw !== null && is_numeric($raw)) ? round((float) $raw, 2) : null;
    }
    foreach (['planned_total_cost', 'actual_cost'] as $f) {
        $raw = null_if_empty(trim((string) ($data[$f] ?? '')));
        $norm[$f] = ($raw !== null && is_numeric($raw)) ? round((float) $raw, 2) : null;
    }
    $pct = null_if_empty(trim((string) ($data['municipal_funding_percent'] ?? '')));
    $norm['municipal_funding_percent'] = ($pct !== null && is_numeric($pct)) ? round((float) $pct, 2) : null;
    $norm['planned_municipal_cost'] = training_actions_compute_planned_municipal_cost(
        $norm['planned_total_cost'],
        $norm['municipal_funding_percent']
    );

    if ($norm['catalog_action_id'] !== null && $norm['catalog_action_id'] < 1) {
        $norm['catalog_action_id'] = null;
    }
    foreach (['subprogram_id', 'knowledge_area_id', 'organizer_id', 'trainer_type_id', 'training_location_id', 'funding_id', 'training_authorizer_id', 'planned_places'] as $f) {
        if ($norm[$f] !== null && $norm[$f] < 1) {
            $norm[$f] = null;
        }
    }

    return $norm;
}

/**
 * @param list<array{session_date:string,start_time:?string,end_time:?string,sort_order:int}> $dates
 */
function training_actions_replace_dates(PDO $db, int $trainingActionId, array $dates): void
{
    $del = $db->prepare('DELETE FROM training_action_dates WHERE training_action_id = :id');
    $del->execute(['id' => $trainingActionId]);
    if ($dates === []) {
        return;
    }
    $ins = $db->prepare('INSERT INTO training_action_dates (training_action_id, session_date, start_time, end_time, sort_order)
        VALUES (:aid, :sd, :st, :en, :so)');
    $ord = 0;
    foreach ($dates as $d) {
        $ins->execute([
            'aid' => $trainingActionId,
            'sd' => $d['session_date'],
            'st' => $d['start_time'],
            'en' => $d['end_time'],
            'so' => $d['sort_order'] ?? $ord,
        ]);
        ++$ord;
    }
}

/**
 * @param array<string,mixed> $data
 */
function training_actions_create(PDO $db, array $data): int
{
    $errors = training_actions_validate_save($db, $data, null);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }
    $norm = training_actions_normalize_payload($db, $data, true);
    [$parsedDates] = training_actions_parse_dates_input($data['dates'] ?? []);
    $norm['dates'] = $parsedDates;

    $py = $norm['program_year'];
    $db->beginTransaction();
    try {
        $num = training_actions_next_action_number($db, $py);
        $st = $db->prepare('INSERT INTO training_actions (
            program_year, action_number, catalog_action_id, name,
            subprogram_id, knowledge_area_id, organizer_id, trainer_type_id, training_location_id,
            funding_id, training_authorizer_id, grouped_plan_code, trainers_text,
            planned_places, planned_duration_hours, planned_schedule, planned_total_cost,
            municipal_funding_percent, planned_municipal_cost,
            target_audience, training_objectives, conceptual_contents, procedural_contents, attitudinal_contents,
            execution_status, actual_cost, actual_duration_hours, execution_notes, notes, is_active
        ) VALUES (
            :program_year, :action_number, :catalog_action_id, :name,
            :subprogram_id, :knowledge_area_id, :organizer_id, :trainer_type_id, :training_location_id,
            :funding_id, :training_authorizer_id, :grouped_plan_code, :trainers_text,
            :planned_places, :planned_duration_hours, :planned_schedule, :planned_total_cost,
            :municipal_funding_percent, :planned_municipal_cost,
            :target_audience, :training_objectives, :conceptual_contents, :procedural_contents, :attitudinal_contents,
            :execution_status, :actual_cost, :actual_duration_hours, :execution_notes, :notes, :is_active
        )');
        $st->execute([
            'program_year' => $py,
            'action_number' => $num,
            'catalog_action_id' => $norm['catalog_action_id'],
            'name' => $norm['name'],
            'subprogram_id' => $norm['subprogram_id'],
            'knowledge_area_id' => $norm['knowledge_area_id'],
            'organizer_id' => $norm['organizer_id'],
            'trainer_type_id' => $norm['trainer_type_id'],
            'training_location_id' => $norm['training_location_id'],
            'funding_id' => $norm['funding_id'],
            'training_authorizer_id' => $norm['training_authorizer_id'],
            'grouped_plan_code' => $norm['grouped_plan_code'],
            'trainers_text' => $norm['trainers_text'],
            'planned_places' => $norm['planned_places'],
            'planned_duration_hours' => $norm['planned_duration_hours'],
            'planned_schedule' => $norm['planned_schedule'],
            'planned_total_cost' => $norm['planned_total_cost'],
            'municipal_funding_percent' => $norm['municipal_funding_percent'],
            'planned_municipal_cost' => $norm['planned_municipal_cost'],
            'target_audience' => $norm['target_audience'],
            'training_objectives' => $norm['training_objectives'],
            'conceptual_contents' => $norm['conceptual_contents'],
            'procedural_contents' => $norm['procedural_contents'],
            'attitudinal_contents' => $norm['attitudinal_contents'],
            'execution_status' => $norm['execution_status'],
            'actual_cost' => $norm['actual_cost'],
            'actual_duration_hours' => $norm['actual_duration_hours'],
            'execution_notes' => $norm['execution_notes'],
            'notes' => $norm['notes'],
            'is_active' => $norm['is_active'],
        ]);
        $newId = (int) $db->lastInsertId();
        training_actions_replace_dates($db, $newId, $parsedDates);
        $db->commit();

        return $newId;
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }
}

/**
 * @param array<string,mixed> $data
 */
function training_actions_update(PDO $db, int $id, array $data): void
{
    if ($id < 1 || !training_actions_get_by_id($db, $id)) {
        throw new RuntimeException('Acció formativa no trobada');
    }
    [$parsedDates] = training_actions_parse_dates_input($data['dates'] ?? []);
    $errors = training_actions_validate_save($db, $data, $id);
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }
    $norm = training_actions_normalize_payload($db, $data, false);
    $norm['dates'] = $parsedDates;

    $st = $db->prepare('UPDATE training_actions SET
            catalog_action_id = :catalog_action_id,
            name = :name,
            subprogram_id = :subprogram_id,
            knowledge_area_id = :knowledge_area_id,
            organizer_id = :organizer_id,
            trainer_type_id = :trainer_type_id,
            training_location_id = :training_location_id,
            funding_id = :funding_id,
            training_authorizer_id = :training_authorizer_id,
            grouped_plan_code = :grouped_plan_code,
            trainers_text = :trainers_text,
            planned_places = :planned_places,
            planned_duration_hours = :planned_duration_hours,
            planned_schedule = :planned_schedule,
            planned_total_cost = :planned_total_cost,
            municipal_funding_percent = :municipal_funding_percent,
            planned_municipal_cost = :planned_municipal_cost,
            target_audience = :target_audience,
            training_objectives = :training_objectives,
            conceptual_contents = :conceptual_contents,
            procedural_contents = :procedural_contents,
            attitudinal_contents = :attitudinal_contents,
            execution_status = :execution_status,
            actual_cost = :actual_cost,
            actual_duration_hours = :actual_duration_hours,
            execution_notes = :execution_notes,
            notes = :notes,
            is_active = :is_active
            WHERE id = :id');
    $st->execute([
        'id' => $id,
        'catalog_action_id' => $norm['catalog_action_id'],
        'name' => $norm['name'],
        'subprogram_id' => $norm['subprogram_id'],
        'knowledge_area_id' => $norm['knowledge_area_id'],
        'organizer_id' => $norm['organizer_id'],
        'trainer_type_id' => $norm['trainer_type_id'],
        'training_location_id' => $norm['training_location_id'],
        'funding_id' => $norm['funding_id'],
        'training_authorizer_id' => $norm['training_authorizer_id'],
        'grouped_plan_code' => $norm['grouped_plan_code'],
        'trainers_text' => $norm['trainers_text'],
        'planned_places' => $norm['planned_places'],
        'planned_duration_hours' => $norm['planned_duration_hours'],
        'planned_schedule' => $norm['planned_schedule'],
        'planned_total_cost' => $norm['planned_total_cost'],
        'municipal_funding_percent' => $norm['municipal_funding_percent'],
        'planned_municipal_cost' => $norm['planned_municipal_cost'],
        'target_audience' => $norm['target_audience'],
        'training_objectives' => $norm['training_objectives'],
        'conceptual_contents' => $norm['conceptual_contents'],
        'procedural_contents' => $norm['procedural_contents'],
        'attitudinal_contents' => $norm['attitudinal_contents'],
        'execution_status' => $norm['execution_status'],
        'actual_cost' => $norm['actual_cost'],
        'actual_duration_hours' => $norm['actual_duration_hours'],
        'execution_notes' => $norm['execution_notes'],
        'notes' => $norm['notes'],
        'is_active' => $norm['is_active'],
    ]);
    training_actions_replace_dates($db, $id, $parsedDates);
}

function training_actions_delete(PDO $db, int $id): void
{
    if ($id < 1) {
        throw new InvalidArgumentException(json_encode(['_general' => 'ID invàlid'], JSON_THROW_ON_ERROR));
    }
    $st = $db->prepare('DELETE FROM training_actions WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]);
    if ($st->rowCount() === 0) {
        throw new RuntimeException('Acció formativa no trobada');
    }
}

function training_actions_parse_validation_exception(Throwable $e): ?array
{
    if (!$e instanceof InvalidArgumentException) {
        return null;
    }
    $d = json_decode($e->getMessage(), true);

    return is_array($d) ? $d : null;
}

/**
 * Dades per omplir des del catàleg (acció GET catalog_pick).
 *
 * @return array<string,mixed>|null
 */
function training_actions_catalog_pick_payload(PDO $db, int $catalogActionId): ?array
{
    if ($catalogActionId < 1) {
        return null;
    }
    $st = $db->prepare('SELECT id, name, knowledge_area_id, target_audience, training_objectives,
            conceptual_contents, procedural_contents, attitudinal_contents, expected_duration_hours
        FROM training_catalog_actions WHERE id = :id LIMIT 1');
    $st->execute(['id' => $catalogActionId]);
    $r = $st->fetch();
    if (!$r) {
        return null;
    }

    return [
        'catalog_action_id' => (int) $r['id'],
        'name' => (string) $r['name'],
        'knowledge_area_id' => (int) $r['knowledge_area_id'],
        'target_audience' => $r['target_audience'] !== null ? (string) $r['target_audience'] : '',
        'training_objectives' => $r['training_objectives'] !== null ? (string) $r['training_objectives'] : '',
        'conceptual_contents' => $r['conceptual_contents'] !== null ? (string) $r['conceptual_contents'] : '',
        'procedural_contents' => $r['procedural_contents'] !== null ? (string) $r['procedural_contents'] : '',
        'attitudinal_contents' => $r['attitudinal_contents'] !== null ? (string) $r['attitudinal_contents'] : '',
        'planned_duration_hours' => $r['expected_duration_hours'] !== null ? (string) $r['expected_duration_hours'] : '',
    ];
}

/**
 * @return list<array{id:int,name:string,label:string}>
 */
function training_actions_catalog_list_for_picker(PDO $db, string $q, int $limit = 80): array
{
    $limit = max(1, min(200, $limit));
    $where = ['tca.is_active = 1'];
    $params = [];
    $qt = trim($q);
    if ($qt !== '') {
        $where[] = '(tca.name LIKE :q OR CAST(tca.action_code AS CHAR) LIKE :q2)';
        $like = '%' . $qt . '%';
        $params['q'] = $like;
        $params['q2'] = $like;
    }
    $sql = 'SELECT tca.id, tca.name, tca.action_code, ka.knowledge_area_code
            FROM training_catalog_actions tca
            INNER JOIN knowledge_areas ka ON ka.id = tca.knowledge_area_id
            WHERE ' . implode(' AND ', $where) . '
            ORDER BY tca.action_code ASC, tca.name ASC
            LIMIT ' . (int) $limit;
    $st = $db->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll() ?: [];
    $out = [];
    foreach ($rows as $r) {
        $code = format_padded_code((int) $r['action_code'], 5);
        $out[] = [
            'id' => (int) $r['id'],
            'name' => (string) $r['name'],
            'label' => $code . ' — ' . (string) $r['name'],
        ];
    }

    return $out;
}

/** @return list<array{id:int,name:string,code_display:string}> */
function training_actions_subprograms_for_select(PDO $db, bool $activeOnly = false): array
{
    $sql = 'SELECT id, subprogram_code, name FROM training_subprograms';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY subprogram_code ASC';
    $st = $db->query($sql);
    $out = [];
    foreach ($st->fetchAll() ?: [] as $r) {
        $out[] = [
            'id' => (int) $r['id'],
            'name' => (string) $r['name'],
            'code_display' => format_padded_code((int) $r['subprogram_code'], 3),
        ];
    }

    return $out;
}

/** @return list<array{id:int,name:string,code_display:string}> */
function training_actions_organizers_for_select(PDO $db, bool $activeOnly = false): array
{
    $sql = 'SELECT id, organizer_code, name FROM training_organizers';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY organizer_code ASC';
    $st = $db->query($sql);
    $out = [];
    foreach ($st->fetchAll() ?: [] as $r) {
        $out[] = [
            'id' => (int) $r['id'],
            'name' => (string) $r['name'],
            'code_display' => format_padded_code((int) $r['organizer_code'], 3),
        ];
    }

    return $out;
}

/** @return list<array{id:int,knowledge_area_code:int,name:string,image_url:?string}> */
function training_actions_knowledge_areas_for_select(PDO $db, bool $activeOnly = false): array
{
    $sql = 'SELECT id, knowledge_area_code, name, image_name FROM knowledge_areas';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY knowledge_area_code ASC';
    $st = $db->query($sql);
    $out = [];
    foreach ($st->fetchAll() ?: [] as $r) {
        $out[] = [
            'id' => (int) $r['id'],
            'knowledge_area_code' => (int) $r['knowledge_area_code'],
            'name' => (string) $r['name'],
            'image_url' => knowledge_areas_public_image_url(
                isset($r['image_name']) && (string) $r['image_name'] !== '' ? (string) $r['image_name'] : null
            ),
        ];
    }

    return $out;
}

/** @return list<array{id:int,name:string,code_display:string}> */
function training_actions_trainer_types_for_select(PDO $db, bool $activeOnly = false): array
{
    $sql = 'SELECT id, trainer_type_code, name FROM training_trainer_types';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY trainer_type_code ASC';
    $st = $db->query($sql);
    $out = [];
    foreach ($st->fetchAll() ?: [] as $r) {
        $out[] = [
            'id' => (int) $r['id'],
            'name' => (string) $r['name'],
            'code_display' => format_padded_code((int) $r['trainer_type_code'], 2),
        ];
    }

    return $out;
}

/** @return list<array{id:int,name:string,code_display:string}> */
function training_actions_locations_for_select(PDO $db, bool $activeOnly = false): array
{
    $sql = 'SELECT id, location_code, name FROM training_locations';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY location_code ASC';
    $st = $db->query($sql);
    $out = [];
    foreach ($st->fetchAll() ?: [] as $r) {
        $out[] = [
            'id' => (int) $r['id'],
            'name' => (string) $r['name'],
            'code_display' => format_padded_code((int) $r['location_code'], 2),
        ];
    }

    return $out;
}

/** @return list<array{id:int,name:string,code_display:string}> */
function training_actions_funding_for_select(PDO $db, bool $activeOnly = false): array
{
    $sql = 'SELECT id, funding_code, name FROM training_funding';
    if ($activeOnly) {
        $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY funding_code ASC';
    $st = $db->query($sql);
    $out = [];
    foreach ($st->fetchAll() ?: [] as $r) {
        $out[] = [
            'id' => (int) $r['id'],
            'name' => (string) $r['name'],
            'code_display' => format_padded_code((int) $r['funding_code'], 2),
        ];
    }

    return $out;
}

/** @return list<array{id:int,label:string}> */
function training_actions_authorizers_for_select(PDO $db, bool $activeOnly = false): array
{
    $sql = 'SELECT t.id, t.full_name, a.area_code, a.name AS area_name
            FROM training_authorizers t
            INNER JOIN org_areas a ON a.id = t.area_id';
    if ($activeOnly) {
        $sql .= ' WHERE t.is_active = 1';
    }
    $sql .= ' ORDER BY a.area_code ASC, t.full_name ASC';
    $st = $db->query($sql);
    $out = [];
    foreach ($st->fetchAll() ?: [] as $r) {
        $out[] = [
            'id' => (int) $r['id'],
            'label' => format_padded_code((int) $r['area_code'], 1) . ' — ' . (string) $r['area_name'] . ' — ' . (string) $r['full_name'],
        ];
    }

    return $out;
}

/**
 * Classificació visual del dia segons els estats de les accions (mateixa lògica que el JS del tauler).
 *
 * @param list<array<string,mixed>> $actions
 */
function training_actions_calendar_day_kind(array $actions): string
{
    if ($actions === []) {
        return 'planned';
    }
    $allCancelled = true;
    foreach ($actions as $a) {
        if (($a['execution_status'] ?? '') !== 'Cancel·lada') {
            $allCancelled = false;
            break;
        }
    }
    if ($allCancelled) {
        return 'cancelled';
    }
    $allRealized = true;
    foreach ($actions as $a) {
        if (($a['execution_status'] ?? '') !== 'Realitzada') {
            $allRealized = false;
            break;
        }
    }
    if ($allRealized) {
        return 'realized';
    }
    $hasR = false;
    $hasNonR = false;
    foreach ($actions as $a) {
        if (($a['execution_status'] ?? '') === 'Realitzada') {
            $hasR = true;
        } else {
            $hasNonR = true;
        }
    }
    if ($hasR && $hasNonR) {
        return 'mixed';
    }
    $hasC = false;
    $hasNonC = false;
    foreach ($actions as $a) {
        if (($a['execution_status'] ?? '') === 'Cancel·lada') {
            $hasC = true;
        } else {
            $hasNonC = true;
        }
    }
    if ($hasC && $hasNonC) {
        return 'mixed';
    }

    return 'planned';
}

/**
 * Dades per al calendari anual del tauler: sessions per data i accions amb totes les dates vinculades.
 *
 * @return array{year:int,dates:array<string,array{day_kind:string,actions:list<array<string,mixed>>}>}
 */
function training_actions_calendar_year_data(PDO $db, int $year): array
{
    if ($year < 1990 || $year > 2100) {
        return ['year' => $year, 'dates' => []];
    }
    $d1 = sprintf('%04d-01-01', $year);
    $d2 = sprintf('%04d-12-31', $year);

    $sql = 'SELECT tad.session_date, ta.id, ta.program_year, ta.action_number, ta.name, ta.execution_status,
                   ta.trainers_text, tl.name AS location_name
            FROM training_action_dates tad
            INNER JOIN training_actions ta ON ta.id = tad.training_action_id AND ta.is_active = 1
            LEFT JOIN training_locations tl ON tl.id = ta.training_location_id
            WHERE tad.session_date >= :d1 AND tad.session_date <= :d2
            GROUP BY tad.session_date, ta.id, ta.program_year, ta.action_number, ta.name, ta.execution_status, ta.trainers_text, tl.name
            ORDER BY tad.session_date ASC, ta.id ASC';
    $st = $db->prepare($sql);
    $st->execute(['d1' => $d1, 'd2' => $d2]);
    $rows = $st->fetchAll() ?: [];

    $actionIds = [];
    foreach ($rows as $r) {
        $aid = (int) ($r['id'] ?? 0);
        if ($aid > 0) {
            $actionIds[$aid] = true;
        }
    }
    $allDatesByAction = [];
    if ($actionIds !== []) {
        $ids = array_keys($actionIds);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $st2 = $db->prepare(
            'SELECT training_action_id, session_date FROM training_action_dates WHERE training_action_id IN (' . $placeholders . ')
             ORDER BY training_action_id ASC, sort_order ASC, session_date ASC, id ASC'
        );
        $st2->execute(array_map('intval', $ids));
        foreach ($st2->fetchAll() ?: [] as $dr) {
            $taid = (int) $dr['training_action_id'];
            $sd = (string) $dr['session_date'];
            if (!isset($allDatesByAction[$taid])) {
                $allDatesByAction[$taid] = [];
            }
            if (!in_array($sd, $allDatesByAction[$taid], true)) {
                $allDatesByAction[$taid][] = $sd;
            }
        }
    }

    /** @var array<string, array<int, array<string, mixed>>> $byDate */
    $byDate = [];
    foreach ($rows as $r) {
        $sessionDate = (string) $r['session_date'];
        $id = (int) $r['id'];
        $py = (int) $r['program_year'];
        $an = (int) $r['action_number'];
        $es = training_actions_normalize_execution_status(
            isset($r['execution_status']) ? (string) $r['execution_status'] : null
        ) ?? 'Pendent';

        $actionRow = [
            'id' => $id,
            'training_action_id' => $id,
            'display_code' => training_actions_format_display_code($py, $an),
            'name' => (string) $r['name'],
            'execution_status' => $es,
            'trainers_text' => isset($r['trainers_text']) && (string) $r['trainers_text'] !== '' ? (string) $r['trainers_text'] : null,
            'location_name' => isset($r['location_name']) && (string) $r['location_name'] !== '' ? (string) $r['location_name'] : null,
            'all_dates' => $allDatesByAction[$id] ?? [],
        ];

        if (!isset($byDate[$sessionDate])) {
            $byDate[$sessionDate] = [];
        }
        $byDate[$sessionDate][$id] = $actionRow;
    }

    $datesOut = [];
    foreach ($byDate as $sessionDate => $actionsMap) {
        $actions = array_values($actionsMap);
        $dayKind = training_actions_calendar_day_kind($actions);
        $datesOut[$sessionDate] = [
            'day_kind' => $dayKind,
            'actions' => $actions,
        ];
    }

    ksort($datesOut);

    return [
        'year' => $year,
        'dates' => $datesOut,
    ];
}
