<?php
// pages/edit_user.php - Admin/HR or own profile
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_once __DIR__ . '/../config/database.php';

$pdo = getPDO();
$id = isset($_GET['id']) ? (int)$_GET['id'] : ($_SESSION['user']['id'] ?? 0);
if ($id <= 0) { header('Location: ' . BASE_URL . '/pages/manage_users.php'); exit(); }

// Check permission: Admin/HR can edit any, Staff only own
if ($_SESSION['user']['role_name'] === 'Staff' && $_SESSION['user']['id'] != $id) {
    header('Location: ' . BASE_URL . '/pages/unauthorized.php'); exit();
}

// Fetch user
$stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) { echo '<div class="alert alert-danger">User not found</div>'; require_once __DIR__ . '/../includes/footer.php'; exit(); }

$errors = [];
$fieldErrors = [];
// whether the current session user is editing their own profile
$isSelf = (isset($_SESSION['user']['id']) && $_SESSION['user']['id'] == $id);
// if the current user is Staff editing their own profile, limit certain fields
$isStaffSelf = (isset($_SESSION['user']['role_name']) && $_SESSION['user']['role_name'] === 'Staff' && $isSelf);
// helper to compute smallest unused index suffix for a given prefix
function compute_next_index_for_prefix($pdo, $prefix) {
    $stmt = $pdo->prepare('SELECT index_number FROM users WHERE index_number LIKE ?');
    $stmt->execute([$prefix . '%']);
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $used = [];
    foreach ($rows as $r) {
        if (preg_match('/(\d+)$/', $r, $m)) { $used[intval($m[1])] = true; }
    }
    $i = 1; while (isset($used[$i])) $i++;
    return sprintf('%s%03d', $prefix, $i);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) { $errors[] = 'Invalid CSRF token.'; }

    $full_names = trim($_POST['full_names'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role_id = isset($_POST['role_id']) ? (int)$_POST['role_id'] : $user['role_id'];
    // prevent Staff editing their own name/email/role
    if ($isStaffSelf) {
        $full_names = $user['full_names'];
        $email = $user['email'];
        $role_id = $user['role_id'];
    }
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!$isStaffSelf) {
        if ($full_names === '') { $fieldErrors['full_names'] = 'Full names are required.'; }
        if ($email === '') { $fieldErrors['email'] = 'Email is required.'; }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $fieldErrors['email'] = 'Invalid email.'; }
    }

    if (empty($errors)) {
        // Check duplicate email (only if allowed to change email)
        if (!$isStaffSelf) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
            $stmt->execute([$email, $id]);
            if ($stmt->fetch()) {
                $fieldErrors['email'] = 'Email already in use.';
            } else {
                // continue
            }
        } else {
            // staff editing own profile: skip email uniqueness check
        }
        // proceed only if no fieldErrors were added above (duplicate email included)
        if (!empty($fieldErrors)) {
            // skip further processing
        } else {
            // Handle optional password change
            $isSelf = ($_SESSION['user']['id'] == $id);
            if ($new_password !== '' || $confirm_password !== '') {
                if ($new_password !== $confirm_password) {
                    $fieldErrors['confirm_password'] = 'New password and confirm password do not match.';
                } else {
                    if ($isSelf) {
                        if (!password_verify($current_password, $user['password'])) {
                            $fieldErrors['current_password'] = 'Current password is incorrect.';
                        }
                    }
                    if (!preg_match('/(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}/', $new_password)) {
                        $fieldErrors['new_password'] = 'Password must be at least 8 characters and include upper, lower, number and special character.';
                    }
                }
            }

            if (empty($errors) && empty($fieldErrors)) {
                // determine current_location from select/other (unless staff editing own profile)
                if ($isStaffSelf) {
                    $current_location_val = $user['current_location'];
                } else {
                    $current_location_val = '';
                    if (isset($_POST['current_location_select'])) {
                        if ($_POST['current_location_select'] === '__other__') {
                            $current_location_val = trim($_POST['current_location_other'] ?? '');
                        } else {
                            $current_location_val = trim($_POST['current_location_select']);
                        }
                    } else {
                        $current_location_val = trim($_POST['current_location'] ?? '');
                    }
                }

                // if role changed, compute a new index for new role and free the old one implicitly
                $new_index_number = $user['index_number'];
                if ($role_id != $user['role_id']) {
                    $roleStmt = $pdo->prepare('SELECT name FROM roles WHERE id = ? LIMIT 1');
                    $roleStmt->execute([$role_id]);
                    $newRoleName = $roleStmt->fetchColumn();
                    $prefix = 'USR';
                    if ($newRoleName) {
                        $r = strtolower($newRoleName);
                        if ($r === 'admin') $prefix = 'ADM';
                        elseif ($r === 'hr') $prefix = 'HR';
                        elseif ($r === 'staff') $prefix = 'STF';
                        else $prefix = strtoupper(substr($r,0,3));
                    }
                    $new_index_number = compute_next_index_for_prefix($pdo, $prefix);
                }

                if ($new_password !== '') {
                    $pwHash = password_hash($new_password, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare('UPDATE users SET index_number=?, full_names=?, email=?, role_id=?, gender=?, current_location=?, highest_education=?, duty_station=?, availability_remote=?, password=? WHERE id=?');
                    $stmt->execute([
                        $new_index_number,
                        $full_names,
                        $email,
                        $role_id,
                        trim($_POST['gender'] ?? ''),
                        $current_location_val,
                        trim($_POST['highest_education'] ?? ''),
                        ($isStaffSelf ? $user['duty_station'] : trim($_POST['duty_station'] ?? '')),
                        isset($_POST['availability_remote']) ? 1 : 0,
                        $pwHash,
                        $id
                    ]);
                } else {
                    $stmt = $pdo->prepare('UPDATE users SET index_number=?, full_names=?, email=?, role_id=?, gender=?, current_location=?, highest_education=?, duty_station=?, availability_remote=? WHERE id=?');
                    $stmt->execute([
                        $new_index_number,
                        $full_names,
                        $email,
                        $role_id,
                        trim($_POST['gender'] ?? ''),
                        $current_location_val,
                        trim($_POST['highest_education'] ?? ''),
                        ($isStaffSelf ? $user['duty_station'] : trim($_POST['duty_station'] ?? '')),
                        isset($_POST['availability_remote']) ? 1 : 0,
                        $id
                    ]);
                }
                header('Location: ' . BASE_URL . '/pages/profile.php?id=' . $id);
                exit();
            }
        }
    }
}

