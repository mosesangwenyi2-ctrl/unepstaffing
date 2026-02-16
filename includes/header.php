<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

$csrf = generate_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo isset($title) ? htmlspecialchars($title) : 'Staff Skills Portal'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="<?php echo BASE_URL; ?>/assets/css/styles.css" rel="stylesheet">
</head>
<body>
<div class="d-flex" style="min-height: 100vh;">
    <!-- Sidebar -->
    <?php if (is_logged_in()): ?>
    <aside class="sidebar bg-dark text-white p-4">
        <div class="sidebar-header mb-4 pb-3 border-bottom border-secondary">
            <h5 class="mb-1">
                <?php echo htmlspecialchars($_SESSION['user']['full_names']); ?> 
                <span class="text-white">|</span> 
                <?php echo htmlspecialchars($_SESSION['user']['role_name']); ?>
            </h5>
        </div>

        <nav class="sidebar-nav">
            <ul class="nav flex-column gap-2">
                <li class="nav-item">
                    <a class="nav-link text-white-50 hover-link" href="<?php echo BASE_URL; ?>/index.php">
                        <i class="bi bi-house-fill"></i> Dashboard
                    </a>
                </li>

                <?php if ($_SESSION['user']['role_name'] === 'Admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white-50 hover-link" href="<?php echo BASE_URL; ?>/pages/manage_users.php">
                            <i class="bi bi-people-fill"></i> Manage Users
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50 hover-link" href="<?php echo BASE_URL; ?>/pages/manage_roles.php">
                            <i class="bi bi-shield-lock-fill"></i> Manage Roles
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50 hover-link" href="<?php echo BASE_URL; ?>/pages/manage_education.php">
                            <i class="bi bi-mortarboard-fill"></i> Manage Education Levels
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50 hover-link" href="<?php echo BASE_URL; ?>/pages/manage_locations.php">
                            <i class="bi bi-geo-alt-fill"></i> Manage Duty Stations
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50 hover-link" href="<?php echo BASE_URL; ?>/pages/manage_languages.php">
                            <i class="bi bi-globe"></i> Manage Languages
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50 hover-link" href="<?php echo BASE_URL; ?>/pages/manage_software_expertise.php">
                            <i class="bi bi-laptop-fill"></i> Manage Software/Tools
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (in_array($_SESSION['user']['role_name'], ['Admin','HR'])): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white-50 hover-link" href="<?php echo BASE_URL; ?>/pages/manage_current_locations.php">
                            <i class="bi bi-geo-alt-fill"></i> Manage Current Locations
                        </a>
                    </li>
                <?php endif; ?>

                <?php if (in_array($_SESSION['user']['role_name'], ['Admin', 'HR'])): ?>
                    <li class="nav-item">
                        <a class="nav-link text-white-50 hover-link" href="<?php echo BASE_URL; ?>/pages/manage_users.php">
                            <i class="bi bi-person-lines-fill"></i> Staff List
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link text-white-50 hover-link" href="<?php echo BASE_URL; ?>/pages/add_user.php">
                            <i class="bi bi-person-plus-fill"></i> Add User
                        </a>
                    </li>
                <?php endif; ?>

                <li class="nav-item border-top border-secondary mt-3 pt-3">
                    <span class="text-white-50 small">My Skills</span>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white-50 hover-link" href="<?php echo BASE_URL; ?>/pages/user_projects.php">
                        <i class="bi bi-briefcase-fill"></i> My Projects
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white-50 hover-link" href="<?php echo BASE_URL; ?>/pages/user_languages.php">
                        <i class="bi bi-globe"></i> My Languages
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-white-50 hover-link" href="<?php echo BASE_URL; ?>/pages/user_software_expertise.php">
                        <i class="bi bi-laptop-fill"></i> My Software & Tools
                    </a>
                </li>

                <li class="nav-item border-top border-secondary mt-3 pt-3">
                    <a class="nav-link text-danger hover-link" href="<?php echo BASE_URL; ?>/logout.php">
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </a>
                </li>
            </ul>
        </nav>
    </aside>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="flex-grow-1">
        <!-- Top Bar -->
        <header class="bg-light border-bottom p-3">
            <div class="container-fluid d-flex justify-content-between align-items-center">
                <h1 class="h5 mb-0">Staff Skills Portal</h1>
                <?php if (!is_logged_in()): ?>
                    <a class="btn btn-sm btn-primary" href="<?php echo BASE_URL; ?>/login.php">Login</a>
                <?php else: ?>
                    <div class="dropdown">
                        <a class="nav-link dropdown-toggle text-dark" href="#" id="userMenu" data-bs-toggle="dropdown" aria-expanded="false" style="text-decoration:none;">
                            <?php echo htmlspecialchars($_SESSION['user']['full_names']); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/pages/profile.php?id=<?php echo $_SESSION['user']['id']; ?>">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/logout.php">Logout</a></li>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </header>

        <!-- Content Area -->
        <div class="p-4">
