<?php
// config/bootstrap.php
// Database connection + session + authentication helpers

session_start();

// Database configuration
$db_host = 'localhost';
$db_name = 'harmony';
$db_user = 'root';
$db_pass = ''; // XAMPP default empty password

$dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (Exception $e) {
    die("DB connection failed: " . $e->getMessage());
}

/* ---------- Authentication helpers ---------- */

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_login() {
    if (!current_user()) {
        header("Location: /harmony/auth/login.php");
        exit;
    }
}

function require_roles(array $roles = []) {
    require_login();
    $user = current_user();
    
    // Check if role is stored as role_id (needs lookup) or role name (direct)
    global $pdo;
    
    if (isset($user['role_id'])) {
        // Role stored as ID - fetch role name from database
        $stmt = $pdo->prepare("SELECT name FROM roles WHERE id = ?");
        $stmt->execute([$user['role_id']]);
        $role_name = $stmt->fetchColumn();
    } else {
        // Role stored directly as name
        $role_name = $user['role'] ?? null;
    }
    
    if (!$role_name || !in_array($role_name, $roles)) {
        http_response_code(403);
        echo "<div style='padding:20px;color:red;font-weight:bold;'>Access denied.</div>";
        exit;
    }
}

/* ---------- Flash message helpers ---------- */

function flash($key, $value = null) {
    if ($value === null) {
        // Get and remove flash message
        $v = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $v;
    }
    // Set flash message
    $_SESSION['flash'][$key] = $value;
}

/* ---------- Utility helpers ---------- */

function e($s) {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}