// Get roles for admin
$roles = $pdo->query('SELECT id,name FROM roles ORDER BY name ASC')->fetchAll();
// fetch education levels and duty stations for selects
$educations = $pdo->query('SELECT name FROM education_levels ORDER BY name ASC')->fetchAll(PDO::FETCH_COLUMN);
$locations = $pdo->query('SELECT name FROM duty_stations ORDER BY name ASC')->fetchAll(PDO::FETCH_COLUMN);
// fetch managed current location suggestions
$currentLocations = [];
try {
    $currentLocations = $pdo->query('SELECT name FROM current_locations ORDER BY name ASC')->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // ignore if table missing
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <h3>Edit User</h3>
        <?php if ($errors): foreach ($errors as $e): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; endif; ?>

        <form method="post" action="?id=<?php echo $id; ?>">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <div class="mb-3">
                <label class="form-label">Full Names</label>
                <input class="form-control" name="full_names" value="<?php echo htmlspecialchars($_POST['full_names'] ?? $user['full_names']); ?>" <?php echo ($isStaffSelf ? 'readonly' : 'required'); ?>>
                <?php if ($isStaffSelf): ?><div class="form-text small text-muted">Contact HR/Admin to change your name.</div><?php endif; ?>
                <?php if (!empty($fieldErrors['full_names'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['full_names']); ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input class="form-control" type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? $user['email']); ?>" <?php echo ($isStaffSelf ? 'readonly' : 'required'); ?> >
                <?php if ($isStaffSelf): ?><div class="form-text small text-muted">Contact HR/Admin to change your email.</div><?php endif; ?>
                <?php if (!empty($fieldErrors['email'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['email']); ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select">
                    <option value="">-- Select Gender --</option>
                    <option value="Male" <?php echo (isset($_POST['gender']) ? ($_POST['gender']==='Male') : ($user['gender']==='Male')) ? 'selected' : ''; ?>>Male</option>
                    <option value="Female" <?php echo (isset($_POST['gender']) ? ($_POST['gender']==='Female') : ($user['gender']==='Female')) ? 'selected' : ''; ?>>Female</option>
                    <option value="Other" <?php echo (isset($_POST['gender']) ? ($_POST['gender']==='Other') : ($user['gender']==='Other')) ? 'selected' : ''; ?>>Other</option>
                </select>
                <?php if (!empty($fieldErrors['gender'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['gender']); ?></div><?php endif; ?>
            </div>
            <?php if ($_SESSION['user']['role_name'] === 'Admin'): ?>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-select">
                    <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r['id']; ?>" <?php echo (isset($_POST['role_id']) ? ($_POST['role_id']==$r['id']) : ($r['id']==$user['role_id'])) ? 'selected': ''; ?>><?php echo htmlspecialchars($r['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($fieldErrors['role_id'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['role_id']); ?></div><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label">Current Location <small class="text-muted">(e.g. "Working remotely from Kisii" or "On mission in Somalia")</small></label>
                <?php if ($isStaffSelf): ?>
                    <input class="form-control" type="text" readonly value="<?php echo htmlspecialchars($_POST['current_location_other'] ?? $user['current_location']); ?>">
                    <div class="form-text small text-muted">Contact HR/Admin to change your current location.</div>
                <?php else: ?>
                    <?php if (!empty($currentLocations)): ?>
                        <select name="current_location_select" id="current_location_select" class="form-select">
                            <option value="">Select current location or choose Other</option>
                            <?php foreach ($currentLocations as $cl): ?>
                                <option value="<?php echo htmlspecialchars($cl); ?>" <?php echo (isset($_POST['current_location_select']) ? ($_POST['current_location_select'] === $cl) : ($user['current_location'] === $cl)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cl); ?></option>
                            <?php endforeach; ?>
                            <option value="__other__" <?php echo (isset($_POST['current_location_select']) && $_POST['current_location_select'] === '__other__') ? 'selected' : ''; ?>>Other (enter manually)</option>
                        </select>
                        <input type="text" name="current_location_other" id="current_location_other" class="form-control mt-2 <?php echo (isset($_POST['current_location_select']) ? ($_POST['current_location_select'] !== '__other__' && $_POST['current_location_select'] !== '') : (in_array($user['current_location'], $currentLocations))) ? 'd-none' : ''; ?>" placeholder="e.g. Working remotely from Kisii" value="<?php echo htmlspecialchars($_POST['current_location_other'] ?? $user['current_location']); ?>">
                        <?php if (!empty($fieldErrors['current_location'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['current_location']); ?></div><?php endif; ?>
                    <?php else: ?>
                        <input class="form-control" name="current_location_other" value="<?php echo htmlspecialchars($_POST['current_location_other'] ?? $user['current_location']); ?>" placeholder="e.g. Working remotely from Kisii" required>
                        <?php if (!empty($fieldErrors['current_location'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['current_location']); ?></div><?php endif; ?>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Highest Education</label>
                <?php if (count($educations) > 0): ?>
                    <select name="highest_education" class="form-select">
                        <option value="">-- Select --</option>
                        <?php foreach ($educations as $e): ?>
                            <option value="<?php echo htmlspecialchars($e); ?>" <?php echo (isset($_POST['highest_education']) ? ($_POST['highest_education'] === $e) : ($user['highest_education'] === $e)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($e); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($fieldErrors['highest_education'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['highest_education']); ?></div><?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning">No education levels defined. Ask admin to <a href="<?php echo BASE_URL; ?>/pages/manage_education.php">add them</a>.</div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Duty Station</label>
                <?php if ($isStaffSelf): ?>
                    <input class="form-control" type="text" readonly value="<?php echo htmlspecialchars($user['duty_station']); ?>">
                    <div class="form-text small text-muted">Contact HR/Admin to change your duty station.</div>
                <?php else: ?>
                    <?php if (count($locations) > 0): ?>
                        <select name="duty_station" class="form-select">
                            <option value="">-- Select --</option>
                            <?php foreach ($locations as $l): ?>
                                <option value="<?php echo htmlspecialchars($l); ?>" <?php echo (isset($_POST['duty_station']) ? ($_POST['duty_station'] === $l) : ($user['duty_station'] === $l)) ? 'selected' : ''; ?>><?php echo htmlspecialchars($l); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (!empty($fieldErrors['duty_station'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['duty_station']); ?></div><?php endif; ?>
                    <?php else: ?>
                        <div class="alert alert-warning">No duty stations defined. Ask admin to <a href="<?php echo BASE_URL; ?>/pages/manage_locations.php">add them</a>.</div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="availability_remote" id="availability_remote" <?php echo $user['availability_remote'] ? 'checked' : ''; ?> >
                <label class="form-check-label" for="availability_remote">Available for remote work</label>
            </div>
            <hr>
            <h5>Change Password</h5>
            <div class="mb-3">
                <label class="form-label">Current Password (required if changing your own password)</label>
                <input class="form-control" type="password" name="current_password">
                <?php if (!empty($fieldErrors['current_password'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['current_password']); ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input class="form-control" type="password" name="new_password">
                <?php if (!empty($fieldErrors['new_password'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['new_password']); ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm New Password</label>
                <input class="form-control" type="password" name="confirm_password">
                <div id="confirm-inline-error-edit" class="text-danger small"></div>
                <?php if (!empty($fieldErrors['confirm_password'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['confirm_password']); ?></div><?php endif; ?>
            </div>
            <div class="mb-2">
                <small>Password requirements:</small>
                <ul id="pw-rules-edit" class="small ms-3">
                    <li id="erule-length" class="text-muted">At least 8 characters</li>
                    <li id="erule-upper" class="text-muted">At least one uppercase letter</li>
                    <li id="erule-lower" class="text-muted">At least one lowercase letter</li>
                    <li id="erule-number" class="text-muted">At least one number</li>
                    <li id="erule-special" class="text-muted">At least one special character</li>
                </ul>
                <div id="pw-inline-error-edit" class="text-danger small"></div>
            </div>
            <script>
                (function(){
                    var sel = document.getElementById('current_location_select');
                    var other = document.getElementById('current_location_other');
                    if (sel && other) {
                        sel.addEventListener('change', function(){
                            if (sel.value === '__other__') { other.classList.remove('d-none'); }
                            else { other.classList.add('d-none'); }
                        });
                    }
                })();
                // Password strength checks for edit form
                (function(){
                    var form = document.querySelector('form');
                    var pw = form.querySelector('input[name="new_password"]');
                    var cpw = form.querySelector('input[name="confirm_password"]');
                    var rules = {
                        length: document.getElementById('erule-length'),
                        upper: document.getElementById('erule-upper'),
                        lower: document.getElementById('erule-lower'),
                        number: document.getElementById('erule-number'),
                        special: document.getElementById('erule-special')
                    };
                    var pwError = document.getElementById('pw-inline-error-edit');

                    function validate(p){
                        var okLen = p.length >= 8;
                        var okU = /[A-Z]/.test(p);
                        var okL = /[a-z]/.test(p);
                        var okN = /\d/.test(p);
                        var okS = /[^A-Za-z0-9]/.test(p);
                        rules.length.className = okLen ? 'text-success' : 'text-muted';
                        rules.upper.className = okU ? 'text-success' : 'text-muted';
                        rules.lower.className = okL ? 'text-success' : 'text-muted';
                        rules.number.className = okN ? 'text-success' : 'text-muted';
                        rules.special.className = okS ? 'text-success' : 'text-muted';
                        return okLen && okU && okL && okN && okS;
                    }

                    var confirmError = document.getElementById('confirm-inline-error-edit');
                    if (pw) pw.addEventListener('input', function(){ validate(pw.value); pwError.textContent = ''; if (confirmError) confirmError.textContent = ''; });
                    if (cpw) cpw.addEventListener('input', function(){ if (confirmError) confirmError.textContent = ''; pwError.textContent = ''; });

                    if (form) form.addEventListener('submit', function(e){
                        var p = pw.value || '';
                        var c = cpw.value || '';
                        if (p !== '' || c !== '') {
                            if (!validate(p)) {
                                e.preventDefault(); pwError.textContent = 'New password does not meet the requirements.'; pw.focus(); return false;
                            }
                            if (p !== c) { e.preventDefault(); if (confirmError) confirmError.textContent = 'New passwords do not match.'; else pwError.textContent = 'New passwords do not match.'; cpw.focus(); return false; }
                        }
                    });
                })();
            </script>
            <button class="btn btn-primary">Save Changes</button>
            <a class="btn btn-secondary" href="<?php echo BASE_URL; ?>/pages/profile.php?id=<?php echo $id; ?>">Cancel</a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>