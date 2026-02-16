<?php
/**
 * Procedural middleware helpers
 * - require_login()
 * - require_role(array $roles)
 */
require_once __DIR__ . '/../config/database.php';

function require_login()
{
    if (!isset($_SESSION)) { session_start(); }
    if (empty($_SESSION['user'])) {
        // Save attempted URL for redirect after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
}

function require_role($roles = [])
{
    if (!isset($_SESSION)) { session_start(); }
    if (empty($_SESSION['user'])) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
    if (empty($roles)) return true; // only require login

    $userRole = $_SESSION['user']['role_name'] ?? null;
    if (!in_array($userRole, $roles, true)) {
        // unauthorized
        header('Location: ' . BASE_URL . '/pages/unauthorized.php');
        exit();
    }
    return true;
}
