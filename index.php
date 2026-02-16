<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/database.php';

if (!is_logged_in()) {
    header('Location: ' . BASE_URL . '/login.php'); exit();
}

// Redirect Admin and HR to dashboard
if (in_array($_SESSION['user']['role_name'], ['Admin', 'HR'])) {
    header('Location: ' . BASE_URL . '/pages/dashboard.php'); exit();
}

$pdo = getPDO();
$id = $_SESSION['user']['id'];

// Fetch skill counts and data
$projCount = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE user_id = {$id}")->fetchColumn();
$langCount = (int)$pdo->query("SELECT COUNT(*) FROM user_languages WHERE user_id = {$id}")->fetchColumn();
$softCount = (int)$pdo->query("SELECT COUNT(*) FROM user_software_expertise WHERE user_id = {$id}")->fetchColumn();

// Fetch user's projects
$stmt = $pdo->prepare('SELECT id, project_name, description, technologies_used, start_date, end_date, role_responsibilities, project_link FROM projects WHERE user_id = ? ORDER BY start_date DESC LIMIT 5');
$stmt->execute([$id]);
$projects = $stmt->fetchAll();

// Fetch user's languages
$stmt = $pdo->prepare('SELECT ul.id, l.name, ul.proficiency FROM user_languages ul JOIN languages l ON ul.language_id = l.id WHERE ul.user_id = ? ORDER BY l.name ASC');
$stmt->execute([$id]);
$languages = $stmt->fetchAll();

// Fetch user's software expertise
$stmt = $pdo->prepare('SELECT usp.id, se.name, se.category, usp.proficiency, usp.years_experience FROM user_software_expertise usp JOIN software_expertise se ON usp.software_expertise_id = se.id WHERE usp.user_id = ? ORDER BY se.category ASC, se.name ASC LIMIT 10');
$stmt->execute([$id]);
$software = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2 class="mb-1">Welcome, <?php echo htmlspecialchars($_SESSION['user']['full_names']); ?></h2>
        <p class="text-muted mb-4">Your Skills Dashboard</p>
    </div>
</div>

<!-- Skills Overview Cards -->
<div class="row mb-4">
    <div class="col-md-4 mb-3">
        <div class="card h-100 border-primary">
            <div class="card-body text-center">
                <div class="mb-3">
                    <i class="bi bi-briefcase-fill text-primary" style="font-size:40px;"></i>
                </div>
                <h5 class="card-title">Projects</h5>
                <h2 class="text-primary mb-3"><?php echo $projCount; ?></h2>
                <a href="<?php echo BASE_URL; ?>/pages/user_projects.php" class="btn btn-sm btn-outline-primary">Manage</a>
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
                <h2 class="text-success mb-3"><?php echo $langCount; ?></h2>
                <a href="<?php echo BASE_URL; ?>/pages/user_languages.php" class="btn btn-sm btn-outline-success">Manage</a>
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
                <h2 class="text-info mb-3"><?php echo $softCount; ?></h2>
                <a href="<?php echo BASE_URL; ?>/pages/user_software_expertise.php" class="btn btn-sm btn-outline-info">Manage</a>
            </div>
        </div>
    </div>
</div>

<!-- Recent Projects -->
<?php if (count($projects) > 0): ?>
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-light">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-briefcase"></i> Your Recent Projects</h5>
                    <a href="<?php echo BASE_URL; ?>/pages/user_projects.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($projects as $proj): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card h-100 shadow-sm border-0">
                                <div class="card-body">
                                    <h6 class="card-title text-primary"><?php echo htmlspecialchars($proj['project_name']); ?></h6>
                                    <p class="card-text small text-muted"><?php echo htmlspecialchars(substr($proj['description'] ?? '', 0, 80)); ?>...</p>
                                    
                                    <div class="mb-2">
                                        <small class="text-muted"><strong>Tech:</strong> <?php echo htmlspecialchars(substr($proj['technologies_used'] ?? '', 0, 40)); ?></small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <strong>Period:</strong> 
                                            <?php 
                                            $start = $proj['start_date'] ? date('M Y', strtotime($proj['start_date'])) : 'N/A';
                                            $end = $proj['end_date'] ? date('M Y', strtotime($proj['end_date'])) : 'In Progress';
                                            echo $start . ' - ' . $end;
                                            ?>
                                        </small>
                                    </div>
                                    
                                    <?php if ($proj['project_link']): ?>
                                        <a href="<?php echo htmlspecialchars($proj['project_link']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-link-45deg"></i> Link
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

<!-- Languages & Software -->
<div class="row mb-4">
    <?php if (count($languages) > 0): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-globe"></i> Languages</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php foreach ($languages as $idx => $lang): ?>
                        <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center <?php echo $idx >= 6 ? 'list-item-hidden' : ''; ?>" data-list-id="languages-list">
                            <strong><?php echo htmlspecialchars($lang['name']); ?></strong>
                            <span class="badge bg-info"><?php echo htmlspecialchars($lang['proficiency']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if (count($languages) > 6): ?>
                    <div class="see-more-divider"></div>
                    <button class="btn btn-sm btn-link see-more-btn" data-toggle-list="languages-list" data-expanded="false">
                        <i class="bi bi-chevron-down"></i> See More
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (count($software) > 0): ?>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-laptop-fill"></i> Software & Tools</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tool</th>
                                <th>Proficiency</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($software as $idx => $soft): ?>
                                <tr class="<?php echo $idx >= 6 ? 'list-item-hidden' : ''; ?>" data-list-id="software-list">
                                    <td><small><?php echo htmlspecialchars($soft['name']); ?></small></td>
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
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($software) > 6): ?>
                    <div class="see-more-divider"></div>
                    <button class="btn btn-sm btn-link see-more-btn" data-toggle-list="software-list" data-expanded="false">
                        <i class="bi bi-chevron-down"></i> See More
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Quick Links -->
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header bg-light">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <a href="<?php echo BASE_URL; ?>/pages/profile.php?id=<?php echo $_SESSION['user']['id']; ?>" class="btn btn-primary">View Full Profile</a>
                <a href="<?php echo BASE_URL; ?>/pages/manage_users.php" class="btn btn-outline-primary">Staff Directory</a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
