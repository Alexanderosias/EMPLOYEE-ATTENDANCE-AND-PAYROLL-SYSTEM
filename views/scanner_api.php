<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Set timezone to Philippine Standard Time
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
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
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

function parseQRData($qrData)
{
    $parts = explode('|', $qrData);
    $data = [];
    foreach ($parts as $part) {
        list($key, $value) = explode(':', $part, 2);
        $data[$key] = trim($value);
    }
    return $data;
}

function saveSnapshot($base64Image, $attendanceLogId)
{
    $uploadDir = '../uploads/snapshots/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $filename = 'snapshot_' . $attendanceLogId . '_' . time() . '.png';
    $filePath = $uploadDir . $filename;
    $webPath = 'uploads/snapshots/' . $filename;

    // Decode base64 and save
    $imageData = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $base64Image));
    if (file_put_contents($filePath, $imageData)) {
        // Insert into snapshots table
        global $mysqli;
        $stmt = $mysqli->prepare("INSERT INTO snapshots (attendance_log_id, image_path) VALUES (?, ?)");
        $stmt->bind_param('is', $attendanceLogId, $webPath);
        $stmt->execute();
        $stmt->close();
        return $webPath;
    }
    return null;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'scan':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'POST required']);
            break;
        }
        try {
            $qrData = trim($_POST['qr_data'] ?? '');
            $snapshot = $_POST['snapshot'] ?? '';

            if (empty($qrData)) {
                throw new Exception('QR data is required.');
            }

            $parsed = parseQRData($qrData);
            if (!isset($parsed['ID']) || !isset($parsed['First']) || !isset($parsed['Last'])) {
                throw new Exception('Invalid QR code format.');
            }

            $employeeId = (int)$parsed['ID'];
            $firstName = $parsed['First'];
            $lastName = $parsed['Last'];

            // Validate employee
            $stmt = $mysqli->prepare("SELECT id, status FROM employees WHERE id = ? AND first_name = ? AND last_name = ? AND status = 'Active'");
            $stmt->bind_param('iss', $employeeId, $firstName, $lastName);
            $stmt->execute();
            $result = $stmt->get_result();
            $employee = $result->fetch_assoc();
            $stmt->close();

            if (!$employee) {
                throw new Exception('Invalid QR code or employee not found/active.');
            }

            $today = date('Y-m-d');
            $now = date('Y-m-d H:i:s');

            // Check existing log for today
            $stmt = $mysqli->prepare("SELECT id, time_in, time_out FROM attendance_logs WHERE employee_id = ? AND date = ?");
            $stmt->bind_param('is', $employeeId, $today);
            $stmt->execute();
            $logResult = $stmt->get_result();
            $existingLog = $logResult->fetch_assoc();
            $stmt->close();

            $checkType = 'in';
            $timeIn = $now;
            $timeOut = null;
            $logId = null;

            // Only treat as existing if time_in is already set (ignore pre-populated 'Absent' logs with null time_in)
            if ($existingLog && $existingLog['time_in']) {
                if ($existingLog['time_out']) {
                    throw new Exception('Already checked out for today.');
                }

                // Cooldown check: Prevent scanning out if less than 60 seconds since time-in
                $secondsSinceTimeIn = strtotime($now) - strtotime($existingLog['time_in']);
                if ($secondsSinceTimeIn < 60) {
                    $remaining = 60 - $secondsSinceTimeIn;
                    throw new Exception("Please wait $remaining seconds before scanning out.");
                }

                $checkType = 'out';
                $timeOut = $now;
                $timeIn = $existingLog['time_in'];
                $logId = $existingLog['id'];
            }

            // Get all schedules for the employee for the current day
            $dayOfWeek = date('l');  // e.g., 'Monday'
            $stmt = $mysqli->prepare("SELECT start_time, end_time FROM schedules WHERE employee_id = ? AND day_of_week = ? ORDER BY start_time ASC");
            $stmt->bind_param('is', $employeeId, $dayOfWeek);
            $stmt->execute();
            $scheduleResult = $stmt->get_result();
            $schedules = $scheduleResult->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (empty($schedules)) {
                throw new Exception('No schedule found for this employee on ' . $dayOfWeek . '. Scanning not allowed.');
            }

            // Determine earliest start time and latest end time
            $earliestStart = $schedules[0]['start_time'];
            $latestEnd = $schedules[0]['end_time'];

            foreach ($schedules as $sched) {
                if (strtotime($sched['end_time']) > strtotime($latestEnd)) {
                    $latestEnd = $sched['end_time'];
                }
            }

            // Check if scanning after the latest end time (Shift Ended)
            // Only apply this check for Time-In. Time-Out is allowed after shift end.
            if ($checkType === 'in' && strtotime($now) > strtotime($today . ' ' . $latestEnd)) {
                throw new Exception('Your shift has ended. You cannot time-in anymore.');
            }

            // Determine Status
            $status = 'Present';
            $gracePeriodMinutes = 15; // Example grace period, can be made dynamic later

            if ($checkType === 'in') {
                $expectedStartTimeStr = $today . ' ' . $earliestStart;
                // Late if current time > expected start time + grace period
                // For strict comparison without grace period, remove the addition
                if (strtotime($now) > strtotime($expectedStartTimeStr)) {
                    $status = 'Late';
                }
            } elseif ($checkType === 'out') {
                $expectedEndTimeStr = $today . ' ' . $latestEnd;
                // Undertime if current time < expected end time
                if (strtotime($now) < strtotime($expectedEndTimeStr)) {
                    $status = 'Undertime';
                }
            }

            $expectedStart = $earliestStart;
            $expectedEnd = $latestEnd;

            // Insert/update attendance log
            if ($logId) {
                // Update existing (time-out)
                $stmt = $mysqli->prepare("UPDATE attendance_logs SET time_out = ?, status = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param('ssi', $timeOut, $status, $logId);
                $stmt->execute();
                $stmt->close();
            } else {
                // Insert new (time-in), or update pre-populated row if it exists
                if ($existingLog) {
                    // Update pre-populated row (set time_in)
                    $stmt = $mysqli->prepare("UPDATE attendance_logs SET time_in = ?, expected_start_time = ?, expected_end_time = ?, status = ?, check_type = ?, updated_at = NOW() WHERE id = ?");
                    $stmt->bind_param('sssssi', $timeIn, $expectedStart, $expectedEnd, $status, $checkType, $existingLog['id']);
                    $stmt->execute();
                    $logId = $existingLog['id'];
                    $stmt->close();
                } else {
                    // Insert new row
                    $stmt = $mysqli->prepare("INSERT INTO attendance_logs (employee_id, date, time_in, expected_start_time, expected_end_time, status, check_type) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param('issssss', $employeeId, $today, $timeIn, $expectedStart, $expectedEnd, $status, $checkType);
                    $stmt->execute();
                    $logId = $mysqli->insert_id;
                    $stmt->close();
                }
            }

            // Save snapshot
            $snapshotPath = null;
            if (!empty($snapshot)) {
                $snapshotPath = saveSnapshot($snapshot, $logId);
                if ($snapshotPath) {
                    $stmt = $mysqli->prepare("UPDATE attendance_logs SET snapshot_path = ? WHERE id = ?");
                    $stmt->bind_param('si', $snapshotPath, $logId);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Attendance logged successfully.', 'data' => ['employee_id' => $employeeId, 'check_type' => $checkType]]);
        } catch (Exception $e) {
            error_log("Scan Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_local_employees':
        try {
            $query = "
                SELECT e.id, e.first_name, e.last_name, jp.name AS job_position_name, e.date_joined,
                       qc.qr_data, qc.qr_image_path
                FROM employees e
                LEFT JOIN job_positions jp ON e.job_position_id = jp.id
                LEFT JOIN qr_codes qc ON e.id = qc.employee_id
                WHERE e.status = 'Active'
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
            error_log("Get Local Employees Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch employees: ' . $e->getMessage()]);
        }
        break;

    case 'get_recent_logs':
        try {
            $query = "
            SELECT al.id, CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                   jp.name AS job_position_name, al.date, al.time_in, al.time_out,
                   TIME_FORMAT(al.time_in, '%h:%i %p') AS time_in_formatted,
                   IF(al.time_out IS NOT NULL, TIME_FORMAT(al.time_out, '%h:%i %p'), 'Not Clocked Out') AS time_out_formatted
            FROM attendance_logs al
            JOIN employees e ON al.employee_id = e.id
            LEFT JOIN job_positions jp ON e.job_position_id = jp.id
            WHERE al.date = CURDATE() AND al.time_in IS NOT NULL  -- Only today's scans with time_in
            ORDER BY al.time_in DESC  -- Most recent scans first
            LIMIT 10
        ";
            $result = $mysqli->query($query);
            if (!$result) {
                throw new Exception('Query failed: ' . $mysqli->error);
            }
            $logs = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();

            ob_end_clean();
            echo json_encode(['success' => true, 'data' => $logs]);
        } catch (Exception $e) {
            error_log("Get Recent Logs Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch logs: ' . $e->getMessage()]);
        }
        break;

    case 'check_clocked_in':
        $employeeId = (int)($_GET['employee_id'] ?? 0);
        $date = $_GET['date'] ?? '';
        if (!$employeeId || !$date) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Employee ID and date required']);
            break;
        }
        try {
            $stmt = $mysqli->prepare("
                SELECT id FROM attendance_logs
                WHERE employee_id = ? AND date = ? AND time_in IS NOT NULL AND time_out IS NULL
                LIMIT 1
            ");
            $stmt->bind_param('is', $employeeId, $date);
            $stmt->execute();
            $result = $stmt->get_result();
            $clockedIn = $result->num_rows > 0;
            $stmt->close();

            ob_end_clean();
            echo json_encode(['success' => true, 'data' => ['clocked_in' => $clockedIn]]);
        } catch (Exception $e) {
            error_log("Check Clocked In Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to check status: ' . $e->getMessage()]);
        }
        break;

    case 'get_last_log_time':
        $employeeId = (int)($_GET['employee_id'] ?? 0);
        if (!$employeeId) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Employee ID required']);
            break;
        }
        try {
            $stmt = $mysqli->prepare("
                SELECT created_at AS last_time FROM attendance_logs
                WHERE employee_id = ? AND DATE(created_at) = CURDATE()
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->bind_param('i', $employeeId);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            ob_end_clean();
            echo json_encode(['success' => true, 'data' => ['last_time' => $row['last_time'] ?? null]]);
        } catch (Exception $e) {
            error_log("Get Last Log Time Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to get last log: ' . $e->getMessage()]);
        }
        break;

    case 'log_attendance':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'POST required']);
            break;
        }
        try {
            $employeeId = (int)($_POST['employee_id'] ?? 0);
            $date = $_POST['date'] ?? '';
            $timeIn = $_POST['time_in'] ?? null;
            $timeOut = $_POST['time_out'] ?? null;
            $snapshotPath = $_POST['qr_snapshot_path'] ?? null;
            $checkType = $_POST['check_type'] ?? 'in';

            if (!$employeeId || !$date) {
                throw new Exception('Employee ID and date required');
            }

            // Check if log exists for today
            $stmt = $mysqli->prepare("SELECT id FROM attendance_logs WHERE employee_id = ? AND date = ?");
            $stmt->bind_param('is', $employeeId, $date);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing = $result->fetch_assoc();
            $stmt->close();

            if ($existing) {
                // Update existing
                $updateField = $checkType === 'in' ? 'time_in' : 'time_out';
                $stmt = $mysqli->prepare("UPDATE attendance_logs SET $updateField = ?, qr_snapshot_path = ? WHERE id = ?");
                $stmt->bind_param('ssi', $checkType === 'in' ? $timeIn : $timeOut, $snapshotPath, $existing['id']);
                $stmt->execute();
                $logId = $existing['id'];
                $stmt->close();
            } else {
                // Insert new
                $stmt = $mysqli->prepare("
                    INSERT INTO attendance_logs (employee_id, date, time_in, time_out, qr_snapshot_path, check_type, synced)
                    VALUES (?, ?, ?, ?, ?, ?, 0)
                ");
                $stmt->bind_param('isssss', $employeeId, $date, $timeIn, $timeOut, $snapshotPath, $checkType);
                $stmt->execute();
                $logId = $mysqli->insert_id;
                $stmt->close();
            }

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Attendance logged.', 'data' => ['id' => $logId]]);
        } catch (Exception $e) {
            error_log("Log Attendance Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'log_attendance_batch':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'POST required']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $logs = $input['logs'] ?? [];
        if (empty($logs)) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No logs provided']);
            break;
        }
        try {
            $mysqli->begin_transaction();
            foreach ($logs as $log) {
                $employeeId = (int)($log['employee_id'] ?? 0);
                $date = $log['date'] ?? '';
                $timeIn = $log['time_in'] ?? null;
                $timeOut = $log['time_out'] ?? null;
                $snapshotPath = $log['qr_snapshot_path'] ?? null;
                $checkType = $log['check_type'] ?? 'in';

                $stmt = $mysqli->prepare("
                    INSERT INTO attendance_logs (employee_id, date, time_in, time_out, qr_snapshot_path, check_type, synced)
                    VALUES (?, ?, ?, ?, ?, ?, 0)
                    ON DUPLICATE KEY UPDATE time_in = VALUES(time_in), time_out = VALUES(time_out), qr_snapshot_path = VALUES(qr_snapshot_path)
                ");
                $stmt->bind_param('isssss', $employeeId, $date, $timeIn, $timeOut, $snapshotPath, $checkType);
                $stmt->execute();
                $stmt->close();
            }
            $mysqli->commit();

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Batch logged.']);
        } catch (Exception $e) {
            $mysqli->rollback();
            error_log("Log Attendance Batch Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Batch logging failed: ' . $e->getMessage()]);
        }
        break;

    case 'save_snapshot':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'POST required']);
            break;
        }
        $input = json_decode(file_get_contents('php://input'), true);
        $snapshot = $input['snapshot'] ?? '';
        if (!$snapshot) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'No snapshot data']);
            break;
        }
        try {
            $uploadDir = '../uploads/snapshots/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $filename = 'snapshot_' . time() . '_' . uniqid() . '.png';
            $filePath = $uploadDir . $filename;
            $data = str_replace('data:image/png;base64,', '', $snapshot);
            file_put_contents($filePath, base64_decode($data));
            $webPath = 'uploads/snapshots/' . $filename;

            ob_end_clean();
            echo json_encode(['success' => true, 'data' => ['path' => $webPath]]);
        } catch (Exception $e) {
            error_log("Save Snapshot Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save snapshot: ' . $e->getMessage()]);
        }
        break;

    case 'get_unsynced_count':
        try {
            $result = $mysqli->query("SELECT COUNT(*) AS count FROM attendance_logs WHERE synced = 0");
            $row = $result->fetch_assoc();
            $count = (int)$row['count'];
            $result->free();

            ob_end_clean();
            echo json_encode(['success' => true, 'data' => ['count' => $count]]);
        } catch (Exception $e) {
            error_log("Get Unsynced Count Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to get count: ' . $e->getMessage()]);
        }
        break;

    case 'sync_attendance':
        try {
            // Mark as synced (no Firebase, just update DB)
            $stmt = $mysqli->prepare("UPDATE attendance_logs SET synced = 1 WHERE synced = 0");
            $stmt->execute();
            $syncedCount = $stmt->affected_rows;
            $stmt->close();

            ob_end_clean();
            echo json_encode(['success' => true, 'syncedCount' => $syncedCount]);
        } catch (Exception $e) {
            error_log("Sync Attendance Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Sync failed: ' . $e->getMessage()]);
        }
        break;

    case 'export':
        try {
            $query = "
                SELECT al.date, al.time_in, al.time_out, al.status, al.check_type,
                       e.first_name, e.last_name, jp.name AS position_name
                FROM attendance_logs al
                JOIN employees e ON al.employee_id = e.id
                LEFT JOIN job_positions jp ON e.job_position_id = jp.id
                ORDER BY al.date DESC, al.time_in DESC
            ";
            $result = $mysqli->query($query);
            if (!$result) {
                throw new Exception('Query failed: ' . $mysqli->error);
            }
            $logs = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();

            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="attendance_export_' . date('Y-m-d') . '.csv"');
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Date', 'Employee Name', 'Position', 'Time In', 'Time Out', 'Status', 'Check Type']);
            foreach ($logs as $log) {
                fputcsv($output, [
                    $log['date'],
                    $log['first_name'] . ' ' . $log['last_name'],
                    $log['position_name'] ?? 'N/A',
                    $log['time_in'] ?? '',
                    $log['time_out'] ?? '',
                    $log['status'],
                    $log['check_type']
                ]);
            }
            fclose($output);
            exit;
        } catch (Exception $e) {
            error_log("Export Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Export failed: ' . $e->getMessage()]);
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
