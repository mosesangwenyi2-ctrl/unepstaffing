<?php
// pages/add_role.php - Admin only
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['Admin']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$pdo = getPDO();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) { $errors[] = 'Invalid CSRF token.'; }
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    if ($name === '') $errors[] = 'Role name required.';

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO roles (name,description,created_at) VALUES (?,?,NOW())');
        try {
            $stmt->execute([$name,$desc]);
            header('Location: ' . BASE_URL . '/pages/manage_roles.php'); exit();
        } catch (PDOException $e) {
            $errors[] = 'Failed to create role. Name may already exist.';
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <h3>Add Role</h3>
        <?php if ($errors): foreach ($errors as $e): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; endif; ?>
        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <div class="mb-3">
                <label class="form-label">Role Name</label>
                <input class="form-control" name="name" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description"></textarea>
            </div>
            <button class="btn btn-primary">Create Role</button>
            <a class="btn btn-secondary" href="<?php echo BASE_URL; ?>/pages/manage_roles.php">Cancel</a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>