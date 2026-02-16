<?php
// pages/user_software_expertise.php - Users can manage their software expertise
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
    
    $software_id = (int)($_POST['software_id'] ?? 0);
    $proficiency = trim($_POST['proficiency'] ?? 'Intermediate');
    $years = trim($_POST['years_experience'] ?? '');
    
    if ($software_id <= 0) { $fieldErrors['software_id'] = 'Please select software/tool.'; }
    
    if (empty($errors) && empty($fieldErrors)) {
        try {
            $stmt = $pdo->prepare('INSERT INTO user_software_expertise (user_id, software_expertise_id, proficiency, years_experience) VALUES (?, ?, ?, ?)');
            $stmt->execute([$user_id, $software_id, $proficiency, $years ?: null]);
            header('Location: ' . BASE_URL . '/pages/user_software_expertise.php?added=1');
            exit();
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate') !== false) {
                $fieldErrors['software_id'] = 'You already have this software/tool added.';
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
        $stmt = $pdo->prepare('DELETE FROM user_software_expertise WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$id, $user_id]);
        header('Location: ' . BASE_URL . '/pages/user_software_expertise.php?deleted=1');
        exit();
    }
}

// Handle EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) { $errors[] = 'Invalid CSRF token.'; }
    
    $id = (int)($_POST['id'] ?? 0);
    $proficiency = trim($_POST['proficiency'] ?? '');
    $years = trim($_POST['years_experience'] ?? '');
    
    if ($proficiency === '') { $fieldErrors['proficiency'] = 'Proficiency is required.'; }
    
    if (empty($errors) && empty($fieldErrors)) {
        $stmt = $pdo->prepare('UPDATE user_software_expertise SET proficiency = ?, years_experience = ? WHERE id = ? AND user_id = ?');
        $stmt->execute([$proficiency, $years ?: null, $id, $user_id]);
        header('Location: ' . BASE_URL . '/pages/user_software_expertise.php?updated=1');
        exit();
    }
}

// Fetch available software
$software = $pdo->query('SELECT * FROM software_expertise ORDER BY category ASC, name ASC')->fetchAll();

// Fetch user's software expertise
$userSoftware = $pdo->prepare('SELECT usp.id, usp.software_expertise_id, se.name, se.category, usp.proficiency, usp.years_experience FROM user_software_expertise usp JOIN software_expertise se ON usp.software_expertise_id = se.id WHERE usp.user_id = ? ORDER BY se.category ASC, se.name ASC');
$userSoftware->execute([$user_id]);
$userSoftwareList = $userSoftware->fetchAll();
$usedSoftwareIds = array_column($userSoftwareList, 'software_expertise_id');
?>

<div class="row">
    <div class="col-md-8">
        <h3>My Software & Tools Expertise</h3>
        
        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success">Software/tool expertise added successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">Software/tool expertise updated successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">Software/tool expertise deleted successfully.</div>
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
                        <label class="form-label">Software/Tool</label>
                        <select class="form-select" name="software_id" required>
                            <option value="">Select software/tool</option>
                            <?php foreach ($software as $s): ?>
                                <?php if (!in_array($s['id'], $usedSoftwareIds)): ?>
                                    <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?> <?php echo $s['category'] ? "({$s['category']})" : ''; ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($fieldErrors['software_id'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['software_id']); ?></div><?php endif; ?>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Proficiency Level</label>
                        <select class="form-select" name="proficiency" required>
                            <option value="Beginner">Beginner</option>
                            <option value="Intermediate" selected>Intermediate</option>
                            <option value="Advanced">Advanced</option>
                            <option value="Expert">Expert</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Years of Experience (optional)</label>
                        <input type="number" step="0.5" min="0" class="form-control" name="years_experience" placeholder="e.g. 2, 5.5">
                    </div>
                    <button class="btn btn-primary">Add Software/Tool</button>
                </form>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Your Software & Tools</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr>
                            <th>Software/Tool</th>
                            <th>Proficiency</th>
                            <th>Experience</th>
                            <th style="width: 100px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($userSoftwareList) > 0): ?>
                            <?php foreach ($userSoftwareList as $us): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($us['name']); ?> <span class="badge bg-light text-dark"><?php echo htmlspecialchars($us['category'] ?? 'General'); ?></span></td>
                                    <td>
                                        <form method="post" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id" value="<?php echo $us['id']; ?>">
                                            <input type="hidden" name="years_experience" value="<?php echo htmlspecialchars($us['years_experience'] ?? ''); ?>">
                                            <select name="proficiency" class="form-select form-select-sm" onchange="this.form.submit();">
                                                <option value="Beginner" <?php echo ($us['proficiency'] === 'Beginner') ? 'selected' : ''; ?>>Beginner</option>
                                                <option value="Intermediate" <?php echo ($us['proficiency'] === 'Intermediate') ? 'selected' : ''; ?>>Intermediate</option>
                                                <option value="Advanced" <?php echo ($us['proficiency'] === 'Advanced') ? 'selected' : ''; ?>>Advanced</option>
                                                <option value="Expert" <?php echo ($us['proficiency'] === 'Expert') ? 'selected' : ''; ?>>Expert</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td><?php echo $us['years_experience'] ? htmlspecialchars($us['years_experience']) . ' years' : '—'; ?></td>
                                    <td>
                                        <form method="post" style="display:inline;" onsubmit="return confirm('Remove this skill?');">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $us['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center text-muted">No software/tools added yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <a href="<?php echo BASE_URL; ?>/pages/profile.php" class="btn btn-secondary mt-3">Back to Profile</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
