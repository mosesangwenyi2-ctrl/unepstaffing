<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/middleware.php';

require_login();
require_role(['Admin', 'HR']);

$pdo = getPDO();

// Fetch all statistics
$stats = [];

// Total Users
$stmt = $pdo->prepare('SELECT COUNT(*) as total FROM users');
$stmt->execute();
$stats['total_users'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Gender breakdown
$stmt = $pdo->prepare('SELECT gender, COUNT(*) as count FROM users WHERE gender IS NOT NULL AND gender != "" GROUP BY gender');
$stmt->execute();
$stats['gender'] = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['gender'][$row['gender']] = $row['count'];
}

// Education breakdown
$stmt = $pdo->prepare('SELECT highest_education, COUNT(*) as count FROM users WHERE highest_education IS NOT NULL AND highest_education != "" GROUP BY highest_education ORDER BY count DESC');
$stmt->execute();
$stats['education'] = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['education'][$row['highest_education']] = $row['count'];
}

// Location/Duty Station breakdown
$stmt = $pdo->prepare('SELECT duty_station, COUNT(*) as count FROM users WHERE duty_station IS NOT NULL AND duty_station != "" GROUP BY duty_station ORDER BY count DESC');
$stmt->execute();
$stats['locations'] = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['locations'][$row['duty_station']] = $row['count'];
}

// Remote availability
$stmt = $pdo->prepare('SELECT availability_remote, COUNT(*) as count FROM users GROUP BY availability_remote');
$stmt->execute();
$remote_available = 0;
$remote_not_available = 0;
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($row['availability_remote']) {
        $remote_available = $row['count'];
    } else {
        $remote_not_available = $row['count'];
    }
}
$stats['remote'] = [
    'available' => $remote_available,
    'not_available' => $remote_not_available
];

// Users by Role
$stmt = $pdo->prepare('SELECT r.name, COUNT(u.id) as count FROM users u LEFT JOIN roles r ON u.role_id = r.id GROUP BY r.id, r.name ORDER BY count DESC');
$stmt->execute();
$stats['roles'] = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['roles'][$row['name']] = $row['count'];
}

// Current Location (not duty station)
$stmt = $pdo->prepare('SELECT current_location, COUNT(*) as count FROM users WHERE current_location IS NOT NULL AND current_location != "" GROUP BY current_location ORDER BY count DESC');
$stmt->execute();
$stats['current_locations'] = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['current_locations'][$row['current_location']] = $row['count'];
}

// Skills Statistics
// Total projects
$projects_total = (int)$pdo->query('SELECT COUNT(*) FROM projects')->fetchColumn();
$stats['projects'] = $projects_total;

// Total languages (user records)
$languages_total = (int)$pdo->query('SELECT COUNT(*) FROM user_languages')->fetchColumn();
$stats['languages'] = $languages_total;

// Total software expertise records
$software_total = (int)$pdo->query('SELECT COUNT(*) FROM user_software_expertise')->fetchColumn();
$stats['software_expertise'] = $software_total;

// Top languages
$stmt = $pdo->prepare('SELECT l.name, COUNT(*) as count FROM user_languages ul JOIN languages l ON ul.language_id = l.id GROUP BY ul.language_id, l.name ORDER BY count DESC LIMIT 5');
$stmt->execute();
$stats['top_languages'] = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['top_languages'][$row['name']] = $row['count'];
}

// Top software
$stmt = $pdo->prepare('SELECT se.name, COUNT(*) as count FROM user_software_expertise usp JOIN software_expertise se ON usp.software_expertise_id = se.id GROUP BY usp.software_expertise_id, se.name ORDER BY count DESC LIMIT 5');
$stmt->execute();
$stats['top_software'] = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['top_software'][$row['name']] = $row['count'];
}

