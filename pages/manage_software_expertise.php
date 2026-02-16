<?php
// pages/manage_software_expertise.php - Admin only: manage software/tools list
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['Admin']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$pdo = getPDO();
$errors = [];
$fieldErrors = [];

// Handle ADD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) { $errors[] = 'Invalid CSRF token.'; }
    
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    
    if ($name === '') { $fieldErrors['name'] = 'Software/tool name is required.'; }
    
    if (empty($errors) && empty($fieldErrors)) {
        $stmt = $pdo->prepare('INSERT INTO software_expertise (name, category) VALUES (?, ?)');
        try {
            $stmt->execute([$name, $category ?: null]);
            header('Location: ' . BASE_URL . '/pages/manage_software_expertise.php?added=1');
            exit();
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $fieldErrors['name'] = 'This software/tool already exists.';
            } else {
                $errors[] = 'Database error: ' . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) { $errors[] = 'Invalid CSRF token.'; }
    
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare('DELETE FROM software_expertise WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        header('Location: ' . BASE_URL . '/pages/manage_software_expertise.php?deleted=1');
        exit();
    }
}

// Fetch all software expertise
$software = $pdo->query('SELECT * FROM software_expertise ORDER BY category ASC, name ASC')->fetchAll();
?>

<div class="row">
    <div class="col-md-8">
        <h3>Manage Software & Tools</h3>
        
        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success">Software/tool added successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">Software/tool deleted successfully.</div>
        <?php endif; ?>
        <?php if ($errors): foreach ($errors as $e): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Add Software/Tool</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Software/Tool Name</label>
                        <input class="form-control" name="name" placeholder="e.g. Python, MySQL, React" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        <?php if (!empty($fieldErrors['name'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['name']); ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category (optional)</label>
                        <input class="form-control" name="category" placeholder="e.g. Programming, Database, DevOps" value="<?php echo htmlspecialchars($_POST['category'] ?? ''); ?>">
                    </div>
                    <button class="btn btn-primary">Add Software/Tool</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Software & Tools List</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Software/Tool</th>
                            <th>Category</th>
                            <th style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($software) > 0): ?>
                            <?php foreach ($software as $soft): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($soft['name']); ?></td>
                                    <td><?php echo htmlspecialchars($soft['category'] ?? '—'); ?></td>
                                    <td>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this software/tool?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $soft['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center text-muted">No software/tools yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
