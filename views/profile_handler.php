<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require_once 'conn.php';
require_once 'auth.php'; // Ensure session is started

define('BASE_PATH', '/eaaps'); // XAMPP Apache base path

$db = null;
$mysqli = null;
try {
  $db = conn();
  $mysqli = $db['mysqli'];
  if (!$mysqli || $mysqli->connect_error) {
    throw new Exception('MySQL connection failed.');
  }
} catch (Exception $e) {
  ob_end_clean();
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
  exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
  ob_end_clean();
  http_response_code(401);
  echo json_encode(['success' => false, 'message' => 'Unauthorized']);
  exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
  case 'get_profile':
    try {
      // Use systemintegration schema: users_employee + departments + employees
      $stmt = $mysqli->prepare("
        SELECT 
          u.id,
          u.first_name,
          u.last_name,
          u.email,
          u.phone_number,
          u.address,
          u.roles,
          u.created_at,
          d.department_name AS department_name,
          d.department_id AS department_id,
          CASE WHEN JSON_CONTAINS(u.roles, '\"employee\"') THEN e.avatar_path ELSE u.avatar_path END AS avatar_path
        FROM users_employee u
        LEFT JOIN departments d ON u.department_id = d.department_id
        LEFT JOIN employees e ON e.user_id = u.id
        WHERE u.id = ?
      ");
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $user = $result->fetch_assoc();
      $stmt->close();

      if (!$user) {
        throw new Exception('User not found');
      }

      ob_end_clean();
      echo json_encode(['success' => true, 'data' => $user]);
    } catch (Exception $e) {
      ob_end_clean();
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

  case 'update_profile':
    try {
      $firstName = $_POST['firstName'] ?? '';
      $lastName = $_POST['lastName'] ?? '';
      $email = $_POST['email'] ?? '';
      $phone = $_POST['phone'] ?? '';
      $address = $_POST['address'] ?? '';
      $departmentId = $_POST['departmentId'] ?? null;

      if ($departmentId !== null && $departmentId !== '') {
        $departmentId = (int)$departmentId;
        $checkStmt = $mysqli->prepare('SELECT department_id FROM departments WHERE department_id = ?');
        $checkStmt->bind_param('i', $departmentId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows === 0) {
          // If invalid, store NULL so we don't break FK
          $departmentId = null;
        }
        $checkStmt->close();
      } else {
        $departmentId = null;
      }

      // Handle avatar upload (users_employee.avatar_path)
      $currentAvatar = null;
      $stmt = $mysqli->prepare('SELECT avatar_path FROM users_employee WHERE id = ?');
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $currentAvatar = $row['avatar_path'];
      }
      $stmt->close();

      $uploadDir = realpath(__DIR__ . '/../uploads/avatars/');
      if (!$uploadDir) {
        $uploadDir = __DIR__ . '/../uploads/avatars/';
      }
      if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
      }

      $avatarPath = $currentAvatar;
      if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        if ($currentAvatar) {
          $oldPath = realpath(__DIR__ . '/../' . $currentAvatar);
          if ($oldPath && file_exists($oldPath)) {
            unlink($oldPath);
          }
        }
        $fileExt = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $userId . '_' . time() . '.' . $fileExt;
        $targetPath = $uploadDir . '/' . $filename;
        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
          $avatarPath = 'uploads/avatars/' . $filename;
        } else {
          throw new Exception('Failed to upload avatar.');
        }
      }

      // Update users_employee record (admin profile)
      $stmt = $mysqli->prepare('UPDATE users_employee SET first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, department_id = ?, avatar_path = ? WHERE id = ?');
      $stmt->bind_param('sssssisi', $firstName, $lastName, $email, $phone, $address, $departmentId, $avatarPath, $userId);
      $stmt->execute();
      $stmt->close();

      ob_end_clean();
      echo json_encode(['success' => true, 'message' => 'Profile updated']);
    } catch (Exception $e) {
      ob_end_clean();
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

  case 'change_password':
    try {
      $currentPassword = $_POST['currentPassword'] ?? '';
      $newPassword = $_POST['newPassword'] ?? '';

      if (!$currentPassword || !$newPassword) {
        throw new Exception('All fields required');
      }

      // Verify current password against users_employee
      $stmt = $mysqli->prepare('SELECT password_hash FROM users_employee WHERE id = ?');
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $user = $result->fetch_assoc();
      $stmt->close();

      if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
        throw new Exception('Current password is incorrect');
      }

      // Update password in users_employee
      $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
      $stmt = $mysqli->prepare('UPDATE users_employee SET password_hash = ? WHERE id = ?');
      $stmt->bind_param('si', $newHash, $userId);
      $stmt->execute();
      $stmt->close();

      ob_end_clean();
      echo json_encode(['success' => true, 'message' => 'Password changed']);
    } catch (Exception $e) {
      ob_end_clean();
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

  default:
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    break;
}

if ($mysqli) {
  $mysqli->close();
}
ob_end_flush();