// Most Skilled Staff (by total skill count)
$stmt = $pdo->prepare('SELECT u.id, u.full_names, u.index_number, (
    COALESCE((SELECT COUNT(*) FROM projects WHERE user_id = u.id), 0) +
    COALESCE((SELECT COUNT(*) FROM user_languages WHERE user_id = u.id), 0) +
    COALESCE((SELECT COUNT(*) FROM user_software_expertise WHERE user_id = u.id), 0)
) as total_skills
FROM users u
WHERE (
    COALESCE((SELECT COUNT(*) FROM projects WHERE user_id = u.id), 0) +
    COALESCE((SELECT COUNT(*) FROM user_languages WHERE user_id = u.id), 0) +
    COALESCE((SELECT COUNT(*) FROM user_software_expertise WHERE user_id = u.id), 0)
) > 0
ORDER BY total_skills DESC LIMIT 10');
$stmt->execute();
$stats['most_skilled'] = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['most_skilled'][] = $row;
}

// Proficiency breakdown for software
$stmt = $pdo->prepare('SELECT proficiency, COUNT(*) as count FROM user_software_expertise GROUP BY proficiency ORDER BY FIELD(proficiency, "Beginner", "Intermediate", "Advanced", "Expert")');
$stmt->execute();
$stats['proficiency_breakdown'] = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['proficiency_breakdown'][$row['proficiency']] = $row['count'];
}

// Recent projects (last 5)
$stmt = $pdo->prepare('SELECT p.id, p.project_name, p.start_date, p.end_date, u.full_names, u.index_number FROM projects p JOIN users u ON p.user_id = u.id ORDER BY p.created_at DESC LIMIT 5');
$stmt->execute();
$stats['recent_projects'] = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $stats['recent_projects'][] = $row;
}

?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<div class="row mb-4">
    <div class="col-md-12">
        <h1>Dashboard</h1>
        <p class="text-muted">System Overview and Statistics</p>
    </div>
