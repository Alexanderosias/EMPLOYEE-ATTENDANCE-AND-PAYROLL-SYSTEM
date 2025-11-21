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

        // Now fetch the attendance logs (existing logic)
        $query = "
        SELECT 
            al.id,
            al.employee_id,
            CONCAT(e.first_name, ' ', e.last_name) AS name,
            e.avatar_path,
            al.date,
            DATE_FORMAT(al.time_in, '%h:%i %p') as time_in,
            DATE_FORMAT(al.time_out, '%h:%i %p') as time_out,
            al.status
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
            $data[] = $row;
        }

        echo json_encode($data);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? null;

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

            // Auto-detect status using updated expected times
            $newStatus = 'Present';  // Default
            if ($timeInDB && $expectedStart) {
                $expectedStartStr = "$date $expectedStart";
                if (strtotime($timeInDB) > strtotime($expectedStartStr)) {
                    $newStatus = 'Late';
                }
            }
            if ($timeOutDB && $expectedEnd && $newStatus !== 'Late') {  // Only check undertime if not late
                $expectedEndStr = "$date $expectedEnd";
                if (strtotime($timeOutDB) < strtotime($expectedEndStr)) {
                    $newStatus = 'Undertime';
                }
            }

            // Update attendance_logs with new times, expected times, and status
            $stmt = $mysqli->prepare("UPDATE attendance_logs SET time_in = ?, time_out = ?, expected_start_time = ?, expected_end_time = ?, status = ? WHERE id = ?");
            $stmt->bind_param('sssssi', $timeInDB, $timeOutDB, $expectedStart, $expectedEnd, $newStatus, $id);

            if ($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Record updated successfully']);
            } else {
                throw new Exception('Failed to update record');
            }

            $stmt->close();
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
