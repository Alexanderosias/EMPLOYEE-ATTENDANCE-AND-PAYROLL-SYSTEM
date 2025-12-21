<?php
// Enable error reporting for debugging (remove in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors as HTML
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log'); // Log errors to a file
require_once 'auth.php'; // Ensure authentication
require_once 'conn.php'; // Database connection
$db = conn(); // Call the function
$mysqli = $db['mysqli']; // Extract mysqli
header('Content-Type: application/json');
// Suppress any HTML output
ob_start();
// Fix: Check POST for action if it's a POST request, otherwise GET
$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
} else {
  $action = $_GET['action'] ?? '';
}

function computeEffectiveLeaveDays($mysqli, $employeeId, $startDateStr, $endDateStr)
{
  $calendarDays = 0;
  try {
    $startDate = new DateTime($startDateStr);
    $endDate = new DateTime($endDateStr);
    if ($startDate > $endDate) {
      $tmp = $startDate;
      $startDate = $endDate;
      $endDate = $tmp;
    }

    $calendarDays = $startDate->diff($endDate)->days + 1;
    $effectiveDays = $calendarDays;

    // Use employee_schedules as the canonical schedule table
    $stmt = $mysqli->prepare("SELECT DISTINCT day_of_week FROM employee_schedules WHERE employee_id = ? AND is_working = 1");
    if ($stmt) {
      $stmt->bind_param('i', $employeeId);
      $stmt->execute();
      $schedResult = $stmt->get_result();
      $workingDays = [];
      while ($row = $schedResult->fetch_assoc()) {
        $workingDays[$row['day_of_week']] = true;
      }
      $stmt->close();

      $specialDates = [];

      $stmt = $mysqli->prepare("SELECT start_date, end_date FROM holidays WHERE NOT (end_date < ? OR start_date > ?)");
      if ($stmt) {
        $stmt->bind_param('ss', $startDateStr, $endDateStr);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
          $hStart = new DateTime($row['start_date']);
          $hEnd = new DateTime($row['end_date']);
          if ($hStart > $hEnd) {
            $tmpH = $hStart;
            $hStart = $hEnd;
            $hEnd = $tmpH;
          }
          $hEndInclusive = (clone $hEnd)->modify('+1 day');
          $hPeriod = new DatePeriod($hStart, new DateInterval('P1D'), $hEndInclusive);
          foreach ($hPeriod as $d) {
            $specialDates[$d->format('Y-m-d')] = true;
          }
        }
        $stmt->close();
      }

      $stmt = $mysqli->prepare("SELECT start_date, end_date FROM special_events WHERE NOT (end_date < ? OR start_date > ?)");
      if ($stmt) {
        $stmt->bind_param('ss', $startDateStr, $endDateStr);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
          $eStart = new DateTime($row['start_date']);
          $eEnd = new DateTime($row['end_date']);
          if ($eStart > $eEnd) {
            $tmpE = $eStart;
            $eStart = $eEnd;
            $eEnd = $tmpE;
          }
          $eEndInclusive = (clone $eEnd)->modify('+1 day');
          $ePeriod = new DatePeriod($eStart, new DateInterval('P1D'), $eEndInclusive);
          foreach ($ePeriod as $d) {
            $specialDates[$d->format('Y-m-d')] = true;
          }
        }
        $stmt->close();
      }

      if (!empty($workingDays)) {
        $effectiveDays = 0;
        $periodEnd = (clone $endDate)->modify('+1 day');
        $period = new DatePeriod($startDate, new DateInterval('P1D'), $periodEnd);
        foreach ($period as $date) {
          $dayName = $date->format('l');
          $dateStrLoop = $date->format('Y-m-d');
          if (!empty($specialDates[$dateStrLoop])) {
            continue;
          }
          if (!empty($workingDays[$dayName])) {
            $effectiveDays++;
          }
        }
      } else {
        // No schedules found; do not deduct anything
        $effectiveDays = 0;
      }

      return $effectiveDays;
    }
  } catch (Exception $e) {
    // On any error, be safe and do not deduct leave days
    return 0;
  }

  // Fallback: if for some reason we did not enter the main branch, do not deduct
  return 0;
}

