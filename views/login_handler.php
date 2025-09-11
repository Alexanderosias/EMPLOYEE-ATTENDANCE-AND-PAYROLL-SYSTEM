<?php

header('Content-Type: application/json');

// Include your database connection file
require_once 'conn.php';

// Check for required data using $_REQUEST, which works for both GET and POST requests.
// This is a more flexible way to handle the request given the server's behavior.
if (!isset($_REQUEST['email']) || !isset($_REQUEST['password'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request.']);
    exit;
}

$email = $_REQUEST['email'];
$password = $_REQUEST['password'];

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
    $hashedPassword = hash('sha512', $password);

    // Compare the hashed submitted password with the stored hash
    if ($hashedPassword === $user['password_hash']) {
        // SUCCESS: Add the 'redirect' key to the response for the frontend to handle
        echo json_encode([
            'status' => 'success',
            'message' => 'Login successful!',
            'redirect' => '../EMPLOYEE%20ATTENDANCE%20AND%20PAYROLL%20SYSTEM/dashboard.html'
        ]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Incorrect email or password.']);
    }

} catch (Exception $e) {
    // Log the error and return a generic message to the user
    error_log("Login error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An internal server error occurred.']);
}
