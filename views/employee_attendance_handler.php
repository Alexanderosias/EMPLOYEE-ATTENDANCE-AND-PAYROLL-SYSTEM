<?php
require_once 'auth.php';
require_once 'conn.php';
header('Content-Type: application/json');

try {
    $db = conn();
    $mysqli = $db['mysqli'];

    // Identify logged-in employee
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    $empStmt = $mysqli->prepare("SELECT id FROM employees WHERE user_id = ? LIMIT 1");
    $empStmt->bind_param('i', $userId);
    $empStmt->execute();
    $empRes = $empStmt->get_result()->fetch_assoc();
    $empStmt->close();
    if (!$empRes) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employee record not found']);
        exit;
    }
    $employeeId = (int)$empRes['id'];

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    if ($action === 'summary') {
        $firstDay = (new DateTime('first day of this month'))->format('Y-m-d');
        $lastDay  = (new DateTime('last day of this month'))->format('Y-m-d');

        $holidays = [];
        if ($hRes = $mysqli->query("SELECT id, name, type, start_date, end_date FROM holidays")) {
            while ($hRow = $hRes->fetch_assoc()) {
                $holidays[] = $hRow;
            }
            $hRes->free();
        }

        $sql = "SELECT 
                    al.id,
                    al.date,
                    al.status,
                    al.time_in AS time_in,
                    al.time_out AS time_out,
                    lr.leave_type,
                    lr.deducted_from AS leave_deducted_from
                FROM attendance_logs al
                LEFT JOIN leave_requests lr
                    ON lr.employee_id = al.employee_id
                   AND lr.status = 'Approved'
                   AND al.date BETWEEN lr.start_date AND lr.end_date
                WHERE al.employee_id = ? AND al.date BETWEEN ? AND ?
                ORDER BY al.date DESC, al.time_in DESC";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iss', $employeeId, $firstDay, $lastDay);
        $stmt->execute();
        $res = $stmt->get_result();

        $map = ['Present' => 0, 'Late' => 0, 'Absent' => 0];

        while ($row = $res->fetch_assoc()) {
            $holidayType = null;
            if (!empty($holidays)) {
                $dateVal = $row['date'];
                foreach ($holidays as $h) {
                    if ($dateVal >= $h['start_date'] && $dateVal <= $h['end_date']) {
                        $holidayType = $h['type'] ?? null;
                        break;
                    }
                }
            }

            $baseStatus = $row['status'] ?? '';

            $leaveType = $row['leave_type'] ?? null;
            $leaveSource = $row['leave_deducted_from'] ?? null;
            $onLeaveApplied = false;

            if ($leaveType) {
                $kind = $leaveSource ?: $leaveType;
                if ($kind === 'Paid' || $kind === 'Unpaid' || $kind === 'Sick') {
                    $baseStatus = 'On Leave';
                } else {
                    $baseStatus = 'On Leave';
                }
                $onLeaveApplied = true;
            }

            $hasLog = !empty($row['time_in']) || !empty($row['time_out']);

            if ($holidayType && !$onLeaveApplied) {
                if ($holidayType === 'regular') {
                    if (!$hasLog) {
                        $baseStatus = 'Holiday';
                    }
                } elseif ($holidayType === 'special_non_working') {
                    if (!$hasLog) {
                        $baseStatus = 'Holiday';
                    }
                } elseif ($holidayType === 'special_working') {
                    if (!$hasLog) {
                        $baseStatus = 'Absent';
                    } else {
                        if ($baseStatus === 'Late' || $baseStatus === 'Undertime') {
                            // keep
                        } else {
                            $baseStatus = 'Present';
                        }
                    }
                }
            }

            if (isset($map[$baseStatus])) {
                $map[$baseStatus]++;
            }
        }
        $stmt->close();

        echo json_encode([
            'success' => true,
            'data' => [
                'present' => $map['Present'],
                'late' => $map['Late'],
                'absent' => $map['Absent'],
            ],
        ]);
        exit;
    }

    if ($action === 'list') {
        $start = $_GET['start'] ?? $_POST['start'] ?? '';
        $end   = $_GET['end'] ?? $_POST['end'] ?? '';
        if (!$start || !$end) {
            $endDt = new DateTime();
            $startDt = (clone $endDt)->modify('-30 days');
            $start = $startDt->format('Y-m-d');
            $end   = $endDt->format('Y-m-d');
        }

        $holidays = [];
        if ($hRes = $mysqli->query("SELECT id, name, type, start_date, end_date FROM holidays")) {
            while ($hRow = $hRes->fetch_assoc()) {
                $holidays[] = $hRow;
            }
            $hRes->free();
        }

        $sql = "SELECT 
                    al.id,
                    al.date,
                    al.time_in AS time_in,
                    al.time_out AS time_out,
                    DATE_FORMAT(al.time_in, '%Y-%m-%d %h:%i %p') AS time_in_fmt,
                    DATE_FORMAT(al.time_out, '%Y-%m-%d %h:%i %p') AS time_out_fmt,
                    al.status,
                    al.expected_start_time,
                    al.expected_end_time,
                    al.snapshot_path,
                    lr.leave_type,
                    lr.deducted_from AS leave_deducted_from
                FROM attendance_logs al
                LEFT JOIN leave_requests lr
                    ON lr.employee_id = al.employee_id
                   AND lr.status = 'Approved'
                   AND al.date BETWEEN lr.start_date AND lr.end_date
                WHERE al.employee_id = ? AND al.date BETWEEN ? AND ?
                ORDER BY al.date DESC, al.time_in DESC";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iss', $employeeId, $start, $end);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $holidayType = null;
            $holidayName = null;
            if (!empty($holidays)) {
                $dateVal = $r['date'];
                foreach ($holidays as $h) {
                    if ($dateVal >= $h['start_date'] && $dateVal <= $h['end_date']) {
                        $holidayType = $h['type'] ?? null;
                        $holidayName = $h['name'] ?? null;
                        break;
                    }
                }
            }

            $baseStatus = $r['status'] ?? '';
            $displayStatus = $baseStatus;

            $leaveType = $r['leave_type'] ?? null;
            $leaveSource = $r['leave_deducted_from'] ?? null;
            $onLeaveApplied = false;

            if ($leaveType) {
                $kind = $leaveSource ?: $leaveType;
                $kindLabel = 'Leave';
                if ($kind === 'Paid') {
                    $kindLabel = 'Paid Leave';
                } elseif ($kind === 'Unpaid') {
                    $kindLabel = 'Unpaid Leave';
                } elseif ($kind === 'Sick') {
                    $kindLabel = 'Sick Leave';
                }

                $displayStatus = 'On Leave';
                if ($kindLabel) {
                    $displayStatus .= ' - ' . $kindLabel;
                }

                if ($holidayType === 'regular') {
                    $displayStatus .= ' (Regular Holiday)';
                } elseif ($holidayType === 'special_non_working') {
                    $displayStatus .= ' (Special Non-Working Holiday)';
                } elseif ($holidayType === 'special_working') {
                    $displayStatus .= ' (Special Working Holiday)';
                }

                $baseStatus = 'On Leave';
                $onLeaveApplied = true;
            }

            $hasLog = !empty($r['time_in']) || !empty($r['time_out']);

            if ($holidayType && !$onLeaveApplied) {
                if ($holidayType === 'regular') {
                    if (!$hasLog) {
                        $displayStatus = 'Regular Holiday (No Work)';
                        $baseStatus = 'Holiday';
                    } else {
                        $displayStatus = 'Regular Holiday - Worked';
                    }
                } elseif ($holidayType === 'special_non_working') {
                    if (!$hasLog) {
                        $displayStatus = 'Special Non-Working Holiday (No Work)';
                        $baseStatus = 'Holiday';
                    } else {
                        $displayStatus = 'Special Non-Working Holiday - Worked';
                    }
                } elseif ($holidayType === 'special_working') {
                    if (!$hasLog) {
                        $displayStatus = 'Absent (Special Working Holiday)';
                        $baseStatus = 'Absent';
                    } else {
                        if ($baseStatus === 'Late') {
                            $displayStatus = 'Late (Special Working Holiday)';
                        } elseif ($baseStatus === 'Undertime') {
                            $displayStatus = 'Undertime (Special Working Holiday)';
                        } else {
                            $displayStatus = 'Present (Special Working Holiday)';
                            $baseStatus = 'Present';
                        }
                    }
                }
            }

            $rows[] = [
                'id' => (int)$r['id'],
                'date' => $r['date'],
                'time_in' => $r['time_in_fmt'] ?? '-',
                'time_out' => $r['time_out_fmt'] ?? '-',
                'status' => $r['status'],
                'base_status' => $baseStatus,
                'display_status' => $displayStatus,
                'expected_start_time' => $r['expected_start_time'],
                'expected_end_time' => $r['expected_end_time'],
                'snapshot_path' => $r['snapshot_path'] ?? null,
                'holiday_type' => $holidayType,
                'holiday_name' => $holidayName,
            ];
        }
        $stmt->close();

        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
