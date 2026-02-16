<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/middleware.php';

require_login();

// Staff can view only their own dashboard; admin/HR can view any staff member's
$requested_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $_SESSION['user']['id'];

// Authorization check
if ($_SESSION['user']['role_name'] !== 'Admin' && $_SESSION['user']['role_name'] !== 'HR' && $_SESSION['user']['id'] != $requested_user_id) {
    header('Location: ' . BASE_URL . '/pages/unauthorized.php');
    exit();
}

$pdo = getPDO();

// Fetch user info
$stmt = $pdo->prepare('SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ? LIMIT 1');
$stmt->execute([$requested_user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo '<div class="alert alert-danger">User not found</div>';
    exit();
}

// Fetch user's projects
$stmt = $pdo->prepare('SELECT id, project_name, description, technologies_used, start_date, end_date, role_responsibilities, project_link FROM projects WHERE user_id = ? ORDER BY start_date DESC');
$stmt->execute([$requested_user_id]);
$projects = $stmt->fetchAll();

// Fetch user's languages
$stmt = $pdo->prepare('SELECT ul.id, l.name, ul.proficiency FROM user_languages ul JOIN languages l ON ul.language_id = l.id WHERE ul.user_id = ? ORDER BY l.name ASC');
$stmt->execute([$requested_user_id]);
$languages = $stmt->fetchAll();

// Fetch user's software expertise
$stmt = $pdo->prepare('SELECT usp.id, se.name, se.category, usp.proficiency, usp.years_experience FROM user_software_expertise usp JOIN software_expertise se ON usp.software_expertise_id = se.id WHERE usp.user_id = ? ORDER BY se.category ASC, se.name ASC');
$stmt->execute([$requested_user_id]);
$software = $stmt->fetchAll();

// Count skills by proficiency
$proficiency_counts = [
    'Beginner' => 0,
    'Intermediate' => 0,
    'Advanced' => 0,
    'Expert' => 0,
    'Fluent' => 0
];
foreach ($software as $s) {
    if (isset($proficiency_counts[$s['proficiency']])) {
        $proficiency_counts[$s['proficiency']]++;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row mb-4">
    <div class="col-md-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2><?php echo htmlspecialchars($user['full_names']); ?>'s Skills</h2>
                <p class="text-muted"><?php echo htmlspecialchars($user['index_number']); ?> | <?php echo htmlspecialchars($user['role_name']); ?></p>
            </div>
            <div>
                <a href="<?php echo BASE_URL; ?>/pages/profile.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary">View Profile</a>
            </div>
        </div>
    </div>
</div>

<!-- Skill Overview Cards -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card h-100 border-primary">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="bi bi-briefcase-fill text-primary" style="font-size:40px;"></i>
                </div>
                <h5 class="card-title">Projects</h5>
                <h2 class="text-primary mb-3"><?php echo count($projects); ?></h2>
                <a href="<?php echo BASE_URL; ?>/pages/user_projects.php<?php echo ($_SESSION['user']['role_name'] !== 'Admin' && $_SESSION['user']['role_name'] !== 'HR') ? '' : '?user_id=' . $user['id']; ?>" class="btn btn-sm btn-outline-primary">
                    <?php echo ($_SESSION['user']['id'] == $user['id']) ? 'Manage' : 'View'; ?>
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card h-100 border-success">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="bi bi-globe text-success" style="font-size:40px;"></i>
                </div>
                <h5 class="card-title">Languages</h5>
                <h2 class="text-success mb-3"><?php echo count($languages); ?></h2>
                <a href="<?php echo BASE_URL; ?>/pages/user_languages.php<?php echo ($_SESSION['user']['role_name'] !== 'Admin' && $_SESSION['user']['role_name'] !== 'HR') ? '' : '?user_id=' . $user['id']; ?>" class="btn btn-sm btn-outline-success">
                    <?php echo ($_SESSION['user']['id'] == $user['id']) ? 'Manage' : 'View'; ?>
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-3">
        <div class="card h-100 border-info">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="bi bi-laptop-fill text-info" style="font-size:40px;"></i>
                </div>
                <h5 class="card-title">Software & Tools</h5>
                <h2 class="text-info mb-3"><?php echo count($software); ?></h2>
                <a href="<?php echo BASE_URL; ?>/pages/user_software_expertise.php<?php echo ($_SESSION['user']['role_name'] !== 'Admin' && $_SESSION['user']['role_name'] !== 'HR') ? '' : '?user_id=' . $user['id']; ?>" class="btn btn-sm btn-outline-info">
                    <?php echo ($_SESSION['user']['id'] == $user['id']) ? 'Manage' : 'View'; ?>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Projects Section -->
<?php if (count($projects) > 0): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-briefcase"></i> Projects</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($projects as $proj): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-body">
                                    <h6 class="card-title text-primary"><?php echo htmlspecialchars($proj['project_name']); ?></h6>
                                    <p class="card-text small text-muted"><?php echo htmlspecialchars(substr($proj['description'] ?? '', 0, 100)); ?>...</p>
                                    
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <strong>Technologies:</strong> <?php echo htmlspecialchars(substr($proj['technologies_used'] ?? '', 0, 50)); ?>
                                        </small>
                                    </div>
                                    
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <strong>Role:</strong> <?php echo htmlspecialchars($proj['role_responsibilities'] ?? 'N/A'); ?>
                                        </small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <strong>Dates:</strong> 
                                            <?php 
                                            $start = $proj['start_date'] ? date('M Y', strtotime($proj['start_date'])) : 'N/A';
                                            $end = $proj['end_date'] ? date('M Y', strtotime($proj['end_date'])) : 'Present';
                                            echo $start . ' - ' . $end;
                                            ?>
                                        </small>
                                    </div>
                                    
                                    <?php if ($proj['project_link']): ?>
                                        <a href="<?php echo htmlspecialchars($proj['project_link']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-link-45deg"></i> View Link
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Languages Section -->
<?php if (count($languages) > 0): ?>
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-globe"></i> Languages</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php foreach ($languages as $lang): ?>
                        <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                            <strong><?php echo htmlspecialchars($lang['name']); ?></strong>
                            <span class="badge bg-info"><?php echo htmlspecialchars($lang['proficiency']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Proficiency Breakdown -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-graph-up"></i> Proficiency Breakdown</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php 
                    $proficiency_colors = [
                        'Beginner' => 'danger',
                        'Intermediate' => 'warning',
                        'Advanced' => 'info',
                        'Expert' => 'success',
                        'Fluent' => 'success'
                    ];
                    foreach ($proficiency_counts as $level => $count): 
                        if ($count > 0):
                            $color = $proficiency_colors[$level] ?? 'secondary';
                    ?>
                        <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center">
                            <strong><?php echo htmlspecialchars($level); ?></strong>
                            <span class="badge bg-<?php echo $color; ?>"><?php echo $count; ?></span>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Software Skills Section -->
<?php if (count($software) > 0): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-laptop-fill"></i> Software & Tools Expertise</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tool/Software</th>
                                <th>Category</th>
                                <th>Proficiency</th>
                                <th>Years Experience</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($software as $soft): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($soft['name']); ?></strong></td>
                                    <td><span class="badge bg-light text-dark"><?php echo htmlspecialchars($soft['category'] ?? 'General'); ?></span></td>
                                    <td>
                                        <?php
                                        $proficiencies = [
                                            'Beginner' => 'danger',
                                            'Intermediate' => 'warning',
                                            'Advanced' => 'info',
                                            'Expert' => 'success'
                                        ];
                                        $color = $proficiencies[$soft['proficiency']] ?? 'secondary';
                                        ?>
                                        <span class="badge bg-<?php echo $color; ?>"><?php echo htmlspecialchars($soft['proficiency']); ?></span>
                                    </td>
                                    <td><?php echo $soft['years_experience'] ? htmlspecialchars($soft['years_experience']) . ' years' : '—'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Empty State -->
<?php if (count($projects) === 0 && count($languages) === 0 && count($software) === 0): ?>
<div class="row">
    <div class="col-md-12">
        <div class="alert alert-info text-center">
            <i class="bi bi-info-circle" style="font-size:24px;"></i>
            <h5 class="mt-2">No Skills Added Yet</h5>
            <p>Start by adding your languages, software expertise, or projects.</p>
            <?php if ($_SESSION['user']['id'] == $user['id']): ?>
                <a href="<?php echo BASE_URL; ?>/pages/user_languages.php" class="btn btn-sm btn-outline-primary">Add Languages</a>
                <a href="<?php echo BASE_URL; ?>/pages/user_software_expertise.php" class="btn btn-sm btn-outline-primary">Add Software</a>
                <a href="<?php echo BASE_URL; ?>/pages/user_projects.php" class="btn btn-sm btn-outline-primary">Add Projects</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<hr class="my-4">

<div class="row">
    <div class="col-md-12">
        <a href="<?php echo BASE_URL; ?>/pages/profile.php?id=<?php echo $user['id']; ?>" class="btn btn-secondary">Back to Profile</a>
        <?php if ($_SESSION['user']['role_name'] === 'Admin' || $_SESSION['user']['role_name'] === 'HR'): ?>
            <a href="<?php echo BASE_URL; ?>/pages/manage_users.php" class="btn btn-outline-secondary">Back to Users</a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
