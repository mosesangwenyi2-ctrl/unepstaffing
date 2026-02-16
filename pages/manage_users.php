<?php
// pages/manage_users.php - Admin/HR can view staff
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['Admin','HR']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$pdo = getPDO();
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$params = [];
$sql = 'SELECT u.id,u.index_number,u.full_names,u.email,u.current_location,u.highest_education,u.duty_station,u.availability_remote,r.name as role_name
        FROM users u JOIN roles r ON u.role_id = r.id WHERE 1=1';
if ($search !== '') {
    $sql .= ' AND (u.full_names LIKE ? OR u.email LIKE ? OR u.index_number LIKE ? OR u.duty_station LIKE ?)';
    $s = "%$search%";
    $params = [$s,$s,$s,$s];
}
$sql .= ' ORDER BY u.full_names ASC LIMIT 100';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<?php if (isset($_GET['error']) && $_GET['error'] === 'cannot_delete_self'): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <strong>Error:</strong> You cannot delete your own account.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="d-flex justify-content-between mb-3">
    <h3>Staff Listing</h3>
    <?php if ($_SESSION['user']['role_name'] === 'Admin'): ?>
        <a href="<?php echo BASE_URL; ?>/pages/add_user.php" class="btn btn-primary">Add User</a>
    <?php endif; ?>
</div>

<form class="row g-2 mb-3" method="get" action="">
    <div class="col-md-4">
        <input type="text" name="search" class="form-control" placeholder="Search by name, email, index number, duty station" value="<?php echo htmlspecialchars($search); ?>">
    </div>
    <div class="col-md-2">
        <button class="btn btn-secondary">Search</button>
    </div>
</form>

<table class="table table-striped">
    <thead>
        <tr>
            <th>Index</th>
            <th>Full Names</th>
            <th>Email</th>
            <th>Location</th>
            <th>Education</th>
            <th>Duty Station</th>
            <th>Remote</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($users): foreach ($users as $u): ?>
            <tr>
                <td><?php echo htmlspecialchars($u['index_number']); ?></td>
                <td><?php echo htmlspecialchars($u['full_names']); ?></td>
                <td><?php echo htmlspecialchars($u['email']); ?></td>
                <td><?php echo htmlspecialchars($u['current_location']); ?></td>
                <td><?php echo htmlspecialchars($u['highest_education']); ?></td>
                <td><?php echo htmlspecialchars($u['duty_station']); ?></td>
                <td><?php echo $u['availability_remote'] ? 'Yes' : 'No'; ?></td>
                <td>
                    <a class="btn btn-sm btn-info" href="<?php echo BASE_URL; ?>/pages/profile.php?id=<?php echo $u['id']; ?>">View</a>
                    <?php if (in_array($_SESSION['user']['role_name'], ['Admin','HR'])): ?>
                        <a class="btn btn-sm btn-secondary" href="<?php echo BASE_URL; ?>/pages/edit_user.php?id=<?php echo $u['id']; ?>">Edit</a>
                    <?php endif; ?>
                    <?php if ($_SESSION['user']['role_name'] === 'Admin'): ?>
                        <?php if ($u['id'] === $_SESSION['user']['id']): ?>
                            <button class="btn btn-sm btn-danger" disabled title="Cannot delete your own account">Delete</button>
                        <?php else: ?>
                            <form method="post" action="<?php echo BASE_URL; ?>/pages/delete_user.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                <button class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; else: ?>
            <tr><td colspan="8">No users found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
