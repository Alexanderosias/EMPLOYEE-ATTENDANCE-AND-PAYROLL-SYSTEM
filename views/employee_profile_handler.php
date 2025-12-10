<?php
// views/employee_profile_handler.php - Backend for employee profile page
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

require_once 'auth.php';
require_once 'conn.php';

// Load Composer autoload for modern QR library (chillerlan/php-qrcode)
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
} else {
    error_log('Composer autoload not found for QR library in employee_profile_handler.php');
}

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

// QR helpers (duplicate of logic in employees.php simplified for profile updates)
function ep_generate_qr($mysqli, $employeeId)
{
    if (!class_exists(QRCode::class)) {
        error_log('QR Library not loaded  chillerlan/php-qrcode QRCode class missing (ep_generate_qr)');
        return [null, null];
    }

    // Fetch fresh employee with position
    $stmt = $mysqli->prepare("SELECT e.id, e.first_name, e.last_name, e.date_joined, jp.name AS position_name FROM employees e LEFT JOIN job_positions jp ON e.job_position_id = jp.id WHERE e.id = ?");
    $stmt->bind_param('i', $employeeId);
    $stmt->execute();
    $emp = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$emp) return [null, null];

    $id = (int)$emp['id'];
    $first = trim(preg_replace('/[|:]/', '', $emp['first_name'] ?? 'EMP'));
    if ($first === '') $first = 'EMP';
    $last  = trim(preg_replace('/[|:]/', '', $emp['last_name'] ?? ''));
    $pos = trim($emp['position_name'] ?? 'N/A');
    $joined = (!empty($emp['date_joined']) && $emp['date_joined'] !== '0000-00-00') ? $emp['date_joined'] : 'N/A';
    $qr_data = "ID:$id|First:$first|Last:$last|Position:$pos|Joined:$joined";

    $dir = '../qrcodes/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $base = $first . $last;
    if ($base === '') $base = 'EMP' . $id;
    $filename = $base . '.png';
    $counter = 1;
    while (file_exists($dir . $filename)) {
        $filename = $base . '_' . $id . '_' . $counter . '.png';
        $counter++;
    }
    $file_path = $dir . $filename;
    $web_path = 'qrcodes/' . $filename;

    // Generate PNG data using chillerlan/php-qrcode and write it to file
    $options = new QROptions([
        'outputType' => QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'   => QRCode::ECC_L,
    ]);

    $imageData = (new QRCode($options))->render($qr_data);

    if ($imageData === null || $imageData === '') {
        error_log('QR generation returned empty image data (ep_generate_qr)');
        return [null, null];
    }

    if (file_put_contents($file_path, $imageData) === false) {
        error_log('Failed to write QR image file (ep_generate_qr): ' . $file_path);
        return [null, null];
    }

    if (!file_exists($file_path)) return [null, null];
    return [$web_path, $qr_data];
}

