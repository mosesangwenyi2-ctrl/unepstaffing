<?php
// pages/manage_languages.php - Admin only: manage language list
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
    if ($name === '') { $fieldErrors['name'] = 'Language name is required.'; }
    
    if (empty($errors) && empty($fieldErrors)) {
        $stmt = $pdo->prepare('INSERT INTO languages (name) VALUES (?)');
        try {
            $stmt->execute([$name]);
            header('Location: ' . BASE_URL . '/pages/manage_languages.php?added=1');
            exit();
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $fieldErrors['name'] = 'This language already exists.';
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
        $stmt = $pdo->prepare('DELETE FROM languages WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        header('Location: ' . BASE_URL . '/pages/manage_languages.php?deleted=1');
        exit();
    }
}

// Fetch all languages
$languages = $pdo->query('SELECT * FROM languages ORDER BY name ASC')->fetchAll();
?>

<div class="row">
    <div class="col-md-8">
        <h3>Manage Languages</h3>
        
        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success">Language added successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">Language deleted successfully.</div>
        <?php endif; ?>
        <?php if ($errors): foreach ($errors as $e): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Add Language</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label class="form-label">Language Name</label>
                        <input class="form-control" name="name" placeholder="e.g. English, Spanish, French" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                        <?php if (!empty($fieldErrors['name'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['name']); ?></div><?php endif; ?>
                    </div>
                    <button class="btn btn-primary">Add Language</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Languages List</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Language</th>
                            <th style="width: 100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($languages) > 0): ?>
                            <?php foreach ($languages as $lang): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($lang['name']); ?></td>
                                    <td>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Delete this language?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $lang['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="2" class="text-center text-muted">No languages yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
