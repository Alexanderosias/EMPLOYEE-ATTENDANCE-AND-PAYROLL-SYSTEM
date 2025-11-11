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
                       d.name AS department_name, u.role, u.is_active, u.created_at
                FROM users u
                LEFT JOIN departments d ON u.department_id = d.id
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
      $role = $_POST['role'] ?? 'admin';
      $password = $_POST['password'] ?? '';
      $isActive = isset($_POST['isActive']) ? 1 : 0;

      if (!$firstName || !$lastName || !$email || !$password) {
        throw new Exception('Required fields missing');
      }

      // Hash password
      $passwordHash = password_hash($password, PASSWORD_DEFAULT);

      $stmt = $mysqli->prepare("INSERT INTO users (first_name, last_name, email, phone_number, address, department_id, role, password_hash, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $stmt->bind_param("sssssisss", $firstName, $lastName, $email, $phone, $address, $departmentId, $role, $passwordHash, $isActive);
      $stmt->execute();
      $stmt->close();

      ob_end_clean();
      echo json_encode(['success' => true, 'message' => 'User added']);
    } catch (Exception $e) {
      error_log("Add User Error: " . $e->getMessage());
      ob_end_clean();
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

  case 'get_user':
    try {
      $id = $_GET['id'] ?? null;
      if (!$id || !is_numeric($id)) {
        throw new Exception('Invalid user ID');
      }

      $stmt = $mysqli->prepare("SELECT * FROM users WHERE id = ?");
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
      $role = $_POST['role'] ?? 'admin';
      $isActive = isset($_POST['isActive']) ? (int)$_POST['isActive'] : 0;

      if ($departmentId !== null) {
        $departmentId = (int)$departmentId;
        $checkStmt = $mysqli->prepare("SELECT id FROM departments WHERE id = ?");
        $checkStmt->bind_param("i", $departmentId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        if ($checkResult->num_rows === 0) $departmentId = null;
        $checkStmt->close();
      }

      // bind_param: s = string, i = integer
      $stmt = $mysqli->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone_number=?, address=?, department_id=?, role=?, is_active=? WHERE id=?");
      $stmt->bind_param("sssssissi", $firstName, $lastName, $email, $phone, $address, $departmentId, $role, $isActive, $id);
      $stmt->execute();
      $stmt->close();

      echo json_encode(['success' => true, 'message' => 'User updated']);
    } catch (Exception $e) {
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

  case 'delete_user':
    try {
      $id = $_GET['id'] ?? null;
      if (!$id || !is_numeric($id)) {
        throw new Exception('Invalid user ID');
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
