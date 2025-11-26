<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors as HTML
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log'); // Log errors to a file
require_once 'auth.php'; // Ensure authentication
require_once 'conn.php'; // Database connection
$db = conn(); // Call the function
$mysqli = $db['mysqli']; // Extract mysqli
header('Content-Type: application/json');
// Suppress any HTML output
ob_start();
// Fix: Check POST for action if it's a POST request, otherwise GET
$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
} else {
  $action = $_GET['action'] ?? '';
}

try {
  switch ($action) {
    case 'leave_balances':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];

      // Fetch leave balances from employees table using user_id
      $stmt = $mysqli->prepare("SELECT annual_paid_leave_days, annual_unpaid_leave_days, annual_sick_leave_days FROM employees WHERE user_id = ?");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $employee = $result->fetch_assoc();
      $stmt->close();

      if (!$employee) {
        throw new Exception('Employee not found');
      }

      echo json_encode([
        'success' => true,
        'data' => [
          'paid' => (int)$employee['annual_paid_leave_days'],
          'unpaid' => (int)$employee['annual_unpaid_leave_days'],
          'sick' => (int)$employee['annual_sick_leave_days']
        ]
      ]);
      break;

    case 'leave_requests':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];

      // First, get the employee ID from user_id
      $stmt = $mysqli->prepare("SELECT id FROM employees WHERE user_id = ?");
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $emp = $result->fetch_assoc();
      $stmt->close();

      if (!$emp) {
        throw new Exception('Employee not found');
      }
      $employeeId = $emp['id'];

      // Fetch leave requests using employee_id, and alias leave_type as type
      $stmt = $mysqli->prepare("SELECT id, leave_type AS type, start_date, end_date, days, reason, status FROM leave_requests WHERE employee_id = ? ORDER BY submitted_at DESC");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param('i', $employeeId);
      $stmt->execute();
      $result = $stmt->get_result();
      $requests = $result->fetch_all(MYSQLI_ASSOC);
      $stmt->close();

      echo json_encode(['success' => true, 'data' => $requests]);
      break;

    case 'request_leave':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];

      // Get employee ID
      $stmt = $mysqli->prepare("SELECT id FROM employees WHERE user_id = ?");
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $emp = $result->fetch_assoc();
      $stmt->close();

      if (!$emp) {
        throw new Exception('Employee not found');
      }
      $employeeId = $emp['id'];

      $type = $_POST['leave-type'] ?? '';
      $startDate = $_POST['leave-start'] ?? '';
      $endDate = $_POST['leave-end'] ?? '';
      $reason = $_POST['leave-reason'] ?? '';

      if (!$type || !$startDate || !$endDate || !$reason) {
        throw new Exception('All fields are required');
      }

      // Calculate days (simple difference)
      $start = new DateTime($startDate);
      $end = new DateTime($endDate);
      $days = $start->diff($end)->days + 1; // Inclusive

      // Insert request
      $stmt = $mysqli->prepare("INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, days, reason, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param('isssis', $employeeId, $type, $startDate, $endDate, $days, $reason);
      if (!$stmt->execute()) {
        throw new Exception('Failed to insert leave request: ' . $stmt->error);
      }
      $stmt->close();
      echo json_encode(['success' => true, 'message' => 'Leave request submitted successfully']);
      break;

    case 'view_leave_request':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];
      $requestId = (int)$_GET['id'];

      error_log("Debug view_leave_request: userId=$userId, requestId=$requestId");

      // Check if the request exists and belongs to the user via proper joins
      $stmt = $mysqli->prepare("
    SELECT lr.id 
    FROM leave_requests lr 
    JOIN employees e ON lr.employee_id = e.id 
    JOIN users u ON e.user_id = u.id 
    WHERE lr.id = ? AND u.id = ?
  ");
      $stmt->bind_param('ii', $requestId, $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $reqCheck = $result->fetch_assoc();
      $stmt->close();

      if (!$reqCheck) {
        error_log("Debug: No matching request found for userId=$userId and requestId=$requestId");
        throw new Exception('Request not found or access denied');
      }

      // Fetch full details
      $stmt = $mysqli->prepare("
    SELECT lr.*, u.first_name, u.last_name, u.email, u.avatar_path 
    FROM leave_requests lr 
    JOIN employees e ON lr.employee_id = e.id 
    JOIN users u ON e.user_id = u.id 
    WHERE lr.id = ? AND u.id = ?
  ");
      $stmt->bind_param('ii', $requestId, $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $request = $result->fetch_assoc();
      $stmt->close();

      if (!$request) {
        error_log("Debug: Failed to fetch details for requestId=$requestId");
        throw new Exception('Unexpected error fetching request details');
      }

      // Handle avatar fallback
      $request['avatar_path'] = $request['avatar_path'] ?: 'img/user.jpg';

      echo json_encode(['success' => true, 'data' => $request]);
      break;

    case 'cancel_leave_request':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];
      $requestId = (int)$_POST['id']; // JS sends via POST body
      // Get employee ID
      $stmt = $mysqli->prepare("SELECT id FROM employees WHERE user_id = ?");
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $emp = $result->fetch_assoc();
      $stmt->close();
      if (!$emp) throw new Exception('Employee not found');
      $employeeId = $emp['id'];
      // Check if request is Pending and belongs to user
      $stmt = $mysqli->prepare("SELECT status FROM leave_requests WHERE id = ? AND employee_id = ?");
      $stmt->bind_param('ii', $requestId, $employeeId);
      $stmt->execute();
      $result = $stmt->get_result();
      $req = $result->fetch_assoc();
      $stmt->close();
      if (!$req) throw new Exception('Request not found or access denied');
      if ($req['status'] !== 'Pending') throw new Exception('Only pending requests can be canceled');
      // Delete the request
      $stmt = $mysqli->prepare("DELETE FROM leave_requests WHERE id = ?");
      $stmt->bind_param('i', $requestId);
      if (!$stmt->execute()) {
        throw new Exception('Failed to cancel request');
      }
      $stmt->close();
      echo json_encode(['success' => true, 'message' => 'Request canceled successfully']);
      break;

    default:
      throw new Exception('Invalid action');
  }
} catch (Exception $e) {
  // Output JSON error
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Close connection if set
if (isset($mysqli)) {
  $mysqli->close();
}
// Flush output buffer to send JSON
ob_end_flush();