</div>

    <!-- Key Metrics Row -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card card-hover">
                <div class="card-body text-center">
                    <h5 class="card-title text-muted">Total Users</h5>
                    <h2 class="text-primary"><?php echo $stats['total_users']; ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card card-hover">
                <div class="card-body text-center">
                    <h5 class="card-title text-muted">Remote Available</h5>
                    <h2 class="text-success"><?php echo $stats['remote']['available']; ?></h2>
                    <small class="text-muted"><?php echo round(($stats['remote']['available'] / $stats['total_users']) * 100, 1); ?>% of staff</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card card-hover">
                <div class="card-body text-center">
                    <h5 class="card-title text-muted">Total Locations</h5>
                    <h2 class="text-info"><?php echo count($stats['locations']); ?></h2>
                    <small class="text-muted">Duty Stations</small>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card card-hover">
                <div class="card-body text-center">
                    <h5 class="card-title text-muted">Education Levels</h5>
                    <h2 class="text-warning"><?php echo count($stats['education']); ?></h2>
                    <small class="text-muted">Categories</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Skills Metrics Row -->
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card card-hover">
                <div class="card-body text-center">
                    <i class="bi bi-briefcase-fill text-primary" style="font-size:24px;"></i>
                    <h5 class="card-title text-muted mt-2">Total Projects</h5>
                    <h2 class="text-primary"><?php echo $stats['projects']; ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card card-hover">
                <div class="card-body text-center">
                    <i class="bi bi-globe text-success" style="font-size:24px;"></i>
                    <h5 class="card-title text-muted mt-2">Languages Added</h5>
                    <h2 class="text-success"><?php echo $stats['languages']; ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card card-hover">
                <div class="card-body text-center">
                    <i class="bi bi-laptop-fill text-info" style="font-size:24px;"></i>
                    <h5 class="card-title text-muted mt-2">Software Skills</h5>
                    <h2 class="text-info"><?php echo $stats['software_expertise']; ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-6 col-lg-3 mb-3">
            <div class="card card-hover">
                <div class="card-body text-center">
                    <i class="bi bi-star-fill text-warning" style="font-size:24px;"></i>
                    <h5 class="card-title text-muted mt-2">Staff w/ Skills</h5>
                    <h2 class="text-warning"><?php echo (int)$pdo->query('SELECT COUNT(DISTINCT user_id) FROM (SELECT user_id FROM projects UNION SELECT user_id FROM user_languages UNION SELECT user_id FROM user_software_expertise) as t')->fetchColumn(); ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Gender Breakdown -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Gender Distribution</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['gender'])): ?>
                        <table class="table table-sm mb-0">
                            <tbody>
                                <?php foreach ($stats['gender'] as $gender => $count): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($gender); ?></td>
                                        <td class="text-end">
                                            <span class="badge bg-primary"><?php echo $count; ?></span>
                                        </td>
                                        <td class="text-end text-muted" style="font-size: 0.9em;">
                                            <?php echo round(($count / $stats['total_users']) * 100, 1); ?>%
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted mb-0">No data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Users by Role -->
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Users by Role</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['roles'])): ?>
                        <table class="table table-sm mb-0">
                            <tbody>
                                <?php foreach ($stats['roles'] as $role => $count): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($role); ?></td>
                                        <td class="text-end">
                                            <span class="badge bg-success"><?php echo $count; ?></span>
                                        </td>
                                        <td class="text-end text-muted" style="font-size: 0.9em;">
                                            <?php echo round(($count / $stats['total_users']) * 100, 1); ?>%
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted mb-0">No data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Education Breakdown -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Education Levels</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['education'])): ?>
                        <table class="table table-sm mb-0">
                            <tbody>
                                <?php foreach ($stats['education'] as $education => $count): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($education); ?></td>
                                        <td class="text-end">
                                            <span class="badge bg-info"><?php echo $count; ?></span>
                                        </td>
                                        <td class="text-end text-muted" style="font-size: 0.9em;">
                                            <?php echo round(($count / $stats['total_users']) * 100, 1); ?>%
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted mb-0">No data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Duty Stations -->
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Duty Stations</h5>
                </div>
                <div class="card-body" style="max-height: 350px; overflow-y: auto;">
                    <?php if (!empty($stats['locations'])): ?>
                        <table class="table table-sm mb-0">
                            <tbody>
                                <?php foreach ($stats['locations'] as $location => $count): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($location); ?></td>
                                        <td class="text-end">
                                            <span class="badge bg-warning"><?php echo $count; ?></span>
                                        </td>
                                        <td class="text-end text-muted" style="font-size: 0.9em;">
                                            <?php echo round(($count / $stats['total_users']) * 100, 1); ?>%
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted mb-0">No data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Current Locations -->
    <div class="row mb-4">
        <div class="col-md-12 mb-3">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Current Locations</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['current_locations'])): ?>
                        <div class="row">
                            <?php foreach ($stats['current_locations'] as $location => $count): ?>
                                <div class="col-md-6 col-lg-4 mb-2">
                                    <div class="d-flex justify-content-between align-items-center p-2 bg-light rounded">
                                        <span><?php echo htmlspecialchars($location); ?></span>
                                        <span class="badge bg-secondary"><?php echo $count; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No data available</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Languages & Software -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-globe"></i> Top Languages</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['top_languages'])): ?>
                        <table class="table table-sm mb-0">
                            <tbody>
                                <?php $lang_idx = 0; foreach ($stats['top_languages'] as $lang => $count): ?>
                                    <tr class="<?php echo $lang_idx >= 6 ? 'list-item-hidden' : ''; ?>" data-list-id="dashboard-languages-list">
                                        <td><?php echo htmlspecialchars($lang); ?></td>
                                        <td class="text-end">
                                            <span class="badge bg-info"><?php echo $count; ?> staff</span>
                                        </td>
                                    </tr>
                                <?php $lang_idx++; endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (count($stats['top_languages']) > 6): ?>
                            <div class="see-more-divider"></div>
                            <button class="btn btn-sm btn-link see-more-btn w-100" data-toggle-list="dashboard-languages-list" data-expanded="false">
                                <i class="bi bi-chevron-down"></i> See More
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">No languages added yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-laptop-fill"></i> Most Used Software</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['top_software'])): ?>
                        <table class="table table-sm mb-0">
                            <tbody>
                                <?php $soft_idx = 0; foreach ($stats['top_software'] as $soft => $count): ?>
                                    <tr class="<?php echo $soft_idx >= 6 ? 'list-item-hidden' : ''; ?>" data-list-id="dashboard-software-list">
                                        <td><?php echo htmlspecialchars($soft); ?></td>
                                        <td class="text-end">
                                            <span class="badge bg-success"><?php echo $count; ?> staff</span>
                                        </td>
                                    </tr>
                                <?php $soft_idx++; endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (count($stats['top_software']) > 6): ?>
                            <div class="see-more-divider"></div>
                            <button class="btn btn-sm btn-link see-more-btn w-100" data-toggle-list="dashboard-software-list" data-expanded="false">
                                <i class="bi bi-chevron-down"></i> See More
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">No software skills added yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Most Skilled Staff & Proficiency Breakdown -->
    <div class="row mb-4">
        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-star-fill"></i> Most Skilled Staff</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['most_skilled'])): ?>
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th class="text-center">Skills</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $staff_idx = 0; foreach ($stats['most_skilled'] as $staff): ?>
                                    <tr class="<?php echo $staff_idx >= 6 ? 'list-item-hidden' : ''; ?>" data-list-id="dashboard-skilled-staff-list">
                                        <td>
                                            <strong><?php echo htmlspecialchars($staff['full_names']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($staff['index_number']); ?></small>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-warning"><?php echo $staff['total_skills']; ?></span>
                                        </td>
                                    </tr>
                                <?php $staff_idx++; endforeach; ?>
                            </tbody>
                        </table>
                        <?php if (count($stats['most_skilled']) > 6): ?>
                            <div class="see-more-divider"></div>
                            <button class="btn btn-sm btn-link see-more-btn w-100" data-toggle-list="dashboard-skilled-staff-list" data-expanded="false">
                                <i class="bi bi-chevron-down"></i> See More
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted mb-0">No staff with skills yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="bi bi-graph-up"></i> Skills Proficiency Breakdown</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['proficiency_breakdown'])): ?>
                        <table class="table table-sm mb-0">
                            <tbody>
                                <?php 
                                $colors = [
                                    'Beginner' => 'danger',
                                    'Intermediate' => 'warning',
                                    'Advanced' => 'info',
                                    'Expert' => 'success'
                                ];
                                foreach ($stats['proficiency_breakdown'] as $level => $count): 
                                    $color = $colors[$level] ?? 'secondary';
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($level); ?></td>
                                        <td class="text-end">
                                            <span class="badge bg-<?php echo $color; ?>"><?php echo $count; ?> entries</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p class="text-muted mb-0">No proficiency data</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Projects -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-briefcase"></i> Recent Projects</h5>
                        <a href="<?php echo BASE_URL; ?>/pages/manage_users.php" class="btn btn-sm btn-outline-primary">View All Staff</a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (!empty($stats['recent_projects'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Project Name</th>
                                        <th>Staff Member</th>
                                        <th>Start Date</th>
                                        <th>End Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($stats['recent_projects'] as $proj): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($proj['project_name']); ?></strong>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($proj['full_names']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($proj['index_number']); ?></small>
                                            </td>
                                            <td><?php echo $proj['start_date'] ? date('M d, Y', strtotime($proj['start_date'])) : 'N/A'; ?></td>
                                            <td><?php echo $proj['end_date'] ? date('M d, Y', strtotime($proj['end_date'])) : 'In Progress'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No projects added yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="<?php echo BASE_URL; ?>/pages/manage_users.php" class="btn btn-primary">Manage Users</a>
                    <a href="<?php echo BASE_URL; ?>/pages/add_user.php" class="btn btn-success">Add New User</a>
                    <?php if ($_SESSION['user']['role_name'] === 'Admin'): ?>
                        <a href="<?php echo BASE_URL; ?>/pages/manage_roles.php" class="btn btn-info">Manage Roles</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .card-hover {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .card-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
