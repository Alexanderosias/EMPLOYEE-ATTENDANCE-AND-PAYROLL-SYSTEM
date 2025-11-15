<?php
session_start();
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'conn.php';

$employee_id = $_SESSION['user_id'] ?? 0; // Assume employee ID from session

$action = $_GET['action'] ?? '';

switch ($action) {
  // Existing cases...

  case 'leave_balances':
    try {
      $stmt = $mysqli->prepare("SELECT annual_paid_leave_days, annual_unpaid_leave_days, annual_sick_leave_days FROM employees WHERE id = ?");
      $stmt->bind_param('i', $employee_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $emp = $result->fetch_assoc();
      $stmt->close();

      if (!$emp) throw new Exception('Employee not found');

      echo json_encode(['success' => true, 'data' => [
        'paid' => $emp['annual_paid_leave_days'],
        'unpaid' => $emp['annual_unpaid_leave_days'],
        'sick' => $emp['annual_sick_leave_days']
      ]]);
    } catch (Exception $e) {
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

  case 'leave_requests':
    try {
      $stmt = $mysqli->prepare("SELECT leave_type, start_date, end_date, days, reason, status FROM leave_requests WHERE employee_id = ? ORDER BY submitted_at DESC");
      $stmt->bind_param('i', $employee_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $requests = $result->fetch_all(MYSQLI_ASSOC);
      $stmt->close();

      echo json_encode(['success' => true, 'data' => $requests]);
    } catch (Exception $e) {
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

  case 'request_leave':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      echo json_encode(['success' => false, 'message' => 'POST required']);
      break;
    }
    try {
      $leave_type = $_POST['leave_type'] ?? '';
      $start_date = $_POST['leave_start'] ?? '';
      $end_date = $_POST['leave_end'] ?? '';
      $reason = $_POST['leave_reason'] ?? '';

      if (!$leave_type || !$start_date || !$end_date || !$reason) {
        throw new Exception('All fields required');
      }

      $days = (strtotime($end_date) - strtotime($start_date)) / (60*60*24) + 1;

      $stmt = $mysqli->prepare("INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, days, reason) VALUES (?, ?, ?, ?, ?, ?)");
      $stmt->bind_param('isssis', $employee_id, $leave_type, $start_date, $end_date, $days, $reason);
      $stmt->execute();
      $stmt->close();

      echo json_encode(['success' => true, 'message' => 'Leave requested']);
    } catch (Exception $e) {
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

  // Other existing cases...
}
?>