<?php
// Database configuration (procedural)
// Update these values for your XAMPP environment if needed
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'staff_skills_portal');
define('DB_USER', 'root');
define('DB_PASS', '');
// Base URL relative to webroot (change if you put the project in a different folder)
define('BASE_URL', '/unep');

/**
 * Return a PDO instance (singleton-like stored in global)
 * Uses PDO prepared statements exclusively in app
 */
function getPDO()
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // In production, don't echo errors; log them and show a generic message
            die('Database connection failed: ' . $e->getMessage());
        }
    }
    return $pdo;
}

// Start session globally for includes that expect session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// CSRF token helper
function generate_csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
