<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $token = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($token)) { $errors[] = 'Invalid request.'; }
    if ($email === '' || $password === '') { $errors[] = 'Email and password required.'; }
    if (empty($errors)) {
        if (login_user($email, $password)) {
            $redirect = $_SESSION['redirect_after_login'] ?? BASE_URL . '/index.php';
            unset($_SESSION['redirect_after_login']);
            header('Location: ' . $redirect);
            exit();
        } else {
            $errors[] = 'Invalid credentials.';
        }
    }
}

if (session_status() === PHP_SESSION_NONE) session_start();
$csrf = generate_csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login - Staff Skills Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo BASE_URL; ?>/assets/css/styles.css" rel="stylesheet">
</head>
<body>
<div class="login-container">
    <div class="login-card bg-white rounded shadow">
        <div class="card-body p-5">
            <h2 class="text-center mb-4">Staff Skills Portal</h2>
            <p class="text-center text-muted mb-4">Sign in to your account</p>

            <?php if ($errors): ?>
                <?php foreach ($errors as $e): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($e); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input class="form-control form-control-lg" id="email" name="email" type="email" placeholder="admin@example.com" required autofocus>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input class="form-control form-control-lg" id="password" name="password" type="password" placeholder="Enter your password" required>
                    <small class="text-muted d-block mt-2">Demo: Use password <strong>User1234</strong> for all accounts</small>
                </div>
                
                <button class="btn btn-primary btn-lg w-100" type="submit">Sign In</button>
            </form>

            <hr class="my-4">
            
            <div class="alert alert-info small">
                <strong>Demo Credentials:</strong><br>
                Admin: admin@example.com<br>
                HR: hr@example.com<br>
                Staff: alice@example.com
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>