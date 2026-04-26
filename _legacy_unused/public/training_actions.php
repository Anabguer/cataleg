<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';
require_can_view('training_actions');
require_once APP_ROOT . '/includes/training_actions/training_actions.php';
require_once APP_ROOT . '/includes/training_actions/training_actions_view_helpers.php';

$db = db();
$currentCalendarYear = (int) date('Y');
$executionStatusIn = get_string('execution_status');
$executionStatusFilter = training_actions_normalize_execution_status(
    trim($executionStatusIn) !== '' ? $executionStatusIn : null
);
$filters = [
    'q' => get_string('q'),
    'program_year' => get_string('program_year'),
    'subprogram_id' => get_string('subprogram_id'),
    'organizer_id' => get_string('organizer_id'),
    'date_from' => get_string('date_from'),
    'training_location_id' => get_string('training_location_id'),
    'knowledge_area_id' => get_string('knowledge_area_id'),
    'trainer_type_id' => get_string('trainer_type_id'),
    'execution_status' => $executionStatusFilter !== null ? $executionStatusFilter : '',
    'active' => get_string('active'),
];
$sortIn = training_actions_normalize_sort(get_string('sort_by'), get_string('sort_dir'));
$sortBy = $sortIn['by'];
$sortDir = $sortIn['dir'];
$perPage = (int) get_string('per_page');
if ($perPage < 1) {
    $perPage = 20;
}
if ($perPage > 100) {
    $perPage = 100;
}
$page = (int) get_string('page');
if ($page < 1) {
    $page = 1;
}
$totalRows = training_actions_count($db, $filters);
$pg = training_actions_normalize_pagination($page, $perPage, $totalRows);
$page = $pg['page'];
$perPage = $pg['per_page'];
$totalPages = $pg['total_pages'];
$offset = $pg['offset'];
$rows = training_actions_list($db, $filters, $sortBy, $sortDir, $perPage, $offset);

$subprogramsFilter = training_actions_subprograms_for_select($db, false);
$organizersFilter = training_actions_organizers_for_select($db, false);
$knowledgeAreasFilter = training_actions_knowledge_areas_for_select($db, false);
$trainerTypesFilter = training_actions_trainer_types_for_select($db, false);
$locationsFilter = training_actions_locations_for_select($db, false);

$subprogramsModal = training_actions_subprograms_for_select($db, true);
$organizersModal = training_actions_organizers_for_select($db, true);
$knowledgeAreasModal = training_actions_knowledge_areas_for_select($db, true);
$trainerTypesModal = training_actions_trainer_types_for_select($db, true);
$locationsModal = training_actions_locations_for_select($db, true);
$fundingModal = training_actions_funding_for_select($db, true);
$authorizersModal = training_actions_authorizers_for_select($db, true);

$canCreate = can_create_form('training_actions');
$canEdit = can_edit_form('training_actions');
$canDelete = can_delete_form('training_actions');
$canViewCatalog = can_view_form('training_catalog_actions');

$nextPreviewYear = $currentCalendarYear;
$nextNumPreview = training_actions_next_action_number($db, $nextPreviewYear);

$pageTitle = 'Accions formatives';
$activeNav = 'training_actions';
$extraCss = ['css/module-users.css', 'css/training-actions.css'];
$extraScripts = ['training_actions.js'];

$knowledgeAreaAssets = [];
foreach ($knowledgeAreasModal as $ka) {
    $kid = (string) (int) $ka['id'];
    $knowledgeAreaAssets[$kid] = [
        'imageUrl' => isset($ka['image_url']) && $ka['image_url'] !== '' && $ka['image_url'] !== null
            ? (string) $ka['image_url']
            : null,
    ];
}

$trainingActionsPageInlineConfig = [
    'apiUrl' => app_url('training_actions_api.php'),
    'certificateUploadUrl' => app_url('training_action_attendee_certificate_upload.php'),
    'documentUploadUrl' => app_url('training_action_document_upload.php'),
    'questionnaireSendUrl' => app_url('training_action_questionnaire_send.php'),
    'evaluationImportUrl' => app_url('training_action_evaluation_import.php'),
    'csrfToken' => csrf_token(),
    'canCreate' => $canCreate,
    'canEdit' => $canEdit,
    'canDelete' => $canDelete,
    'canViewCatalog' => $canViewCatalog,
    'defaultProgramYear' => $nextPreviewYear,
    'nextActionNumberPreview' => $nextNumPreview,
    'nextDisplayCodePreview' => training_actions_format_display_code($nextPreviewYear, $nextNumPreview),
    'knowledgeAreaAssets' => $knowledgeAreaAssets,
];

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/training_actions/index.php';
require APP_ROOT . '/includes/footer.php';
