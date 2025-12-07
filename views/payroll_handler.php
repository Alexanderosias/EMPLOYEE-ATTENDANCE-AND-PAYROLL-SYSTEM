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

        // Load tax and government deduction settings (same model as preview)
        $taxAuto = true;
        $phRate = 5.0;
        $phMin = 500.0;
        $phMax = 5000.0;
        $phSplit = 1;
        $pagibigThreshold = 1500.0;
        $pagibigLowRate = 1.0;
        $pagibigHighRate = 2.0;
        $sssMscMin = 10000.0;
        $sssMscMax = 20000.0;
        $sssEe = 450.0;
        $incomeTaxRate = 10.0;
        $taxRule = 'flat';

        if ($r = $mysqli->query("SELECT philhealth_rate, philhealth_min, philhealth_max, philhealth_split_5050, pagibig_threshold, pagibig_employee_low_rate, pagibig_employee_high_rate, sss_msc_min, sss_msc_max, sss_ee_contribution, income_tax_rate, tax_calculation_rule, auto_apply_deductions FROM tax_deduction_settings WHERE id = 1 LIMIT 1")) {
            $row = $r->fetch_assoc();
            if ($row) {
                $taxAuto = (int)($row['auto_apply_deductions'] ?? 1) === 1;
                $phRate = (float)($row['philhealth_rate'] ?? $phRate);
                $phMin = (float)($row['philhealth_min'] ?? $phMin);
                $phMax = (float)($row['philhealth_max'] ?? $phMax);
                $phSplit = (int)($row['philhealth_split_5050'] ?? $phSplit);
                $pagibigThreshold = (float)($row['pagibig_threshold'] ?? $pagibigThreshold);
                $pagibigLowRate = (float)($row['pagibig_employee_low_rate'] ?? $pagibigLowRate);
                $pagibigHighRate = (float)($row['pagibig_employee_high_rate'] ?? $pagibigHighRate);
                $sssMscMin = (float)($row['sss_msc_min'] ?? $sssMscMin);
                $sssMscMax = (float)($row['sss_msc_max'] ?? $sssMscMax);
                $sssEe = (float)($row['sss_ee_contribution'] ?? $sssEe);
                $incomeTaxRate = (float)($row['income_tax_rate'] ?? $incomeTaxRate);
                $taxRule = $row['tax_calculation_rule'] ?? $taxRule;
            }
        }

        // Load tax and government deduction settings (same model as preview)
        $taxAuto = true;
        $phRate = 5.0;
        $phMin = 500.0;
        $phMax = 5000.0;
        $phSplit = 1;
        $pagibigThreshold = 1500.0;
        $pagibigLowRate = 1.0;
        $pagibigHighRate = 2.0;
        $sssMscMin = 10000.0;
        $sssMscMax = 20000.0;
        $sssEe = 450.0;
        $incomeTaxRate = 10.0;
        $taxRule = 'flat';

        if ($r = $mysqli->query("SELECT philhealth_rate, philhealth_min, philhealth_max, philhealth_split_5050, pagibig_threshold, pagibig_employee_low_rate, pagibig_employee_high_rate, sss_msc_min, sss_msc_max, sss_ee_contribution, income_tax_rate, tax_calculation_rule, auto_apply_deductions FROM tax_deduction_settings WHERE id = 1 LIMIT 1")) {
            $row = $r->fetch_assoc();
            if ($row) {
                $taxAuto = (int)($row['auto_apply_deductions'] ?? 1) === 1;
                $phRate = (float)($row['philhealth_rate'] ?? $phRate);
                $phMin = (float)($row['philhealth_min'] ?? $phMin);
                $phMax = (float)($row['philhealth_max'] ?? $phMax);
                $phSplit = (int)($row['philhealth_split_5050'] ?? $phSplit);
                $pagibigThreshold = (float)($row['pagibig_threshold'] ?? $pagibigThreshold);
                $pagibigLowRate = (float)($row['pagibig_employee_low_rate'] ?? $pagibigLowRate);
                $pagibigHighRate = (float)($row['pagibig_employee_high_rate'] ?? $pagibigHighRate);
                $sssMscMin = (float)($row['sss_msc_min'] ?? $sssMscMin);
                $sssMscMax = (float)($row['sss_msc_max'] ?? $sssMscMax);
                $sssEe = (float)($row['sss_ee_contribution'] ?? $sssEe);
                $incomeTaxRate = (float)($row['income_tax_rate'] ?? $incomeTaxRate);
                $taxRule = $row['tax_calculation_rule'] ?? $taxRule;
            }
        }

        // Load tax and government deduction settings
        $taxAuto = true;
        $phRate = 5.0;
        $phMin = 500.0;
        $phMax = 5000.0;
        $phSplit = 1;
        $pagibigThreshold = 1500.0;
        $pagibigLowRate = 1.0;
        $pagibigHighRate = 2.0;
        $sssMscMin = 10000.0;
        $sssMscMax = 20000.0;
        $sssEe = 450.0;
        $incomeTaxRate = 10.0;
        $taxRule = 'flat';

        if ($r = $mysqli->query("SELECT philhealth_rate, philhealth_min, philhealth_max, philhealth_split_5050, pagibig_threshold, pagibig_employee_low_rate, pagibig_employee_high_rate, sss_msc_min, sss_msc_max, sss_ee_contribution, income_tax_rate, tax_calculation_rule, auto_apply_deductions FROM tax_deduction_settings WHERE id = 1 LIMIT 1")) {
            $row = $r->fetch_assoc();
            if ($row) {
                $taxAuto = (int)($row['auto_apply_deductions'] ?? 1) === 1;
                $phRate = (float)($row['philhealth_rate'] ?? $phRate);
                $phMin = (float)($row['philhealth_min'] ?? $phMin);
                $phMax = (float)($row['philhealth_max'] ?? $phMax);
                $phSplit = (int)($row['philhealth_split_5050'] ?? $phSplit);
                $pagibigThreshold = (float)($row['pagibig_threshold'] ?? $pagibigThreshold);
                $pagibigLowRate = (float)($row['pagibig_employee_low_rate'] ?? $pagibigLowRate);
                $pagibigHighRate = (float)($row['pagibig_employee_high_rate'] ?? $pagibigHighRate);
                $sssMscMin = (float)($row['sss_msc_min'] ?? $sssMscMin);
                $sssMscMax = (float)($row['sss_msc_max'] ?? $sssMscMax);
                $sssEe = (float)($row['sss_ee_contribution'] ?? $sssEe);
                $incomeTaxRate = (float)($row['income_tax_rate'] ?? $incomeTaxRate);
                $taxRule = $row['tax_calculation_rule'] ?? $taxRule;
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
            $empRoleId = (int)($emp['role_id'] ?? 0);
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

            // Apply manual deductions with scope (per employee / per role / global)
            $perEmpDeduction = 0.0;
            foreach ($deductions as $d) {
                if (!is_array($d)) continue;
                $amt = (float)($d['amount'] ?? 0);
                if ($amt <= 0) continue;

                $scope = strtolower(trim($d['scope'] ?? 'per_employee'));
                $roles = $d['roles'] ?? [];
                if (!is_array($roles)) {
                    $roles = [];
                }
                $roles = array_map('intval', $roles);

                $apply = false;
                if ($scope === 'per_role') {
                    // If no roles specified, treat as all roles
                    if (empty($roles)) {
                        $apply = true;
                    } else {
                        $apply = in_array($empRoleId, $roles, true);
                    }
                } elseif ($scope === 'global') {
                    $apply = true;
                } else {
                    // "per_employee" or unknown: apply to each employee row
                    $apply = true;
                }

                if ($apply) {
                    $perEmpDeduction += $amt;
                }
            }

            // Automatic government deductions based on tax_deduction_settings
            $philhealth = 0.0;
            $sss = 0.0;
            $pagibig = 0.0;
            $incomeTax = 0.0;

            if ($taxAuto) {
                $baseGross = max(0.0, (float)$gross);

                // PhilHealth (clamped with optional 50/50 split)
                if ($phRate > 0.0 && $baseGross > 0.0) {
                    $phFull = $baseGross * ($phRate / 100.0);
                    if ($phMin > 0.0 && $phFull < $phMin) $phFull = $phMin;
                    if ($phMax > 0.0 && $phFull > $phMax) $phFull = $phMax;
                    if ($phSplit === 1) {
                        $philhealth = $phFull * 0.5;
                    } else {
                        $philhealth = $phFull;
                    }
                }

                // Pag-IBIG (tiered rate)
                if ($baseGross > 0.0) {
                    $piRate = $baseGross <= $pagibigThreshold ? $pagibigLowRate : $pagibigHighRate;
                    if ($piRate > 0.0) {
                        $pagibig = $baseGross * ($piRate / 100.0);
                    }
                }

                // SSS (simple banded contribution around MSC min/max)
                if ($sssEe > 0.0 && $baseGross > 0.0) {
                    if ($baseGross < $sssMscMin && $sssMscMin > 0.0) {
                        $sss = $sssEe * ($baseGross / $sssMscMin);
                    } elseif ($baseGross > $sssMscMax && $sssMscMax > 0.0) {
                        $sss = $sssEe;
                    } else {
                        $sss = $sssEe;
                    }
                }

                // Income tax (flat rule only for now)
                $taxableBase = max(0.0, $baseGross - $philhealth - $pagibig - $sss);
                if ($taxRule === 'flat' && $incomeTaxRate > 0.0 && $taxableBase > 0.0) {
                    $incomeTax = $taxableBase * ($incomeTaxRate / 100.0);
                }
            }

            $totalDeductionsRow = $perEmpDeduction + $philhealth + $sss + $pagibig + $incomeTax;
            $net = max(0.0, $gross - $totalDeductionsRow);

            $rows[] = [
                'employee' => $empName,
                'role' => $roleName,
                'hours_days' => $hoursOrDaysLabel,
                'gross' => fmt2($gross),
                'deductions' => fmt2($totalDeductionsRow),
                'net' => fmt2($net),
                'status' => 'Included',
                'philhealth' => fmt2($philhealth),
                'sss' => fmt2($sss),
                'pagibig' => fmt2($pagibig),
                'tax' => fmt2($incomeTax),
                'manual_other' => fmt2($perEmpDeduction),
            ];

            $sumGross += $gross;
            $sumDeductions += $totalDeductionsRow;
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

    if ($action === 'finalize_payroll') {
        $roles = $_SESSION['roles'] ?? [];
        if (!in_array('head_admin', $roles, true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $start = $input['start'] ?? ($_POST['start'] ?? '');
        $end   = $input['end'] ?? ($_POST['end'] ?? '');
        $roleId = isset($input['role_id']) ? (int)$input['role_id'] : (isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0);
        $deductions = $input['deductions'] ?? [];

        $force = false;
        if (array_key_exists('force', $input)) {
            $force = (bool)$input['force'];
        } elseif (isset($_POST['force'])) {
            $force = (bool)$_POST['force'];
        }

        if (!$start || !$end) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'start and end are required']);
            exit;
        }

        $existingCount = 0;
        if ($roleId > 0) {
            if ($stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt
                                          FROM payroll p
                                          JOIN employees e ON e.id = p.employee_id
                                          JOIN job_positions jp ON e.job_position_id = jp.id
                                          WHERE p.payroll_period_start = ? AND p.payroll_period_end = ? AND jp.id = ?")) {
                $stmt->bind_param('ssi', $start, $end, $roleId);
                if ($stmt->execute()) {
                    $resCnt = $stmt->get_result()->fetch_assoc();
                    $existingCount = (int)($resCnt['cnt'] ?? 0);
                }
                $stmt->close();
            }
        } else {
            if ($stmt = $mysqli->prepare("SELECT COUNT(*) AS cnt FROM payroll p WHERE p.payroll_period_start = ? AND p.payroll_period_end = ?")) {
                $stmt->bind_param('ss', $start, $end);
                if ($stmt->execute()) {
                    $resCnt = $stmt->get_result()->fetch_assoc();
                    $existingCount = (int)($resCnt['cnt'] ?? 0);
                }
                $stmt->close();
            }
        }

        if ($existingCount > 0 && !$force) {
            echo json_encode([
                'success' => false,
                'code' => 'EXISTING_PERIOD',
                'message' => 'There are already payroll records saved for this period.',
                'data' => [
                    'count' => $existingCount,
                    'start' => $start,
                    'end' => $end,
                ],
            ]);
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

        $inserted = 0;
        $updated = 0;

        while ($emp = $res->fetch_assoc()) {
            $empId = (int)$emp['emp_id'];
            $empRoleId = (int)($emp['role_id'] ?? 0);

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
            if ($ratePerDay > 0) {
                $grossBase = $totalDayEquivalent * $ratePerDay;
            } else {
                // Fall back to hourly: convert day-equivalent to hours using working hours per day
                $equivHours = $totalDayEquivalent * $roleHoursPerDay;
                $grossBase = $equivHours * $ratePerHour;
            }

            $gross = $grossBase + $totalOtPay;

            // Apply manual deductions with scope (per employee / per role / global)
            $perEmpDeduction = 0.0;
            foreach ($deductions as $d) {
                if (!is_array($d)) continue;
                $amt = (float)($d['amount'] ?? 0);
                if ($amt <= 0) continue;

                $scope = strtolower(trim($d['scope'] ?? 'per_employee'));
                $roles = $d['roles'] ?? [];
                if (!is_array($roles)) {
                    $roles = [];
                }
                $roles = array_map('intval', $roles);

                $apply = false;
                if ($scope === 'per_role') {
                    // If no roles specified, treat as all roles
                    if (empty($roles)) {
                        $apply = true;
                    } else {
                        $apply = in_array($empRoleId, $roles, true);
                    }
                } elseif ($scope === 'global') {
                    $apply = true;
                } else {
                    // "per_employee" or unknown: apply to each employee row
                    $apply = true;
                }

                if ($apply) {
                    $perEmpDeduction += $amt;
                }
            }

            // Automatic government deductions based on tax_deduction_settings
            $philhealth = 0.0;
            $sss = 0.0;
            $pagibig = 0.0;
            $incomeTax = 0.0;

            if ($taxAuto) {
                $baseGross = max(0.0, (float)$gross);

                // PhilHealth (clamped with optional 50/50 split)
                if ($phRate > 0.0 && $baseGross > 0.0) {
                    $phFull = $baseGross * ($phRate / 100.0);
                    if ($phMin > 0.0 && $phFull < $phMin) $phFull = $phMin;
                    if ($phMax > 0.0 && $phFull > $phMax) $phFull = $phMax;
                    if ($phSplit === 1) {
                        $philhealth = $phFull * 0.5;
                    } else {
                        $philhealth = $phFull;
                    }
                }

                // Pag-IBIG (tiered rate)
                if ($baseGross > 0.0) {
                    $piRate = $baseGross <= $pagibigThreshold ? $pagibigLowRate : $pagibigHighRate;
                    if ($piRate > 0.0) {
                        $pagibig = $baseGross * ($piRate / 100.0);
                    }
                }

                // SSS (simple banded contribution around MSC min/max)
                if ($sssEe > 0.0 && $baseGross > 0.0) {
                    if ($baseGross < $sssMscMin && $sssMscMin > 0.0) {
                        $sss = $sssEe * ($baseGross / $sssMscMin);
                    } elseif ($baseGross > $sssMscMax && $sssMscMax > 0.0) {
                        $sss = $sssEe;
                    } else {
                        $sss = $sssEe;
                    }
                }

                // Income tax (flat rule only for now)
                $taxableBase = max(0.0, $baseGross - $philhealth - $pagibig - $sss);
                if ($taxRule === 'flat' && $incomeTaxRate > 0.0 && $taxableBase > 0.0) {
                    $incomeTax = $taxableBase * ($incomeTaxRate / 100.0);
                }
            }

            // Store income tax together with other/manual deductions for now
            $other = $perEmpDeduction + $incomeTax;

            // Upsert into payroll table for this employee and period
            $check = $mysqli->prepare("SELECT id FROM payroll WHERE employee_id = ? AND payroll_period_start = ? AND payroll_period_end = ? LIMIT 1");
            $check->bind_param('iss', $empId, $start, $end);
            $check->execute();
            $existing = $check->get_result()->fetch_assoc();
            $check->close();

            if ($existing) {
                $pid = (int)$existing['id'];
                $upd = $mysqli->prepare("UPDATE payroll SET gross_pay = ?, philhealth_deduction = ?, sss_deduction = ?, pagibig_deduction = ?, other_deductions = ?, paid_status = 'Unpaid', payment_date = NULL WHERE id = ?");
                $upd->bind_param('dddddi', $gross, $philhealth, $sss, $pagibig, $other, $pid);
                $upd->execute();
                $upd->close();
                $updated++;
            } else {
                $ins = $mysqli->prepare("INSERT INTO payroll (employee_id, payroll_period_start, payroll_period_end, gross_pay, philhealth_deduction, sss_deduction, pagibig_deduction, other_deductions, paid_status, payment_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Unpaid', NULL)");
                $ins->bind_param('issddddd', $empId, $start, $end, $gross, $philhealth, $sss, $pagibig, $other);
                $ins->execute();
                $ins->close();
                $inserted++;
            }
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'inserted' => $inserted,
                'updated' => $updated,
                'total' => $inserted + $updated,
                'start' => $start,
                'end' => $end,
            ],
            'message' => 'Payroll finalized.',
        ]);
        exit;
    }

    if ($action === 'list_period_payroll') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $start = $input['start'] ?? ($_POST['start'] ?? '');
        $end   = $input['end'] ?? ($_POST['end'] ?? '');
        $roleId = isset($input['role_id']) ? (int)$input['role_id'] : (isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0);

        if (!$start || !$end) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'start and end are required']);
            exit;
        }

        $rows = [];

        if ($roleId > 0) {
            if ($stmt = $mysqli->prepare("SELECT p.id, p.payroll_period_start, p.payroll_period_end, p.net_pay, p.paid_status, p.payment_date, e.first_name, e.last_name, jp.name AS role_name
                                           FROM payroll p
                                           JOIN employees e ON e.id = p.employee_id
                                           JOIN job_positions jp ON e.job_position_id = jp.id
                                           WHERE p.payroll_period_start = ? AND p.payroll_period_end = ? AND jp.id = ?
                                           ORDER BY e.last_name, e.first_name")) {
                $stmt->bind_param('ssi', $start, $end, $roleId);
            } else {
                $stmt = null;
            }
        } else {
            if ($stmt = $mysqli->prepare("SELECT p.id, p.payroll_period_start, p.payroll_period_end, p.net_pay, p.paid_status, p.payment_date, e.first_name, e.last_name, jp.name AS role_name
                                           FROM payroll p
                                           JOIN employees e ON e.id = p.employee_id
                                           JOIN job_positions jp ON e.job_position_id = jp.id
                                           WHERE p.payroll_period_start = ? AND p.payroll_period_end = ?
                                           ORDER BY e.last_name, e.first_name")) {
                $stmt->bind_param('ss', $start, $end);
            } else {
                $stmt = null;
            }
        }

        if ($stmt) {
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                while ($r = $res->fetch_assoc()) {
                    $name = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
                    $rows[] = [
                        'id' => (int)$r['id'],
                        'employee' => $name,
                        'role' => $r['role_name'] ?? '',
                        'period' => ($r['payroll_period_start'] ?? '') . ' to ' . ($r['payroll_period_end'] ?? ''),
                        'net' => fmt2((float)($r['net_pay'] ?? 0)),
                        'paid_status' => $r['paid_status'] ?? 'Unpaid',
                        'payment_date' => $r['payment_date'],
                    ];
                }
            }
            $stmt->close();
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'rows' => $rows,
            ],
        ]);
        exit;
    }

    if ($action === 'mark_payroll_paid') {
        $roles = $_SESSION['roles'] ?? [];
        if (!in_array('head_admin', $roles, true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = isset($input['id']) ? (int)$input['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);

        if ($id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid payroll id']);
            exit;
        }

        $affected = 0;
        if ($stmt = $mysqli->prepare("UPDATE payroll SET paid_status = 'Paid', payment_date = IFNULL(payment_date, CURDATE()) WHERE id = ?")) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
        }

        if ($affected <= 0) {
            echo json_encode(['success' => false, 'message' => 'No payroll record updated']);
            exit;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'id' => $id,
                'updated' => $affected,
            ],
            'message' => 'Payroll marked as Paid.',
        ]);
        exit;
    }

    if ($action === 'mark_period_paid') {
        $roles = $_SESSION['roles'] ?? [];
        if (!in_array('head_admin', $roles, true)) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $start = $input['start'] ?? ($_POST['start'] ?? '');
        $end   = $input['end'] ?? ($_POST['end'] ?? '');
        $roleId = isset($input['role_id']) ? (int)$input['role_id'] : (isset($_POST['role_id']) ? (int)$_POST['role_id'] : 0);

        if (!$start || !$end) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'start and end are required']);
            exit;
        }

        $affected = 0;

        if ($roleId > 0) {
            if ($stmt = $mysqli->prepare("UPDATE payroll p
                                           JOIN employees e ON e.id = p.employee_id
                                           JOIN job_positions jp ON e.job_position_id = jp.id
                                           SET p.paid_status = 'Paid', p.payment_date = IFNULL(p.payment_date, CURDATE())
                                           WHERE p.payroll_period_start = ? AND p.payroll_period_end = ? AND jp.id = ? AND p.paid_status = 'Unpaid'")) {
                $stmt->bind_param('ssi', $start, $end, $roleId);
                $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();
            }
        } else {
            if ($stmt = $mysqli->prepare("UPDATE payroll p
                                           SET p.paid_status = 'Paid', p.payment_date = IFNULL(p.payment_date, CURDATE())
                                           WHERE p.payroll_period_start = ? AND p.payroll_period_end = ? AND p.paid_status = 'Unpaid'")) {
                $stmt->bind_param('ss', $start, $end);
                $stmt->execute();
                $affected = $stmt->affected_rows;
                $stmt->close();
            }
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'updated' => $affected,
                'start' => $start,
                'end' => $end,
                'role_id' => $roleId,
            ],
            'message' => $affected > 0 ? 'Payroll period marked as Paid.' : 'No payroll records were updated.',
        ]);
        exit;
    }

    if ($action === 'thirteenth_preview') {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $year = isset($input['year']) ? (int)$input['year'] : (isset($_POST['year']) ? (int)$_POST['year'] : (int)($_GET['year'] ?? (int)date('Y')));
        if ($year < 1970 || $year > 2100) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid year']);
            exit;
        }

        $asOf = $input['as_of'] ?? ($_POST['as_of'] ?? ($_GET['as_of'] ?? ''));

        $roleId = isset($input['role_id']) ? (int)$input['role_id'] : (isset($_POST['role_id']) ? (int)$_POST['role_id'] : (int)($_GET['role_id'] ?? 0));

        $yearStartDt = new DateTime($year . '-01-01');
        $yearEndDt = new DateTime($year . '-12-31');
        $endDt = clone $yearEndDt;
        if (!empty($asOf)) {
            try {
                $asOfDt = new DateTime($asOf);
                if ($asOfDt < $yearStartDt) {
                    $endDt = clone $yearStartDt;
                } elseif ($asOfDt > $yearEndDt) {
                    $endDt = clone $yearEndDt;
                } else {
                    $endDt = $asOfDt;
                }
            } catch (Exception $e) {
                // ignore invalid as_of and fall back to year end
            }
        }

        $start = $yearStartDt->format('Y-m-d');
        $end = $endDt->format('Y-m-d');

        // Load company_hours_per_day for day-equivalent calc (fallback for roles)
        $chpd = 8.0;
        if ($r = $mysqli->query("SELECT company_hours_per_day FROM time_date_settings WHERE id = 1 LIMIT 1")) {
            $row = $r->fetch_assoc();
            $val = (float)($row['company_hours_per_day'] ?? 8.0);
            if ($val > 0) $chpd = $val;
        }

        // Load holiday multipliers from payroll_settings (same as preview)
        $phRegular = 2.0;
        $phSpecNon = 1.3;
        $phSpecWork = 1.3;
        if ($r = $mysqli->query("SELECT regular_holiday_rate, special_nonworking_rate, special_working_rate FROM payroll_settings WHERE id = 1 LIMIT 1")) {
            $row = $r->fetch_assoc();
            if ($row) {
                $phRegular   = max(0.0, (float)($row['regular_holiday_rate'] ?? $phRegular));
                $phSpecNon   = max(0.0, (float)($row['special_nonworking_rate'] ?? $phSpecNon));
                $phSpecWork  = max(0.0, (float)($row['special_working_rate'] ?? $phSpecWork));
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
        $sumBasic = 0.0;
        $sumThirteenth = 0.0;

        while ($emp = $res->fetch_assoc()) {
            $empId = (int)$emp['emp_id'];
            $empName = trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
            $roleName = $emp['role_name'] ?? '';

            $ratePerDay = (float)($emp['e_rpd'] ?? 0);
            if ($ratePerDay <= 0) $ratePerDay = (float)($emp['jp_rpd'] ?? 0);
            $ratePerHour = (float)($emp['e_rph'] ?? 0);
            if ($ratePerHour <= 0) $ratePerHour = (float)($emp['jp_rph'] ?? 0);

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

            $periodStart = new DateTime($start);
            $periodEnd = new DateTime($end);
            $totalDayEquivalent = 0.0;

            for ($d = clone $periodStart; $d <= $periodEnd; $d->modify('+1 day')) {
                $dateStr = $d->format('Y-m-d');

                $holidayType = $holidaysByDate[$dateStr] ?? null;
                $leaveType = $leaveByDate[$dateStr] ?? null;
                $log = $attendanceByDate[$dateStr] ?? null;

                $status = $log['status'] ?? '';
                $timeIn = $log['time_in'] ?? null;
                $worked = !empty($timeIn) && $status !== 'Absent';

                $mult = 0.0;

                if ($holidayType) {
                    if ($holidayType === 'regular') {
                        if ($worked) {
                            $mult = $phRegular;
                        } else {
                            $mult = 1.0;
                        }
                    } elseif ($holidayType === 'special_non_working') {
                        if ($worked) {
                            $mult = $phSpecNon;
                        } else {
                            $mult = 0.0;
                        }
                    } elseif ($holidayType === 'special_working') {
                        if ($worked) {
                            $mult = $phSpecWork;
                        } else {
                            $mult = 0.0;
                        }
                    }
                } else {
                    if ($leaveType === 'Paid' || $leaveType === 'Sick') {
                        $mult = 1.0;
                    } elseif ($leaveType === 'Unpaid') {
                        $mult = 0.0;
                    } else {
                        if ($worked) {
                            $mult = 1.0;
                        } else {
                            $mult = 0.0;
                        }
                    }
                }

                $totalDayEquivalent += $mult;
            }

            $totalBasicYear = 0.0;
            if ($ratePerDay > 0) {
                $totalBasicYear = $totalDayEquivalent * $ratePerDay;
            } else {
                $equivHours = $totalDayEquivalent * $roleHoursPerDay;
                $totalBasicYear = $equivHours * $ratePerHour;
            }

            $thirteenth = $totalBasicYear / 12.0;

            $rows[] = [
                'employee' => $empName,
                'role' => $roleName,
                'basic_year' => fmt2($totalBasicYear),
                'thirteenth' => fmt2($thirteenth),
            ];

            $sumBasic += $totalBasicYear;
            $sumThirteenth += $thirteenth;
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'year' => $year,
                'start' => $start,
                'end' => $end,
                'summary' => [
                    'total_basic' => fmt2($sumBasic),
                    'total_thirteenth' => fmt2($sumThirteenth),
                ],
                'rows' => $rows,
            ],
        ]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
