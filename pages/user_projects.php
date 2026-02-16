<?php
// pages/user_projects.php - Users can manage their project portfolio
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$pdo = getPDO();
$user_id = $_SESSION['user']['id'];
$errors = [];
$fieldErrors = [];
$edit_id = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editProject = null;

// If editing, fetch the project
if ($edit_id > 0) {
    $stmt = $pdo->prepare('SELECT * FROM projects WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$edit_id, $user_id]);
    $editProject = $stmt->fetch();
    if (!$editProject) { $edit_id = 0; }
}

// Handle ADD/EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && ($_POST['action'] === 'add' || $_POST['action'] === 'edit')) {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) { $errors[] = 'Invalid CSRF token.'; }
    
    $project_name = trim($_POST['project_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $technologies = trim($_POST['technologies_used'] ?? '');
    $start_date = trim($_POST['start_date'] ?? '');
    $end_date = trim($_POST['end_date'] ?? '');
    $role = trim($_POST['role_responsibilities'] ?? '');
    $link = trim($_POST['project_link'] ?? '');
    
    if ($project_name === '') { $fieldErrors['project_name'] = 'Project name is required.'; }
    if ($description === '') { $fieldErrors['description'] = 'Description is required.'; }
    if ($start_date === '') { $fieldErrors['start_date'] = 'Start date is required.'; }
    
    if (empty($errors) && empty($fieldErrors)) {
        if ($_POST['action'] === 'add') {
            $stmt = $pdo->prepare('INSERT INTO projects (user_id, project_name, description, technologies_used, start_date, end_date, role_responsibilities, project_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([$user_id, $project_name, $description, $technologies, $start_date, $end_date ?: null, $role, $link ?: null]);
            header('Location: ' . BASE_URL . '/pages/user_projects.php?added=1');
            exit();
        } else {
            $project_id = (int)($_POST['id'] ?? 0);
            $stmt = $pdo->prepare('UPDATE projects SET project_name=?, description=?, technologies_used=?, start_date=?, end_date=?, role_responsibilities=?, project_link=? WHERE id=? AND user_id=?');
            $stmt->execute([$project_name, $description, $technologies, $start_date, $end_date ?: null, $role, $link ?: null, $project_id, $user_id]);
            header('Location: ' . BASE_URL . '/pages/user_projects.php?updated=1');
            exit();
        }
    }
}

// Handle DELETE
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) { $errors[] = 'Invalid CSRF token.'; }
    
    $id = (int)($_POST['id'] ?? 0);
    if ($id > 0) {
        $stmt = $pdo->prepare('DELETE FROM projects WHERE id = ? AND user_id = ? LIMIT 1');
        $stmt->execute([$id, $user_id]);
        header('Location: ' . BASE_URL . '/pages/user_projects.php?deleted=1');
        exit();
    }
}

// Fetch user's projects
$projects = $pdo->prepare('SELECT * FROM projects WHERE user_id = ? ORDER BY start_date DESC');
$projects->execute([$user_id]);
$userProjects = $projects->fetchAll();
?>

<div class="row">
    <div class="col-md-9">
        <h3><?php echo $edit_id > 0 ? 'Edit Project' : 'My Projects'; ?></h3>
        
        <?php if (isset($_GET['added'])): ?>
            <div class="alert alert-success">Project added successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">Project updated successfully.</div>
        <?php endif; ?>
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-success">Project deleted successfully.</div>
        <?php endif; ?>
        <?php if ($errors): foreach ($errors as $e): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; endif; ?>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><?php echo $edit_id > 0 ? 'Edit Project' : 'Add New Project'; ?></h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="action" value="<?php echo $edit_id > 0 ? 'edit' : 'add'; ?>">
                    <?php if ($edit_id > 0): ?>
                        <input type="hidden" name="id" value="<?php echo $edit_id; ?>">
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <label class="form-label">Project Name</label>
                        <input class="form-control" name="project_name" value="<?php echo htmlspecialchars($_POST['project_name'] ?? $editProject['project_name'] ?? ''); ?>" required>
                        <?php if (!empty($fieldErrors['project_name'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['project_name']); ?></div><?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3" required><?php echo htmlspecialchars($_POST['description'] ?? $editProject['description'] ?? ''); ?></textarea>
                        <?php if (!empty($fieldErrors['description'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['description']); ?></div><?php endif; ?>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" class="form-control" name="start_date" value="<?php echo htmlspecialchars($_POST['start_date'] ?? $editProject['start_date'] ?? ''); ?>" required>
                                <?php if (!empty($fieldErrors['start_date'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['start_date']); ?></div><?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">End Date (optional)</label>
                                <input type="date" class="form-control" name="end_date" value="<?php echo htmlspecialchars($_POST['end_date'] ?? $editProject['end_date'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Technologies Used</label>
                        <input class="form-control" name="technologies_used" placeholder="e.g. PHP, MySQL, Bootstrap, JavaScript" value="<?php echo htmlspecialchars($_POST['technologies_used'] ?? $editProject['technologies_used'] ?? ''); ?>">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Your Role & Responsibilities</label>
                        <textarea class="form-control" name="role_responsibilities" rows="3" placeholder="Describe your role and contributions to the project"><?php echo htmlspecialchars($_POST['role_responsibilities'] ?? $editProject['role_responsibilities'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Project Link (optional)</label>
                        <input type="url" class="form-control" name="project_link" placeholder="https://example.com/project" value="<?php echo htmlspecialchars($_POST['project_link'] ?? $editProject['project_link'] ?? ''); ?>">
                    </div>
                    
                    <button class="btn btn-primary"><?php echo $edit_id > 0 ? 'Update Project' : 'Add Project'; ?></button>
                    <?php if ($edit_id > 0): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/user_projects.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <?php if (!($edit_id > 0)): ?>
        <div class="card">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0">Your Projects (<?php echo count($userProjects); ?>)</h5>
            </div>
            <div class="card-body">
                <?php if (count($userProjects) > 0): ?>
                    <?php foreach ($userProjects as $proj): ?>
                        <div class="card mb-3 border-light">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($proj['project_name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($proj['description']); ?></p>
                                <?php if ($proj['technologies_used']): ?>
                                    <p class="small text-muted"><strong>Technologies:</strong> <?php echo htmlspecialchars($proj['technologies_used']); ?></p>
                                <?php endif; ?>
                                <p class="small text-muted">
                                    <strong>Duration:</strong> <?php echo htmlspecialchars(date('M Y', strtotime($proj['start_date']))); ?> 
                                    <?php echo $proj['end_date'] ? '— ' . date('M Y', strtotime($proj['end_date'])) : '— Present'; ?>
                                </p>
                                <?php if ($proj['role_responsibilities']): ?>
                                    <p class="small"><strong>Role:</strong> <?php echo htmlspecialchars(substr($proj['role_responsibilities'], 0, 100)); ?>...</p>
                                <?php endif; ?>
                                <div class="mt-2">
                                    <a href="<?php echo BASE_URL; ?>/pages/user_projects.php?edit=<?php echo $proj['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <form method="post" style="display:inline;" onsubmit="return confirm('Delete this project?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $proj['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted">No projects added yet.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <a href="<?php echo BASE_URL; ?>/pages/profile.php" class="btn btn-secondary mt-3">Back to Profile</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
