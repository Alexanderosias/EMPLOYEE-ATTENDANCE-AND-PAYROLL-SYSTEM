<?php
require_once 'auth.php';
require_once 'conn.php';
header('Content-Type: application/json');

function fmt2($n)
{
    return number_format((float)$n, 2, '.', '');
}

try {
    $db = conn();
    $mysqli = $db['mysqli'];

    // Resolve logged-in employee
    $userId = $_SESSION['user_id'] ?? null;
    if (!$userId) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Not logged in']);
        exit;
    }
    $empStmt = $mysqli->prepare("SELECT id FROM employees WHERE user_id = ? LIMIT 1");
    $empStmt->bind_param('i', $userId);
    $empStmt->execute();
    $emp = $empStmt->get_result()->fetch_assoc();
    $empStmt->close();
    if (!$emp) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Employee not found']);
        exit;
    }
    $employeeId = (int)$emp['id'];

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    if ($action === 'summary') {
        $start = $_GET['start'] ?? $_POST['start'] ?? '';
        $end   = $_GET['end'] ?? $_POST['end'] ?? '';
        if (!$start || !$end) {
            $endDt = new DateTime();
            $startDt = (clone $endDt)->modify('-90 days');
            $start = $startDt->format('Y-m-d');
            $end   = $endDt->format('Y-m-d');
        }

        $stmt = $mysqli->prepare("SELECT 
                COALESCE(SUM(gross_pay),0) AS total_gross,
                COALESCE(SUM(total_deductions),0) AS total_deductions,
                COALESCE(SUM(net_pay),0) AS total_net,
                SUM(paid_status = 'Paid') AS paid_count,
                SUM(paid_status = 'Unpaid') AS unpaid_count
            FROM payroll
            WHERE employee_id = ? AND payroll_period_start <= ? AND payroll_period_end >= ?");
        $stmt->bind_param('iss', $employeeId, $end, $start);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        echo json_encode(['success' => true, 'data' => [
            'total_gross' => fmt2($row['total_gross'] ?? 0),
            'total_deductions' => fmt2($row['total_deductions'] ?? 0),
            'total_net' => fmt2($row['total_net'] ?? 0),
            'paid_count' => (int)($row['paid_count'] ?? 0),
            'unpaid_count' => (int)($row['unpaid_count'] ?? 0),
        ]]);
        exit;
    }

    if ($action === 'list') {
        $start = $_GET['start'] ?? $_POST['start'] ?? '';
        $end   = $_GET['end'] ?? $_POST['end'] ?? '';
        $status = $_GET['status'] ?? $_POST['status'] ?? '';
        if (!$start || !$end) {
            $endDt = new DateTime();
            $startDt = (clone $endDt)->modify('-90 days');
            $start = $startDt->format('Y-m-d');
            $end   = $endDt->format('Y-m-d');
        }

        $sql = "SELECT id, payroll_period_start, payroll_period_end, gross_pay,
                       philhealth_deduction, sss_deduction, pagibig_deduction,
                       other_deductions, total_deductions, net_pay, paid_status,
                       payment_date
                FROM payroll
                WHERE employee_id = ? AND payroll_period_start <= ? AND payroll_period_end >= ?";
        $types = 'iss';
        $params = [$employeeId, $end, $start];
        if ($status === 'Paid' || $status === 'Unpaid') {
            $sql .= " AND paid_status = ?";
            $types .= 's';
            $params[] = $status;
        }
        $sql .= " ORDER BY payroll_period_end DESC";

        $stmt = $mysqli->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $res = $stmt->get_result();
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'id' => (int)$r['id'],
                'period_start' => $r['payroll_period_start'],
                'period_end' => $r['payroll_period_end'],
                'gross' => fmt2($r['gross_pay']),
                'philhealth' => fmt2($r['philhealth_deduction']),
                'sss' => fmt2($r['sss_deduction']),
                'pagibig' => fmt2($r['pagibig_deduction']),
                'other' => fmt2($r['other_deductions']),
                'total_deductions' => fmt2($r['total_deductions']),
                'net' => fmt2($r['net_pay']),
                'paid_status' => $r['paid_status'],
                'payment_date' => $r['payment_date'] ?? null,
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
