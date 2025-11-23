<?php
ob_start();
ini_set('display_errors', 1);
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

define('BASE_PATH', ''); // Change to '' for localhost:8000, or '/newpath' for Hostinger

$db = null;
$mysqli = null;
try {
  $db = conn();
  $mysqli = $db['mysqli'];
  if (!$mysqli || $mysqli->connect_error) {
    throw new Exception('MySQL connection failed: ' . ($mysqli ? $mysqli->connect_error : 'No connection'));
  }
} catch (Exception $e) {
  ob_end_clean();
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
  exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
  case 'list_users':
    try {
      $query = "
      SELECT u.id, u.first_name, u.last_name, u.email, u.phone_number, u.address,
             d.name AS department_name, 
             JSON_UNQUOTE(JSON_EXTRACT(u.roles, '$[0]')) AS role,  -- Extract first role from JSON array
             u.is_active, u.created_at,
             CASE WHEN JSON_CONTAINS(u.roles, '\"employee\"') THEN e.avatar_path ELSE u.avatar_path END AS avatar_path
      FROM users u
      LEFT JOIN departments d ON u.department_id = d.id
      LEFT JOIN employees e ON u.id = e.user_id
      ORDER BY u.created_at DESC
    ";
      $result = $mysqli->query($query);
      if (!$result) {
        throw new Exception('Query failed: ' . $mysqli->error);
      }
      $users = $result->fetch_all(MYSQLI_ASSOC);
      $result->free();
      ob_end_clean();
      echo json_encode(['success' => true, 'data' => $users]);
    } catch (Exception $e) {
      error_log("List Users Error: " . $e->getMessage());
      ob_end_clean();
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'Failed to fetch users: ' . $e->getMessage()]);
    }
    break;

  case 'add_user':
    try {
      $firstName = $_POST['firstName'] ?? '';
      $lastName = $_POST['lastName'] ?? '';
      $email = $_POST['email'] ?? '';
      $phone = $_POST['phone'] ?? '';
      $address = $_POST['address'] ?? '';
      $departmentId = $_POST['departmentId'] ?? null;
      $roles = json_decode($_POST['roles'] ?? '["admin"]', true); // Decode roles array
      $password = $_POST['password'] ?? '';
      $isActive = isset($_POST['isActive']) ? 1 : 0;

      if (!$firstName || !$lastName || !$email || !$password || empty($roles)) {
        throw new Exception('Required fields missing');
      }

      // Check if email exists in employees
      $employeeCheck = $mysqli->prepare("SELECT id FROM employees WHERE email = ?");
      $employeeCheck->bind_param("s", $email);
      $employeeCheck->execute();
      $empResult = $employeeCheck->get_result();
      $employeeExists = $empResult->num_rows > 0;
      $employeeId = $employeeExists ? $empResult->fetch_assoc()['id'] : null;
      $employeeCheck->close();

      // Validate roles
      // Rule 1: Admin and Head Admin are mutually exclusive
      if (in_array('admin', $roles) && in_array('head_admin', $roles)) {
        throw new Exception('User cannot be both Admin and Head Admin.');
      }

      if ($employeeExists) {
        // Linked: Force 'employee' role
        if (!in_array('employee', $roles)) {
          $roles[] = 'employee';
        }
      } else {
        // Not Linked: Disallow 'employee'.
        if (in_array('employee', $roles)) {
          throw new Exception('Cannot assign Employee role to a user without a matching employee record.');
        }
        // Must have at least one role
        if (empty($roles)) {
          throw new Exception('User must have at least one role.');
        }
      }

      // === Handle avatar upload ===
      $uploadDir = realpath(__DIR__ . '/../uploads/avatars/');
      if (!$uploadDir) {
        $uploadDir = __DIR__ . '/../uploads/avatars/';
      }

      if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
      }

      $avatarPath = null;

      if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $fileExt = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . time() . '_' . uniqid() . '.' . $fileExt;
        $targetPath = $uploadDir . '/' . $filename;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
          $avatarPath = 'uploads/avatars/' . $filename;
        } else {
          throw new Exception('Failed to upload avatar.');
        }
      }

      // Insert user with roles
      $rolesJson = json_encode($roles);
      $passwordHash = password_hash($password, PASSWORD_DEFAULT);
      $stmt = $mysqli->prepare("INSERT INTO users (first_name, last_name, email, phone_number, address, department_id, roles, password_hash, is_active, avatar_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("sssssissss", $firstName, $lastName, $email, $phone, $address, $departmentId, $rolesJson, $passwordHash, $isActive, $avatarPath);
      $stmt->execute();
      $userId = $stmt->insert_id;
      $stmt->close();

      // If employee exists, link by setting user_id in employees AND update avatar if provided
      if ($employeeExists) {
        if ($avatarPath) {
          $linkStmt = $mysqli->prepare("UPDATE employees SET user_id = ?, avatar_path = ? WHERE id = ?");
          $linkStmt->bind_param("isi", $userId, $avatarPath, $employeeId);
        } else {
          $linkStmt = $mysqli->prepare("UPDATE employees SET user_id = ? WHERE id = ?");
          $linkStmt->bind_param("ii", $userId, $employeeId);
        }
        $linkStmt->execute();
        $linkStmt->close();
      }

      ob_end_clean();
      echo json_encode(['success' => true, 'message' => 'User added']);
    } catch (Exception $e) {
      error_log("Add User Error: " . $e->getMessage());
      ob_end_clean();
      http_response_code(500);
      $message = $e->getMessage();
      if (strpos($mysqli->error, 'Duplicate entry') !== false || strpos($message, 'Duplicate entry') !== false) {
        $message = 'A user with this email already exists.';
      }
      echo json_encode(['success' => false, 'message' => $message]);
    }
    break;

  case 'get_user':
    try {
      $id = $_GET['id'] ?? null;
      if (!$id || !is_numeric($id)) {
        throw new Exception('Invalid user ID');
      }
      $stmt = $mysqli->prepare("
      SELECT u.*, JSON_UNQUOTE(JSON_EXTRACT(u.roles, '$[0]')) AS role,
             CASE WHEN JSON_CONTAINS(u.roles, '\"employee\"') THEN e.avatar_path ELSE u.avatar_path END AS avatar_path,
             e.id AS linked_employee_id
      FROM users u
      LEFT JOIN employees e ON u.id = e.user_id
      WHERE u.id = ?
    ");
      $stmt->bind_param("i", $id);
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
      error_log("Get User Error: " . $e->getMessage());
      ob_end_clean();
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

  case 'update_user':
    try {
      $id = $_GET['id'] ?? null;
      if (!$id || !is_numeric($id)) throw new Exception('Invalid user ID');

      $firstName = $_POST['firstName'] ?? '';
      $lastName = $_POST['lastName'] ?? '';
      $email = $_POST['email'] ?? '';
      $phone = $_POST['phone'] ?? '';
      $address = $_POST['address'] ?? '';
      $departmentId = $_POST['departmentId'] ?? null;
      $roles = json_decode($_POST['roles'] ?? '["admin"]', true);
      if (!is_array($roles)) $roles = ['admin']; // Safety check
      $isActive = isset($_POST['isActive']) ? (int)$_POST['isActive'] : 0;


      // Check if trying to deactivate the last head admin (including self)
      if ($isActive === 0) {
        $stmt = $mysqli->prepare("SELECT JSON_CONTAINS(roles, '\"head_admin\"') as is_head_admin FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && $user['is_head_admin']) {
          $countStmt = $mysqli->prepare("SELECT COUNT(*) as count FROM users WHERE JSON_CONTAINS(roles, '\"head_admin\"') AND is_active = 1 AND id != ?");
          $countStmt->bind_param("i", $id);
          $countStmt->execute();
          $countResult = $countStmt->get_result();
          $otherHeadAdminCount = $countResult->fetch_assoc()['count'];
          $countStmt->close();
          if ($otherHeadAdminCount === 0) {
            throw new Exception('Cannot deactivate the last active head administrator.');
          }
        }
      }

      // Check if trying to change role away from head_admin for the last head admin
      $currentRoleStmt = $mysqli->prepare("SELECT JSON_CONTAINS(roles, '\"head_admin\"') as is_head_admin FROM users WHERE id = ?");
      $currentRoleStmt->bind_param("i", $id);
      $currentRoleStmt->execute();
      $currentRoleResult = $currentRoleStmt->get_result();
      $currentUser = $currentRoleResult->fetch_assoc();
      $currentRoleStmt->close();

      if ($currentUser && $currentUser['is_head_admin'] && !in_array('head_admin', $roles)) {
        $countStmt = $mysqli->prepare("SELECT COUNT(*) as count FROM users WHERE JSON_CONTAINS(roles, '\"head_admin\"') AND is_active = 1");
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $headAdminCount = $countResult->fetch_assoc()['count'];
        $countStmt->close();
        if ($headAdminCount <= 1) {
          throw new Exception('Unable to update role: At least one head administrator must remain active.');
        }
      }

      if ($departmentId !== null) {
        $departmentId = (int)$departmentId;
        $checkStmt = $mysqli->prepare("SELECT id FROM departments WHERE id = ?");
        $checkStmt->bind_param("i", $departmentId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows === 0) $departmentId = null;
        $checkStmt->close();
      }

      $currentAvatar = null;
      $stmt = $mysqli->prepare("SELECT avatar_path FROM users WHERE id = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $result = $stmt->get_result();
      if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $currentAvatar = $row['avatar_path'];
      }
      $stmt->close();

      // === Handle avatar upload ===
      $uploadDir = realpath(__DIR__ . '/../uploads/avatars/');
      if (!$uploadDir) {
        $uploadDir = __DIR__ . '/../uploads/avatars/';
      }

      if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
      }

      $avatarPath = $currentAvatar;

      if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        // Delete old avatar if exists
        if ($currentAvatar) {
          $oldPath = realpath(__DIR__ . '/../' . $currentAvatar);
          if ($oldPath && file_exists($oldPath)) {
            unlink($oldPath);
          }
        }

        $fileExt = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $id . '_' . time() . '.' . $fileExt;
        $targetPath = $uploadDir . '/' . $filename;

        if (move_uploaded_file($_FILES['avatar']['tmp_name'], $targetPath)) {
          $avatarPath = 'uploads/avatars/' . $filename;
        } else {
          throw new Exception('Failed to upload new avatar.');
        }
      }

      // Check if user is linked to an employee
      $linkCheck = $mysqli->prepare("SELECT id FROM employees WHERE user_id = ?");
      $linkCheck->bind_param("i", $id);
      $linkCheck->execute();
      $linkResult = $linkCheck->get_result();
      $isLinked = $linkResult->num_rows > 0;
      $linkCheck->close();

      // Update user
      $rolesJson = json_encode($roles);

      if ($isLinked) {
        // Linked: Update ONLY roles, is_active. Do NOT update avatar_path.
        $stmt = $mysqli->prepare("UPDATE users SET roles=?, is_active=? WHERE id=?");
        $stmt->bind_param("sii", $rolesJson, $isActive, $id);
      } else {
        // Not Linked: Update ALL fields
        $stmt = $mysqli->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone_number=?, address=?, department_id=?, roles=?, is_active=?, avatar_path=? WHERE id=?");
        $stmt->bind_param("sssssisisi", $firstName, $lastName, $email, $phone, $address, $departmentId, $rolesJson, $isActive, $avatarPath, $id);
      }
      $stmt->execute();
      $stmt->close();

      // REMOVED: Sync update to employees table (as per user request)

      ob_end_clean();
      echo json_encode(['success' => true, 'message' => 'User updated']);
    } catch (Exception $e) {
      error_log("Update User Error: " . $e->getMessage());
      ob_end_clean();
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

  case 'delete_user':
    try {
      $id = $_GET['id'] ?? null;
      if (!$id || !is_numeric($id)) {
        throw new Exception('Invalid user ID');
      }
      $current_user_id = $_SESSION['user_id'] ?? 0;
      // Check if trying to delete the last head_admin
      $stmt = $mysqli->prepare("SELECT JSON_CONTAINS(roles, '\"head_admin\"') AS is_head_admin FROM users WHERE id = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $result = $stmt->get_result();
      $user = $result->fetch_assoc();
      $stmt->close();
      if ($user['is_head_admin']) {
        $countStmt = $mysqli->prepare("SELECT COUNT(*) as count FROM users WHERE JSON_CONTAINS(roles, '\"head_admin\"') AND is_active = 1");
        $countStmt->execute();
        $countResult = $countStmt->get_result();
        $headAdminCount = $countResult->fetch_assoc()['count'];
        $countStmt->close();
        if ($headAdminCount <= 1) {
          throw new Exception('Cannot delete the last active head administrator.');
        }
      }

      // Check if user is linked to an employee
      $linkCheck = $mysqli->prepare("SELECT id FROM employees WHERE user_id = ?");
      $linkCheck->bind_param("i", $id);
      $linkCheck->execute();
      $linkResult = $linkCheck->get_result();
      $isLinked = $linkResult->num_rows > 0;
      $linkCheck->close();

      if ($isLinked) {
        // Unlink employee: Set user_id to NULL
        $unlinkStmt = $mysqli->prepare("UPDATE employees SET user_id = NULL WHERE user_id = ?");
        $unlinkStmt->bind_param("i", $id);
        $unlinkStmt->execute();
        $unlinkStmt->close();
        // Do NOT delete avatar as it belongs to the employee
      } else {
        // Not linked: Delete avatar file if exists
        $stmt = $mysqli->prepare("SELECT avatar_path FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
          $row = $result->fetch_assoc();
          if ($row['avatar_path']) {
            $filePath = realpath(__DIR__ . '/../' . $row['avatar_path']);
            if ($filePath && file_exists($filePath)) {
              unlink($filePath);
            }
          }
        }
        $stmt->close();
      }
      $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $stmt->close();

      ob_end_clean();
      echo json_encode(['success' => true, 'message' => 'User deleted']);
    } catch (Exception $e) {
      error_log("Delete User Error: " . $e->getMessage());
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
