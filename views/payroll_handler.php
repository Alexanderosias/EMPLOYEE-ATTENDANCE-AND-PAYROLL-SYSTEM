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

        // Load company_hours_per_day for day-equivalent calc (fallback for roles)
        $chpd = 8.0;
        if ($r = $mysqli->query("SELECT company_hours_per_day FROM time_date_settings WHERE id = 1 LIMIT 1")) {
            $row = $r->fetch_assoc();
            $val = (float)($row['company_hours_per_day'] ?? 8.0);
            if ($val > 0) $chpd = $val;
        }

        // Load regular overtime multiplier from attendance_settings (for non-holiday OT)
        $regularOtMultiplier = 1.25;
        $holidayOtMultiplier = 2.00; // reserved / fallback
        if ($r = $mysqli->query("SELECT regular_overtime_multiplier, holiday_overtime_multiplier FROM attendance_settings LIMIT 1")) {
            $row = $r->fetch_assoc();
            if ($row) {
                if (isset($row['regular_overtime_multiplier'])) {
                    $regularOtMultiplier = max(0.0, (float)$row['regular_overtime_multiplier']);
                }
                if (isset($row['holiday_overtime_multiplier'])) {
                    $holidayOtMultiplier = max(0.0, (float)$row['holiday_overtime_multiplier']);
                }
            }
        }

        // Load holiday multipliers from payroll_settings
        $phRegular = 2.0;       // Worked on regular holiday
        $phRegularOT = 2.6;     // OT on regular holiday (reserved for future use)
        $phSpecNon = 1.3;       // Worked on special non-working
        $phSpecNonOT = 1.69;    // OT on special non-working (reserved)
        $phSpecWork = 1.3;      // Worked on special working
        $phSpecWorkOT = 1.69;   // OT on special working (reserved)

        if ($r = $mysqli->query("SELECT regular_holiday_rate, regular_holiday_ot_rate, special_nonworking_rate, special_nonworking_ot_rate, special_working_rate, special_working_ot_rate FROM payroll_settings WHERE id = 1 LIMIT 1")) {
            $row = $r->fetch_assoc();
            if ($row) {
                $phRegular     = max(0.0, (float)($row['regular_holiday_rate'] ?? $phRegular));
                $phRegularOT   = max(0.0, (float)($row['regular_holiday_ot_rate'] ?? $phRegularOT));
                $phSpecNon     = max(0.0, (float)($row['special_nonworking_rate'] ?? $phSpecNon));
                $phSpecNonOT   = max(0.0, (float)($row['special_nonworking_ot_rate'] ?? $phSpecNonOT));
                $phSpecWork    = max(0.0, (float)($row['special_working_rate'] ?? $phSpecWork));
                $phSpecWorkOT  = max(0.0, (float)($row['special_working_ot_rate'] ?? $phSpecWorkOT));
            }
        }

        // Preload holidays in the period into a date => type map
        $holidaysByDate = [];
        if ($hStmt = $mysqli->prepare("SELECT type, start_date, end_date FROM holidays WHERE end_date >= ? AND start_date <= ?")) {
            $hStmt->bind_param('ss', $start, $end);
            if ($hStmt->execute()) {
                $hRes = $hStmt->get_result();
                while ($h = $hRes->fetch_assoc()) {
                    $hs = new DateTime($h['start_date']);
                    $he = new DateTime($h['end_date']);
                    for ($d = clone $hs; $d <= $he; $d->modify('+1 day')) {
                        $holidaysByDate[$d->format('Y-m-d')] = $h['type'];
                    }
                }
            }
            $hStmt->close();
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

            // Determine working hours per day for this role/employee
            $roleHoursPerDay = (float)($emp['whpd'] ?? 0);
            if ($roleHoursPerDay <= 0) {
                $roleHoursPerDay = $chpd;
            }

            // Fetch attendance logs in the window and index by date
            $stmt = $mysqli->prepare("SELECT date, time_in, time_out, status FROM attendance_logs WHERE employee_id = ? AND date BETWEEN ? AND ?");
            $stmt->bind_param('iss', $empId, $start, $end);
            $stmt->execute();
            $logsRes = $stmt->get_result();
            $attendanceByDate = [];
            while ($log = $logsRes->fetch_assoc()) {
                $attendanceByDate[$log['date']] = $log;
            }
            $stmt->close();

            // Fetch approved leave requests in the window and index by date
            $leaveByDate = [];
            if ($lStmt = $mysqli->prepare("SELECT leave_type, start_date, end_date, status FROM leave_requests WHERE employee_id = ? AND status = 'Approved' AND end_date >= ? AND start_date <= ?")) {
                $lStmt->bind_param('iss', $empId, $start, $end);
                if ($lStmt->execute()) {
                    $lRes = $lStmt->get_result();
                    while ($lr = $lRes->fetch_assoc()) {
                        $ls = new DateTime($lr['start_date']);
                        $le = new DateTime($lr['end_date']);
                        for ($d = clone $ls; $d <= $le; $d->modify('+1 day')) {
                            $leaveByDate[$d->format('Y-m-d')] = $lr['leave_type'];
                        }
                    }
                }
                $lStmt->close();
            }

            // Fetch approved overtime (Approved or AutoApproved) in the window and index by date
            $otByDate = [];
            if ($otStmt = $mysqli->prepare("SELECT date, approved_ot_minutes, status FROM overtime_requests WHERE employee_id = ? AND date BETWEEN ? AND ? AND status IN ('Approved','AutoApproved')")) {
                $otStmt->bind_param('iss', $empId, $start, $end);
                if ($otStmt->execute()) {
                    $otRes = $otStmt->get_result();
                    while ($ot = $otRes->fetch_assoc()) {
                        $d = $ot['date'];
                        $mins = (int)($ot['approved_ot_minutes'] ?? 0);
                        if ($mins > 0) {
                            if (!isset($otByDate[$d])) {
                                $otByDate[$d] = 0;
                            }
                            $otByDate[$d] += $mins;
                        }
                    }
                }
                $otStmt->close();
            }

            // Walk each date in the payroll window and compute a day-equivalent multiplier
            $periodStart = new DateTime($start);
            $periodEnd = new DateTime($end);
            $totalDayEquivalent = 0.0;
            $totalOtPay = 0.0;

            for ($d = clone $periodStart; $d <= $periodEnd; $d->modify('+1 day')) {
                $dateStr = $d->format('Y-m-d');

                $holidayType = $holidaysByDate[$dateStr] ?? null; // 'regular', 'special_non_working', 'special_working'
                $leaveType = $leaveByDate[$dateStr] ?? null;       // 'Paid', 'Unpaid', 'Sick'
                $log = $attendanceByDate[$dateStr] ?? null;

                $status = $log['status'] ?? '';
                $timeIn = $log['time_in'] ?? null;
                $timeOut = $log['time_out'] ?? null;
                $worked = !empty($timeIn) && $status !== 'Absent';

                $approvedOtMinutes = isset($otByDate[$dateStr]) ? (int)$otByDate[$dateStr] : 0;

                $mult = 0.0;

                if ($holidayType) {
                    // Holiday rules take precedence over normal/leave rules
                    if ($holidayType === 'regular') {
                        if ($worked) {
                            // Worked on regular holiday: 200% by default
                            $mult = $phRegular;
                        } else {
                            // Absent on regular holiday: paid 100%
                            $mult = 1.0;
                        }
                    } elseif ($holidayType === 'special_non_working') {
                        if ($worked) {
                            // Worked on special non-working: 130%
                            $mult = $phSpecNon;
                        } else {
                            // No work = no pay (unless company policy differs)
                            $mult = 0.0;
                        }
                    } elseif ($holidayType === 'special_working') {
                        if ($worked) {
                            // Special working day: treated as normal day with 30% premium
                            $mult = $phSpecWork;
                        } else {
                            // Absent on special working: no pay (treated as normal day)
                            $mult = 0.0;
                        }
                    }
                } else {
                    // Normal day: consider leave and attendance
                    if ($leaveType === 'Paid' || $leaveType === 'Sick') {
                        // Approved paid or sick leave: 100% pay even without time-in
                        $mult = 1.0;
                    } elseif ($leaveType === 'Unpaid') {
                        // Unpaid leave day: no pay
                        $mult = 0.0;
                    } else {
                        // No leave recorded: pay only if worked
                        if ($worked) {
                            $mult = 1.0;
                        } else {
                            $mult = 0.0;
                        }
                    }
                }

                $totalDayEquivalent += $mult;

                // Compute overtime pay for this date (if any approved OT minutes)
                if ($approvedOtMinutes > 0 && $ratePerHour > 0) {
                    $otHours = $approvedOtMinutes / 60.0;
                    $otMult = $regularOtMultiplier;
                    if ($holidayType === 'regular') {
                        $otMult = $phRegularOT;
                    } elseif ($holidayType === 'special_non_working') {
                        $otMult = $phSpecNonOT;
                    } elseif ($holidayType === 'special_working') {
                        $otMult = $phSpecWorkOT;
                    }
                    if ($otMult < 0.0) {
                        $otMult = 0.0;
                    }
                    $totalOtPay += $otHours * $ratePerHour * $otMult;
                }
            }

            // Determine gross using day-equivalent logic
            $grossBase = 0.0;
            $hoursOrDaysLabel = '';
            if ($ratePerDay > 0) {
                $grossBase = $totalDayEquivalent * $ratePerDay;
                $hoursOrDaysLabel = number_format($totalDayEquivalent, 2) . ' day' . ($totalDayEquivalent == 1.0 ? '' : 's');
            } else {
                // Fall back to hourly: convert day-equivalent to hours using working hours per day
                $equivHours = $totalDayEquivalent * $roleHoursPerDay;
                $grossBase = $equivHours * $ratePerHour;
                $hoursOrDaysLabel = number_format($equivHours, 2) . ' hrs';
            }

            $gross = $grossBase + $totalOtPay;

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
