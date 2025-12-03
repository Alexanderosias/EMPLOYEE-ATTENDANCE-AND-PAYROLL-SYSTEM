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
        // Current month boundaries
        $firstDay = (new DateTime('first day of this month'))->format('Y-m-d');
        $lastDay  = (new DateTime('last day of this month'))->format('Y-m-d');

        $sql = "SELECT status, COUNT(*) AS c
                FROM attendance_logs
                WHERE employee_id = ? AND date BETWEEN ? AND ?
                GROUP BY status";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iss', $employeeId, $firstDay, $lastDay);
        $stmt->execute();
        $res = $stmt->get_result();
        $map = ['Present' => 0, 'Late' => 0, 'Absent' => 0];
        while ($r = $res->fetch_assoc()) {
            $st = $r['status'] ?? '';
            $cnt = (int)($r['c'] ?? 0);
            if (isset($map[$st])) $map[$st] = $cnt;
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

        $sql = "SELECT 
                    id,
                    date,
                    DATE_FORMAT(time_in, '%Y-%m-%d %h:%i %p') AS time_in_fmt,
                    DATE_FORMAT(time_out, '%Y-%m-%d %h:%i %p') AS time_out_fmt,
                    status,
                    expected_start_time,
                    expected_end_time,
                    snapshot_path
                FROM attendance_logs
                WHERE employee_id = ? AND date BETWEEN ? AND ?
                ORDER BY date DESC, time_in DESC";
        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param('iss', $employeeId, $start, $end);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'id' => (int)$r['id'],
                'date' => $r['date'],
                'time_in' => $r['time_in_fmt'] ?? '-',
                'time_out' => $r['time_out_fmt'] ?? '-',
                'status' => $r['status'],
                'expected_start_time' => $r['expected_start_time'],
                'expected_end_time' => $r['expected_end_time'],
                'snapshot_path' => $r['snapshot_path'] ?? null,
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
