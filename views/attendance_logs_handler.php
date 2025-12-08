<?php
require_once 'conn.php';  // Adjust path if conn.php is elsewhere

header('Content-Type: application/json');

try {
    $db = conn();
    $mysqli = $db['mysqli'];

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Pre-populate attendance logs for employees with schedules but no log for today
        $today = date('Y-m-d');
        $dayOfWeek = date('l');  // e.g., 'Monday'

        // Get all employees with schedules for today
        $scheduleQuery = "
        SELECT DISTINCT s.employee_id, e.first_name, e.last_name
        FROM schedules s
        JOIN employees e ON s.employee_id = e.id
        WHERE s.day_of_week = ?
    ";
        $stmt = $mysqli->prepare($scheduleQuery);
        $stmt->bind_param('s', $dayOfWeek);
        $stmt->execute();
        $scheduledEmployees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($scheduledEmployees as $emp) {
            $employeeId = $emp['employee_id'];

            // Check if attendance log already exists for today
            $checkStmt = $mysqli->prepare("SELECT id FROM attendance_logs WHERE employee_id = ? AND date = ?");
            $checkStmt->bind_param('is', $employeeId, $today);
            $checkStmt->execute();
            $exists = $checkStmt->get_result()->num_rows > 0;
            $checkStmt->close();

            if (!$exists) {
                // Compute expected times: earliest start and latest end
                $expectedQuery = "
                SELECT MIN(start_time) AS expected_start, MAX(end_time) AS expected_end
                FROM schedules
                WHERE employee_id = ? AND day_of_week = ?
            ";
                $expStmt = $mysqli->prepare($expectedQuery);
                $expStmt->bind_param('is', $employeeId, $dayOfWeek);
                $expStmt->execute();
                $expected = $expStmt->get_result()->fetch_assoc();
                $expStmt->close();

                $expectedStart = $expected['expected_start'] ?? null;
                $expectedEnd = $expected['expected_end'] ?? null;

                // Insert new attendance log
                $insertStmt = $mysqli->prepare("
                INSERT INTO attendance_logs (employee_id, date, status, time_in, time_out, expected_start_time, expected_end_time)
                VALUES (?, ?, 'Absent', NULL, NULL, ?, ?)
            ");
                $insertStmt->bind_param('isss', $employeeId, $today, $expectedStart, $expectedEnd);
                $insertStmt->execute();
                $insertStmt->close();
            }
        }

        // Now fetch the attendance logs including snapshot info
        $query = "
        SELECT 
            al.id,
            al.employee_id,
            CONCAT(e.first_name, ' ', e.last_name) AS name,
            e.avatar_path,
            al.date,
            DATE_FORMAT(al.time_in, '%h:%i %p') as time_in,
            DATE_FORMAT(al.time_out, '%h:%i %p') as time_out,
            al.status,
            al.snapshot_path,
            (
                SELECT GROUP_CONCAT(
                    JSON_OBJECT(
                        'id', s.id,
                        'image_path', s.image_path,
                        'captured_at', s.captured_at
                    )
                    SEPARATOR '|||'
                )
                FROM snapshots s
                WHERE s.attendance_log_id = al.id
            ) AS snapshots_json
        FROM attendance_logs al
        JOIN employees e ON al.employee_id = e.id
        ORDER BY al.date DESC, al.time_in DESC
    ";

        $result = $mysqli->query($query);

        if (!$result) {
            throw new Exception($mysqli->error);
        }

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $row['timeIn'] = $row['time_in'] ? $row['time_in'] : '-';
            $row['timeOut'] = $row['time_out'] ? $row['time_out'] : '-';

            $snapshots = [];
            if (!empty($row['snapshots_json'])) {
                $snapParts = explode('|||', $row['snapshots_json']);
                foreach ($snapParts as $snap) {
                    $decoded = json_decode($snap, true);
                    if ($decoded && isset($decoded['image_path'])) {
                        $snapshots[] = $decoded;
                    }
                }
            }

            $row['snapshots'] = $snapshots;
            $row['hasSnapshot'] = (!empty($row['snapshot_path']) || !empty($snapshots)) ? 1 : 0;
            unset($row['snapshots_json']);

            $data[] = $row;
        }

        echo json_encode($data);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $input = null;
        $action = null;
        if (stripos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? null;
        } else {
            $action = $_POST['action'] ?? null;
        }

        if ($action === 'delete') {
            // Delete logic (from delete_attendance_log.php), now with file deletion
            $id = $input['id'] ?? null;

            if (!$id) {
                throw new Exception('ID is required');
            }

            // Step 1: Fetch snapshot paths from snapshots table and attendance_logs.snapshot_path
            $snapshotPaths = [];

            // Get paths from snapshots table
            $stmt = $mysqli->prepare("SELECT image_path FROM snapshots WHERE attendance_log_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $snapshotPaths[] = $row['image_path'];
            }
            $stmt->close();

            // Get snapshot_path from attendance_logs (if exists)
            $stmt = $mysqli->prepare("SELECT snapshot_path FROM attendance_logs WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $log = $result->fetch_assoc();
            if ($log && $log['snapshot_path']) {
                $snapshotPaths[] = $log['snapshot_path'];
            }
            $stmt->close();

            // Step 2: Delete the physical files
            $rootPath = $_SERVER['DOCUMENT_ROOT'];  // Web root path
            foreach ($snapshotPaths as $path) {
                $fullPath = $rootPath . '/' . $path;  // e.g., /var/www/html/uploads/snapshots/file.png
                if (file_exists($fullPath)) {
                    if (!unlink($fullPath)) {
                        error_log("Failed to delete snapshot file: $fullPath");  // Log error but continue
                    }
                } else {
                    error_log("Snapshot file not found: $fullPath");  // Log warning but continue
                }
            }

            // Step 3: Delete the attendance log (cascades to snapshots table)
            $stmt = $mysqli->prepare("DELETE FROM attendance_logs WHERE id = ?");
            $stmt->bind_param('i', $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Record and associated snapshots deleted successfully']);
            } else {
                throw new Exception('Failed to delete record');
            }

            $stmt->close();
        } elseif ($action === 'import_attendance') {
            // Import zip package containing logs.json and snapshots/
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('No import file uploaded.');
            }

            $uploadedName = $_FILES['file']['name'] ?? '';
            $ext = strtolower(pathinfo($uploadedName, PATHINFO_EXTENSION));
            if ($ext !== 'zip') {
                throw new Exception('Invalid file type. Please upload a .zip package.');
            }

            $tmpFile = $_FILES['file']['tmp_name'];

            $extractDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'attendance_import_' . uniqid();
            if (!mkdir($extractDir, 0755, true)) {
                throw new Exception('Failed to create temp directory.');
            }

            $zip = new ZipArchive();
            if ($zip->open($tmpFile) !== true) {
                throw new Exception('Failed to open zip file.');
            }
            if (!$zip->extractTo($extractDir)) {
                $zip->close();
                throw new Exception('Failed to extract zip file.');
            }
            $zip->close();

            $logsPath = $extractDir . DIRECTORY_SEPARATOR . 'logs.json';
            if (!file_exists($logsPath)) {
                rrmdir($extractDir);
                throw new Exception('logs.json not found in package.');
            }
            $payload = json_decode(file_get_contents($logsPath), true);
            if (!is_array($payload)) {
                rrmdir($extractDir);
                throw new Exception('Invalid logs.json format.');
            }
            $logs = $payload['logs'] ?? [];

            $destSnapshotsDir = realpath(__DIR__ . '/../uploads') . DIRECTORY_SEPARATOR . 'snapshots';
            if (!$destSnapshotsDir || !is_dir($destSnapshotsDir)) {
                $destSnapshotsDir = __DIR__ . '/../uploads/snapshots';
                if (!is_dir($destSnapshotsDir)) {
                    mkdir($destSnapshotsDir, 0755, true);
                }
            }

            $imported = 0;
            foreach ($logs as $log) {
                $employeeId = (int)($log['employee_id'] ?? 0);
                $date = $log['date'] ?? '';
                $timeIn = $log['time_in'] ?? null;
                $timeOut = $log['time_out'] ?? null;
                $status = $log['status'] ?? 'Present';
                $checkType = $log['check_type'] ?? 'in';
                $expectedStart = $log['expected_start_time'] ?? null;
                $expectedEnd = $log['expected_end_time'] ?? null;
                $snapBasenames = is_array($log['snapshots'] ?? null) ? $log['snapshots'] : [];

                if (!$employeeId || !$date) {
                    continue; // skip invalid entries
                }

                // Upsert attendance log by (employee_id, date)
                $stmt = $mysqli->prepare("SELECT id, snapshot_path FROM attendance_logs WHERE employee_id = ? AND date = ? LIMIT 1");
                $stmt->bind_param('is', $employeeId, $date);
                $stmt->execute();
                $res = $stmt->get_result();
                $existing = $res->fetch_assoc();
                $stmt->close();

                if ($existing) {
                    $logId = (int)$existing['id'];
                    $stmt = $mysqli->prepare("UPDATE attendance_logs SET time_in = ?, time_out = ?, status = ?, check_type = ?, expected_start_time = ?, expected_end_time = ? WHERE id = ?");
                    $stmt->bind_param('ssssssi', $timeIn, $timeOut, $status, $checkType, $expectedStart, $expectedEnd, $logId);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $stmt = $mysqli->prepare("INSERT INTO attendance_logs (employee_id, date, time_in, time_out, status, check_type, expected_start_time, expected_end_time) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param('isssssss', $employeeId, $date, $timeIn, $timeOut, $status, $checkType, $expectedStart, $expectedEnd);
                    $stmt->execute();
                    $logId = $mysqli->insert_id;
                    $stmt->close();
                }

                // Import snapshots from extracted /snapshots directory
                $firstSavedPath = null;
                $srcSnapshotsDir = $extractDir . DIRECTORY_SEPARATOR . 'snapshots';
                foreach ($snapBasenames as $base) {
                    $base = basename($base);
                    $srcPath = $srcSnapshotsDir . DIRECTORY_SEPARATOR . $base;
                    if (!file_exists($srcPath)) continue;

                    $destName = $base;
                    $destPath = $destSnapshotsDir . DIRECTORY_SEPARATOR . $destName;
                    if (file_exists($destPath)) {
                        $destName = time() . '_' . uniqid() . '_' . $destName;
                        $destPath = $destSnapshotsDir . DIRECTORY_SEPARATOR . $destName;
                    }
                    if (!copy($srcPath, $destPath)) {
                        continue;
                    }
                    $webPath = 'uploads/snapshots/' . $destName;

                    $stmt = $mysqli->prepare("INSERT INTO snapshots (attendance_log_id, image_path) VALUES (?, ?)");
                    $stmt->bind_param('is', $logId, $webPath);
                    $stmt->execute();
                    $stmt->close();

                    if ($firstSavedPath === null) {
                        $firstSavedPath = $webPath;
                    }
                }

                // Set primary snapshot_path if empty
                if ($firstSavedPath) {
                    $stmt = $mysqli->prepare("UPDATE attendance_logs SET snapshot_path = COALESCE(NULLIF(snapshot_path, ''), ?) WHERE id = ?");
                    $stmt->bind_param('si', $firstSavedPath, $logId);
                    $stmt->execute();
                    $stmt->close();
                }

                $imported++;
            }

            rrmdir($extractDir);

            echo json_encode(['success' => true, 'message' => 'Import completed.', 'imported' => $imported]);
        } elseif ($action === 'update') {
            // Update logic (with corrected expected time recalculation)
            $id = $input['id'] ?? null;
            $timeIn = $input['timeIn'] ?? null;
            $timeOut = $input['timeOut'] ?? null;

            if (!$id) {
                throw new Exception('ID is required');
            }

            // Fetch existing log
            $stmt = $mysqli->prepare("SELECT employee_id, date FROM attendance_logs WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $log = $result->fetch_assoc();
            $stmt->close();

            if (!$log) {
                throw new Exception('Log not found');
            }

            $employeeId = $log['employee_id'];
            $date = $log['date'];
            $dayOfWeek = date('l', strtotime($date));  // e.g., 'Monday'

            // Recalculate expected times from schedules
            // For expected_start_time: Earliest start_time (ORDER BY start_time ASC, take first)
            $stmt = $mysqli->prepare("SELECT start_time FROM schedules WHERE employee_id = ? AND day_of_week = ? ORDER BY start_time ASC LIMIT 1");
            $stmt->bind_param('is', $employeeId, $dayOfWeek);
            $stmt->execute();
            $startResult = $stmt->get_result();
            $expectedStart = $startResult->fetch_assoc()['start_time'] ?? null;
            $stmt->close();

            // For expected_end_time: Highest (latest) end_time (ORDER BY end_time DESC, take first)
            $stmt = $mysqli->prepare("SELECT end_time FROM schedules WHERE employee_id = ? AND day_of_week = ? ORDER BY end_time DESC LIMIT 1");
            $stmt->bind_param('is', $employeeId, $dayOfWeek);
            $stmt->execute();
            $endResult = $stmt->get_result();
            $expectedEnd = $endResult->fetch_assoc()['end_time'] ?? null;
            $stmt->close();

            $timeInDB = $timeIn ? date('Y-m-d H:i:s', strtotime("$date $timeIn")) : null;
            $timeOutDB = $timeOut ? date('Y-m-d H:i:s', strtotime("$date $timeOut")) : null;

            // Load grace periods from settings (same logic as scanner_api.php)
            $graceInMinutes = 0;
            $graceOutMinutes = 0;
            $gpRes = $mysqli->query("SELECT grace_in_minutes, grace_out_minutes FROM time_date_settings LIMIT 1");
            if ($gpRes) {
                $gpRow = $gpRes->fetch_assoc();
                if ($gpRow) {
                    $graceInMinutes = isset($gpRow['grace_in_minutes']) ? (int)$gpRow['grace_in_minutes'] : 0;
                    $graceOutMinutes = isset($gpRow['grace_out_minutes']) ? (int)$gpRow['grace_out_minutes'] : 0;
                }
                $gpRes->free();
            }

            // Load auto OT limit from attendance_settings (defaults to 30 minutes)
            $autoOtMinutes = 30;
            $attRes = $mysqli->query("SELECT auto_ot_minutes FROM attendance_settings LIMIT 1");
            if ($attRes) {
                $attRow = $attRes->fetch_assoc();
                if ($attRow && isset($attRow['auto_ot_minutes'])) {
                    $autoOtMinutes = max(0, (int)$attRow['auto_ot_minutes']);
                }
                $attRes->free();
            }

            $graceInSeconds = max(0, $graceInMinutes) * 60;
            $graceOutSeconds = max(0, $graceOutMinutes) * 60;

            // Auto-detect status using updated expected times and grace periods
            $newStatus = 'Present';  // Default
            if ($timeInDB && $expectedStart) {
                $expectedStartStr = "$date $expectedStart";
                $expectedStartTs = strtotime($expectedStartStr);
                if (strtotime($timeInDB) > ($expectedStartTs + $graceInSeconds)) {
                    $newStatus = 'Late';
                }
            }
            if ($timeOutDB && $expectedEnd && $newStatus !== 'Late') {  // Only check undertime if not late
                $expectedEndStr = "$date $expectedEnd";
                $expectedEndTs = strtotime($expectedEndStr);
                if (strtotime($timeOutDB) < ($expectedEndTs - $graceOutSeconds)) {
                    $newStatus = 'Undertime';
                }
            }

            // Update attendance_logs with new times, expected times, and status
            $stmt = $mysqli->prepare("UPDATE attendance_logs SET time_in = ?, time_out = ?, expected_start_time = ?, expected_end_time = ?, status = ? WHERE id = ?");
            $stmt->bind_param('sssssi', $timeInDB, $timeOutDB, $expectedStart, $expectedEnd, $newStatus, $id);

            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('Failed to update record');
            }
            $stmt->close();

            // Recompute overtime for this log (if any) and upsert/delete overtime_requests
            if ($timeOutDB && $expectedEnd) {
                $expectedEndStr = "$date $expectedEnd";
                $expectedEndTs = strtotime($expectedEndStr);
                $outTs = strtotime($timeOutDB);
                $rawOtMinutes = 0;
                if ($outTs > $expectedEndTs) {
                    $rawOtMinutes = (int) floor(($outTs - $expectedEndTs) / 60);
                }

                // Ensure overtime_requests table exists
                @$mysqli->query("CREATE TABLE IF NOT EXISTS overtime_requests (
                    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    attendance_log_id INT(11) NOT NULL,
                    employee_id INT(11) NOT NULL,
                    date DATE NOT NULL,
                    scheduled_end_time TIME NOT NULL,
                    actual_out_time DATETIME NOT NULL,
                    raw_ot_minutes INT(11) NOT NULL DEFAULT 0,
                    approved_ot_minutes INT(11) NOT NULL DEFAULT 0,
                    status ENUM('Pending','Approved','Rejected','AutoApproved') DEFAULT 'Pending',
                    approved_by INT(11) DEFAULT NULL,
                    approved_at DATETIME DEFAULT NULL,
                    remarks TEXT DEFAULT NULL,
                    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    KEY idx_ot_attendance (attendance_log_id),
                    KEY idx_ot_employee_date (employee_id, date)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

                if ($rawOtMinutes <= $autoOtMinutes || $rawOtMinutes <= 0) {
                    // OT within or below limit: treat as no overtime, remove any existing request
                    if ($stmt = $mysqli->prepare("DELETE FROM overtime_requests WHERE attendance_log_id = ?")) {
                        $stmt->bind_param('i', $id);
                        $stmt->execute();
                        $stmt->close();
                    }
                } else {
                    // Only minutes beyond the auto-OT limit require approval
                    $effectiveOt = $rawOtMinutes - $autoOtMinutes;
                    if ($effectiveOt < 0) {
                        $effectiveOt = 0;
                    }

                    if ($effectiveOt > 0) {
                        $statusOt = 'Pending';
                        $approvedMinutes = 0;

                        // Upsert overtime request for this attendance log
                        $stmt = $mysqli->prepare("SELECT id FROM overtime_requests WHERE attendance_log_id = ? LIMIT 1");
                        if ($stmt) {
                            $stmt->bind_param('i', $id);
                            $stmt->execute();
                            $res = $stmt->get_result();
                            $existingOt = $res->fetch_assoc();
                            $stmt->close();

                            if ($existingOt) {
                                $otId = (int)$existingOt['id'];
                                $stmt = $mysqli->prepare("UPDATE overtime_requests SET date = ?, scheduled_end_time = ?, actual_out_time = ?, raw_ot_minutes = ?, approved_ot_minutes = ?, status = ?, updated_at = NOW() WHERE id = ?");
                                if ($stmt) {
                                    $stmt->bind_param('sssissi', $date, $expectedEnd, $timeOutDB, $effectiveOt, $approvedMinutes, $statusOt, $otId);
                                    $stmt->execute();
                                    $stmt->close();
                                }
                            } else {
                                $stmt = $mysqli->prepare("INSERT INTO overtime_requests (attendance_log_id, employee_id, date, scheduled_end_time, actual_out_time, raw_ot_minutes, approved_ot_minutes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                                if ($stmt) {
                                    $stmt->bind_param('iisssiis', $id, $employeeId, $date, $expectedEnd, $timeOutDB, $effectiveOt, $approvedMinutes, $statusOt);
                                    $stmt->execute();
                                    $stmt->close();
                                }
                            }
                        }
                    } else {
                        // Safety: if effective OT is 0, remove any existing request
                        if ($stmt = $mysqli->prepare("DELETE FROM overtime_requests WHERE attendance_log_id = ?")) {
                            $stmt->bind_param('i', $id);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            } else {
                // No valid time-out or expected end; ensure no overtime request remains
                if ($stmt = $mysqli->prepare("DELETE FROM overtime_requests WHERE attendance_log_id = ?")) {
                    $stmt->bind_param('i', $id);
                    $stmt->execute();
                    $stmt->close();
                }
            }

            echo json_encode(['success' => true, 'message' => 'Record updated successfully']);
        } else {
            throw new Exception('Invalid action');
        }
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

// Helper to recursively delete directories
function rrmdir($dir) {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            rrmdir($path);
        } else {
            @unlink($path);
        }
    }
    @rmdir($dir);
}