try {
  switch ($action) {
    case 'today_attendance':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];

      // employees.employee_id is the PK in systemintegration schema
      $stmt = $mysqli->prepare("SELECT employee_id FROM employees WHERE user_id = ? LIMIT 1");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $res = $stmt->get_result();
      $emp = $res->fetch_assoc();
      $stmt->close();
      if (!$emp) {
        throw new Exception('Employee not found');
      }
      $employeeId = (int)$emp['employee_id'];

      $today = date('Y-m-d');

      // attendance_logs uses log_id and attendance_date in systemintegration
      $stmt = $mysqli->prepare("SELECT log_id, attendance_date AS date, time_in, time_out, status, expected_start_time, expected_end_time FROM attendance_logs WHERE employee_id = ? AND attendance_date = ? LIMIT 1");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param('is', $employeeId, $today);
      $stmt->execute();
      $res = $stmt->get_result();
      $log = $res->fetch_assoc();
      $stmt->close();

      $holidayType = null;
      $holidayName = null;
      // holidays uses holiday_name/holiday_type; alias to keep existing PHP field names
      $stmt = $mysqli->prepare("SELECT holiday_name AS name, holiday_type AS type FROM holidays WHERE start_date <= ? AND end_date >= ? LIMIT 1");
      if ($stmt) {
        $stmt->bind_param('ss', $today, $today);
        $stmt->execute();
        $hRes = $stmt->get_result();
        if ($hRow = $hRes->fetch_assoc()) {
          $holidayType = $hRow['type'] ?? null;
          $holidayName = $hRow['name'] ?? null;
        }
        $stmt->close();
      }

      $leaveType = null;
      $leaveSource = null;
      $stmt = $mysqli->prepare("SELECT leave_type, deducted_from FROM leave_requests WHERE employee_id = ? AND status = 'Approved' AND ? BETWEEN start_date AND end_date LIMIT 1");
      if ($stmt) {
        $stmt->bind_param('is', $employeeId, $today);
        $stmt->execute();
        $lRes = $stmt->get_result();
        if ($lRow = $lRes->fetch_assoc()) {
          $leaveType = $lRow['leave_type'] ?? null;
          $leaveSource = $lRow['deducted_from'] ?? null;
        }
        $stmt->close();
      }

      $baseStatus = $log ? ($log['status'] ?? '') : '';
      $displayStatus = $baseStatus ?: 'No record yet today';
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

      $hasLog = $log && (!empty($log['time_in']) || !empty($log['time_out']));

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

      if (!$log && !$leaveType && !$holidayType) {
        $baseStatus = '';
        $displayStatus = 'No record yet today';
      }

      $totalHours = 0.0;
      $timeInFormatted = null;
      $timeOutFormatted = null;
      if ($log) {
        if (!empty($log['time_in'])) {
          $timeInFormatted = date('h:i A', strtotime($log['time_in']));
        }
        if (!empty($log['time_out'])) {
          $timeOutFormatted = date('h:i A', strtotime($log['time_out']));
        }
        if (!empty($log['time_in']) && !empty($log['time_out'])) {
          $inTs = strtotime($log['time_in']);
          $outTs = strtotime($log['time_out']);
          if ($outTs > $inTs) {
            $totalHours = round(($outTs - $inTs) / 3600, 2);
          }
        }
      }

      $lateFlag = ($baseStatus === 'Late');
      $undertimeFlag = ($baseStatus === 'Undertime');

      echo json_encode([
        'success' => true,
        'data' => [
          'status' => $displayStatus,
          'base_status' => $baseStatus,
          'time_in' => $timeInFormatted,
          'time_out' => $timeOutFormatted,
          'total_hours' => $totalHours,
          'late' => $lateFlag,
          'undertime' => $undertimeFlag,
          'holiday_type' => $holidayType,
          'holiday_name' => $holidayName
        ]
      ]);
      break;

    case 'monthly_summary':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];

      // employees.employee_id is PK; alias as id if needed
      $stmt = $mysqli->prepare("SELECT employee_id FROM employees WHERE user_id = ? LIMIT 1");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $res = $stmt->get_result();
      $emp = $res->fetch_assoc();
      $stmt->close();
      if (!$emp) {
        throw new Exception('Employee not found');
      }
      $employeeId = (int)$emp['employee_id'];

      $firstDay = (new DateTime('first day of this month'))->format('Y-m-d');
      $lastDay = (new DateTime('last day of this month'))->format('Y-m-d');

      $holidays = [];
      // holidays use holiday_id/holiday_name/holiday_type; alias to match existing code expectations
      if ($hRes = $mysqli->query("SELECT holiday_id AS id, holiday_name AS name, holiday_type AS type, start_date, end_date FROM holidays")) {
        while ($hRow = $hRes->fetch_assoc()) {
          $holidays[] = $hRow;
        }
        $hRes->free();
      }

      $sql = "SELECT 
                al.log_id AS id,
                al.attendance_date AS date,
                al.time_in AS time_in,
                al.time_out AS time_out,
                al.status,
                lr.leave_type,
                lr.deducted_from AS leave_deducted_from
              FROM attendance_logs al
              LEFT JOIN leave_requests lr
                ON lr.employee_id = al.employee_id
               AND lr.status = 'Approved'
               AND al.attendance_date BETWEEN lr.start_date AND lr.end_date
              WHERE al.employee_id = ? AND al.attendance_date BETWEEN ? AND ?
              ORDER BY al.attendance_date ASC";
      $stmt = $mysqli->prepare($sql);
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param('iss', $employeeId, $firstDay, $lastDay);
      $stmt->execute();
      $res = $stmt->get_result();

      $map = ['Present' => 0, 'Late' => 0, 'Absent' => 0, 'Undertime' => 0];
      $hoursPerDate = [];

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
              } else {
                $baseStatus = 'Present';
              }
            }
          }
        }

        if (isset($map[$baseStatus])) {
          $map[$baseStatus]++;
        }

        if (!empty($row['time_in']) && !empty($row['time_out'])) {
          $inTs = strtotime($row['time_in']);
          $outTs = strtotime($row['time_out']);
          if ($outTs > $inTs) {
            $hours = ($outTs - $inTs) / 3600;
            $dateKey = $row['date'];
            if (!isset($hoursPerDate[$dateKey])) {
              $hoursPerDate[$dateKey] = 0.0;
            }
            $hoursPerDate[$dateKey] += $hours;
          }
        }
      }
      $stmt->close();

      ksort($hoursPerDate);
      $days = array_keys($hoursPerDate);
      $hoursArr = [];
      foreach ($hoursPerDate as $h) {
        $hoursArr[] = round($h, 2);
      }

      $overtimeHours = 0.0;
      if ($stmt = $mysqli->prepare("SELECT SUM(approved_ot_minutes) AS mins FROM overtime_requests WHERE employee_id = ? AND date BETWEEN ? AND ? AND status IN ('Approved','AutoApproved')")) {
        $stmt->bind_param('iss', $employeeId, $firstDay, $lastDay);
        $stmt->execute();
        $oRes = $stmt->get_result();
        if ($oRow = $oRes->fetch_assoc()) {
          $mins = isset($oRow['mins']) ? (int)$oRow['mins'] : 0;
          if ($mins > 0) {
            $overtimeHours = round($mins / 60, 2);
          }
        }
        $stmt->close();
      }

      $workingDays = computeEffectiveLeaveDays($mysqli, $employeeId, $firstDay, $lastDay);

      echo json_encode([
        'success' => true,
        'data' => [
          'present' => $map['Present'],
          'absent' => $map['Absent'],
          'lates' => $map['Late'],
          'undertime' => $map['Undertime'],
          'overtime' => $overtimeHours,
          'working_days' => $workingDays,
          'days' => $days,
          'hours' => $hoursArr
        ]
      ]);
      break;

    case 'work_schedule':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];

      // departments.department_name and job_positions.position_name in schema; alias to generic names
      $stmt = $mysqli->prepare("SELECT e.employee_id, e.department_id, e.position_id, d.department_name AS dept_name, jp.position_name AS pos_name FROM employees e JOIN departments d ON e.department_id = d.department_id JOIN job_positions jp ON e.position_id = jp.position_id WHERE e.user_id = ? LIMIT 1");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $res = $stmt->get_result();
      $emp = $res->fetch_assoc();
      $stmt->close();
      if (!$emp) {
        throw new Exception('Employee not found');
      }
      $employeeId = (int)$emp['employee_id'];

      // Use employee_schedules (canonical in systemintegration schema)
      $stmt = $mysqli->prepare("SELECT day_of_week, start_time, end_time, is_working FROM employee_schedules WHERE employee_id = ? ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param('i', $employeeId);
      $stmt->execute();
      $res = $stmt->get_result();
      $workingDays = [];
      $restDaysSet = [
        'Monday' => true,
        'Tuesday' => true,
        'Wednesday' => true,
        'Thursday' => true,
        'Friday' => true,
        'Saturday' => true,
        'Sunday' => true
      ];
      $earliestStart = null;
      $latestEnd = null;
      while ($row = $res->fetch_assoc()) {
        $day = $row['day_of_week'];
        $isWorking = (int)$row['is_working'] === 1;
        if ($isWorking) {
          $workingDays[] = $day;
          unset($restDaysSet[$day]);
          $s = $row['start_time'];
          $e = $row['end_time'];
          if ($s && ($earliestStart === null || strtotime($s) < strtotime($earliestStart))) {
            $earliestStart = $s;
          }
          if ($e && ($latestEnd === null || strtotime($e) > strtotime($latestEnd))) {
            $latestEnd = $e;
          }
        }
      }
      $stmt->close();

      $scheduleStr = !empty($workingDays) ? implode(', ', $workingDays) : 'N/A';
      $restDays = array_keys($restDaysSet);
      $restDayStr = !empty($restDays) ? implode(', ', $restDays) : 'None';

      $shiftTimeStr = 'N/A';
      if ($earliestStart && $latestEnd) {
        $shiftTimeStr = date('h:i A', strtotime($earliestStart)) . ' - ' . date('h:i A', strtotime($latestEnd));
      }

      echo json_encode([
        'success' => true,
        'data' => [
          'schedule' => $scheduleStr,
          'rest_day' => $restDayStr,
          'shift_time' => $shiftTimeStr,
          'department' => $emp['dept_name'],
          'position' => $emp['pos_name']
        ]
      ]);
      break;

    case 'payroll_summary':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];

      $stmt = $mysqli->prepare("SELECT employee_id FROM employees WHERE user_id = ? LIMIT 1");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $res = $stmt->get_result();
      $emp = $res->fetch_assoc();
      $stmt->close();
      if (!$emp) {
        throw new Exception('Employee not found');
      }
      $employeeId = (int)$emp['employee_id'];

      $stmt = $mysqli->prepare("SELECT payroll_id AS id, payroll_period_start, payroll_period_end, gross_pay, philhealth_deduction, sss_deduction, pagibig_deduction, other_deductions, total_deductions, net_pay, paid_status, payment_date FROM payroll WHERE employee_id = ? ORDER BY payment_date DESC, payroll_id DESC LIMIT 1");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param('i', $employeeId);
      $stmt->execute();
      $res = $stmt->get_result();
      $row = $res->fetch_assoc();
      $stmt->close();

      $totalHours = 0.0;
      $grossPay = 0.0;
      $deductions = 0.0;
      $netPay = 0.0;

      if ($row) {
        $periodStart = $row['payroll_period_start'];
        $periodEnd = $row['payroll_period_end'];
        $grossPay = (float)$row['gross_pay'];
        $deductions = isset($row['total_deductions']) ? (float)$row['total_deductions'] : ((float)$row['philhealth_deduction'] + (float)$row['sss_deduction'] + (float)$row['pagibig_deduction'] + (float)$row['other_deductions']);
        $netPay = isset($row['net_pay']) ? (float)$row['net_pay'] : ($grossPay - $deductions);

        $stmt = $mysqli->prepare("SELECT time_in, time_out, attendance_date AS date FROM attendance_logs WHERE employee_id = ? AND attendance_date BETWEEN ? AND ? AND time_in IS NOT NULL AND time_out IS NOT NULL");
        if ($stmt) {
          $stmt->bind_param('iss', $employeeId, $periodStart, $periodEnd);
          $stmt->execute();
          $aRes = $stmt->get_result();
          while ($logRow = $aRes->fetch_assoc()) {
            $inTs = strtotime($logRow['time_in']);
            $outTs = strtotime($logRow['time_out']);
            if ($outTs > $inTs) {
              $totalHours += ($outTs - $inTs) / 3600;
            }
          }
          $stmt->close();
          $totalHours = round($totalHours, 2);
        }
      }

      echo json_encode([
        'success' => true,
        'data' => [
          'total_hours' => $totalHours,
          'gross_pay' => number_format($grossPay, 2, '.', ''),
          'deductions' => number_format($deductions, 2, '.', ''),
          'net_pay' => number_format($netPay, 2, '.', '')
        ]
      ]);
      break;

    case 'profile_overview':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];

      $stmt = $mysqli->prepare("SELECT e.employee_id AS id, e.first_name, e.last_name, e.contact_number, e.avatar_path, d.department_name AS dept_name, jp.position_name AS pos_name FROM employees e JOIN departments d ON e.department_id = d.department_id JOIN job_positions jp ON e.position_id = jp.position_id WHERE e.user_id = ? LIMIT 1");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $res = $stmt->get_result();
      $emp = $res->fetch_assoc();
      $stmt->close();
      if (!$emp) {
        throw new Exception('Employee not found');
      }

      $avatarPath = $emp['avatar_path'] ? ('../' . $emp['avatar_path']) : '../pages/img/user.jpg';

      echo json_encode([
        'success' => true,
        'data' => [
          'id' => (int)$emp['id'],
          'name' => $emp['first_name'] . ' ' . $emp['last_name'],
          'department' => $emp['dept_name'],
          'position' => $emp['pos_name'],
          'contact' => $emp['contact_number'],
          'avatar' => $avatarPath
        ]
      ]);
      break;

    case 'get_qr':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];

      $stmt = $mysqli->prepare("SELECT employee_id FROM employees WHERE user_id = ? LIMIT 1");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $res = $stmt->get_result();
      $emp = $res->fetch_assoc();
      $stmt->close();
      if (!$emp) {
        throw new Exception('Employee not found');
      }
      $employeeId = (int)$emp['employee_id'];

      $stmt = $mysqli->prepare("SELECT qr_image_path FROM qr_codes WHERE employee_id = ? ORDER BY generated_at DESC, qr_id DESC LIMIT 1");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param('i', $employeeId);
      $stmt->execute();
      $res = $stmt->get_result();
      $row = $res->fetch_assoc();
      $stmt->close();

      $qrPath = null;
      if ($row && !empty($row['qr_image_path'])) {
        $qrPath = '../' . $row['qr_image_path'];
      }

      echo json_encode([
        'success' => true,
        'data' => [
          'qr_path' => $qrPath
        ]
      ]);
      break;

    case 'notifications':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];

      $stmt = $mysqli->prepare("SELECT employee_id FROM employees WHERE user_id = ? LIMIT 1");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $res = $stmt->get_result();
      $emp = $res->fetch_assoc();
      $stmt->close();
      if (!$emp) {
        throw new Exception('Employee not found');
      }
      $employeeId = (int)$emp['employee_id'];

      $notifications = [];

      $today = date('Y-m-d');
      $twoWeeks = (new DateTime($today))->modify('+14 days')->format('Y-m-d');

      if ($stmt = $mysqli->prepare("SELECT holiday_name AS name, holiday_type AS type, start_date FROM holidays WHERE start_date BETWEEN ? AND ? ORDER BY start_date ASC")) {
        $stmt->bind_param('ss', $today, $twoWeeks);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
          $label = $row['type'] === 'regular' ? 'Regular Holiday' : ($row['type'] === 'special_non_working' ? 'Special Non-Working Holiday' : 'Special Working Holiday');
          $notifications[] = [
            'message' => $label . ' on ' . $row['start_date'] . ': ' . $row['name']
          ];
        }
        $stmt->close();
      }

      if ($stmt = $mysqli->prepare("SELECT leave_type, start_date, end_date, status FROM leave_requests WHERE employee_id = ? AND status = 'Approved' AND end_date >= ? ORDER BY start_date ASC LIMIT 5")) {
        $stmt->bind_param('is', $employeeId, $today);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
          $notifications[] = [
            'message' => 'Approved ' . $row['leave_type'] . ' leave from ' . $row['start_date'] . ' to ' . $row['end_date']
          ];
        }
        $stmt->close();
      }

      if ($stmt = $mysqli->prepare("SELECT payment_date, net_pay FROM payroll WHERE employee_id = ? AND paid_status = 'Paid' ORDER BY payment_date DESC, payroll_id DESC LIMIT 1")) {
        $stmt->bind_param('i', $employeeId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
          $notifications[] = [
            'message' => 'Latest payroll paid on ' . $row['payment_date'] . ' (Net: ₱' . number_format((float)$row['net_pay'], 2, '.', '') . ')'
          ];
        }
        $stmt->close();
      }

      echo json_encode([
        'success' => true,
        'data' => $notifications
      ]);
      break;

    case 'leave_balances':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];

      // Fetch leave balances from employees table using user_id
      $stmt = $mysqli->prepare("SELECT annual_paid_leave_days, annual_unpaid_leave_days, annual_sick_leave_days FROM employees WHERE user_id = ?");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $employee = $result->fetch_assoc();
      $stmt->close();

      if (!$employee) {
        throw new Exception('Employee not found');
      }

      echo json_encode([
        'success' => true,
        'data' => [
          'paid' => (int)$employee['annual_paid_leave_days'],
          'unpaid' => (int)$employee['annual_unpaid_leave_days'],
          'sick' => (int)$employee['annual_sick_leave_days']
        ]
      ]);
      break;

    case 'leave_requests':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];

      // First, get the employee ID from user_id
      $stmt = $mysqli->prepare("SELECT employee_id FROM employees WHERE user_id = ?");
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $emp = $result->fetch_assoc();
      $stmt->close();

      if (!$emp) {
        throw new Exception('Employee not found');
      }
      $employeeId = (int)$emp['employee_id'];

      // Fetch leave requests using employee_id, and alias leave_type as type. leave_id is PK.
      $stmt = $mysqli->prepare("SELECT leave_id AS id, leave_type AS type, start_date, end_date, days, reason, status, proof_path, admin_feedback FROM leave_requests WHERE employee_id = ? ORDER BY submitted_at DESC");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param('i', $employeeId);
      $stmt->execute();
      $result = $stmt->get_result();
      $requests = $result->fetch_all(MYSQLI_ASSOC);
      $stmt->close();

      echo json_encode(['success' => true, 'data' => $requests]);
      break;

    case 'request_leave':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];

      // Get employee ID
      $stmt = $mysqli->prepare("SELECT employee_id FROM employees WHERE user_id = ?");
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $emp = $result->fetch_assoc();
      $stmt->close();

      if (!$emp) {
        throw new Exception('Employee not found');
      }
      $employeeId = (int)$emp['employee_id'];

      $type = $_POST['leave-type'] ?? '';
      $startDate = $_POST['leave-start'] ?? '';
      $endDate = $_POST['leave-end'] ?? '';
      $reason = $_POST['leave-reason'] ?? '';
      $proofPath = null;
      if (isset($_FILES['leave-proof']) && $_FILES['leave-proof']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/proofs/'; // Adjust path
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $fileName = uniqid() . '_' . basename($_FILES['leave-proof']['name']);
        $targetPath = $uploadDir . $fileName;
        if (move_uploaded_file($_FILES['leave-proof']['tmp_name'], $targetPath)) {
          $proofPath = 'uploads/proofs/' . $fileName;
        } else {
          throw new Exception('Failed to upload proof.');
        }
      }

      if (!$type || !$startDate || !$endDate || !$reason) {
        throw new Exception('All fields are required');
      }

      $start = new DateTime($startDate);
      $end = new DateTime($endDate);
      if ($start > $end) {
        $tmp = $start;
        $start = $end;
        $end = $tmp;
      }
      $effectiveDays = computeEffectiveLeaveDays($mysqli, $employeeId, $startDate, $endDate);
      $days = $effectiveDays;

      // Insert request
      $stmt = $mysqli->prepare("INSERT INTO leave_requests (employee_id, leave_type, start_date, end_date, days, reason, status, proof_path) VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)");
      if (!$stmt) {
        throw new Exception('Prepare failed: ' . $mysqli->error);
      }
      $stmt->bind_param('isssiss', $employeeId, $type, $startDate, $endDate, $days, $reason, $proofPath);
      if (!$stmt->execute()) {
        throw new Exception('Failed to insert leave request: ' . $stmt->error);
      }
      $stmt->close();
      echo json_encode(['success' => true, 'message' => 'Leave request submitted successfully']);
      break;

    case 'check_overlap':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];
      $startDate = $_GET['start'] ?? '';
      $endDate = $_GET['end'] ?? '';

      if (!$startDate || !$endDate) {
        throw new Exception('Start and end dates required');
      }

      // Get employee ID
      $stmt = $mysqli->prepare("SELECT employee_id FROM employees WHERE user_id = ?");
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $emp = $result->fetch_assoc();
      $stmt->close();
      if (!$emp) throw new Exception('Employee not found');
      $employeeId = (int)$emp['employee_id'];

      // Check for overlaps with Approved or Pending requests
      $stmt = $mysqli->prepare("SELECT leave_id FROM leave_requests WHERE employee_id = ? AND status IN ('Approved', 'Pending') AND ((start_date <= ? AND end_date >= ?) OR (start_date <= ? AND end_date >= ?))");
      $stmt->bind_param('issss', $employeeId, $endDate, $startDate, $startDate, $endDate);
      $stmt->execute();
      $result = $stmt->get_result();
      $overlap = $result->num_rows > 0;
      $stmt->close();

      echo json_encode(['success' => true, 'overlap' => $overlap]);
      break;

    case 'preview_leave_days':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];
      $startDate = $_GET['start'] ?? '';
      $endDate = $_GET['end'] ?? '';

      if (!$startDate || !$endDate) {
        throw new Exception('Start and end dates required');
      }

      $stmt = $mysqli->prepare("SELECT employee_id FROM employees WHERE user_id = ?");
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $emp = $result->fetch_assoc();
      $stmt->close();
      if (!$emp) {
        throw new Exception('Employee not found');
      }
      $employeeId = (int)$emp['employee_id'];

      $start = new DateTime($startDate);
      $end = new DateTime($endDate);
      if ($start > $end) {
        $tmp = $start;
        $start = $end;
        $end = $tmp;
      }
      $calendarDays = $start->diff($end)->days + 1;
      $effectiveDays = computeEffectiveLeaveDays($mysqli, $employeeId, $startDate, $endDate);

      echo json_encode([
        'success' => true,
        'data' => [
          'calendar_days' => $calendarDays,
          'effective_days' => $effectiveDays
        ]
      ]);
      break;

    case 'view_leave_request':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];
      $requestId = (int)$_GET['id'];

      error_log("Debug view_leave_request: userId=$userId, requestId=$requestId");

      // Check if the request exists and belongs to the user via proper joins
      $stmt = $mysqli->prepare("
        SELECT lr.*, u.first_name, u.last_name, u.email, u.avatar_path 
        FROM leave_requests lr 
        JOIN employees e ON lr.employee_id = e.employee_id 
        JOIN users_employee u ON e.user_id = u.id 
        WHERE lr.leave_id = ? AND u.id = ?
      ");
      $stmt->bind_param('ii', $requestId, $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $reqCheck = $result->fetch_assoc();
      $stmt->close();

      if (!$reqCheck) {
        error_log("Debug: No matching request found for userId=$userId and requestId=$requestId");
        throw new Exception('Request not found or access denied');
      }

      // Fetch full details
      $stmt = $mysqli->prepare("
        SELECT lr.*, u.first_name, u.last_name, u.email, u.avatar_path 
        FROM leave_requests lr 
        JOIN employees e ON lr.employee_id = e.employee_id 
        JOIN users_employee u ON e.user_id = u.id 
        WHERE lr.leave_id = ? AND u.id = ?
      ");
      $stmt->bind_param('ii', $requestId, $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $request = $result->fetch_assoc();
      $stmt->close();

      if (!$request) {
        error_log("Debug: Failed to fetch details for requestId=$requestId");
        throw new Exception('Unexpected error fetching request details');
      }

      // Handle avatar fallback
      $request['avatar_path'] = $request['avatar_path'] ?: 'img/user.jpg';

      echo json_encode(['success' => true, 'data' => $request]);
      break;

    case 'cancel_leave_request':
      if (!isset($_SESSION['user_id'])) {
        throw new Exception('User not authenticated');
      }
      $userId = $_SESSION['user_id'];
      $requestId = (int)$_POST['id']; // JS sends via POST body

      // Move file deletion here, after $requestId is defined
      $stmt = $mysqli->prepare("SELECT proof_path FROM leave_requests WHERE leave_id = ?");
      $stmt->bind_param('i', $requestId);
      $stmt->execute();
      $result = $stmt->get_result();
      $req = $result->fetch_assoc();
      if ($req && $req['proof_path'] && file_exists('../' . $req['proof_path'])) {
        unlink('../' . $req['proof_path']);
      }
      $stmt->close();

      // Get employee ID
      $stmt = $mysqli->prepare("SELECT employee_id FROM employees WHERE user_id = ?");
      $stmt->bind_param('i', $userId);
      $stmt->execute();
      $result = $stmt->get_result();
      $emp = $result->fetch_assoc();
      $stmt->close();
      if (!$emp) throw new Exception('Employee not found');
      $employeeId = (int)$emp['employee_id'];

      // Check if request is Pending and belongs to user
      $stmt = $mysqli->prepare("SELECT status FROM leave_requests WHERE leave_id = ? AND employee_id = ?");
      $stmt->bind_param('ii', $requestId, $employeeId);
      $stmt->execute();
      $result = $stmt->get_result();
      $req = $result->fetch_assoc();
      $stmt->close();
      if (!$req) throw new Exception('Request not found or access denied');
      if ($req['status'] !== 'Pending') throw new Exception('Only pending requests can be canceled');

      // Delete the request
      $stmt = $mysqli->prepare("DELETE FROM leave_requests WHERE leave_id = ?");
      $stmt->bind_param('i', $requestId);
      if (!$stmt->execute()) {
        throw new Exception('Failed to cancel request');
      }
      $stmt->close();
      echo json_encode(['success' => true, 'message' => 'Request canceled successfully']);
      break;

    default:
      throw new Exception('Invalid action');
  }
} catch (Exception $e) {
  // Output JSON error
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Close connection if set
if (isset($mysqli)) {
  $mysqli->close();
}
// Flush output buffer to send JSON
ob_end_flush();
