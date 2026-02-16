<?php
// pages/manage_roles.php - Admin only
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['Admin']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$pdo = getPDO();
$roles = $pdo->query('SELECT * FROM roles ORDER BY name ASC')->fetchAll();
?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'role_in_use'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Error:</strong> Cannot delete role that is assigned to users. Please reassign or delete users with this role first.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between mb-3">
    <h3>Roles</h3>
    <a class="btn btn-primary" href="<?php echo BASE_URL; ?>/pages/add_role.php">Add Role</a>
</div>

<table class="table">
    <thead><tr><th>ID</th><th>Name</th><th>Description</th><th>Actions</th></tr></thead>
    <tbody>
        <?php if ($roles): foreach ($roles as $r): ?>
            <tr>
                <td><?php echo $r['id']; ?></td>
                <td><?php echo htmlspecialchars($r['name']); ?></td>
                <td><?php echo htmlspecialchars($r['description']); ?></td>
                <td>
                    <a class="btn btn-sm btn-secondary" href="<?php echo BASE_URL; ?>/pages/edit_role.php?id=<?php echo $r['id']; ?>">Edit</a>
                    <form method="post" action="<?php echo BASE_URL; ?>/pages/delete_role.php" style="display:inline;" onsubmit="return confirm('Delete role?');">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                        <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                        <button class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="4">No roles found</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>