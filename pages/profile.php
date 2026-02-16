<?php
// pages/profile.php - any logged in user can view; staff can edit own profile
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

$pdo = getPDO();
$id = isset($_GET['id']) ? (int)$_GET['id'] : ($_SESSION['user']['id'] ?? 0);
if ($id <= 0) {
    echo '<div class="alert alert-danger">Invalid user</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

$stmt = $pdo->prepare('SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ? LIMIT 1');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) {
    echo '<div class="alert alert-danger">User not found</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

// Fetch skill counts
$projCount = (int)$pdo->query("SELECT COUNT(*) FROM projects WHERE user_id = {$id}")->fetchColumn();
$langCount = (int)$pdo->query("SELECT COUNT(*) FROM user_languages WHERE user_id = {$id}")->fetchColumn();
$softCount = (int)$pdo->query("SELECT COUNT(*) FROM user_software_expertise WHERE user_id = {$id}")->fetchColumn();

// Fetch user's projects
$stmt = $pdo->prepare('SELECT id, project_name, description, technologies_used, start_date, end_date, role_responsibilities, project_link FROM projects WHERE user_id = ? ORDER BY start_date DESC');
$stmt->execute([$id]);
$projects = $stmt->fetchAll();

// Fetch user's languages
$stmt = $pdo->prepare('SELECT ul.id, l.name, ul.proficiency FROM user_languages ul JOIN languages l ON ul.language_id = l.id WHERE ul.user_id = ? ORDER BY l.name ASC');
$stmt->execute([$id]);
$languages = $stmt->fetchAll();

// Fetch user's software expertise
$stmt = $pdo->prepare('SELECT usp.id, se.name, se.category, usp.proficiency, usp.years_experience FROM user_software_expertise usp JOIN software_expertise se ON usp.software_expertise_id = se.id WHERE usp.user_id = ? ORDER BY se.category ASC, se.name ASC');
$stmt->execute([$id]);
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

<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body d-flex gap-4 align-items-center">
                <div class="profile-avatar text-center">
                    <?php
                        $gender = strtolower(trim($user['gender'] ?? ''));
                        if ($gender === 'male') {
                            $icon = 'bi-gender-male';
                            $bg = 'bg-primary';
                        } elseif ($gender === 'female') {
                            $icon = 'bi-gender-female';
                            $bg = 'bg-danger';
                        } else {
                            $icon = 'bi-person-circle';
                            $bg = 'bg-secondary';
                        }
                    ?>
                    <div class="rounded-circle d-inline-flex justify-content-center align-items-center <?php echo $bg; ?> text-white" style="width:96px;height:96px;font-size:38px;">
                        <i class="bi <?php echo $icon; ?>" style="font-size:38px;"></i>
                    </div>
                    <div class="mt-2 text-muted small"><?php echo htmlspecialchars(ucfirst($gender ?: 'Unknown')); ?></div>
                </div>

                <div class="flex-grow-1">
                    <h3 class="mb-1"><?php echo htmlspecialchars($user['full_names']); ?></h3>
                    <div class="text-muted mb-3"><?php echo htmlspecialchars($user['role_name']); ?> &nbsp;|&nbsp; <?php echo htmlspecialchars($user['index_number']); ?></div>

                    <div class="row">
                        <div class="col-sm-6 mb-2"><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></div>
                        <div class="col-sm-6 mb-2"><strong>Current Location:</strong> <?php echo htmlspecialchars($user['current_location']); ?></div>
                        <div class="col-sm-6 mb-2"><strong>Highest Education:</strong> <?php echo htmlspecialchars($user['highest_education']); ?></div>
                        <div class="col-sm-6 mb-2"><strong>Duty Station:</strong> <?php echo htmlspecialchars($user['duty_station']); ?></div>
                        <div class="col-sm-6 mb-2"><strong>Available Remote:</strong> <?php echo $user['availability_remote'] ? 'Yes' : 'No'; ?></div>
                    </div>

                    <?php if ($_SESSION['user']['role_name'] === 'Admin' || $_SESSION['user']['role_name'] === 'HR' || $_SESSION['user']['id'] == $user['id']): ?>
                        <a class="btn btn-secondary mt-3" href="<?php echo BASE_URL; ?>/pages/edit_user.php?id=<?php echo $user['id']; ?>">Edit Profile</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Skills Cards -->
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-briefcase-fill text-primary" style="font-size:32px;"></i>
                        <h5 class="card-title mt-2">Projects</h5>
                        <p class="display-6"><?php echo $projCount; ?></p>
                        <a href="<?php echo BASE_URL; ?>/pages/user_projects.php" class="btn btn-sm btn-outline-primary">View / Edit</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-globe text-success" style="font-size:32px;"></i>
                        <h5 class="card-title mt-2">Languages</h5>
                        <p class="display-6"><?php echo $langCount; ?></p>
                        <a href="<?php echo BASE_URL; ?>/pages/user_languages.php" class="btn btn-sm btn-outline-success">View / Edit</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="card text-center">
                    <div class="card-body">
                        <i class="bi bi-laptop-fill text-info" style="font-size:32px;"></i>
                        <h5 class="card-title mt-2">Software & Tools</h5>
                        <p class="display-6"><?php echo $softCount; ?></p>
                        <a href="<?php echo BASE_URL; ?>/pages/user_software_expertise.php" class="btn btn-sm btn-outline-info">View / Edit</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Projects Section -->
        <?php if (count($projects) > 0): ?>
        <div class="card mb-4 mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="bi bi-briefcase"></i> Projects</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <?php foreach ($projects as $idx => $proj): ?>
                        <div class="col-md-6 mb-3 <?php echo $idx >= 6 ? 'list-item-hidden' : ''; ?>" data-list-id="projects-list">
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
                <?php if (count($projects) > 6): ?>
                    <div class="see-more-divider"></div>
                    <button class="btn btn-sm btn-link see-more-btn" data-toggle-list="projects-list" data-expanded="false">
                        <i class="bi bi-chevron-down"></i> See More Projects
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Languages & Proficiency Section -->
        <?php if (count($languages) > 0): ?>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="bi bi-globe"></i> Languages</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php foreach ($languages as $idx => $lang): ?>
                                <div class="list-group-item px-0 py-2 d-flex justify-content-between align-items-center <?php echo $idx >= 6 ? 'list-item-hidden' : ''; ?>" data-list-id="profile-languages-list">
                                    <strong><?php echo htmlspecialchars($lang['name']); ?></strong>
                                    <span class="badge bg-info"><?php echo htmlspecialchars($lang['proficiency']); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($languages) > 6): ?>
                            <div class="see-more-divider"></div>
                            <button class="btn btn-sm btn-link see-more-btn" data-toggle-list="profile-languages-list" data-expanded="false">
                                <i class="bi bi-chevron-down"></i> See More
                            </button>
                        <?php endif; ?>
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
        <div class="card mb-4">
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
                            <?php foreach ($software as $idx => $soft): ?>
                                <tr class="<?php echo $idx >= 6 ? 'list-item-hidden' : ''; ?>" data-list-id="profile-software-list">
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
                <?php if (count($software) > 6): ?>
                    <div class="see-more-divider"></div>
                    <button class="btn btn-sm btn-link see-more-btn" data-toggle-list="profile-software-list" data-expanded="false">
                        <i class="bi bi-chevron-down"></i> See More
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
