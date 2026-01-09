<?php
session_start();
require_once 'conn.php';

// Define base URL for easy deployment (Apache/XAMPP at http://localhost/eaaps)
define('BASE_URL', 'http://localhost'); // Change to 'https://yourdomain.com' on Hostinger

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . BASE_URL);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!$email || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
    exit;
}

$db = conn();
$mysqli = $db['mysqli'];

$stmt = $mysqli->prepare("SELECT id, first_name, last_name, roles, password_hash, is_active FROM users_employee WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

if (!password_verify($password, $user['password_hash'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
    exit;
}

if ($user['is_active'] != 1) {
    echo json_encode(['status' => 'error', 'message' => 'Account is inactive.']);
    exit;
}

// Set session data
session_regenerate_id(true);
$_SESSION['user_id'] = $user['id'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['last_name'] = $user['last_name'];

// Decode roles
$roles = json_decode($user['roles'] ?? '[]', true);
if (!is_array($roles)) {
    $roles = [];
}
$_SESSION['roles'] = $roles; // Store all roles in session

// Determine redirect or role selection
$hasEmployee = in_array('employee', $roles);
$hasAdmin = in_array('admin', $roles) || in_array('head_admin', $roles);

if ($hasEmployee && $hasAdmin) {
    // Multi-role: Ask user to select
    echo json_encode([
        'status' => 'success',
        'action' => 'select_role',
        'message' => 'Login successful. Please select a role.'
    ]);
} elseif ($hasEmployee) {
    // Employee only
    $_SESSION['role'] = 'employee'; // Set current active role
    echo json_encode([
        'status' => 'success',
        'redirect' => 'employee-pages/employee_dashboard.php',
        'message' => 'Login successful.'
    ]);
} else {
    // Admin/Head Admin only (or no role, default to dashboard)
    // Default to the first role found or admin
    $_SESSION['role'] = $roles[0] ?? 'admin';
    echo json_encode([
        'status' => 'success',
        'redirect' => 'pages/dashboard.php',
        'message' => 'Login successful.'
    ]);
}
exit;
