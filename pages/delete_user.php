<?php
// pages/delete_user.php - Admin only (procedural)
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['Admin']);
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/pages/manage_users.php');
    exit();
}

$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) {
    die('Invalid CSRF token');
}

$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) {
    die('Invalid user id');
}

// Prevent admin from deleting themselves
if ($id === $_SESSION['user']['id']) {
    header('Location: ' . BASE_URL . '/pages/manage_users.php?error=cannot_delete_self');
    exit();
}

$pdo = getPDO();
$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
$stmt->execute([$id]);

header('Location: ' . BASE_URL . '/pages/manage_users.php');
exit();
