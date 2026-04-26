<?php
declare(strict_types=1);

function knowledge_areas_imatges_path(): string
{
    return APP_ROOT . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR . 'imatges';
}

function knowledge_areas_public_image_url(?string $imageName): ?string
{
    if ($imageName === null || $imageName === '') {
        return null;
    }
    $base = basename($imageName);
    if ($base === '' || strpbrk($base, "/\\") !== false) {
        return null;
    }
    return asset_url('imatges/' . rawurlencode($base));
}

function knowledge_areas_next_code(PDO $db): int
{
    $st = $db->query('SELECT COALESCE(MAX(knowledge_area_code), 0) + 1 AS n FROM knowledge_areas');
    $r = $st->fetch();
    $n = (int) ($r['n'] ?? 1);
    return $n < 1 ? 1 : $n;
}

function knowledge_areas_allowed_image_types(): array
{
    return [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];
}

/** Només en entorn no productiu i APP_DEBUG: detall de pujada (no usar en producció). */
function knowledge_areas_should_attach_upload_debug(): bool
{
    return defined('APP_IS_PRODUCTION') && !APP_IS_PRODUCTION
        && defined('APP_DEBUG') && APP_DEBUG;
}

function knowledge_areas_upload_err_label(int $code): string
{
    switch ($code) {
        case UPLOAD_ERR_OK:
            return 'UPLOAD_ERR_OK';
        case UPLOAD_ERR_INI_SIZE:
            return 'UPLOAD_ERR_INI_SIZE (supera upload_max_filesize)';
        case UPLOAD_ERR_FORM_SIZE:
            return 'UPLOAD_ERR_FORM_SIZE (supera MAX_FILE_SIZE del formulari)';
        case UPLOAD_ERR_PARTIAL:
            return 'UPLOAD_ERR_PARTIAL (pujada incompleta)';
        case UPLOAD_ERR_NO_FILE:
            return 'UPLOAD_ERR_NO_FILE';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'UPLOAD_ERR_NO_TMP_DIR (falta carpeta temporal al servidor)';
        case UPLOAD_ERR_CANT_WRITE:
            return 'UPLOAD_ERR_CANT_WRITE (no s’ha pogut escriure al disc)';
        case UPLOAD_ERR_EXTENSION:
            return 'UPLOAD_ERR_EXTENSION (una extensió PHP ha blocat la pujada)';
        default:
            return 'UNKNOWN(' . $code . ')';
    }
}

/** Context HTTP per diagnosticar POST/FILES buits (p. ex. post_max_size). */
function knowledge_areas_request_debug_context(): array
{
    $cl = isset($_SERVER['CONTENT_LENGTH']) ? (int) $_SERVER['CONTENT_LENGTH'] : null;
    $postMax = ini_get('post_max_size') ?: '';
    $uploadMax = ini_get('upload_max_filesize') ?: '';
    $tmpDir = ini_get('upload_tmp_dir') ?: '';

    return [
        'CONTENT_TYPE' => $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? null,
        'CONTENT_LENGTH' => $cl,
        'post_max_size_ini' => $postMax,
        'upload_max_filesize_ini' => $uploadMax,
        'upload_tmp_dir_ini' => $tmpDir !== '' ? $tmpDir : '(buit = defecte del sistema)',
        'post_key_count' => is_array($_POST) ? count($_POST) : 0,
        'post_keys' => is_array($_POST) ? array_keys($_POST) : [],
        'files_key_count' => is_array($_FILES) ? count($_FILES) : 0,
        'files_top_keys' => is_array($_FILES) ? array_keys($_FILES) : [],
    ];
}

/**
 * Enriqueix errors 422 del desament amb diagnòstic (només APP_DEBUG i no producció).
 *
 * @param array<string, mixed> $errors
 * @return array<string, mixed>
 */
