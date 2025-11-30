<?php
// views/employee_profile_handler.php - Backend for employee profile page
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

require_once 'auth.php';
require_once 'conn.php';

$db = conn();
$mysqli = $db['mysqli'];

header('Content-Type: application/json');
ob_start();

// Check if user is employee
if ($_SESSION['role'] !== 'employee') {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'get_profile':
            // Fetch employee data
            $stmt = $mysqli->prepare("
                SELECT e.*, u.first_name, u.last_name, u.email, u.avatar_path, u.created_at,
                       d.name as department_name, jp.name as position_name
                FROM employees e
                JOIN users u ON e.user_id = u.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN job_positions jp ON e.job_position_id = jp.id
                WHERE e.user_id = ?
            ");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $employee = $result->fetch_assoc();
            $stmt->close();

            if (!$employee) {
                throw new Exception('Employee record not found');
            }

            // Format avatar path
            if ($employee['avatar_path'] && strpos($employee['avatar_path'], 'uploads/') === 0) {
                $employee['avatar_path'] = '../' . $employee['avatar_path'];
            } elseif (!$employee['avatar_path']) {
                $employee['avatar_path'] = '../pages/img/user.jpg';
            }

            ob_end_clean();
            echo json_encode(['success' => true, 'data' => $employee]);
            break;

        case 'update_profile':
            // Update editable fields only: address, phone_number, emergency_contact_name, emergency_contact_phone
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST required');
            }

            $address = trim($_POST['address'] ?? '');
            $phoneNumber = trim($_POST['phone_number'] ?? '');
            $emergencyContactName = trim($_POST['emergency_contact_name'] ?? '');
            $emergencyContactPhone = trim($_POST['emergency_contact_phone'] ?? '');

            // Validate
            if (empty($phoneNumber)) {
                throw new Exception('Phone number is required');
            }

            // Update employees table
            $stmt = $mysqli->prepare("
                UPDATE employees 
                SET address = ?, phone_number = ?, 
                    emergency_contact_name = ?, emergency_contact_phone = ?
                WHERE user_id = ?
            ");
            $stmt->bind_param('ssssi', $address, $phoneNumber, $emergencyContactName, $emergencyContactPhone, $userId);

            if (!$stmt->execute()) {
                throw new Exception('Failed to update profile');
            }
            $stmt->close();

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
            break;

        case 'update_avatar':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST required');
            }

            if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No file uploaded or upload error');
            }

            $file = $_FILES['avatar'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];

            if (!in_array($file['type'], $allowedTypes)) {
                throw new Exception('Invalid file type. Only JPG, PNG, and GIF allowed');
            }

            if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
                throw new Exception('File too large. Maximum size is 5MB');
            }

            // Create uploads directory if not exists
            $uploadDir = __DIR__ . '/../uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = 'avatar_' . $userId . '_' . time() . '.' . $extension;
            $targetPath = $uploadDir . $filename;
            $dbPath = 'uploads/avatars/' . $filename;

            // Move uploaded file
            if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
                throw new Exception('Failed to save uploaded file');
            }

            // Update database
            $stmt = $mysqli->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
            $stmt->bind_param('si', $dbPath, $userId);

            if (!$stmt->execute()) {
                unlink($targetPath); // Delete uploaded file if database update fails
                throw new Exception('Failed to update avatar in database');
            }
            $stmt->close();

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Avatar updated successfully',
                'avatar_path' => '../' . $dbPath
            ]);
            break;

        case 'change_password':
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST required');
            }

            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            // Validate
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new Exception('All password fields are required');
            }

            if ($newPassword !== $confirmPassword) {
                throw new Exception('New passwords do not match');
            }

            if (strlen($newPassword) < 6) {
                throw new Exception('New password must be at least 6 characters');
            }

            // Verify current password
            $stmt = $mysqli->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if (!$user || !password_verify($currentPassword, $user['password'])) {
                throw new Exception('Current password is incorrect');
            }

            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param('si', $hashedPassword, $userId);

            if (!$stmt->execute()) {
                throw new Exception('Failed to update password');
            }
            $stmt->close();

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$mysqli->close();
ob_end_flush();
