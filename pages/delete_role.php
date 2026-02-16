<?php
// pages/delete_role.php - Admin only
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['Admin']);
require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . BASE_URL . '/pages/manage_roles.php'); exit(); }
$token = $_POST['csrf_token'] ?? '';
if (!verify_csrf_token($token)) { die('Invalid CSRF'); }
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
if ($id <= 0) { die('Invalid id'); }
$pdo = getPDO();

// Check if role is in use
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role_id = ?');
$stmt->execute([$id]);
$count = $stmt->fetchColumn();

if ($count > 0) {
    header('Location: ' . BASE_URL . '/pages/manage_roles.php?error=role_in_use');
    exit();
}

// Delete role if not in use
$stmt = $pdo->prepare('DELETE FROM roles WHERE id = ?');
$stmt->execute([$id]);
header('Location: ' . BASE_URL . '/pages/manage_roles.php');
exit();