function knowledge_areas_enrich_save_errors_with_debug(array $errors): array
{
    if (!knowledge_areas_should_attach_upload_debug()) {
        return $errors;
    }
    $errors['_request_debug'] = knowledge_areas_request_debug_context();
    if (isset($_FILES['image']) && is_array($_FILES['image'])) {
        $f = $_FILES['image'];
        $tmp = (string) ($f['tmp_name'] ?? '');
        $err = isset($f['error']) ? (int) $f['error'] : null;
        $errors['_files_image_debug'] = [
            'error_code' => $err,
            'error_label' => $err !== null ? knowledge_areas_upload_err_label($err) : null,
            'name' => $f['name'] ?? null,
            'type' => $f['type'] ?? null,
            'size' => isset($f['size']) ? (int) $f['size'] : null,
            'tmp_name_length' => strlen($tmp),
            'tmp_name_empty' => $tmp === '',
            'tmp_file_exists' => $tmp !== '' && file_exists($tmp),
            'tmp_is_readable' => $tmp !== '' && is_readable($tmp),
            'tmp_bytes' => ($tmp !== '' && is_readable($tmp)) ? @filesize($tmp) : null,
            'is_uploaded_file' => $tmp !== '' ? is_uploaded_file($tmp) : null,
        ];
        if ($tmp !== '' && is_readable($tmp) && @filesize($tmp) > 0 && @filesize($tmp) <= 65536) {
            $fh = @fopen($tmp, 'rb');
            if ($fh !== false) {
                $head = (string) fread($fh, 16);
                fclose($fh);
                $errors['_files_image_debug']['first_16_bytes_hex'] = bin2hex($head);
            }
        }
    } else {
        $errors['_files_image_debug'] = ['note' => '$_FILES["image"] no està definit (PHP no ha parsejat cap fitxer amb aquest nom).'];
    }

    $pk = (int) ($errors['_request_debug']['post_key_count'] ?? 0);
    $cl = (int) ($errors['_request_debug']['CONTENT_LENGTH'] ?? 0);
    if ($pk === 0 && $cl > 0) {
        $hint = ' El cos de la petició arriba (Content-Length>0) però $_POST és buit: revisa post_max_size (php.ini) respecte la mida de la pujada.';
        $errors['_general'] = ($errors['_general'] ?? '') . $hint;
    }

    return $errors;
}

function knowledge_areas_delete_image_file(?string $name): void
{
    if ($name === null || $name === '') {
        return;
    }
    $base = basename($name);
    if ($base === '' || strpbrk($base, "/\\") !== false) {
        return;
    }
    $path = knowledge_areas_imatges_path() . DIRECTORY_SEPARATOR . $base;
    if (is_file($path)) {
        @unlink($path);
    }
}

/**
 * @return array{0: string, 1: array<string, mixed>}
 */
