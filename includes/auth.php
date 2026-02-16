<?php
/**
 * Procedural auth helpers
 * - login_user()
 * - logout_user()
 * - current_user(), is_logged_in()
 */
require_once __DIR__ . '/../config/database.php';

function login_user($email, $password)
{
    $pdo = getPDO();
    $sql = 'SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ? LIMIT 1';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        // Remove password from session
        unset($user['password']);
        $_SESSION['user'] = $user;
        $_SESSION['last_activity'] = time();
        return true;
    }
    return false;
}

function logout_user()
{
    $_SESSION = [];
    if (session_id() !== '') {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    session_destroy();
}

function is_logged_in()
{
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

function current_user()
{
    return is_logged_in() ? $_SESSION['user'] : null;
}
