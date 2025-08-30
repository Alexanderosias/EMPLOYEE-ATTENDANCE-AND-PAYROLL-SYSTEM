<?php

header('Content-Type: application/json');

// Include your database connection file
require_once 'conn.php';

// Check for POST request and form data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['email']) || !isset($_POST['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

$email = $_POST['email'];
$password = $_POST['password'];

try {
    // Prepare a query to fetch the user by email
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception($conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    // Check if user exists
    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'Incorrect email or password.']);
        exit;
    }

    // Hash the submitted password using SHA-512 for comparison
    // NOTE: This is less secure than using password_hash and password_verify
    $hashedPassword = hash('sha512', $password);

    // Compare the hashed submitted password with the stored hash
    if ($hashedPassword === $user['password_hash']) {
        echo json_encode(['status' => 'success', 'message' => 'Login successful!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Incorrect email or password.']);
    }

} catch (Exception $e) {
    // Log the error and return a generic message to the user
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An internal server error occurred.']);
}
