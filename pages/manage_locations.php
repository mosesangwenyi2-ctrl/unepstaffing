<?php
// pages/manage_locations.php - Admin only (duty stations)
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['Admin']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$pdo = getPDO();
$errors = [];
// Add new location
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) { $errors[] = 'Invalid CSRF token.'; }
    $name = trim($_POST['name'] ?? '');
    if ($name === '') { $errors[] = 'Name required.'; }
    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO duty_stations (name) VALUES (?)');
        $stmt->execute([$name]);
        header('Location: ' . BASE_URL . '/pages/manage_locations.php'); exit();
    }
}

// Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) { $errors[] = 'Invalid CSRF token.'; }
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare('DELETE FROM duty_stations WHERE id = ?');
        $stmt->execute([$id]);
        header('Location: ' . BASE_URL . '/pages/manage_locations.php'); exit();
    }
}

$list = $pdo->query('SELECT id,name FROM duty_stations ORDER BY name ASC')->fetchAll();
?>

<div class="row">
    <div class="col-md-8">
        <h3>Manage Duty Stations</h3>
        <?php if ($errors): foreach ($errors as $e): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; endif; ?>

        <form method="post" class="mb-4">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <input type="hidden" name="action" value="add">
            <div class="input-group">
                <input class="form-control" name="name" placeholder="New duty station (e.g. NYC Office)">
                <button class="btn btn-primary">Add</button>
            </div>
        </form>

        <table class="table table-striped">
            <thead><tr><th>Name</th><th></th></tr></thead>
            <tbody>
                <?php if ($list): foreach ($list as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Delete?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                                <button class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="2">No duty stations defined.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
