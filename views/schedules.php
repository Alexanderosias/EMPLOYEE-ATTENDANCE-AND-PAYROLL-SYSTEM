<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
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
  echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
  exit;
}

function mapDayToEnum($day)
{
  $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  return $days[$day] ?? 'Monday';  // Default to Monday if invalid
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
  case 'list_employees':
    try {
      $query = "
                SELECT e.employee_id AS id, e.first_name, e.last_name, jp.position_name AS position_name, d.department_name AS department_name
                FROM employees e
                LEFT JOIN job_positions jp ON e.position_id = jp.position_id
                LEFT JOIN departments d ON e.department_id = d.department_id
                ORDER BY e.last_name, e.first_name
            ";
      $result = $mysqli->query($query);
      if (!$result) {
        throw new Exception('Query failed: ' . $mysqli->error);
      }
      $employees = $result->fetch_all(MYSQLI_ASSOC);
      $result->free();

      ob_end_clean();
      echo json_encode(['success' => true, 'data' => $employees]);
    } catch (Exception $e) {
      error_log("List Employees Error: " . $e->getMessage());
      ob_end_clean();
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'Failed to fetch employees: ' . $e->getMessage()]);
    }
    break;

  case 'list_schedules':
    $employeeId = (int)($_GET['employee_id'] ?? 0);
    if (!$employeeId) {
      ob_end_clean();
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'Employee ID required']);
      break;
    }
    try {
      $query = "SELECT schedule_id AS id, employee_id, day_of_week, shift_name, start_time, end_time, is_working, break_minutes FROM employee_schedules WHERE employee_id = ? ORDER BY day_of_week, start_time";
      $stmt = $mysqli->prepare($query);
      $stmt->bind_param('i', $employeeId);
      $stmt->execute();
      $result = $stmt->get_result();
      $schedules = $result->fetch_all(MYSQLI_ASSOC);
      $stmt->close();

      // Map day_of_week back to number for JS (0=Sunday, etc.)
      $dayMap = ['Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6];
      foreach ($schedules as &$schedule) {
        $schedule['day_of_week'] = $dayMap[$schedule['day_of_week']] ?? 1;
      }

      ob_end_clean();
      echo json_encode(['success' => true, 'data' => $schedules]);
    } catch (Exception $e) {
      error_log("List Schedules Error: " . $e->getMessage());
      ob_end_clean();
      http_response_code(500);
      echo json_encode(['success' => false, 'message' => 'Failed to fetch schedules: ' . $e->getMessage()]);
    }
    break;

  case 'add_schedule':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      ob_end_clean();
      echo json_encode(['success' => false, 'message' => 'POST required']);
      break;
    }
    try {
      $employeeId = (int)($_POST['employee_id'] ?? 0);
      $dayOfWeek = mapDayToEnum((int)($_POST['day_of_week'] ?? 1));
      $shiftName = trim($_POST['shift_name'] ?? '');
      $startTime = $_POST['start_time'] ?? '';
      $endTime = $_POST['end_time'] ?? '';
      $isWorking = (int)($_POST['is_working'] ?? 1);
      $breakMinutes = (int)($_POST['break_minutes'] ?? 0);

      if (!$employeeId) {
        throw new Exception('Employee ID is required.');
      }
      if (empty($shiftName)) {
        throw new Exception('Shift/class name cannot be empty.');
      }
      if (empty($startTime) || empty($endTime)) {
        throw new Exception('Start and end times are required.');
      }
      if ($startTime >= $endTime) {
        throw new Exception('Start time must be before end time.');
      }

      // Check for duplicates (same employee, day, shift name)
      $duplicateQuery = "SELECT schedule_id FROM employee_schedules WHERE employee_id = ? AND day_of_week = ? AND shift_name = ?";
      $stmt = $mysqli->prepare($duplicateQuery);
      $stmt->bind_param('iss', $employeeId, $dayOfWeek, $shiftName);
      $stmt->execute();
      if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('A schedule with this shift name already exists for the selected employee and day.');
      }
      $stmt->close();

      // Check for overlaps
      $overlapQuery = "SELECT schedule_id FROM employee_schedules WHERE employee_id = ? AND day_of_week = ? AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?))";
      $stmt = $mysqli->prepare($overlapQuery);
      $stmt->bind_param('isssss', $employeeId, $dayOfWeek, $endTime, $startTime, $startTime, $endTime);
      $stmt->execute();
      if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('This schedule overlaps with an existing shift for the selected employee and day.');
      }
      $stmt->close();

      // Insert the schedule
      $query = "INSERT INTO employee_schedules (employee_id, day_of_week, shift_name, start_time, end_time, is_working, break_minutes) VALUES (?, ?, ?, ?, ?, ?, ?)";
      $stmt = $mysqli->prepare($query);
      $stmt->bind_param('issssii', $employeeId, $dayOfWeek, $shiftName, $startTime, $endTime, $isWorking, $breakMinutes);
      if (!$stmt->execute()) {
        throw new Exception('Insert failed: ' . $stmt->error);
      }
      $newId = $mysqli->insert_id;
      $stmt->close();

      // Now check for existing attendance logs on the same date/day and update expected times
      // Calculate the date for the day_of_week (current week)
      $daysOfWeek = ['Sunday' => 0, 'Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6];
      $today = new DateTime();
      $currentDayOfWeek = $today->format('w');  // 0=Sunday, etc.
      $targetDayOfWeek = $daysOfWeek[$dayOfWeek] ?? 1;
      $daysDiff = $targetDayOfWeek - $currentDayOfWeek;
      $targetDate = $today->modify("+$daysDiff days")->format('Y-m-d');

      // Check if attendance log exists for this employee on this date
      $attendanceQuery = "SELECT log_id FROM attendance_logs WHERE employee_id = ? AND attendance_date = ?";
      $stmt = $mysqli->prepare($attendanceQuery);
      $stmt->bind_param('is', $employeeId, $targetDate);
      $stmt->execute();
      $attendanceResult = $stmt->get_result();
      if ($attendanceResult->num_rows > 0) {
        // Update expected times in attendance_logs
        $attendanceRow = $attendanceResult->fetch_assoc();
        $updateQuery = "UPDATE attendance_logs SET expected_start_time = ?, expected_end_time = ? WHERE log_id = ?";
        $stmtUpdate = $mysqli->prepare($updateQuery);
        $stmtUpdate->bind_param('ssi', $startTime, $endTime, $attendanceRow['log_id']);
        $stmtUpdate->execute();
        $stmtUpdate->close();
        error_log("Updated expected times for attendance log ID {$attendanceRow['log_id']} with new schedule.");
      }
      $stmt->close();

      ob_end_clean();
      echo json_encode(['success' => true, 'message' => 'Schedule added successfully.', 'data' => ['id' => $newId]]);
    } catch (Exception $e) {
      error_log("Add Schedule Error: " . $e->getMessage());
      ob_end_clean();
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

  case 'update_schedule':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      ob_end_clean();
      http_response_code(405);
      echo json_encode(['success' => false, 'message' => 'POST required']);
      break;
    }
    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
      ob_end_clean();
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'Schedule ID required']);
      break;
    }
    try {
      $employeeId = (int)($_POST['employee_id'] ?? 0);
      $dayOfWeek = mapDayToEnum((int)($_POST['day_of_week'] ?? 1));
      $shiftName = trim($_POST['shift_name'] ?? '');
      $startTime = trim($_POST['start_time'] ?? '');
      $endTime = trim($_POST['end_time'] ?? '');
      $isWorking = (int)($_POST['is_working'] ?? 1);
      $breakMinutes = (int)($_POST['break_minutes'] ?? 0);

      if (!$employeeId) {
        throw new Exception('Employee ID is required.');
      }
      if (empty($shiftName)) {
        throw new Exception('Shift/class name cannot be empty.');
      }
      if (empty($startTime) || empty($endTime)) {
        throw new Exception('Start and end times are required.');
      }
      if ($startTime >= $endTime) {
        throw new Exception('Start time must be before end time.');
      }

      // Check for duplicates (same employee, day, shift name, excluding current)
      $duplicateQuery = "SELECT schedule_id FROM employee_schedules WHERE employee_id = ? AND day_of_week = ? AND shift_name = ? AND schedule_id != ?";
      $stmt = $mysqli->prepare($duplicateQuery);
      $stmt->bind_param('issi', $employeeId, $dayOfWeek, $shiftName, $id);
      $stmt->execute();
      if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('A schedule with this shift name already exists for the selected employee and day.');
      }
      $stmt->close();

      // Check for overlaps (excluding current schedule)
      $overlapQuery = "SELECT schedule_id FROM employee_schedules WHERE employee_id = ? AND day_of_week = ? AND schedule_id != ? AND ((start_time < ? AND end_time > ?) OR (start_time < ? AND end_time > ?))";
      $stmt = $mysqli->prepare($overlapQuery);
      $stmt->bind_param('iisssss', $employeeId, $dayOfWeek, $id, $endTime, $startTime, $startTime, $endTime);
      $stmt->execute();
      if ($stmt->get_result()->num_rows > 0) {
        throw new Exception('This schedule overlaps with an existing shift for the selected employee and day.');
      }
      $stmt->close();

      $query = "UPDATE employee_schedules SET employee_id = ?, day_of_week = ?, shift_name = ?, start_time = ?, end_time = ?, is_working = ?, break_minutes = ? WHERE schedule_id = ?";
      $stmt = $mysqli->prepare($query);
      $stmt->bind_param('issssiii', $employeeId, $dayOfWeek, $shiftName, $startTime, $endTime, $isWorking, $breakMinutes, $id);
      $stmt->execute();
      $affected = $stmt->affected_rows;
      $stmt->close();

      if ($affected === 0) {
        throw new Exception('Schedule not found or no changes made.');
      }

      ob_end_clean();
      echo json_encode(['success' => true, 'message' => 'Schedule updated successfully.']);
    } catch (Exception $e) {
      error_log("Update Schedule Error: " . $e->getMessage());
      ob_end_clean();
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

  case 'delete_schedule':
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      ob_end_clean();
      http_response_code(405);
      echo json_encode(['success' => false, 'message' => 'POST required']);
      break;
    }

    // Check if user has head_admin role
    session_start();
    $userRoles = $_SESSION['roles'] ?? [];
    if (!in_array('head_admin', $userRoles)) {
      ob_end_clean();
      http_response_code(403);
      echo json_encode(['success' => false, 'message' => 'Only Head Admin can delete schedules']);
      break;
    }

    $id = (int)($_POST['id'] ?? 0);
    if (!$id) {
      ob_end_clean();
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => 'Schedule ID required']);
      break;
    }
    try {
      $stmt = $mysqli->prepare("DELETE FROM employee_schedules WHERE schedule_id = ?");
      $stmt->bind_param('i', $id);
      $stmt->execute();
      $affected = $stmt->affected_rows;
      $stmt->close();

      if ($affected === 0) {
        throw new Exception('Schedule not found.');
      }

      ob_end_clean();
      echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully.']);
    } catch (Exception $e) {
      error_log("Delete Schedule Error: " . $e->getMessage());
      ob_end_clean();
      http_response_code(400);
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

  default:
    ob_end_clean();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
    break;
}

if ($mysqli) {
  $mysqli->close();
}
ob_end_flush();
