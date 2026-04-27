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
            $originalForSave = $isCreate ? null : (in_array($module, ['maintenance_programs', 'maintenance_subprograms', 'maintenance_social_security_companies', 'maintenance_social_security_coefficients', 'maintenance_social_security_base_limits', 'maintenance_salary_base_by_group', 'maintenance_destination_allowances', 'maintenance_seniority_pay_by_group', 'maintenance_specific_compensation_special_prices', 'maintenance_specific_compensation_general'], true) ? trim($originalIdRaw) : (int) ($in['original_id'] ?? 0));
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
                'base_salary' => (string) ($in['base_salary'] ?? ''),
                'base_salary_extra_pay' => (string) ($in['base_salary_extra_pay'] ?? ''),
                'base_salary_new' => (string) ($in['base_salary_new'] ?? ''),
                'base_salary_extra_pay_new' => (string) ($in['base_salary_extra_pay_new'] ?? ''),
                'destination_allowance' => (string) ($in['destination_allowance'] ?? ''),
                'destination_allowance_new' => (string) ($in['destination_allowance_new'] ?? ''),
                'seniority_amount' => (string) ($in['seniority_amount'] ?? ''),
                'seniority_extra_pay_amount' => (string) ($in['seniority_extra_pay_amount'] ?? ''),
                'seniority_amount_new' => (string) ($in['seniority_amount_new'] ?? ''),
                'seniority_extra_pay_amount_new' => (string) ($in['seniority_extra_pay_amount_new'] ?? ''),
                'special_specific_compensation_id' => (string) ($in['special_specific_compensation_id'] ?? ''),
                'special_specific_compensation_name' => (string) ($in['special_specific_compensation_name'] ?? ''),
                'amount' => (string) ($in['amount'] ?? ''),
                'amount_new' => (string) ($in['amount_new'] ?? ''),
                'general_specific_compensation_id' => (string) ($in['general_specific_compensation_id'] ?? ''),
                'general_specific_compensation_name' => (string) ($in['general_specific_compensation_name'] ?? ''),
                'decrease_amount' => (string) ($in['decrease_amount'] ?? ''),
                'decrease_amount_new' => (string) ($in['decrease_amount_new'] ?? ''),
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

    if (in_array($action, ['increment_imports', 'apply_imports', 'cancel_increment'], true)) {
        if (!in_array($module, ['maintenance_salary_base_by_group', 'maintenance_destination_allowances', 'maintenance_seniority_pay_by_group', 'maintenance_specific_compensation_special_prices', 'maintenance_specific_compensation_general', 'maintenance_personal_transitory_bonus'], true)) {
            maintenance_api_json(false, ['errors' => ['_general' => 'Acció no vàlida per al mòdul.']], 400);
            exit;
        }
        if (!can_edit_form($module)) {
            maintenance_api_json(false, ['errors' => ['_general' => 'Sense permís per editar']], 403);
            exit;
        }
        try {
            if ($action === 'increment_imports') {
                $res = $module === 'maintenance_destination_allowances'
                    ? maintenance_destination_allowance_increment_imports(db(), $year, (string) ($in['percent'] ?? ''))
                    : ($module === 'maintenance_seniority_pay_by_group'
                        ? maintenance_seniority_pay_increment_imports(db(), $year, (string) ($in['percent'] ?? ''))
                        : ($module === 'maintenance_specific_compensation_special_prices'
                            ? maintenance_specific_comp_special_increment_imports(db(), $year, (string) ($in['percent'] ?? ''))
                            : ($module === 'maintenance_specific_compensation_general'
                                ? maintenance_specific_comp_general_increment_imports(db(), $year, (string) ($in['percent'] ?? ''))
                                : ($module === 'maintenance_personal_transitory_bonus'
                                    ? maintenance_personal_transitory_bonus_increment_imports(db(), $year, (string) ($in['percent'] ?? ''))
                                    : maintenance_salary_base_increment_imports(db(), $year, (string) ($in['percent'] ?? ''))))));
                if (!$res['ok']) {
                    maintenance_api_json(false, ['errors' => ['_general' => $res['error']]], 422);
                    exit;
                }
                maintenance_api_json(true, ['message' => 'Imports incrementats correctament.']);
                exit;
            }
            if ($action === 'cancel_increment') {
                if ($module === 'maintenance_destination_allowances') {
                    maintenance_destination_allowance_cancel_increment(db(), $year);
                } elseif ($module === 'maintenance_seniority_pay_by_group') {
                    maintenance_seniority_pay_cancel_increment(db(), $year);
                } elseif ($module === 'maintenance_specific_compensation_special_prices') {
                    maintenance_specific_comp_special_cancel_increment(db(), $year);
                } elseif ($module === 'maintenance_specific_compensation_general') {
                    maintenance_specific_comp_general_cancel_increment(db(), $year);
                } elseif ($module === 'maintenance_personal_transitory_bonus') {
                    maintenance_personal_transitory_bonus_cancel_increment(db(), $year);
                } else {
                    maintenance_salary_base_cancel_increment(db(), $year);
                }
                maintenance_api_json(true, ['message' => 'Increment anul·lat correctament.']);
                exit;
            }
            $res = $module === 'maintenance_destination_allowances'
                ? maintenance_destination_allowance_apply_imports(db(), $year)
                : ($module === 'maintenance_seniority_pay_by_group'
                    ? maintenance_seniority_pay_apply_imports(db(), $year)
                    : ($module === 'maintenance_specific_compensation_special_prices'
                        ? maintenance_specific_comp_special_apply_imports(db(), $year)
                        : ($module === 'maintenance_specific_compensation_general'
                            ? maintenance_specific_comp_general_apply_imports(db(), $year)
                            : ($module === 'maintenance_personal_transitory_bonus'
                                ? maintenance_personal_transitory_bonus_apply_imports(db(), $year)
                                : maintenance_salary_base_apply_imports(db(), $year)))));
            if (!$res['ok']) {
                maintenance_api_json(false, ['errors' => ['_general' => $res['error']]], 422);
                exit;
            }
            maintenance_api_json(true, ['message' => 'Imports actualitzats correctament.']);
            exit;
        } catch (Throwable $e) {
            maintenance_api_json(false, ['errors' => ['_general' => 'No s’ha pogut completar l’operació d’imports. Torna-ho a provar.']], 500);
            exit;
        }
    }

    if ($action === 'update_people_seniority') {
        if ($module !== 'maintenance_seniority_pay_by_group') {
            maintenance_api_json(false, ['errors' => ['_general' => 'Acció no vàlida per al mòdul.']], 400);
            exit;
        }
        if (!can_edit_form($module)) {
            maintenance_api_json(false, ['errors' => ['_general' => 'Sense permís per editar']], 403);
            exit;
        }
        try {
            $scope = (string) ($in['scope'] ?? '');
            $res = maintenance_seniority_pay_update_people(db(), $year, $scope);
            if (!$res['ok']) {
                maintenance_api_json(false, ['errors' => ['_general' => $res['error']]], 422);
                exit;
            }
            $updated = (int) ($res['updated'] ?? 0);
            $msg = ($res['scope'] ?? '') === 'active'
                ? "S'han actualitzat els triennis de {$updated} persones actives."
                : "S'han actualitzat els triennis de {$updated} persones.";
            maintenance_api_json(true, ['updated' => $updated, 'message' => $msg]);
            exit;
        } catch (Throwable $e) {
            maintenance_api_json(false, ['errors' => ['_general' => 'No s’ha pogut completar l’actualització de triennis de persones. Torna-ho a provar.']], 500);
            exit;
        }
    }

    if ($action === 'update_personal_transitory_bonus_new') {
        if ($module !== 'maintenance_personal_transitory_bonus') {
            maintenance_api_json(false, ['errors' => ['_general' => 'Acció no vàlida per al mòdul.']], 400);
            exit;
        }
        if (!can_edit_form($module)) {
            maintenance_api_json(false, ['errors' => ['_general' => 'Sense permís per editar']], 403);
            exit;
        }
        $personId = (int) ($in['person_id'] ?? 0);
        $valueRaw = (string) ($in['value'] ?? '');
        try {
            $res = maintenance_personal_transitory_bonus_update_new(db(), $year, $personId, $valueRaw);
            if (!$res['ok']) {
                maintenance_api_json(false, ['errors' => ['_general' => (string) ($res['error'] ?? 'Import no vàlid.')]], 422);
                exit;
            }
            maintenance_api_json(true, [
                'value_display' => (string) ($res['value_display'] ?? ''),
                'value_for_input' => (string) ($res['value_for_input'] ?? ''),
            ]);
            exit;
        } catch (Throwable $e) {
            maintenance_api_json(false, ['errors' => ['_general' => 'No s’ha pogut desar el valor.']], 500);
            exit;
        }
    }

    if ($action === 'update_job_positions_special_prices') {
        if ($module !== 'maintenance_specific_compensation_special_prices') {
            maintenance_api_json(false, ['success' => false, 'errors' => ['_general' => 'Acció no vàlida per al mòdul.'], 'message' => 'No s’ha pogut actualitzar els preus dels llocs de treball.'], 400);
            exit;
        }
        if (!can_edit_form($module)) {
            maintenance_api_json(false, ['success' => false, 'errors' => ['_general' => 'Sense permís per editar'], 'message' => 'No s’ha pogut actualitzar els preus dels llocs de treball.'], 403);
            exit;
        }
        try {
            $res = maintenance_specific_comp_special_update_job_positions(db(), $year);
            $updated = (int) ($res['updated'] ?? 0);
            $msg = "S'han actualitzat els preus de {$updated} llocs de treball.";
            maintenance_api_json(true, ['success' => true, 'updated' => $updated, 'message' => $msg]);
            exit;
        } catch (Throwable $e) {
            maintenance_api_json(false, ['success' => false, 'errors' => ['_general' => 'No s’ha pogut actualitzar els preus dels llocs de treball.'], 'message' => 'No s’ha pogut actualitzar els preus dels llocs de treball.'], 500);
            exit;
        }
    }

    maintenance_api_json(false, ['errors' => ['_general' => 'Acció no vàlida']], 400);
} catch (Throwable $e) {
    maintenance_api_json(false, ['errors' => ['_general' => 'Error intern']], 500);
}
