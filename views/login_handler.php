<?php
session_start();
require_once 'conn.php';

// Define base URL for easy deployment (change this for Hostinger)
define('BASE_URL', 'http://localhost:8000'); // Change to 'https://yourdomain.com' on Hostinger

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

$stmt = $mysqli->prepare("SELECT id, first_name, last_name, role, password_hash, is_active FROM users WHERE email = ?");
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
$_SESSION['user_id'] = $user['id'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['last_name'] = $user['last_name'];
$_SESSION['role'] = $user['role'];

// Redirect based on role (all users go to dashboard, but access is controlled by auth.php)
$redirect = 'pages/dashboard.php'; // Relative to root

echo json_encode([
    'status' => 'success',
    'redirect' => $redirect,
    'message' => 'Login successful.'
]);
exit;
?>