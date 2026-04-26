<?php
declare(strict_types=1);

function auth_user_id(): ?int
{
    return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
}

function auth_role_id(): ?int
{
    if (!array_key_exists('role_id', $_SESSION)) {
        return null;
    }
    return $_SESSION['role_id'] !== null ? (int) $_SESSION['role_id'] : null;
}

/**
 * Slug del rol de l’usuari connectat (users.role_id → roles.slug). Null si sense rol o sense sessió.
 */
function auth_user_role_slug(): ?string
{
    if (!auth_is_logged_in()) {
        return null;
    }
    $st = db()->prepare(
        'SELECT r.slug FROM roles r
         INNER JOIN users u ON u.role_id = r.id
         WHERE u.id = :uid LIMIT 1'
    );
    $st->execute(['uid' => auth_user_id()]);
    $row = $st->fetch();

    return $row ? (string) $row['slug'] : null;
}

function auth_is_logged_in(): bool
{
    return auth_user_id() !== null;
}

function auth_require_login(): void
{
    if (!auth_is_logged_in()) {
        redirect(app_url('login.php'));
    }
}

function auth_login(int $userId, string $username, string $fullName, ?int $roleId): void
{
    session_regenerate_id(true);
    $_SESSION['user_id']    = $userId;
    $_SESSION['username']   = $username;
    $_SESSION['full_name']  = $fullName;
    $_SESSION['role_id']    = $roleId;
    unset($_SESSION['permissions']);
}

function auth_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function auth_attempt_login(string $username, string $password): bool
{
    $username = trim($username);
    if ($username === '' || $password === '') {
        return false;
    }

    $sql = 'SELECT id, username, password_hash, full_name, role_id, is_active
            FROM users WHERE username = :u LIMIT 1';
    $st = db()->prepare($sql);
    $st->execute(['u' => $username]);
    $row = $st->fetch();

    if (!$row || !(bool) $row['is_active']) {
        return false;
    }

    if (!password_verify($password, $row['password_hash'])) {
        return false;
    }

    auth_login(
        (int) $row['id'],
        (string) $row['username'],
        (string) $row['full_name'],
        $row['role_id'] !== null ? (int) $row['role_id'] : null
    );
    return true;
}