function ep_delete_qr($mysqli, $employeeId)
{
    $stmt = $mysqli->prepare("SELECT qr_image_path FROM qr_codes WHERE employee_id = ?");
    $stmt->bind_param('i', $employeeId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();
    if ($row && !empty($row['qr_image_path'])) {
        $full = '../' . $row['qr_image_path'];
        if (file_exists($full)) @unlink($full);
    }
    $stmt = $mysqli->prepare("DELETE FROM qr_codes WHERE employee_id = ?");
    $stmt->bind_param('i', $employeeId);
    $stmt->execute();
    $stmt->close();
}

$db = conn();
$mysqli = $db['mysqli'];

header('Content-Type: application/json');
ob_start();

// Check if user is employee
$userRoles = $_SESSION['roles'] ?? [];
$hasEmployeeRole = (isset($_SESSION['role']) && $_SESSION['role'] === 'employee')
    || (is_array($userRoles) && in_array('employee', $userRoles));
if (!$hasEmployeeRole) {
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
                SELECT e.*, u.first_name, u.last_name, u.email, u.avatar_path AS user_avatar_path, u.created_at,
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

            // Normalize fields for the employee profile UI (e.g., date_of_birth)
            if (array_key_exists('date_of_birth', $employee)) {
                if (empty($employee['date_of_birth']) || $employee['date_of_birth'] === '0000-00-00') {
                    $employee['date_of_birth'] = '';
                } else {
                    $employee['date_of_birth'] = trim($employee['date_of_birth']);
                }
            }

            // Prefer employees.avatar_path for display; fallback to users.avatar_path; else default image
            $empAvatar = $employee['avatar_path'] ?? '';
            $userAvatar = $employee['user_avatar_path'] ?? '';
            if (!empty($empAvatar)) {
                $employee['avatar_path'] = (strpos($empAvatar, 'uploads/') === 0) ? ('../' . $empAvatar) : $empAvatar;
            } elseif (!empty($userAvatar)) {
                $employee['avatar_path'] = (strpos($userAvatar, 'uploads/') === 0) ? ('../' . $userAvatar) : $userAvatar;
            } else {
                $employee['avatar_path'] = '../pages/img/user.jpg';
            }

            ob_end_clean();
            echo json_encode(['success' => true, 'data' => $employee]);
            break;

        case 'update_profile':
            // Update editable fields. If QR-relevant name fields change, regenerate QR.
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('POST required');
            }

            $address = trim($_POST['address'] ?? '');
            $phoneNumber = trim($_POST['phone_number'] ?? '');
            $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : null;
            $lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : null;
            $emergencyContactName = trim($_POST['emergency_contact_name'] ?? '');
            $emergencyContactPhone = trim($_POST['emergency_contact_phone'] ?? '');
            $dateOfBirthRaw = trim($_POST['date_of_birth'] ?? '');

            if ($dateOfBirthRaw === '') {
                $dateOfBirth = null;
            } else {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfBirthRaw)) {
                    throw new Exception('Invalid Date of Birth format. Use YYYY-MM-DD.');
                }
                if (strtotime($dateOfBirthRaw) > time()) {
                    throw new Exception('Date of Birth cannot be in the future.');
                }
                $dateOfBirth = $dateOfBirthRaw;
            }

            // Validate
            if (empty($phoneNumber)) {
                throw new Exception('Phone number is required');
            }

            // Load current values for QR change detection and employee id
            $stmt = $mysqli->prepare("SELECT e.id, e.first_name, e.last_name FROM employees e WHERE e.user_id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            $empRow = $res->fetch_assoc();
            $stmt->close();
            if (!$empRow) throw new Exception('Employee record not found');
            $employeeId = (int)$empRow['id'];
            $currentFirst = $empRow['first_name'] ?? '';
            $currentLast = $empRow['last_name'] ?? '';

            $qrChanged = false;
            if ($firstName !== null && $firstName !== $currentFirst) $qrChanged = true;
            if ($lastName !== null && $lastName !== $currentLast) $qrChanged = true;

            // Update users table (names, phone, address)
            if ($firstName !== null || $lastName !== null || $address !== '' || $phoneNumber !== '') {
                $stmt = $mysqli->prepare("UPDATE users SET first_name = COALESCE(?, first_name), last_name = COALESCE(?, last_name), phone_number = ?, address = ? WHERE id = ?");
                $stmt->bind_param('ssssi', $firstName, $lastName, $phoneNumber, $address, $userId);
                if (!$stmt->execute()) throw new Exception('Failed to update user info');
                $stmt->close();
            }

            // Update employees table (mirror names, contact_number, address, emergency contacts, date_of_birth)
            $stmt = $mysqli->prepare("UPDATE employees SET first_name = COALESCE(?, first_name), last_name = COALESCE(?, last_name), address = ?, contact_number = ?, emergency_contact_name = ?, emergency_contact_phone = ?, date_of_birth = ? WHERE user_id = ?");
            $stmt->bind_param('sssssssi', $firstName, $lastName, $address, $phoneNumber, $emergencyContactName, $emergencyContactPhone, $dateOfBirth, $userId);
            if (!$stmt->execute()) throw new Exception('Failed to update profile');
            $stmt->close();

            $qrPath = null;
            if ($qrChanged) {
                // Delete old QR then generate new
                ep_delete_qr($mysqli, $employeeId);
                [$qr_path, $qr_data] = ep_generate_qr($mysqli, $employeeId);
                if ($qr_path && $qr_data) {
                    $stmt = $mysqli->prepare("INSERT INTO qr_codes (employee_id, qr_data, qr_image_path) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE qr_data = VALUES(qr_data), qr_image_path = VALUES(qr_image_path)");
                    $stmt->bind_param('iss', $employeeId, $qr_data, $qr_path);
                    $stmt->execute();
                    $stmt->close();
                    $qrPath = $qr_path;
                }
            }

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully', 'qr_regenerated' => $qrChanged, 'qr_path' => $qrPath]);
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

            // Read old avatar paths (employees and users)
            $oldEmpPath = '';
            $oldUserPath = '';
            $stmt = $mysqli->prepare("SELECT e.avatar_path AS emp_avatar, u.avatar_path AS user_avatar, e.id AS emp_id FROM employees e JOIN users u ON e.user_id = u.id WHERE e.user_id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();
            if ($row) {
                $oldEmpPath = trim($row['emp_avatar'] ?? '');
                $oldUserPath = trim($row['user_avatar'] ?? '');
            }

            // Update both tables in a transaction
            $mysqli->begin_transaction();
            try {
                // Update users
                $stmt = $mysqli->prepare("UPDATE users SET avatar_path = ? WHERE id = ?");
                $stmt->bind_param('si', $dbPath, $userId);
                if (!$stmt->execute()) throw new Exception('Failed to update user avatar');
                $stmt->close();

                // Update employees (linked by user_id)
                $stmt = $mysqli->prepare("UPDATE employees SET avatar_path = ? WHERE user_id = ?");
                $stmt->bind_param('si', $dbPath, $userId);
                if (!$stmt->execute()) throw new Exception('Failed to update employee avatar');
                $stmt->close();

                $mysqli->commit();
            } catch (Exception $txe) {
                $mysqli->rollback();
                // Clean up uploaded file on failure
                if (file_exists($targetPath)) @unlink($targetPath);
                throw $txe;
            }

            // Delete old avatar files (avoid deleting defaults or if same as new)
            $toDelete = [];
            foreach ([$oldEmpPath, $oldUserPath] as $p) {
                if (!empty($p) && strpos($p, 'uploads/avatars/') === 0 && $p !== $dbPath) {
                    $abs = __DIR__ . '/../' . $p;
                    if (file_exists($abs)) $toDelete[$abs] = true; // de-dup
                }
            }
            foreach (array_keys($toDelete) as $absPath) {
                @unlink($absPath);
            }

            ob_end_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Avatar updated successfully',
                // Return the employee avatar path for display
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
            $stmt = $mysqli->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->bind_param('i', $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();

            if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
                throw new Exception('Current password is incorrect');
            }

            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
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
