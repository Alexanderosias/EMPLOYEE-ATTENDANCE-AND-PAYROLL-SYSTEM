<?php
ob_start();
date_default_timezone_set('Asia/Manila');
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(200);
  exit;
}

require_once 'conn.php';

// PhpSpreadsheet (top-level)
if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
  if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
  }
}

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

function sanitizeName($name)
{
  return trim(preg_replace('/[^a-zA-Z]/', '', $name ?? ''));
}

// Helper: Generate safe log ID (e.g., UUID-like; adjust if in conn.php)
if (!function_exists('generateSafeLogId')) {
  function generateSafeLogId()
  {
    return sprintf(
      '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0x0fff) | 0x4000,
      mt_rand(0, 0x3fff) | 0x8000,
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff),
      mt_rand(0, 0xffff)
    );
  }
}

$db = null;
$mysqli = null;
try {
  $db = conn();
  $mysqli = $db['mysqli'];
  if (!$mysqli || $mysqli->connect_error) {
    throw new Exception('MySQL failed: ' . ($mysqli ? $mysqli->connect_error : 'No connection'));
  }
  // FIXED: Set MySQL session TZ to PHT (NOW() uses this)
  $mysqli->query("SET time_zone = '+08:00'");  // UTC+8 for PHT
} catch (Exception $e) {
  ob_end_clean();
  die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// Define hasFirebase if not in conn.php
if (!function_exists('hasFirebase')) {
  function hasFirebase($db)
  {
    return isset($db) && isset($db['firebase']) && $db['firebase'] !== null;
  }
}

switch ($action) {
  case 'get_local_employees':  // GET: Fetch all local employees (QR validation fields only)
    try {
      $query = "SELECT id, first_name, last_name, job_position_name, date_joined, qr_first_name_sanitized, qr_last_name_sanitized 
                      FROM local_employees ORDER BY last_name, first_name";
      $result = $mysqli->query($query);
      if (!$result) {
        throw new Exception('Query failed: ' . $mysqli->error);
      }
      $employees = $result->fetch_all(MYSQLI_ASSOC);
      $result->free();

      ob_end_clean();
      echo json_encode(['success' => true, 'data' => $employees], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
      error_log("Get Local Employees Error: " . $e->getMessage());
      ob_end_clean();
      echo json_encode(['success' => false, 'message' => 'Failed to fetch local employees: ' . $e->getMessage()]);
    }
    break;

  case 'sync_employees':  // GET: Pull from Firebase, update local_employees (QR fields only)
    $is_test = isset($_GET['test']);
    try {
      if (!hasFirebase($db)) {
        error_log("Sync Employees: Firebase unavailable, skipping cloud pull");
        $query = "SELECT COUNT(*) as count FROM local_employees";
        $result = $mysqli->query($query);
        $count = $result ? ($result->fetch_assoc()['count'] ?? 0) : 0;
        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Local only (Firebase offline)', 'synced_count' => $count]);
        break;
      }

      $firebase = $db['firebase'];
      $ref = $firebase->getReference('employees');
      $snapshot = $ref->getValue();
      if (!$snapshot) {
        throw new Exception('No employees data in Firebase');
      }

      $fb_employees = [];
      foreach ($snapshot as $id => $emp) {
        if (!is_array($emp)) continue;
        $fb_employees[] = [
          'id' => (int)($emp['id'] ?? 0),
          'first_name' => trim($emp['first_name'] ?? ''),
          'last_name' => trim($emp['last_name'] ?? ''),
          'job_position_name' => trim($emp['job_position_name'] ?? ''),
          'date_joined' => isset($emp['date_joined']) ? date('Y-m-d', strtotime($emp['date_joined'])) : '1970-01-01',
          'qr_first_name_sanitized' => sanitizeName($emp['first_name']),
          'qr_last_name_sanitized' => sanitizeName($emp['last_name'])
        ];
      }

      if ($is_test) {
        ob_end_clean();
        echo json_encode(['success' => true, 'test_data' => $fb_employees, 'count' => count($fb_employees)]);
        break;
      }

      // Batch UPSERT (only QR fields; id is INT PRIMARY KEY)
      $mysqli->autocommit(false);
      $updated = 0;
      foreach ($fb_employees as $emp) {
        if ($emp['id'] <= 0) continue;  // Skip invalid IDs

        $stmt = $mysqli->prepare("INSERT INTO local_employees 
                    (id, first_name, last_name, job_position_name, date_joined, qr_first_name_sanitized, qr_last_name_sanitized, synced_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW()) 
                    ON DUPLICATE KEY UPDATE 
                    first_name = VALUES(first_name), last_name = VALUES(last_name), 
                    job_position_name = VALUES(job_position_name), date_joined = VALUES(date_joined),
                    qr_first_name_sanitized = VALUES(qr_first_name_sanitized), 
                    qr_last_name_sanitized = VALUES(qr_last_name_sanitized), synced_at = NOW()");
        if (!$stmt) throw new Exception('Prepare failed: ' . $mysqli->error);
        $stmt->bind_param(
          'issssss',
          $emp['id'],
          $emp['first_name'],
          $emp['last_name'],
          $emp['job_position_name'],
          $emp['date_joined'],
          $emp['qr_first_name_sanitized'],
          $emp['qr_last_name_sanitized']
        );
        if (!$stmt->execute()) throw new Exception('Execute failed: ' . $stmt->error);
        $updated += $stmt->affected_rows;
        $stmt->close();
      }
      $mysqli->autocommit(true);

      error_log("Pulled and updated $updated employees from Firebase to local DB");
      ob_end_clean();
      echo json_encode(['success' => true, 'synced_count' => $updated, 'message' => 'Employees pulled successfully']);
    } catch (Exception $e) {
      error_log("Sync Employees Error: " . $e->getMessage());
      if (isset($mysqli)) $mysqli->autocommit(true);  // Ensure commit/rollback
      ob_end_clean();
      echo json_encode(['success' => false, 'message' => 'Employee pull failed: ' . $e->getMessage()]);
    }
    break;

  case 'sync_attendance':  // GET: Push unsynced logs to Firebase
    $is_test = isset($_GET['test']);
    try {
      if (!hasFirebase($db)) {
        error_log("Sync Attendance: Firebase unavailable, skipping push");
        $query = "SELECT COUNT(*) as count FROM attendance_logs WHERE synced = 0";
        $result = $mysqli->query($query);
        $count = $result ? ($result->fetch_assoc()['count'] ?? 0) : 0;
        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'Local only (Firebase offline)', 'synced_count' => 0, 'pending' => $count]);
        break;
      }

      $firebase = $db['firebase'];

      // Fetch unsynced
      $query = "SELECT * FROM attendance_logs WHERE synced = 0 ORDER BY created_at ASC";
      $result = $mysqli->query($query);
      if (!$result) throw new Exception('Query failed: ' . $mysqli->error);
      $logs = $result->fetch_all(MYSQLI_ASSOC);
      $result->free();

      if ($is_test) {
        ob_end_clean();
        echo json_encode(['success' => true, 'test_data' => $logs, 'count' => count($logs)]);
        break;
      }

      $synced = 0;
      $mysqli->autocommit(false);

      foreach ($logs as $log) {
        try {
          // Push to Firebase (use id as key)
          $ref = $firebase->getReference("attendance_logs/{$log['id']}");
          $ref->set([
            'id' => $log['id'],
            'employee_id' => (int)$log['employee_id'],
            'date' => $log['date'],
            'time_in' => $log['time_in'],
            'time_out' => $log['time_out'],
            'expected_start_time' => $log['expected_start_time'],
            'expected_end_time' => $log['expected_end_time'],
            'partial_absence_hours' => (float)$log['partial_absence_hours'],
            'status' => $log['status'],
            'qr_snapshot_path' => $log['qr_snapshot_path'],
            'synced' => 1,
            'created_at' => $log['created_at'],
            'updated_at' => date('c')  // ISO for Firebase
          ]);

          // Mark synced in local
          $stmt = $mysqli->prepare("UPDATE attendance_logs SET synced = 1, updated_at = NOW() WHERE id = ?");
          if (!$stmt) throw new Exception('Update prepare failed: ' . $mysqli->error);
          $stmt->bind_param('s', $log['id']);  // id is VARCHAR
          $stmt->execute();
          $synced += $stmt->affected_rows;
          $stmt->close();

          error_log("Pushed attendance log {$log['id']} to Firebase");
        } catch (Exception $log_error) {
          error_log("Failed to push log {$log['id']}: " . $log_error->getMessage());
        }
      }

      $mysqli->autocommit(true);
      ob_end_clean();
      echo json_encode(['success' => true, 'synced_count' => $synced, 'message' => 'Attendance pushed successfully']);
    } catch (Exception $e) {
      error_log("Sync Attendance Error: " . $e->getMessage());
      if (isset($mysqli)) $mysqli->autocommit(true);
      ob_end_clean();
      echo json_encode(['success' => false, 'message' => 'Attendance push failed: ' . $e->getMessage()]);
    }
    break;

  case 'get_unsynced_count':  // GET: Count unsynced attendance
    try {
      $query = "SELECT COUNT(*) as count FROM attendance_logs WHERE synced = 0";
      $result = $mysqli->query($query);
      if (!$result) throw new Exception('Query failed: ' . $mysqli->error);
      $row = $result->fetch_assoc();
      $count = (int)$row['count'];

      ob_end_clean();
      echo json_encode(['success' => true, 'data' => ['count' => $count]]);
    } catch (Exception $e) {
      error_log("Get Unsynced Count Error: " . $e->getMessage());
      ob_end_clean();
      echo json_encode(['success' => false, 'message' => 'Failed to get count: ' . $e->getMessage()]);
    }
    break;

  case 'get_recent_logs':
    try {
      $limit = (int)($_GET['limit'] ?? 7);
      if ($limit > 10) $limit = 7;

      $query = "
          SELECT al.id, al.employee_id, al.date, al.time_in, al.time_out, al.qr_snapshot_path, al.created_at, al.synced,
                 CONCAT(COALESCE(le.first_name, 'Unknown'), ' ', COALESCE(le.last_name, '')) as employee_name,
                 COALESCE(le.job_position_name, 'Unknown') as job_position_name
          FROM attendance_logs al 
          LEFT JOIN local_employees le ON al.employee_id = le.id 
          ORDER BY al.created_at DESC 
          LIMIT ?";
      $stmt = $mysqli->prepare($query);
      if (!$stmt) throw new Exception('Prepare failed: ' . $mysqli->error);
      $stmt->bind_param('i', $limit);
      $stmt->execute();
      $result = $stmt->get_result();
      $logs = $result->fetch_all(MYSQLI_ASSOC);
      $stmt->close();

      // FIXED: Format stored local time in PHT to 12h (h:i:s A)
      foreach ($logs as &$log) {
        $log['time_in_formatted'] = $log['time_in'] ? date('h:i:s A', strtotime($log['time_in'])) : 'N/A';  // e.g., "10:02:00 PM"
        $log['time_out_formatted'] = $log['time_out'] ? date('h:i:s A', strtotime($log['time_out'])) : 'N/A';
        $log['status_display'] = $log['time_out'] ? 'Clocked Out' : 'Clocked In';
        $log['has_snapshot'] = !empty($log['qr_snapshot_path']);
      }

      ob_end_clean();
      echo json_encode(['success' => true, 'data' => $logs], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
      error_log("Get Recent Logs Error: " . $e->getMessage());
      ob_end_clean();
      echo json_encode(['success' => false, 'message' => 'Failed to load recent logs: ' . $e->getMessage()]);
    }
    break;


  case 'get_last_log_time':  // NEW: GET: Get last log time for cooldown check
    try {
      $employee_id = (int)($_GET['employee_id'] ?? 0);
      if ($employee_id <= 0) {
        throw new Exception('Invalid employee ID');
      }

      $query = "SELECT created_at as last_time FROM attendance_logs WHERE employee_id = ? ORDER BY created_at DESC LIMIT 1";
      $stmt = $mysqli->prepare($query);
      $stmt->bind_param('i', $employee_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();
      $stmt->close();

      ob_end_clean();
      echo json_encode([
        'success' => true,
        'data' => [
          'last_time' => $row['last_time'] ?? null
        ]
      ]);
    } catch (Exception $e) {
      error_log("Get Last Log Time Error: " . $e->getMessage());
      ob_end_clean();
      echo json_encode(['success' => false, 'message' => 'Failed to get last log time: ' . $e->getMessage()]);
    }
    break;

  case 'check_clocked_in':  // NEW: GET: Check if employee clocked in today (time_in exists, time_out null)
    try {
      $employee_id = (int)($_GET['employee_id'] ?? 0);
      $date = $_GET['date'] ?? date('Y-m-d');
      if ($employee_id <= 0) {
        throw new Exception('Invalid employee ID');
      }

      $query = "SELECT EXISTS(SELECT 1 FROM attendance_logs WHERE employee_id = ? AND date = ? AND time_in IS NOT NULL AND time_out IS NULL) as clocked_in";
      $stmt = $mysqli->prepare($query);
      $stmt->bind_param('is', $employee_id, $date);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();
      $stmt->close();

      ob_end_clean();
      echo json_encode([
        'success' => true,
        'data' => [
          'clocked_in' => (bool)($row['clocked_in'] ?? false)
        ]
      ]);
    } catch (Exception $e) {
      error_log("Check Clocked In Error: " . $e->getMessage());
      ob_end_clean();
      echo json_encode(['success' => false, 'message' => 'Failed to check clocked in: ' . $e->getMessage()]);
    }
    break;

  case 'log_attendance':  // UPDATED: POST: Log attendance (handles in/out logic via FormData/$_POST, no base64 photo - uses path)
    try {
      // Handle FormData ($_POST) instead of JSON for compatibility with JS FormData
      $employee_id = (int)($_POST['employee_id'] ?? 0);
      $date = $_POST['date'] ?? date('Y-m-d');
      $time_in = $_POST['time_in'] ?? null;
      $time_out = $_POST['time_out'] ?? null;
      $qr_snapshot_path = $_POST['qr_snapshot_path'] ?? null;
      $check_type = $_POST['check_type'] ?? 'in';  // 'in' or 'out' - but we determine server-side

      if ($employee_id <= 0) {
        throw new Exception('Missing or invalid employee_id');
      }

      // Log incoming data for debug (remove in prod)
      error_log("Log Attendance Input: employee_id=$employee_id, date=$date, time_in=" . ($time_in ?? 'null') . ", time_out=" . ($time_out ?? 'null') . ", path=" . ($qr_snapshot_path ?? 'null') . ", type=$check_type");

      // Validate employee exists
      $empQuery = "SELECT id FROM local_employees WHERE id = ?";
      $empStmt = $mysqli->prepare($empQuery);
      if (!$empStmt) throw new Exception('Employee prepare failed: ' . $mysqli->error);
      $empStmt->bind_param('i', $employee_id);
      $empStmt->execute();
      if ($empStmt->get_result()->num_rows === 0) {
        throw new Exception('Employee not found in local_employees');
      }
      $empStmt->close();

      $currentTime = date('Y-m-d H:i:s');  // Now PHT time
      if ($time_in) $time_in = date('Y-m-d H:i:s', strtotime($date . ' ' . $time_in));  // Combines local, formats in PHT
      if ($time_out) $time_out = date('Y-m-d H:i:s', strtotime($date . ' ' . $time_out));

      // Log for debug (remove after test)
      error_log("Storing PHT time: date=$date, time_in=$time_in, time_out=$time_out");

      // Check for existing record today
      $checkQuery = "
          SELECT id, time_in, time_out, created_at 
          FROM attendance_logs 
          WHERE employee_id = ? AND date = ? 
          LIMIT 1";
      $checkStmt = $mysqli->prepare($checkQuery);
      if (!$checkStmt) throw new Exception('Check prepare failed: ' . $mysqli->error);
      $checkStmt->bind_param('is', $employee_id, $date);
      $checkStmt->execute();
      $result = $checkStmt->get_result();
      $existing = $result->fetch_assoc();
      $checkStmt->close();

      $logId = $existing ? $existing['id'] : generateSafeLogId();  // Use existing ID or generate new
      $actionType = 'clock_in';  // Default

      if (!$existing) {
        // Insert with PHT time
        $insertQuery = "
              INSERT INTO attendance_logs (id, employee_id, date, time_in, qr_snapshot_path, synced, created_at, updated_at) 
              VALUES (?, ?, ?, ?, ?, 0, ?, ?)";  // Use PHT $currentTime for timestamps
        $insertStmt = $mysqli->prepare($insertQuery);
        if (!$insertStmt) throw new Exception('Insert prepare failed: ' . $mysqli->error);
        $insertStmt->bind_param('sisssss', $logId, $employee_id, $date, $time_in, $qr_snapshot_path, $currentTime, $currentTime);
        if (!$insertStmt->execute()) {
          throw new Exception('Insert execute failed: ' . $insertStmt->error);
        }
        $insertStmt->close();
        error_log("New PHT attendance log inserted for employee {$employee_id} (clock-in) ID: {$logId}");
      } else {
        if ($existing['time_out'] !== null) {
          throw new Exception('Already clocked out for today. Scan again tomorrow.');
        }

        if ($existing['time_in'] === null) {
          // Edge case: Update time_in
          $updateQuery = "UPDATE attendance_logs SET time_in = ?, qr_snapshot_path = ?, updated_at = NOW() WHERE id = ?";
          $updateStmt = $mysqli->prepare($updateQuery);
          if (!$updateStmt) throw new Exception('Update time_in prepare failed: ' . $mysqli->error);
          $time_in = $currentTime;
          $updateStmt->bind_param('sss', $time_in, $qr_snapshot_path, $logId);
          if (!$updateStmt->execute()) {
            throw new Exception('Update time_in execute failed: ' . $updateStmt->error);
          }
          $updateStmt->close();
          error_log("Updated time_in for existing log {$logId}");
        } else {
          // Has time_in, no time_out: Clock-out (check 3min cooldown from time_in)
          $lastInTime = new DateTime($existing['time_in']);
          $scanTime = new DateTime($currentTime);
          $diff = $scanTime->diff($lastInTime);
          $minutesDiff = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;

          if ($minutesDiff < 3) {
            throw new Exception('Please wait 3 minutes before scanning again.');
          }

          // Set time_out
          $updateQuery = "UPDATE attendance_logs SET time_out = ?, updated_at = NOW() WHERE id = ?";
          $updateStmt = $mysqli->prepare($updateQuery);
          if (!$updateStmt) throw new Exception('Update time_out prepare failed: ' . $mysqli->error);
          $time_out = $currentTime;
          $updateStmt->bind_param('ss', $time_out, $logId);
          if (!$updateStmt->execute()) {
            throw new Exception('Update time_out execute failed: ' . $updateStmt->error);
          }
          $updateStmt->close();
          $actionType = 'clock_out';
          error_log("Updated time_out for existing log {$logId}");
        }
      }

      // Sync to Firebase (if available)
      $syncedVal = 0;
      if (hasFirebase($db)) {
        try {
          $firebase = $db['firebase'];
          $ref = $firebase->getReference("attendance_logs/{$logId}");
          $firebaseData = [
            'id' => $logId,
            'employee_id' => $employee_id,
            'date' => $date,
            'time_in' => $time_in ?? $existing['time_in'] ?? null,
            'time_out' => $time_out ?? $existing['time_out'] ?? null,
            'qr_snapshot_path' => $qr_snapshot_path,
            'synced' => 1,
            'created_at' => $existing ? $existing['created_at'] : $currentTime,
            'updated_at' => $currentTime
          ];
          $ref->set($firebaseData);
          $syncedVal = 1;
          error_log("Synced log {$logId} to Firebase");
        } catch (Exception $syncErr) {
          error_log("Firebase sync failed for {$logId}: " . $syncErr->getMessage());
          $syncedVal = 0;
        }
      }

      // Update synced status in local DB
      $updateSyncQuery = "UPDATE attendance_logs SET synced = ? WHERE id = ?";
      $syncStmt = $mysqli->prepare($updateSyncQuery);
      if (!$syncStmt) throw new Exception('Sync update prepare failed: ' . $mysqli->error);
      $syncStmt->bind_param('is', $syncedVal, $logId);
      $syncStmt->execute();
      $syncStmt->close();

      ob_end_clean();
      echo json_encode([
        'success' => true,
        'data' => [
          'id' => $logId,
          'action' => $actionType,
          'synced' => $syncedVal,
          'time_in' => $time_in ?? $existing['time_in'] ?? null,
          'time_out' => $time_out ?? $existing['time_out'] ?? null
        ]
      ]);
    } catch (Exception $e) {
      error_log("Log Attendance Error: " . $e->getMessage());
      ob_end_clean();
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;


  case 'get_today_status':  // GET: For JS pre-check (today's record: time_in, time_out for cooldown/clocked-out)
    try {
      $employee_id = (int)($_GET['employee_id'] ?? 0);
      if ($employee_id <= 0) {
        throw new Exception('Invalid employee ID');
      }

      $query = "
            SELECT time_in, time_out 
            FROM attendance_logs 
            WHERE employee_id = ? AND date = CURDATE() 
            LIMIT 1";
      $stmt = $mysqli->prepare($query);
      $stmt->bind_param('i', $employee_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $row = $result->fetch_assoc();
      $stmt->close();

      ob_end_clean();
      echo json_encode([
        'success' => true,
        'data' => [
          'time_in' => $row['time_in'] ?? null,
          'time_out' => $row['time_out'] ?? null
        ]
      ]);
    } catch (Exception $e) {
      error_log("Get Today Status Error: " . $e->getMessage());
      ob_end_clean();
      echo json_encode(['success' => false, 'message' => 'Failed to get status']);
    }
    break;

  case 'save_snapshot':
    try {
      $input = json_decode(file_get_contents('php://input'), true);
      $base64Data = $input['snapshot'] ?? '';
      if (empty($base64Data) || !preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $base64Data)) {
        throw new Exception('Invalid snapshot data');
      }

      $base64Data = preg_replace('/^data:image\/(png|jpeg|jpg);base64,/', '', $base64Data);
      $binaryData = base64_decode($base64Data);
      if ($binaryData === false || strlen($binaryData) < 100) {
        throw new Exception('Failed to decode snapshot');
      }

      $uploadDir = __DIR__ . '/../uploads/snapshots/';
      if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
          throw new Exception('Failed to create snapshots directory');
        }
      }

      $timestamp = time();
      $extension = 'png';
      $filename = "qr-snapshot_{$timestamp}_" . generateSafeLogId() . ".{$extension}";
      $filePath = $uploadDir . $filename;
      $relativePath = "uploads/snapshots/{$filename}";

      if (file_put_contents($filePath, $binaryData) === false) {
        throw new Exception('Failed to save snapshot file');
      }
      chmod($filePath, 0644);

      error_log("Saved snapshot: {$relativePath}");  // Minimal log
      ob_end_clean();
      echo json_encode(['success' => true, 'data' => ['path' => $relativePath]]);
    } catch (Exception $e) {
      error_log("Save Snapshot Error: " . $e->getMessage());
      ob_end_clean();
      echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    break;

  case 'log_attendance_batch':  // POST: Batch insert from offline queue (assume 'in' scans)
    try {
      $input = json_decode(file_get_contents('php://input'), true);
      $logs = $input['logs'] ?? [];
      if (empty($logs)) {
        throw new Exception('No logs provided');
      }

      $mysqli->autocommit(false);
      $inserted = 0;
      foreach ($logs as $log) {
        $employee_id = (int)($log['employee_id'] ?? 0);
        $date = $log['date'] ?? date('Y-m-d');
        $time_in = $log['time_in'] ?? date('Y-m-d H:i:s');
        $qr_snapshot_path = $log['qr_snapshot_path'] ?? null;
        $id = $log['id'] ?? generateSafeLogId();

        if ($employee_id <= 0) continue;

        // Check existing, insert or update
        $check_query = "SELECT id FROM attendance_logs WHERE employee_id = ? AND date = ? LIMIT 1";
        $check_stmt = $mysqli->prepare($check_query);
        $check_stmt->bind_param('is', $employee_id, $date);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if ($existing) {
          $update_stmt = $mysqli->prepare("UPDATE attendance_logs SET time_in = ?, qr_snapshot_path = ?, updated_at = NOW(), synced = 0 WHERE id = ?");
          $update_stmt->bind_param('sss', $time_in, $qr_snapshot_path, $existing['id']);
          if ($update_stmt->execute()) $inserted++;
          $update_stmt->close();
        } else {
          $stmt = $mysqli->prepare("INSERT INTO attendance_logs 
                        (id, employee_id, date, time_in, time_out, expected_start_time, expected_end_time, partial_absence_hours, status, qr_snapshot_path, synced, created_at, updated_at) 
                        VALUES (?, ?, ?, ?, NULL, '08:00:00', '17:00:00', 0.00, 'Present', ?, 0, NOW(), NOW())");
          if ($stmt) {
            $stmt->bind_param('sisss', $id, $employee_id, $date, $time_in, $qr_snapshot_path);
            if ($stmt->execute()) $inserted++;
            $stmt->close();
          }
        }
      }

      $mysqli->autocommit(true);
      error_log("Batch inserted/updated $inserted attendance logs");
      ob_end_clean();
      echo json_encode(['success' => true, 'inserted_count' => $inserted, 'message' => 'Batch attendance logged locally']);
    } catch (Exception $e) {
      error_log("Log Attendance Batch Error: " . $e->getMessage());
      if (isset($mysqli)) $mysqli->autocommit(true);
      ob_end_clean();
      echo json_encode(['success' => false, 'message' => 'Failed to log batch attendance: ' . $e->getMessage()]);
    }
    break;

  case 'save_photo':  // POST: Save base64 photo to file and return path (legacy for older JS)
    try {
      $input = json_decode(file_get_contents('php://input'), true);
      $base64Data = $input['photo'] ?? '';
      if (empty($base64Data) || !preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $base64Data)) {
        throw new Exception('Invalid photo data');
      }

      // Extract base64
      $base64Data = preg_replace('/^data:image\/(png|jpeg|jpg);base64,/', '', $base64Data);
      $binaryData = base64_decode($base64Data);
      if ($binaryData === false) {
        throw new Exception('Failed to decode photo');
      }

      // Upload dir
      $uploadDir = __DIR__ . '/../uploads/attendance_snaps/';
      if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
          throw new Exception('Failed to create uploads directory');
        }
      }

      // Generate unique filename
      $timestamp = time();
      $extension = 'png';  // Default; can detect from base64 if needed
      $filename = "attendance_{$timestamp}_" . generateSafeLogId() . ".{$extension}";
      $filePath = $uploadDir . $filename;
      $relativePath = "uploads/attendance_snaps/{$filename}";  // For DB storage

      if (file_put_contents($filePath, $binaryData) === false) {
        throw new Exception('Failed to save photo file');
      }

      chmod($filePath, 0644);  // Secure permissions

      error_log("Saved photo to: {$relativePath}");
      ob_end_clean();
      echo json_encode(['success' => true, 'data' => ['path' => $relativePath]]);
    } catch (Exception $e) {
      error_log("Save Photo Error: " . $e->getMessage());
      ob_end_clean();
      echo json_encode(['success' => false, 'message' => 'Failed to save photo: ' . $e->getMessage()]);
    }
    break;

  // Updated export case: Only unsynced records
  case 'export':
    try {
      if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        throw new Exception('PhpSpreadsheet not installed. Run: composer require phpoffice/phpspreadsheet');
      }

      // FIXED: Slim query (only requested columns, no join needed for employee_id)
      $query = "
          SELECT employee_id, date, time_in, time_out, qr_snapshot_path, created_at, updated_at
          FROM attendance_logs 
          WHERE synced = 0
          ORDER BY created_at DESC";
      $result = $mysqli->query($query);
      if (!$result) throw new Exception('Query failed: ' . $mysqli->error);
      $logs = $result->fetch_all(MYSQLI_ASSOC);
      $result->free();

      $spreadsheet = new Spreadsheet();
      $sheet = $spreadsheet->getActiveSheet();
      $sheet->setTitle('Unsynced Attendance');

      // FIXED: Slim headers
      $headers = ['Employee ID', 'Date', 'Time In', 'Time Out', 'Snapshot Path', 'Created At', 'Updated At'];
      $sheet->fromArray($headers, null, 'A1');

      $headerRange = 'A1:G1';
      $sheet->getStyle($headerRange)->getFont()->setBold(true);
      $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCCC');
      $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

      if (!empty($logs)) {
        $dataRows = [];
        foreach ($logs as $log) {
          $dataRows[] = [
            $log['employee_id'],
            $log['date'],
            $log['time_in'] ? date('h:i:s A', strtotime($log['time_in'])) : 'N/A',  // 12h
            $log['time_out'] ? date('h:i:s A', strtotime($log['time_out'])) : 'N/A',  // 12h
            $log['qr_snapshot_path'] ?: 'No snapshot',
            $log['created_at'],
            $log['updated_at']
          ];
        }
        $sheet->fromArray($dataRows, null, 'A2');

        foreach (range('A', 'G') as $col) {
          $sheet->getColumnDimension($col)->setAutoSize(true);
        }
      } else {
        $sheet->setCellValue('A2', 'No unsynced attendance logs found.');
      }

      ob_end_clean();
      $filename = 'unsynced_attendance_export_' . date('Y-m-d') . '.xlsx';
      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment;filename="' . $filename . '"');
      header('Cache-Control: max-age=0');

      $writer = new Xlsx($spreadsheet);
      $writer->save('php://output');
      exit;
    } catch (Exception $e) {
      error_log("Export Error: " . $e->getMessage());
      ob_end_clean();
      echo json_encode(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()]);
    }
    break;

  case 'export_all':
    try {
      if (!class_exists('PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        throw new Exception('PhpSpreadsheet not installed. Run: composer require phpoffice/phpspreadsheet');
      }

      // FIXED: Slim query (only requested columns, no join)
      $query = "
          SELECT employee_id, date, time_in, time_out, qr_snapshot_path, created_at, updated_at
          FROM attendance_logs 
          ORDER BY created_at DESC";
      $result = $mysqli->query($query);
      if (!$result) throw new Exception('Query failed: ' . $mysqli->error);
      $logs = $result->fetch_all(MYSQLI_ASSOC);
      $result->free();

      $spreadsheet = new Spreadsheet();
      $sheet = $spreadsheet->getActiveSheet();
      $sheet->setTitle('All Attendance Logs');

      // FIXED: Slim headers (same as export)
      $headers = ['Employee ID', 'Date', 'Time In', 'Time Out', 'Snapshot Path', 'Created At', 'Updated At'];
      $sheet->fromArray($headers, null, 'A1');

      $headerRange = 'A1:G1';
      $sheet->getStyle($headerRange)->getFont()->setBold(true);
      $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('CCCCCC');
      $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

      if (!empty($logs)) {
        $dataRows = [];
        foreach ($logs as $log) {
          $dataRows[] = [
            $log['employee_id'],
            $log['date'],
            $log['time_in'] ? date('h:i:s A', strtotime($log['time_in'])) : 'N/A',  // 12h format
            $log['time_out'] ? date('h:i:s A', strtotime($log['time_out'])) : 'N/A',  // 12h format
            $log['qr_snapshot_path'] ?: 'No snapshot',
            $log['created_at'],
            $log['updated_at']
          ];
        }
        $sheet->fromArray($dataRows, null, 'A2');

        foreach (range('A', 'G') as $col) {
          $sheet->getColumnDimension($col)->setAutoSize(true);
        }
      } else {
        $sheet->setCellValue('A2', 'No attendance logs found.');
      }

      ob_end_clean();
      $filename = 'all_attendance_export_' . date('Y-m-d') . '.xlsx';
      header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
      header('Content-Disposition: attachment;filename="' . $filename . '"');
      header('Cache-Control: max-age=0');

      $writer = new Xlsx($spreadsheet);
      $writer->save('php://output');
      exit;
    } catch (Exception $e) {
      error_log("Export All Error: " . $e->getMessage());
      ob_end_clean();
      echo json_encode(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()]);
    }
    break;

  default:
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
    break;
}

if (isset($mysqli)) $mysqli->close();
ob_end_flush();
