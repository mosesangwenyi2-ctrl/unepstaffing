<?php
// pages/add_user.php - Admin and HR can add users
require_once __DIR__ . '/../includes/middleware.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();
require_role(['Admin','HR']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';

$pdo = getPDO();
$errors = [];
$fieldErrors = [];
// fetch education levels and duty stations for selects
$educations = $pdo->query('SELECT name FROM education_levels ORDER BY name ASC')->fetchAll(PDO::FETCH_COLUMN);
$locations = $pdo->query('SELECT name FROM duty_stations ORDER BY name ASC')->fetchAll(PDO::FETCH_COLUMN);
// fetch managed current location suggestions
$currentLocations = [];
try {
    $currentLocations = $pdo->query('SELECT name FROM current_locations ORDER BY name ASC')->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    // table may not exist yet
}
// fetch staff role id (used when HR creates users)
$staffRoleStmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'Staff' LIMIT 1");
$staffRoleStmt->execute();
$staffRoleId = (int)$staffRoleStmt->fetchColumn();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) {
        $errors[] = 'Invalid CSRF token.';
    }

    $full_names = trim($_POST['full_names'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    // determine role_id: Admins may select role; HR can only create Staff
    $current = current_user();
    $isHR = isset($current['role_name']) && strtolower($current['role_name']) === 'hr';
    $role_id = $isHR ? $staffRoleId : (int)($_POST['role_id'] ?? 0);

    // field-level required checks
    if ($full_names === '') { $fieldErrors['full_names'] = 'Full names are required.'; }
    if ($email === '') { $fieldErrors['email'] = 'Email is required.'; }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $fieldErrors['email'] = 'Invalid email.'; }
    if ($password === '') { $fieldErrors['password'] = 'Password is required.'; }
    if ($confirm_password === '') { $fieldErrors['confirm_password'] = 'Confirm password is required.'; }
    if (!$isHR && $role_id <= 0) { $fieldErrors['role_id'] = 'Role is required.'; }
    if (empty($currentLocations)) {
        if (trim($_POST['current_location_other'] ?? '') === '') { $fieldErrors['current_location'] = 'Current location is required.'; }
    } else {
        $sel = $_POST['current_location_select'] ?? '';
        if ($sel === '') { $fieldErrors['current_location'] = 'Current location is required.'; }
        elseif ($sel === '__other__' && trim($_POST['current_location_other'] ?? '') === '') { $fieldErrors['current_location'] = 'Please enter current location.'; }
    }
    if (trim($_POST['highest_education'] ?? '') === '') { $fieldErrors['highest_education'] = 'Highest education is required.'; }
    if (trim($_POST['duty_station'] ?? '') === '') { $fieldErrors['duty_station'] = 'Duty station is required.'; }
    if (trim($_POST['gender'] ?? '') === '') { $fieldErrors['gender'] = 'Gender is required.'; }

    if (empty($errors) && empty($fieldErrors)) {
        // ensure email is unique
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $fieldErrors['email'] = 'Email already exists.';
        } else {
            // generate index number based on role - reuse smallest freed numeric suffix for prefix
            $getPrefixStmt = $pdo->prepare('SELECT name FROM roles WHERE id = ? LIMIT 1');
            $getPrefixStmt->execute([$role_id]);
            $roleName = $getPrefixStmt->fetchColumn();
            $prefix = 'USR';
            if ($roleName) {
                $r = strtolower($roleName);
                if ($r === 'admin') $prefix = 'ADM';
                elseif ($r === 'hr') $prefix = 'HR';
                elseif ($r === 'staff') $prefix = 'STF';
                else $prefix = strtoupper(substr($r,0,3));
            }

            // find smallest unused integer suffix for this prefix
            $usedStmt = $pdo->prepare('SELECT index_number FROM users WHERE index_number LIKE ?');
            $usedStmt->execute([$prefix . '%']);
            $rows = $usedStmt->fetchAll(PDO::FETCH_COLUMN);
            $used = [];
            foreach ($rows as $r) {
                if (preg_match('/(\d+)$/', $r, $m)) { $used[intval($m[1])] = true; }
            }
            $num = 1;
            while (isset($used[$num])) { $num++; }
            $index_number = sprintf('%s%03d', $prefix, $num);

            // password strength: min 8 chars, 1 upper, 1 lower, 1 number, 1 special
            if ($password !== $confirm_password) {
                $fieldErrors['confirm_password'] = 'Password and confirm password do not match.';
            }
            if (!preg_match('/(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[\W_]).{8,}/', $password)) {
                $fieldErrors['password'] = 'Password must be at least 8 characters and include upper, lower, number and special character.';
            }

            if (!empty($fieldErrors)) {
                // stop before insert
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare('INSERT INTO users (index_number, full_names, email, password, role_id, gender, current_location, highest_education, duty_station, availability_remote, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,NOW())');
                $stmt->execute([
                    $index_number,
                    $full_names,
                    $email,
                    $hash,
                    $role_id,
                    trim($_POST['gender'] ?? ''),
                    // current_location: accept select value or manual other
                    (isset($_POST['current_location_select']) && $_POST['current_location_select'] === '__other__') ? trim($_POST['current_location_other'] ?? '') : trim($_POST['current_location_select'] ?? ''),
                    trim($_POST['highest_education'] ?? ''),
                    trim($_POST['duty_station'] ?? ''),
                    isset($_POST['availability_remote']) ? 1 : 0
                ]);
                header('Location: ' . BASE_URL . '/pages/manage_users.php');
                exit();
            }
        }
    }
}
// Get roles for select
$roles = $pdo->query('SELECT id,name FROM roles ORDER BY name ASC')->fetchAll();
?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <h3>Add User</h3>
        <?php if ($errors): foreach ($errors as $e): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($e); ?></div>
        <?php endforeach; endif; ?>

        <form method="post" action="">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
            <!-- Index number is auto-generated and not editable -->
            <div class="mb-3">
                <label class="form-label">Full Names</label>
                <input class="form-control" name="full_names" required value="<?php echo htmlspecialchars($_POST['full_names'] ?? ''); ?>">
                <?php if (!empty($fieldErrors['full_names'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['full_names']); ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input class="form-control" type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                <?php if (!empty($fieldErrors['email'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['email']); ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <input class="form-control" type="password" name="password" required>
                <?php if (!empty($fieldErrors['password'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['password']); ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input class="form-control" type="password" name="confirm_password" required>
                <div id="confirm-inline-error" class="text-danger small"></div>
                <?php if (!empty($fieldErrors['confirm_password'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['confirm_password']); ?></div><?php endif; ?>
            </div>
            <div class="mb-2">
                <small>Password requirements:</small>
                <ul id="pw-rules" class="small ms-3">
                    <li id="rule-length" class="text-muted">At least 8 characters</li>
                    <li id="rule-upper" class="text-muted">At least one uppercase letter</li>
                    <li id="rule-lower" class="text-muted">At least one lowercase letter</li>
                    <li id="rule-number" class="text-muted">At least one number</li>
                    <li id="rule-special" class="text-muted">At least one special character</li>
                </ul>
                <div id="pw-inline-error" class="text-danger small"></div>
            </div>
            <?php // Only show role selection to Admins; HR will create users with Staff role ?>
            <?php if (isset($current) && isset($current['role_name']) && strtolower($current['role_name']) === 'admin'): ?>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <select name="role_id" class="form-select" required>
                    <option value="">Select role</option>
                    <?php foreach ($roles as $r): ?>
                        <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if (!empty($fieldErrors['role_id'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['role_id']); ?></div><?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="mb-3">
                <label class="form-label">Gender</label>
                <select name="gender" class="form-select" required>
                    <option value="">Select gender</option>
                    <option value="Male">Male</option>
                    <option value="Female">Female</option>
                    <option value="Other">Other</option>
                </select>
                <?php if (!empty($fieldErrors['gender'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['gender']); ?></div><?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Current Location <small class="text-muted">(e.g. "Working remotely from Kisii" or "On mission in Somalia")</small></label>
                <?php if (!empty($currentLocations)): ?>
                    <select name="current_location_select" id="current_location_select" class="form-select" required>
                        <option value="">Select current location or choose Other</option>
                        <?php foreach ($currentLocations as $cl): ?>
                            <option value="<?php echo htmlspecialchars($cl); ?>" <?php echo (isset($_POST['current_location_select']) && $_POST['current_location_select'] === $cl) ? 'selected' : ''; ?>><?php echo htmlspecialchars($cl); ?></option>
                        <?php endforeach; ?>
                        <option value="__other__">Other (enter manually)</option>
                    </select>
                    <input type="text" name="current_location_other" id="current_location_other" class="form-control mt-2 d-none" placeholder="e.g. Working remotely from Kisii" value="<?php echo htmlspecialchars($_POST['current_location_other'] ?? ''); ?>">
                    <?php if (!empty($fieldErrors['current_location'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['current_location']); ?></div><?php endif; ?>
                <?php else: ?>
                    <input class="form-control" name="current_location_other" placeholder="e.g. Working remotely from Kisii" required value="<?php echo htmlspecialchars($_POST['current_location_other'] ?? ''); ?>">
                    <?php if (!empty($fieldErrors['current_location'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['current_location']); ?></div><?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Highest Education</label>
                <?php if (count($educations) > 0): ?>
                    <select name="highest_education" class="form-select" required>
                        <option value="">Select education</option>
                        <?php foreach ($educations as $e): ?>
                            <option value="<?php echo htmlspecialchars($e); ?>" <?php echo (isset($_POST['highest_education']) && $_POST['highest_education'] === $e) ? 'selected' : ''; ?>><?php echo htmlspecialchars($e); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($fieldErrors['highest_education'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['highest_education']); ?></div><?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning">No education levels defined. Admin must <a href="<?php echo BASE_URL; ?>/pages/manage_education.php">add education levels</a> before creating users.</div>
                <?php endif; ?>
            </div>
            <div class="mb-3">
                <label class="form-label">Duty Station</label>
                <?php if (count($locations) > 0): ?>
                    <select name="duty_station" class="form-select" required>
                        <option value="">Select duty station</option>
                        <?php foreach ($locations as $l): ?>
                            <option value="<?php echo htmlspecialchars($l); ?>" <?php echo (isset($_POST['duty_station']) && $_POST['duty_station'] === $l) ? 'selected' : ''; ?>><?php echo htmlspecialchars($l); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($fieldErrors['duty_station'])): ?><div class="text-danger small"><?php echo htmlspecialchars($fieldErrors['duty_station']); ?></div><?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning">No duty stations defined. Admin must <a href="<?php echo BASE_URL; ?>/pages/manage_locations.php">add duty stations</a> before creating users.</div>
                <?php endif; ?>
            </div>
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="availability_remote" id="availability_remote">
                <label class="form-check-label" for="availability_remote">Available for remote work</label>
            </div>
            <!-- Confirm Password moved earlier to follow Password input -->
            <script>
                (function(){
                    var sel = document.getElementById('current_location_select');
                    var other = document.getElementById('current_location_other');
                    if (sel && other) {
                        sel.addEventListener('change', function(){
                            if (sel.value === '__other__') { other.classList.remove('d-none'); other.required = true; }
                            else { other.classList.add('d-none'); other.required = false; }
                        });
                    }
                })();
                // Password strength client-side checks
                (function(){
                    var form = document.querySelector('form');
                    var pw = form.querySelector('input[name="password"]');
                    var cpw = form.querySelector('input[name="confirm_password"]');
                    var rules = {
                        length: document.getElementById('rule-length'),
                        upper: document.getElementById('rule-upper'),
                        lower: document.getElementById('rule-lower'),
                        number: document.getElementById('rule-number'),
                        special: document.getElementById('rule-special')
                    };
                    var pwError = document.getElementById('pw-inline-error');
                    var cpwError = document.getElementById('confirm-inline-error');

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

                    if (pw) pw.addEventListener('input', function(){ validate(pw.value); pwError.textContent = ''; });
                    if (cpw) cpw.addEventListener('input', function(){ cpwError.textContent = ''; });

                    if (form) form.addEventListener('submit', function(e){
                        var p = pw.value || '';
                        var c = cpw.value || '';
                        if (!validate(p)) {
                            e.preventDefault(); pwError.textContent = 'Password does not meet the requirements.'; pw.focus(); return false;
                        }
                        if (p !== c) {
                            e.preventDefault(); cpwError.textContent = 'Passwords do not match.'; cpw.focus(); return false;
                        }
                    });
                })();
            </script>
            <button class="btn btn-primary">Create User</button>
            <a class="btn btn-secondary" href="<?php echo BASE_URL; ?>/pages/manage_users.php">Cancel</a>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
