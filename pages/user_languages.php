<?php
// pages/user_languages.php - Users can manage their languages
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$pdo = getPDO();
$user_id = $_SESSION['user']['id'];
$errors = [];
$fieldErrors = [];

// Handle ADD
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) { $errors[] = 'Invalid CSRF token.'; }
    
    $language_id = (int)($_POST['language_id'] ?? 0);
    $proficiency = trim($_POST['proficiency'] ?? 'Intermediate');
    
    if ($language_id <= 0) { $fieldErrors['language_id'] = 'Please select a language.'; }
    
    if (empty($errors) && empty($fieldErrors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO user_languages (user_id, language_id, proficiency) VALUES (?, ?, ?)');
            $stmt->execute([$user_id, $language_id, $proficiency]);
            header('Location: ' . BASE_URL . '/pages/user_languages.php?added=1');
            exit();
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $fieldErrors['language_id'] = 'You already have this language added.';
            } else {
                $errors[] = 'Error: ' . htmlspecialchars($e->getMessage());
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
        $stmt = $pdo->prepare('DELETE FROM user_languages WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$id, $user_id]);
        header('Location: ' . BASE_URL . '/pages/user_languages.php?deleted=1');
        exit();
    }
}

// Handle EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) { $errors[] = 'Invalid CSRF token.'; }
    
    $id = (int)($_POST['id'] ?? 0);
    $proficiency = trim($_POST['proficiency'] ?? '');
    
    if ($proficiency === '') { $fieldErrors['proficiency'] = 'Proficiency level is required.'; }
    
    if (empty($errors) && empty($fieldErrors)) {
        $stmt = $pdo->prepare('UPDATE user_languages SET proficiency = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$proficiency, $id, $user_id]);
        header('Location: ' . BASE_URL . '/pages/user_languages.php?updated=1');
        exit();
    }
}

// Fetch available languages
$languages = $pdo->query('SELECT * FROM languages ORDER BY name ASC')->fetchAll();

// Fetch user's languages
$userLangs = $pdo->prepare('SELECT ul.id, l.name, ul.proficiency FROM user_languages ul JOIN languages l ON ul.language_id = l.id WHERE ul.user_id = ? ORDER BY l.name ASC');
$userLangs->execute([$user_id]);
$userLanguages = $userLangs->fetchAll();

// Get used language IDs
$usedLangIds = array_column($userLanguages, 'language_id');
// Better: fetch with language ID
$userLangsWithIds = $pdo->prepare('SELECT ul.id, ul.language_id, l.name, ul.proficiency FROM user_languages ul JOIN languages l ON ul.language_id = l.id WHERE ul.user_id = ? ORDER BY l.name ASC');
$userLangsWithIds->execute([$user_id]);
$userLanguages = $userLangsWithIds->fetchAll();
$usedLangIds = array_column($userLanguages, 'language_id');
?>

<div class="row">
    <div class="col-md-8">
        <h3>My Languages</h3>
        
        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success">Language added successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">Language updated successfully.</div>
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
                        <label class="form-label">Language</label>
                        <select class="form-select" name="language_id" required>
                            <option value="">Select a language</option>
                            <?php foreach ($languages as $lang): ?>
                                <?php if (!in_array($lang['id'], $usedLangIds)): ?>
                                    <option value="<?php echo $lang['id']; ?>"><?php echo htmlspecialchars($lang['name']); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($fieldErrors['language_id'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['language_id']); ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Proficiency Level</label>
                        <select class="form-select" name="proficiency" required>
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate" selected>Intermediate</option>
                            <option value="Advanced">Advanced</option>
                            <option value="Fluent">Fluent/Native</option>
                        </select>
                    </div>
                    <button class="btn btn-primary">Add Language</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Your Languages</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Language</th>
                            <th>Proficiency</th>
                            <th style="width: 150px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($userLanguages) > 0): ?>
                            <?php foreach ($userLanguages as $ul): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ul['name']); ?></td>
                                    <td>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id" value="<?php echo $ul['id']; ?>">
                                            <select name="proficiency" class="form-select form-select-sm" onchange="this.form.submit();">
                                                <option value="Beginner" <?php echo ($ul['proficiency'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                                                <option value="Intermediate" <?php echo ($ul['proficiency'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                                <option value="Advanced" <?php echo ($ul['proficiency'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                                                <option value="Fluent" <?php echo ($ul['proficiency'] === 'Fluent') ? 'selected' : ''; ?>>Fluent/Native</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Remove this language?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $ul['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center text-muted">No languages added yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <a href="<?php echo BASE_URL; ?>/pages/profile.php" class="btn btn-secondary mt-3">Back to Profile</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
