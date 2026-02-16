<?php
// pages/edit_role.php - Admin only
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['Admin']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$pdo = getPDO();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: ' . BASE_URL . '/pages/manage_roles.php'); exit(); }

$stmt = $pdo->prepare('SELECT * FROM roles WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$role = $stmt->fetch();
if (!$role) { echo '<div class="alert alert-danger">Role not found</div>'; require_once __DIR__ . '/../includes/footer.php'; exit(); }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) { $errors[] = 'Invalid CSRF token.'; }
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($name === '') $errors[] = 'Role name required.';
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare('UPDATE roles SET name=?, description=? WHERE id=?');
            $stmt->execute([$name,$desc,$id]);
            header('Location: ' . BASE_URL . '/pages/manage_roles.php'); exit();
        } catch (PDOException $e) {
            $errors[] = 'Failed to update role. Name may be in use.';
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h3>Edit Role</h3>
        <?php if ($errors): foreach ($errors as $e): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; endif; ?>
        <form method="post" action="?id=<?php echo $id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <div class="mb-3">
                <label class="form-label">Role Name</label>
                <input class="form-control" name="name" value="<?php echo htmlspecialchars($role['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description"><?php echo htmlspecialchars($role['description']); ?></textarea>
            </div>
            <button class="btn btn-primary">Save</button>
            <a class="btn btn-secondary" href="<?php echo BASE_URL; ?>/pages/manage_roles.php">Cancel</a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>