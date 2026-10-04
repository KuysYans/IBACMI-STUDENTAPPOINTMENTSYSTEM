<?php
/**
 * Session / auth helpers.
 * Include this AFTER config/db.php has already been required
 * (it needs $pdo for login_user()).
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Returns the logged-in user array, or null. */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Guards a page to one or more roles. Redirects to the landing
 * page (with a friendly message) if not logged in / wrong role.
 * $homePath is the relative path back to index.php from wherever
 * this is called (e.g. '../index.php' from /student or /staff).
 */
function require_role($roles, string $homePath = 'index.php'): array
{
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    $user = current_user();
    if (!$user || !in_array($user['role'], $roles, true)) {
        header('Location: ' . $homePath . '?auth=required');
        exit;
    }
    return $user;
}

/** Attempts a login; returns true/false. Stores user (minus password) in session. */
function login_user(PDO $pdo, string $email, string $password, string $role): bool
{
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1');
    $stmt->execute([$email, $role]);
    $u = $stmt->fetch();

    if ($u && password_verify($password, $u['password'])) {
        unset($u['password']);
        $_SESSION['user'] = $u;
        return true;
    }
    return false;
}

function logout_user(): void
{
    $_SESSION = [];
    session_destroy();
}