function knowledge_areas_throw_upload_invalid(string $userImageMessage, array $diag): void
{
    $payload = ['image' => $userImageMessage];
    if (knowledge_areas_should_attach_upload_debug()) {
        $payload['_upload_debug'] = $diag;
        $payload['_general'] = sprintf(
            '[Depuració pujada] Pas: %s. error=%s (%s). tmp_buit=%s. tmp_existeix=%s. is_uploaded_file=%s. getimagesize_ok=%s.',
            (string) ($diag['step'] ?? '?'),
            (string) ($diag['php_upload_err'] ?? ''),
            (string) ($diag['php_upload_err_label'] ?? ''),
            isset($diag['tmp_name_empty']) && $diag['tmp_name_empty'] ? 'sí' : 'no',
            isset($diag['tmp_file_exists']) && $diag['tmp_file_exists'] ? 'sí' : 'no',
            isset($diag['is_uploaded_file']) ? (string) json_encode($diag['is_uploaded_file']) : 'null',
            isset($diag['getimagesize_ok']) ? (string) json_encode($diag['getimagesize_ok']) : 'null'
        );
    }
    throw new InvalidArgumentException(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
}

function knowledge_areas_process_upload(?array $file): ?string
{
    if ($file === null) {
        return null;
    }
    $diag = [
        'step' => 'start',
        'php_upload_err' => null,
        'php_upload_err_label' => null,
        'tmp_name_empty' => null,
        'tmp_file_exists' => null,
        'is_uploaded_file' => null,
        'getimagesize_ok' => null,
        'getimagesize_mime' => null,
        'getimagesize_type' => null,
        'move_ok' => null,
        'dest_dir' => null,
        'dest_dir_writable' => null,
    ];

    $err = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    $diag['php_upload_err'] = $err;
    $diag['php_upload_err_label'] = knowledge_areas_upload_err_label($err);
    $diag['step'] = 'after_error_code';

    if ($err === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($err !== UPLOAD_ERR_OK) {
        switch ($err) {
            case UPLOAD_ERR_INI_SIZE:
                $msg = 'El fitxer supera upload_max_filesize del servidor (php.ini).';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $msg = 'El fitxer supera la mida màxima permesa.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $msg = 'La pujada s’ha interromput (fitxer incomplet).';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $msg = 'El servidor no té carpeta temporal per a pujades (upload_tmp_dir).';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $msg = 'No s’ha pogut escriure el fitxer temporal al disc.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $msg = 'Una extensió de PHP ha blocat aquesta pujada.';
                break;
            default:
                $msg = 'Error en pujar la imatge (codi ' . $err . ').';
                break;
        }
        knowledge_areas_throw_upload_invalid($msg, $diag);
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $diag['tmp_name_empty'] = $tmp === '';
    $diag['tmp_file_exists'] = $tmp !== '' && file_exists($tmp);
    $diag['is_uploaded_file'] = $tmp !== '' ? is_uploaded_file($tmp) : false;
    $diag['step'] = 'after_tmp_checks';

    if ($tmp === '' || !$diag['is_uploaded_file']) {
        knowledge_areas_throw_upload_invalid(
            'Fitxer de pujada no vàlid (PHP no reconeix el fitxer temporal com a pujada vàlida).',
            $diag
        );
    }

    $info = @getimagesize($tmp);
    $diag['getimagesize_ok'] = $info !== false;
    if (is_array($info)) {
        $diag['getimagesize_mime'] = $info['mime'] ?? null;
        $diag['getimagesize_type'] = isset($info[2]) ? (int) $info[2] : null;
    }
    $diag['step'] = 'after_getimagesize';

    if ($info === false) {
        knowledge_areas_throw_upload_invalid(
            'El fitxer seleccionat no és una imatge JPG, PNG o WEBP vàlida.',
            $diag
        );
    }

    $type = (int) ($info[2] ?? 0);
    $map = knowledge_areas_allowed_image_types();
    if (!isset($map[$type])) {
        $diag['rejected_imagetype'] = $type;
        knowledge_areas_throw_upload_invalid(
            'Només es permeten imatges JPG, PNG o WEBP vàlides.',
            $diag
        );
    }

    $ext = $map[$type];
    $dir = knowledge_areas_imatges_path();
    $diag['dest_dir'] = $dir;
    $diag['dest_dir_writable'] = is_dir($dir) ? is_writable($dir) : null;
    $diag['step'] = 'before_mkdir';

    if (!is_dir($dir)) {
        if (!@mkdir($dir, 0775, true) && !is_dir($dir)) {
            if (knowledge_areas_should_attach_upload_debug()) {
                throw new InvalidArgumentException(json_encode([
                    'image' => 'No s’ha pogut crear el directori d’imatges.',
                    '_general' => '[Depuració] mkdir ha fallat a: ' . $dir,
                    '_upload_debug' => $diag + ['mkdir_failed' => true],
                ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            }
            throw new RuntimeException('No s’ha pogut crear el directori d’imatges.');
        }
    }
    $diag['dest_dir_writable'] = is_writable($dir);
    $diag['step'] = 'before_move';

    $basename = 'ka_' . bin2hex(random_bytes(8)) . '_' . time() . '.' . $ext;
    $dest = $dir . DIRECTORY_SEPARATOR . $basename;
    $diag['dest_path'] = $dest;

    if (!move_uploaded_file($tmp, $dest)) {
        $last = error_get_last();
        $diag['move_ok'] = false;
        $diag['php_error_last'] = $last ? ($last['message'] ?? null) : null;
        if (knowledge_areas_should_attach_upload_debug()) {
            knowledge_areas_throw_upload_invalid(
                'No s’ha pogut desar la imatge (move_uploaded_file ha fallat).',
                $diag
            );
        }
        throw new RuntimeException('No s’ha pogut desar la imatge.');
    }
    $diag['move_ok'] = true;

    return $basename;
}

function knowledge_areas_list_sort_keys(): array
{
    return ['knowledge_area_code', 'name', 'is_active', 'created_at'];
}

function knowledge_areas_normalize_sort(string $sortBy, string $sortDir): array
{
    $allowed = array_flip(knowledge_areas_list_sort_keys());
    return [
        'by' => isset($allowed[$sortBy]) ? $sortBy : 'knowledge_area_code',
        'dir' => strtolower($sortDir) === 'desc' ? 'desc' : 'asc',
    ];
}

function knowledge_areas_list_filters_clause(array $filters): array
{
    $where = ['1=1'];
    $params = [];
    $q = trim((string) ($filters['q'] ?? ''));
    if ($q !== '') {
        $where[] = '(ka.name LIKE :q1 OR CAST(ka.knowledge_area_code AS CHAR) LIKE :q2 OR ka.image_name LIKE :q3)';
        $like = '%' . $q . '%';
        $params['q1'] = $like;
        $params['q2'] = $like;
        $params['q3'] = $like;
    }
    $active = trim((string) ($filters['active'] ?? ''));
    if ($active === '1' || $active === '0') {
        $where[] = 'ka.is_active = :active';
        $params['active'] = (int) $active;
    }
    return ['where' => $where, 'params' => $params];
}

function knowledge_areas_list_order_sql(string $sortBy, string $sortDir): string
{
    $n = knowledge_areas_normalize_sort($sortBy, $sortDir);
    $dir = strtoupper($n['dir']) === 'DESC' ? 'DESC' : 'ASC';
    switch ($n['by']) {
        case 'name':
            return 'ka.name ' . $dir;
        case 'is_active':
            return 'ka.is_active ' . $dir;
        case 'created_at':
            return 'ka.created_at ' . $dir;
        default:
            return 'ka.knowledge_area_code ' . $dir;
    }
}

function knowledge_areas_count(PDO $db, array $filters): int
{
    $f = knowledge_areas_list_filters_clause($filters);
    $sql = 'SELECT COUNT(*) AS c FROM knowledge_areas ka WHERE ' . implode(' AND ', $f['where']);
    $st = $db->prepare($sql);
    $st->execute($f['params']);
    $r = $st->fetch();
    return (int) ($r['c'] ?? 0);
}

function knowledge_areas_list(PDO $db, array $filters, string $sortBy, string $sortDir, int $limit, int $offset): array
{
    $f = knowledge_areas_list_filters_clause($filters);
    $sql = 'SELECT ka.* FROM knowledge_areas ka
            WHERE ' . implode(' AND ', $f['where']) . '
            ORDER BY ' . knowledge_areas_list_order_sql($sortBy, $sortDir) . '
            ' . db_sql_limit_offset($limit, $offset);
    $params = $f['params'];
    $st = $db->prepare($sql);
    foreach ($params as $k => $v) {
        $st->bindValue(':' . $k, $v, PDO::PARAM_STR);
    }
    $st->execute();
    return $st->fetchAll() ?: [];
}

function knowledge_areas_normalize_pagination(int $page, int $perPage, int $total): array
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

function knowledge_areas_get_by_id(PDO $db, int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    $st = $db->prepare('SELECT * FROM knowledge_areas WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]);
    $r = $st->fetch();
    return $r ?: null;
}

function knowledge_areas_row_for_api(array $row): array
{
    $out = $row;
    $out['knowledge_area_code_display'] = format_padded_code((int) ($row['knowledge_area_code'] ?? 0), 3);
    $out['image_url'] = knowledge_areas_public_image_url(isset($row['image_name']) ? (string) $row['image_name'] : null);
    return $out;
}

function knowledge_areas_validate_name(string $name): array
{
    $errors = [];
    $t = trim($name);
    if ($t === '') {
        $errors['name'] = 'El nom és obligatori.';
    }
    return $errors;
}

function knowledge_areas_create(PDO $db, array $data, ?array $uploadFile): int
{
    $errors = knowledge_areas_validate_name((string) ($data['name'] ?? ''));
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }
    $newName = knowledge_areas_process_upload($uploadFile);
    $code = knowledge_areas_next_code($db);
    $db->beginTransaction();
    try {
        $st = $db->prepare('INSERT INTO knowledge_areas (knowledge_area_code, name, image_name, is_active)
                VALUES (:code, :name, :image_name, :is_active)');
        $st->execute([
            'code' => $code,
            'name' => trim((string) $data['name']),
            'image_name' => $newName,
            'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0,
        ]);
        $id = (int) $db->lastInsertId();
        $db->commit();
        return $id;
    } catch (Throwable $e) {
        $db->rollBack();
        if ($newName !== null) {
            knowledge_areas_delete_image_file($newName);
        }
        throw $e;
    }
}

function knowledge_areas_update(PDO $db, int $id, array $data, ?array $uploadFile, bool $removeImage): void
{
    if ($id < 1) {
        throw new RuntimeException('Àrea no trobada');
    }
    $row = knowledge_areas_get_by_id($db, $id);
    if (!$row) {
        throw new RuntimeException('Àrea no trobada');
    }
    $errors = knowledge_areas_validate_name((string) ($data['name'] ?? ''));
    if ($errors !== []) {
        throw new InvalidArgumentException(json_encode($errors, JSON_THROW_ON_ERROR));
    }
    $oldImage = isset($row['image_name']) && $row['image_name'] !== null ? (string) $row['image_name'] : null;
    $imageName = $oldImage;
    if ($removeImage) {
        $imageName = null;
    }
    $uploaded = knowledge_areas_process_upload($uploadFile);
    if ($uploaded !== null) {
        $imageName = $uploaded;
    }
    $st = $db->prepare('UPDATE knowledge_areas SET name = :name, image_name = :image_name, is_active = :is_active WHERE id = :id');
    $st->execute([
        'id' => $id,
        'name' => trim((string) $data['name']),
        'image_name' => $imageName,
        'is_active' => isset($data['is_active']) && (string) $data['is_active'] === '1' ? 1 : 0,
    ]);
    if ($uploaded !== null && $oldImage !== null && $oldImage !== $uploaded) {
        knowledge_areas_delete_image_file($oldImage);
    }
    if ($removeImage && $oldImage !== null && $uploaded === null) {
        knowledge_areas_delete_image_file($oldImage);
    }
}

function knowledge_areas_delete(PDO $db, int $id): void
{
    if ($id < 1) {
        throw new InvalidArgumentException(json_encode(['_general' => 'ID invàlid'], JSON_THROW_ON_ERROR));
    }
    $row = knowledge_areas_get_by_id($db, $id);
    if (!$row) {
        throw new RuntimeException('Àrea no trobada');
    }
    $img = isset($row['image_name']) && $row['image_name'] !== null ? (string) $row['image_name'] : null;
    $st = $db->prepare('DELETE FROM knowledge_areas WHERE id = :id LIMIT 1');
    $st->execute(['id' => $id]);
    if ($st->rowCount() === 0) {
        throw new RuntimeException('Àrea no trobada');
    }
    knowledge_areas_delete_image_file($img);
}

function knowledge_areas_parse_validation_exception(Throwable $e): ?array
{
    if (!$e instanceof InvalidArgumentException) {
        return null;
    }
    $d = json_decode($e->getMessage(), true);
    return is_array($d) ? $d : null;
}
