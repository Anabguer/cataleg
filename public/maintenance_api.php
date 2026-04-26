<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once APP_ROOT . '/includes/maintenance/aux_catalog.php';

auth_require_login();
permissions_load_for_session();
header('Content-Type: application/json; charset=utf-8');

function maintenance_api_json(bool $ok, array $payload = [], int $code = 200): void
{
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $payload), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
}

function maintenance_api_read_json(): array
{
    $raw = file_get_contents('php://input') ?: '';
    if ($raw === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

try {
    $module = get_string('module');
    if ($module === '') {
        $in = maintenance_api_read_json();
        $module = (string) ($in['module'] ?? '');
    } else {
        $in = [];
    }

    $cfg = maintenance_module_config($module);
    if ($cfg === null) {
        maintenance_api_json(false, ['errors' => ['_general' => 'Mòdul no vàlid']], 400);
        exit;
    }
    if (!can_view_form($module)) {
        maintenance_api_json(false, ['errors' => ['_general' => 'Sense permís de consulta']], 403);
        exit;
    }

    $year = catalog_year_current();
    if ($year === null) {
        maintenance_api_json(false, ['errors' => ['_general' => 'Any de catàleg no disponible']], 400);
        exit;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        $action = get_string('action');
        if ($action !== 'get') {
            maintenance_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
            exit;
        }
        $id = (string) get_string('id');
        $row = maintenance_get_by_id(db(), $module, $year, $id);
        if (!$row) {
            maintenance_api_json(false, ['errors' => ['_general' => 'Registre no trobat']], 404);
            exit;
        }
        maintenance_api_json(true, ['row' => $row]);
        exit;
    }

    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        maintenance_api_json(false, ['errors' => ['_general' => 'Mètode no permès']], 405);
        exit;
    }

    if ($in === []) {
        $in = maintenance_api_read_json();
    }
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($in['csrf_token'] ?? '');
    if (!isset($_SESSION['csrf_token']) || !is_string($token) || !hash_equals((string) $_SESSION['csrf_token'], (string) $token)) {
        maintenance_api_json(false, ['errors' => ['_general' => 'CSRF no vàlid']], 403);
        exit;
    }

    $action = (string) ($in['action'] ?? '');
    if ($action === 'save') {
        $isCreate = !isset($in['original_id']) || (string) $in['original_id'] === '';
        if ($isCreate && !can_create_form($module)) {
            maintenance_api_json(false, ['errors' => ['_general' => 'Sense permís per crear']], 403);
            exit;
        }
        if (!$isCreate && !can_edit_form($module)) {
            maintenance_api_json(false, ['errors' => ['_general' => 'Sense permís per editar']], 403);
            exit;
        }
        try {
            $originalIdRaw = (string) ($in['original_id'] ?? '');
            $originalForSave = $isCreate ? null : (in_array($module, ['maintenance_programs', 'maintenance_subprograms', 'maintenance_social_security_companies', 'maintenance_social_security_coefficients', 'maintenance_social_security_base_limits'], true) ? trim($originalIdRaw) : (int) ($in['original_id'] ?? 0));
            maintenance_save(db(), $module, $year, $originalForSave, [
                'id' => (string) ($in['id'] ?? ''),
                'name' => (string) ($in['name'] ?? ''),
                'short_name' => (string) ($in['short_name'] ?? ''),
                'full_name' => (string) ($in['full_name'] ?? ''),
                'sort_order' => (string) ($in['sort_order'] ?? ''),
                'address' => (string) ($in['address'] ?? ''),
                'postal_code' => (string) ($in['postal_code'] ?? ''),
                'city' => (string) ($in['city'] ?? ''),
                'phone' => (string) ($in['phone'] ?? ''),
                'fax' => (string) ($in['fax'] ?? ''),
                'scale_id' => (int) ($in['scale_id'] ?? 0),
                'subscale_id' => (int) ($in['subscale_id'] ?? 0),
                'class_id' => (int) ($in['class_id'] ?? 0),
                'org_unit_level_1_id' => (string) ($in['org_unit_level_1_id'] ?? ''),
                'org_unit_level_2_id' => (string) ($in['org_unit_level_2_id'] ?? ''),
                'original_id_text' => $originalIdRaw,
                'subfunction_id' => (string) ($in['subfunction_id'] ?? ''),
                'program_number' => (string) ($in['program_number'] ?? ''),
                'responsible_person_code' => (string) ($in['responsible_person_code'] ?? ''),
                'description' => (string) ($in['description'] ?? ''),
                'program_id' => (string) ($in['program_id'] ?? ''),
                'subprogram_number' => (string) ($in['subprogram_number'] ?? ''),
                'subprogram_name' => (string) ($in['subprogram_name'] ?? ''),
                'technical_manager_code' => (string) ($in['technical_manager_code'] ?? ''),
                'elected_manager_code' => (string) ($in['elected_manager_code'] ?? ''),
                'nature' => (string) ($in['nature'] ?? ''),
                'is_mandatory_service' => $in['is_mandatory_service'] ?? 0,
                'has_corporate_agreements' => $in['has_corporate_agreements'] ?? 0,
                'objectives' => (string) ($in['objectives'] ?? ''),
                'activities' => (string) ($in['activities'] ?? ''),
                'notes' => (string) ($in['notes'] ?? ''),
                'contribution_account_code' => (string) ($in['contribution_account_code'] ?? ''),
                'company_1' => (string) ($in['company_1'] ?? ''),
                'company_2' => (string) ($in['company_2'] ?? ''),
                'company_3' => (string) ($in['company_3'] ?? ''),
                'company_4' => (string) ($in['company_4'] ?? ''),
                'company_5a' => (string) ($in['company_5a'] ?? ''),
                'company_5b' => (string) ($in['company_5b'] ?? ''),
                'company_5c' => (string) ($in['company_5c'] ?? ''),
                'company_5d' => (string) ($in['company_5d'] ?? ''),
                'company_5e' => (string) ($in['company_5e'] ?? ''),
                'temporary_employment_company' => (string) ($in['temporary_employment_company'] ?? ''),
                'minimum_base' => (string) ($in['minimum_base'] ?? ''),
                'maximum_base' => (string) ($in['maximum_base'] ?? ''),
                'period_label' => (string) ($in['period_label'] ?? ''),
            ]);
            maintenance_api_json(true, ['message' => $isCreate ? 'Registre creat.' : 'Registre actualitzat.']);
        } catch (Throwable $e) {
            $errs = maintenance_parse_validation_exception($e);
            if ($errs !== null) {
                maintenance_api_json(false, ['errors' => $errs], 422);
            } elseif (db_is_integrity_constraint_violation($e)) {
                maintenance_api_json(false, ['errors' => ['_general' => 'No es pot desar per restricció d’integritat.']], 422);
            } else {
                maintenance_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 500);
            }
        }
        exit;
    }

    if ($action === 'delete') {
        if (!can_delete_form($module)) {
            maintenance_api_json(false, ['errors' => ['_general' => 'Sense permís per eliminar']], 403);
            exit;
        }
        try {
            maintenance_delete(db(), $module, $year, (string) ($in['id'] ?? ''));
            maintenance_api_json(true, ['message' => 'Registre eliminat.']);
        } catch (Throwable $e) {
            if (db_is_integrity_constraint_violation($e)) {
                maintenance_api_json(false, ['errors' => ['_general' => 'No es pot eliminar perquè té dades dependents.']], 422);
            } else {
                maintenance_api_json(false, ['errors' => ['_general' => $e->getMessage()]], 422);
            }
        }
        exit;
    }

    maintenance_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
} catch (Throwable $e) {
    maintenance_api_json(false, ['errors' => ['_general' => 'Error intern']], 500);
}
