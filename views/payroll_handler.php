<?php
require_once 'auth.php';
require_once 'conn.php';
header('Content-Type: application/json');

function fmt2($n){ return number_format((float)$n, 2, '.', ''); }

try {
    $db = conn();
    $mysqli = $db['mysqli'];

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    if ($action === 'roles') {
        $sql = "SELECT id, name, payroll_frequency FROM job_positions ORDER BY name";
        $res = $mysqli->query($sql);
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'id' => (int)$r['id'],
                'name' => $r['name'],
                'payroll_frequency' => $r['payroll_frequency'] ?? 'bi-weekly',
            ];
        }
        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }

    if ($action === 'next_payroll_per_role') {
        $now = new DateTime();
        $rolesSql = "SELECT id, name, payroll_frequency FROM job_positions ORDER BY name";
        $res = $mysqli->query($rolesSql);
        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $freq = strtolower($r['payroll_frequency'] ?? 'bi-weekly');
            $end = clone $now;
            $start = clone $now;
            switch ($freq) {
                case 'daily':
                    // today window
                    $start = new DateTime($now->format('Y-m-d'));
                    $end = new DateTime($now->format('Y-m-d'));
                    $next = clone $end;
                    break;
                case 'weekly':
                    $next = (clone $now)->modify('+7 days');
                    $end = new DateTime($next->format('Y-m-d'));
                    $start = (clone $end)->modify('-6 days');
                    break;
                case 'bi-weekly':
                    $next = (clone $now)->modify('+14 days');
                    $end = new DateTime($next->format('Y-m-d'));
                    $start = (clone $end)->modify('-13 days');
                    break;
                case 'monthly':
                default:
                    $firstOfMonth = new DateTime('first day of this month');
                    $lastOfMonth = new DateTime('last day of this month');
                    if ($now > $lastOfMonth) {
                        $firstOfMonth = new DateTime('first day of next month');
                        $lastOfMonth = new DateTime('last day of next month');
                    }
                    $start = $firstOfMonth;
                    $end = $lastOfMonth;
                    $next = $lastOfMonth;
                    break;
            }
            $rows[] = [
                'role_id' => (int)$r['id'],
                'role_name' => $r['name'],
                'frequency' => $freq,
                'next_payroll_date' => $next->format('Y-m-d'),
                'period_start' => $start->format('Y-m-d'),
                'period_end' => $end->format('Y-m-d'),
            ];
        }
        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }

    if ($action === 'preview') {
        // Expect JSON body
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $start = $input['start'] ?? ($_POST['start'] ?? '');
        $end   = $input['end'] ?? ($_POST['end'] ?? '');
        $roleId = isset($input['role_id']) ? (int)$input['role_id'] : (isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0);
        $deductions = $input['deductions'] ?? [];

        if (!$start || !$end) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'start and end are required']);
            exit;
        }

        // Load company_hours_per_day for day-equivalent calc
        $chpd = 8.0;
        if ($r = $mysqli->query("SELECT company_hours_per_day FROM time_date_settings WHERE id = 1 LIMIT 1")) {
            $row = $r->fetch_assoc();
            $val = (float)($row['company_hours_per_day'] ?? 8.0);
            if ($val > 0) $chpd = $val;
        }

        // Load employees with their role, rates
        $sql = "SELECT e.id AS emp_id, e.first_name, e.last_name,
                       e.rate_per_day AS e_rpd, e.rate_per_hour AS e_rph,
                       jp.id AS role_id, jp.name AS role_name,
                       COALESCE(jp.rate_per_day, 0) AS jp_rpd,
                       COALESCE(jp.rate_per_hour, 0) AS jp_rph,
                       COALESCE(jp.working_hours_per_day, 0) AS whpd
                FROM employees e
                JOIN job_positions jp ON e.job_position_id = jp.id";
        if ($roleId > 0) {
            $sql .= " WHERE jp.id = " . (int)$roleId;
        }
        $res = $mysqli->query($sql);

        $rows = [];
        $sumGross = 0.0; $sumDeductions = 0.0; $sumNet = 0.0;

        while ($emp = $res->fetch_assoc()) {
            $empId = (int)$emp['emp_id'];
            $empName = trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
            $roleName = $emp['role_name'] ?? '';

            $ratePerDay = (float)($emp['e_rpd'] ?? 0);
            if ($ratePerDay <= 0) $ratePerDay = (float)($emp['jp_rpd'] ?? 0);
            $ratePerHour = (float)($emp['e_rph'] ?? 0);
            if ($ratePerHour <= 0) $ratePerHour = (float)($emp['jp_rph'] ?? 0);

            // Fetch attendance logs in the window
            $stmt = $mysqli->prepare("SELECT date, time_in, time_out, status FROM attendance_logs WHERE employee_id = ? AND date BETWEEN ? AND ?");
            $stmt->bind_param('iss', $empId, $start, $end);
            $stmt->execute();
            $logs = $stmt->get_result();

            $uniqueWorkedDays = [];
            $totalSeconds = 0;
            while ($log = $logs->fetch_assoc()) {
                $date = $log['date'];
                $status = $log['status'] ?? '';
                $ti = $log['time_in'];
                $to = $log['time_out'];
                if ($status !== 'Absent') {
                    $uniqueWorkedDays[$date] = true;
                }
                if (!empty($ti) && !empty($to)) {
                    $sec = max(0, strtotime($to) - strtotime($ti));
                    $totalSeconds += $sec;
                }
            }
            $stmt->close();

            $daysWorked = count($uniqueWorkedDays);
            $hoursWorked = $totalSeconds / 3600.0;

            // Determine gross using day rate if available; else hourly
            $gross = 0.0;
            $hoursOrDaysLabel = '';
            if ($ratePerDay > 0) {
                $gross = $daysWorked * $ratePerDay;
                $hoursOrDaysLabel = $daysWorked . ' day' . ($daysWorked == 1 ? '' : 's');
            } else {
                $gross = $hoursWorked * $ratePerHour;
                $hoursOrDaysLabel = number_format($hoursWorked, 2) . ' hrs';
            }

            // Apply deductions (UI-provided; no specific mapping yet)
            $perEmpDeduction = 0.0;
            foreach ($deductions as $d) {
                $amt = (float)($d['amount'] ?? 0);
                if ($amt <= 0) continue;
                // For now, treat every deduction as per-employee (applied to each row)
                $perEmpDeduction += $amt;
            }

            $net = max(0.0, $gross - $perEmpDeduction);

            $rows[] = [
                'employee' => $empName,
                'role' => $roleName,
                'hours_days' => $hoursOrDaysLabel,
                'gross' => fmt2($gross),
                'deductions' => fmt2($perEmpDeduction),
                'net' => fmt2($net),
                'status' => 'Included',
            ];

            $sumGross += $gross;
            $sumDeductions += $perEmpDeduction;
            $sumNet += $net;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_gross' => fmt2($sumGross),
                    'total_deductions' => fmt2($sumDeductions),
                    'total_net' => fmt2($sumNet),
                ],
                'rows' => $rows,
            ]
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
