<?php
declare(strict_types=1);

require_once __DIR__ . '/_init.php';

require_can_view('password_change');

$errors = [];
$successMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!can_edit_form('password_change')) {
        $errors['_general'] = 'No tens permís per canviar la contrasenya.';
    } elseif (!csrf_verify('csrf_token')) {
        $errors['_general'] = 'Sessió no vàlida. Torna-ho a provar.';
    } else {
        $currentPassword = isset($_POST['current_password']) && is_string($_POST['current_password'])
            ? $_POST['current_password']
            : '';
        $newPassword = isset($_POST['new_password']) && is_string($_POST['new_password'])
            ? $_POST['new_password']
            : '';
        $confirmPassword = isset($_POST['confirm_password']) && is_string($_POST['confirm_password'])
            ? $_POST['confirm_password']
            : '';

        if ($currentPassword === '') {
            $errors['current_password'] = 'La contrasenya actual és obligatòria.';
        }
        if ($newPassword === '') {
            $errors['new_password'] = 'La nova contrasenya és obligatòria.';
        } elseif (strlen($newPassword) < 8) {
            $errors['new_password'] = 'La nova contrasenya ha de tenir mínim 8 caràcters.';
        }
        if ($confirmPassword === '') {
            $errors['confirm_password'] = 'La confirmació de contrasenya és obligatòria.';
        } elseif ($newPassword !== '' && $newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'La nova contrasenya i la confirmació no coincideixen.';
        }
        if (!isset($errors['new_password']) && !isset($errors['current_password']) && $newPassword === $currentPassword) {
            $errors['new_password'] = 'La nova contrasenya ha de ser diferent de l’actual.';
        }

        $userId = auth_user_id();
        if ($userId === null) {
            $errors['_general'] = 'Sessió no vàlida.';
        }

        if ($errors === []) {
            $st = db()->prepare('SELECT password_hash, is_active FROM users WHERE id = :id LIMIT 1');
            $st->execute(['id' => $userId]);
            $row = $st->fetch();

            if (!$row || !(bool) $row['is_active']) {
                $errors['_general'] = 'No s’ha pogut verificar el compte.';
            } elseif (!password_verify($currentPassword, (string) $row['password_hash'])) {
                $errors['current_password'] = 'La contrasenya actual no coincideix.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $up = db()->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
                $up->execute([
                    'id' => $userId,
                    'password_hash' => $newHash,
                ]);
                // Endurim la sessió després del canvi per minimitzar riscos de hijacking.
                session_regenerate_id(true);
                $successMessage = 'La contrasenya s’ha actualitzat correctament.';
            }
        }
    }
}

$pageTitle = 'Canvi contrasenya';
$activeNav = 'password_change';
$csrfToken = csrf_token();

require APP_ROOT . '/includes/header.php';
require APP_ROOT . '/views/security/change_password.php';
require APP_ROOT . '/includes/footer.php';
