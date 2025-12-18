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
  case 'list_departments':
    try {
      $query = "SELECT department_id AS id, department_name AS name FROM departments ORDER BY department_name ASC";
      $result = $mysqli->query($query);
      if (!$result) {
        throw new Exception('Query failed: ' . $mysqli->error);
      }
      $departments = $result->fetch_all(MYSQLI_ASSOC);
      $result->free();

      ob_end_clean();
      echo json_encode(['success' => true, 'data' => $departments]);
    } catch (Exception $e) {
      error_log("List Departments Error: " . $e->getMessage());
      ob_end_clean();
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'Failed to fetch departments: ' . $e->getMessage()]);
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
?>