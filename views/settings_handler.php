<?php
// session_start();  // REMOVE THIS LINE - auth.php already starts the session

require_once 'conn.php';
require_once 'auth.php';  // Ensure head_admin access for saves only

header('Content-Type: application/json');

// Helper function to check if user has head_admin role
function hasHeadAdminRole()
{
    if (isset($_SESSION['roles'])) {
        $userRoles = is_array($_SESSION['roles']) ? $_SESSION['roles'] : json_decode($_SESSION['roles'], true);
        return in_array('head_admin', $userRoles);
    } elseif (isset($_SESSION['role'])) {
        return $_SESSION['role'] === 'head_admin';
    }
    return false;
}

try {
    $db = conn();
    $mysqli = $db['mysqli'];

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    // Ensure new columns exist for time_date_settings (id=1 row exists from seed)
    // MariaDB 10.4 supports IF NOT EXISTS
    @$mysqli->query("ALTER TABLE time_date_settings ADD COLUMN IF NOT EXISTS grace_in_minutes INT DEFAULT 0");
    @$mysqli->query("ALTER TABLE time_date_settings ADD COLUMN IF NOT EXISTS grace_out_minutes INT DEFAULT 0");
    @$mysqli->query("ALTER TABLE time_date_settings ADD COLUMN IF NOT EXISTS company_hours_per_day DECIMAL(5,2) DEFAULT 8.00");
    // Ensure payroll_frequency column exists with correct enum set
    $hasPayrollFreq = false;
    if ($d = $mysqli->query("DESCRIBE job_positions")) {
        while ($row = $d->fetch_assoc()) {
            if (($row['Field'] ?? '') === 'payroll_frequency') { $hasPayrollFreq = true; break; }
        }
        $d->free();
    }
    if (!$hasPayrollFreq) {
        @$mysqli->query("ALTER TABLE job_positions ADD COLUMN payroll_frequency ENUM('daily','weekly','bi-weekly','monthly') NOT NULL DEFAULT 'bi-weekly'");
    }

    // Ensure attendance_settings table exists with at least one row
    @$mysqli->query("CREATE TABLE IF NOT EXISTS attendance_settings (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        late_threshold_minutes INT(11) DEFAULT 15,
        undertime_threshold_minutes INT(11) DEFAULT 30,
        regular_overtime_multiplier DECIMAL(5,2) DEFAULT 1.25,
        holiday_overtime_multiplier DECIMAL(5,2) DEFAULT 2.00,
        auto_ot_minutes INT(11) DEFAULT 30,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    // In case the table already exists without the new column, add it
    @$mysqli->query("ALTER TABLE attendance_settings ADD COLUMN IF NOT EXISTS auto_ot_minutes INT(11) DEFAULT 30");

    $attCheck = $mysqli->query("SELECT id FROM attendance_settings LIMIT 1");
    if ($attCheck && $attCheck->num_rows === 0) {
        @$mysqli->query("INSERT INTO attendance_settings (late_threshold_minutes, undertime_threshold_minutes, regular_overtime_multiplier, holiday_overtime_multiplier, auto_ot_minutes)
                         VALUES (15, 30, 1.25, 2.00, 30)");
    }
    if ($attCheck) { $attCheck->free(); }

    // Ensure payroll_settings table exists with at least one row (holiday multipliers)
    @$mysqli->query("CREATE TABLE IF NOT EXISTS payroll_settings (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        regular_holiday_rate DECIMAL(5,2) DEFAULT 2.00,
        regular_holiday_ot_rate DECIMAL(5,2) DEFAULT 2.60,
        special_nonworking_rate DECIMAL(5,2) DEFAULT 1.30,
        special_nonworking_ot_rate DECIMAL(5,2) DEFAULT 1.69,
        special_working_rate DECIMAL(5,2) DEFAULT 1.30,
        special_working_ot_rate DECIMAL(5,2) DEFAULT 1.69,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $payrollCheck = $mysqli->query("SELECT id FROM payroll_settings LIMIT 1");
    if ($payrollCheck && $payrollCheck->num_rows === 0) {
        @$mysqli->query("INSERT INTO payroll_settings (regular_holiday_rate, regular_holiday_ot_rate, special_nonworking_rate, special_nonworking_ot_rate, special_working_rate, special_working_ot_rate)
                         VALUES (2.00, 2.60, 1.30, 1.69, 1.30, 1.69)");
    }
    if ($payrollCheck) { $payrollCheck->free(); }

    switch ($action) {
        case 'load':
            // Load all settings
            $systemResult = $mysqli->query("SELECT system_name, logo_path, annual_paid_leave_days, annual_unpaid_leave_days, annual_sick_leave_days FROM school_settings LIMIT 1");
            $systemData = $systemResult->fetch_assoc() ?: [];

            $timeDateResult = $mysqli->query("SELECT auto_logout_time_hours, date_format, grace_in_minutes, grace_out_minutes, company_hours_per_day FROM time_date_settings LIMIT 1");
            $attendanceResult = $mysqli->query("SELECT late_threshold_minutes, undertime_threshold_minutes, regular_overtime_multiplier, holiday_overtime_multiplier, auto_ot_minutes FROM attendance_settings LIMIT 1");
            $payrollSettingsResult = $mysqli->query("SELECT regular_holiday_rate, regular_holiday_ot_rate, special_nonworking_rate, special_nonworking_ot_rate, special_working_rate, special_working_ot_rate FROM payroll_settings LIMIT 1");
            $backupResult = $mysqli->query("SELECT backup_frequency, session_timeout_minutes FROM backup_restore_settings LIMIT 1");
            $rolesRes = $mysqli->query("SELECT id, name, payroll_frequency FROM job_positions ORDER BY name");
            $roles = $rolesRes ? $rolesRes->fetch_all(MYSQLI_ASSOC) : [];

            $settings = [
                'system' => $systemData,
                'time_date' => $timeDateResult->fetch_assoc() ?: [],
                'leave' => [
                    'annual_paid_leave_days' => $systemData['annual_paid_leave_days'] ?? 15,
                    'annual_unpaid_leave_days' => $systemData['annual_unpaid_leave_days'] ?? 5,
                    'annual_sick_leave_days' => $systemData['annual_sick_leave_days'] ?? 10
                ],
                'attendance' => [],
                'payroll' => [ 'roles' => $roles ],
                'backup' => $backupResult->fetch_assoc() ?: []
            ];

            $attendanceData = $attendanceResult ? $attendanceResult->fetch_assoc() : [];
            if ($attendanceResult) { $attendanceResult->free(); }
            $settings['attendance'] = [
                'late_threshold' => $attendanceData['late_threshold_minutes'] ?? 15,
                'undertime_threshold' => $attendanceData['undertime_threshold_minutes'] ?? 30,
                'regular_overtime' => $attendanceData['regular_overtime_multiplier'] ?? 1.25,
                'holiday_overtime' => $attendanceData['holiday_overtime_multiplier'] ?? 2,
                'auto_ot_minutes' => isset($attendanceData['auto_ot_minutes']) ? (int)$attendanceData['auto_ot_minutes'] : 30,
            ];

            $payrollSettings = $payrollSettingsResult ? $payrollSettingsResult->fetch_assoc() : [];
            if ($payrollSettingsResult) { $payrollSettingsResult->free(); }
            $settings['payroll']['holiday'] = [
                'regular_holiday_rate' => isset($payrollSettings['regular_holiday_rate']) ? (float)$payrollSettings['regular_holiday_rate'] : 2.0,
                'regular_holiday_ot_rate' => isset($payrollSettings['regular_holiday_ot_rate']) ? (float)$payrollSettings['regular_holiday_ot_rate'] : 2.6,
                'special_nonworking_rate' => isset($payrollSettings['special_nonworking_rate']) ? (float)$payrollSettings['special_nonworking_rate'] : 1.3,
                'special_nonworking_ot_rate' => isset($payrollSettings['special_nonworking_ot_rate']) ? (float)$payrollSettings['special_nonworking_ot_rate'] : 1.69,
                'special_working_rate' => isset($payrollSettings['special_working_rate']) ? (float)$payrollSettings['special_working_rate'] : 1.3,
                'special_working_ot_rate' => isset($payrollSettings['special_working_ot_rate']) ? (float)$payrollSettings['special_working_ot_rate'] : 1.69,
            ];

            echo json_encode(['success' => true, 'data' => $settings]);
            break;

        case 'save_role_payroll':
            if (!hasHeadAdminRole()) {
                throw new Exception('Unauthorized: Must have head_admin role.');
            }
            $freqJson = $_POST['frequencies'] ?? '';
            $map = json_decode($freqJson, true);
            if (!is_array($map)) {
                throw new Exception('Invalid frequencies payload.');
            }
            $allowed = ['daily','weekly','bi-weekly','monthly'];
            $stmt = $mysqli->prepare("UPDATE job_positions SET payroll_frequency = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            foreach ($map as $id => $freq) {
                $freq = strtolower(trim((string)$freq));
                $id = intval($id);
                if ($id <= 0 || !in_array($freq, $allowed, true)) continue;
                $stmt->bind_param('si', $freq, $id);
                if (!$stmt->execute()) {
                    throw new Exception('Execute failed for ID ' . $id . ': ' . $stmt->error);
                }
            }
            $stmt->close();
            echo json_encode(['success' => true, 'message' => 'Payroll frequencies saved.']);
            break;

        case 'save_system_info':
            // Check if user has head_admin role
            if (!hasHeadAdminRole()) {
                throw new Exception('Unauthorized: Must have head_admin role.');
            }

            $systemName = trim($_POST['system_name'] ?? '');
            $logo = $_FILES['logo'] ?? null;

            if (empty($systemName)) {
                throw new Exception('System name is required.');
            }
            if (strlen($systemName) > 12) {
                throw new Exception('System name cannot exceed 12 characters.');
            }

            // Fetch current logo path to handle replacement or preservation
            $currentQuery = $mysqli->query("SELECT logo_path FROM school_settings WHERE id = 1");
            $currentSettings = $currentQuery->fetch_assoc();
            $currentLogoPath = $currentSettings['logo_path'] ?? null;

            $logoPath = $currentLogoPath; // Default to keeping existing logo

            if ($logo && $logo['error'] === UPLOAD_ERR_OK) {
                if ($logo['size'] > 2 * 1024 * 1024) {
                    throw new Exception('Logo size must be less than 2MB.');
                }

                // Delete old logo if it exists
                if ($currentLogoPath && file_exists('../' . $currentLogoPath)) {
                    unlink('../' . $currentLogoPath);
                }

                $uploadDir = '../uploads/logos/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'logo_' . time() . '.' . pathinfo($logo['name'], PATHINFO_EXTENSION);
                $logoPath = 'uploads/logos/' . $filename;
                if (!move_uploaded_file($logo['tmp_name'], '../' . $logoPath)) {
                    throw new Exception('Failed to upload logo.');
                }
            }

            $stmt = $mysqli->prepare("UPDATE school_settings SET system_name = ?, logo_path = ? WHERE id = 1");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $stmt->bind_param('ss', $systemName, $logoPath);
            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
            $stmt->close();

            echo json_encode(['success' => true, 'message' => 'System information saved.', 'system_name' => $systemName, 'logo_path' => $logoPath]);
            break;

        case 'save_time_date':
            // Check if user has head_admin role
            if (!hasHeadAdminRole()) {
                throw new Exception('Unauthorized: Must have head_admin role.');
            }

            $minutes = intval($_POST['auto_logout'] ?? 60);
            $dateFormat = $_POST['date_format'] ?? 'DD/MM/YYYY';
            $graceIn = intval($_POST['grace_in'] ?? 0);
            $graceOut = intval($_POST['grace_out'] ?? 0);
            $companyHours = floatval($_POST['company_hours_per_day'] ?? 8);

            // 0 = disabled
            if ($minutes === 0) {
                $decimal = 0;
            }
            // 1–9 = invalid
            else if ($minutes < 10) {
                throw new Exception('Minimum auto logout time is 10 minutes if not disabled.');
            }
            // 10+ = valid, convert to decimal
            else {
                $decimal = $minutes / 60;
            }

            // Validate grace periods and company hours
            if ($graceIn < 0 || $graceIn > 120) {
                throw new Exception('Grace Period (Time In) must be between 0 and 120 minutes.');
            }
            if ($graceOut < 0 || $graceOut > 120) {
                throw new Exception('Grace Period (Time Out) must be between 0 and 120 minutes.');
            }
            if ($companyHours < 1 || $companyHours > 24) {
                throw new Exception('Total Working Hours per Day must be between 1 and 24 hours.');
            }

            $stmt = $mysqli->prepare("UPDATE time_date_settings SET auto_logout_time_hours = ?, date_format = ?, grace_in_minutes = ?, grace_out_minutes = ?, company_hours_per_day = ? WHERE id = 1");
            $stmt->bind_param('dsiid', $decimal, $dateFormat, $graceIn, $graceOut, $companyHours);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true, 'message' => 'Time & date settings saved.']);
            break;

        case 'save_leave':
            // Check if user has head_admin role
            if (!hasHeadAdminRole()) {
                throw new Exception('Unauthorized: Must have head_admin role.');
            }

            $paidLeave = intval($_POST['annual_paid_leave'] ?? 15);
            $unpaidLeave = intval($_POST['annual_unpaid_leave'] ?? 5);
            $sickLeave = intval($_POST['annual_sick_leave'] ?? 10);

            $stmt = $mysqli->prepare("UPDATE school_settings SET annual_paid_leave_days = ?, annual_unpaid_leave_days = ?, annual_sick_leave_days = ? WHERE id = 1");
            $stmt->bind_param('iii', $paidLeave, $unpaidLeave, $sickLeave);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true, 'message' => 'Leave settings saved.']);
            break;

        case 'save_attendance':
            // Check if user has head_admin role
            if (!hasHeadAdminRole()) {
                throw new Exception('Unauthorized: Must have head_admin role.');
            }

            $lateThreshold = max(0, intval($_POST['late_threshold'] ?? 15));
            $undertimeThreshold = max(0, intval($_POST['undertime_threshold'] ?? 30));
            $regularOvertime = max(0.0, floatval($_POST['regular_overtime'] ?? 1.25));
            $holidayOvertime = max(0.0, floatval($_POST['holiday_overtime'] ?? 2));
            $autoOtMinutes = max(0, intval($_POST['auto_ot_minutes'] ?? 30));

            $stmt = $mysqli->prepare("UPDATE attendance_settings SET late_threshold_minutes = ?, undertime_threshold_minutes = ?, regular_overtime_multiplier = ?, holiday_overtime_multiplier = ?, auto_ot_minutes = ? WHERE id = 1");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $stmt->bind_param('iiddi', $lateThreshold, $undertimeThreshold, $regularOvertime, $holidayOvertime, $autoOtMinutes);
            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }

            if ($stmt->affected_rows === 0) {
                $stmt->close();
                $stmt = $mysqli->prepare("INSERT INTO attendance_settings (id, late_threshold_minutes, undertime_threshold_minutes, regular_overtime_multiplier, holiday_overtime_multiplier, auto_ot_minutes) VALUES (1, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception('Prepare failed (insert): ' . $mysqli->error);
                }
                $stmt->bind_param('iiddi', $lateThreshold, $undertimeThreshold, $regularOvertime, $holidayOvertime, $autoOtMinutes);
                if (!$stmt->execute()) {
                    throw new Exception('Execute failed (insert): ' . $stmt->error);
                }
            }
            $stmt->close();

            echo json_encode(['success' => true, 'message' => 'Attendance settings saved.']);
            break;

        case 'save_payroll_holiday':
            // Check if user has head_admin role
            if (!hasHeadAdminRole()) {
                throw new Exception('Unauthorized: Must have head_admin role.');
            }

            $regularHolidayRate = max(0.0, floatval($_POST['regular_holiday_rate'] ?? 2.0));
            $regularHolidayOtRate = max(0.0, floatval($_POST['regular_holiday_ot_rate'] ?? 2.6));
            $specialNonworkingRate = max(0.0, floatval($_POST['special_nonworking_rate'] ?? 1.3));
            $specialNonworkingOtRate = max(0.0, floatval($_POST['special_nonworking_ot_rate'] ?? 1.69));
            $specialWorkingRate = max(0.0, floatval($_POST['special_working_rate'] ?? 1.3));
            $specialWorkingOtRate = max(0.0, floatval($_POST['special_working_ot_rate'] ?? 1.69));

            $stmt = $mysqli->prepare("UPDATE payroll_settings SET regular_holiday_rate = ?, regular_holiday_ot_rate = ?, special_nonworking_rate = ?, special_nonworking_ot_rate = ?, special_working_rate = ?, special_working_ot_rate = ? WHERE id = 1");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $stmt->bind_param('dddddd', $regularHolidayRate, $regularHolidayOtRate, $specialNonworkingRate, $specialNonworkingOtRate, $specialWorkingRate, $specialWorkingOtRate);
            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }

            if ($stmt->affected_rows === 0) {
                $stmt->close();
                $stmt = $mysqli->prepare("INSERT INTO payroll_settings (id, regular_holiday_rate, regular_holiday_ot_rate, special_nonworking_rate, special_nonworking_ot_rate, special_working_rate, special_working_ot_rate) VALUES (1, ?, ?, ?, ?, ?, ?)");
                if (!$stmt) {
                    throw new Exception('Prepare failed (insert): ' . $mysqli->error);
                }
                $stmt->bind_param('dddddd', $regularHolidayRate, $regularHolidayOtRate, $specialNonworkingRate, $specialNonworkingOtRate, $specialWorkingRate, $specialWorkingOtRate);
                if (!$stmt->execute()) {
                    throw new Exception('Execute failed (insert): ' . $stmt->error);
                }
            }
            $stmt->close();

            echo json_encode(['success' => true, 'message' => 'Payroll & holiday settings saved.']);
            break;

        case 'save_backup':
            // Check if user has head_admin role
            if (!hasHeadAdminRole()) {
                throw new Exception('Unauthorized: Must have head_admin role.');
            }

            $frequency = $_POST['backup_frequency'] ?? 'weekly';
            $timeout = intval($_POST['session_timeout'] ?? 30);

            $stmt = $mysqli->prepare("UPDATE backup_restore_settings SET backup_frequency = ?, session_timeout_minutes = ? WHERE id = 1");
            $stmt->bind_param('si', $frequency, $timeout);
            $stmt->execute();
            $stmt->close();

            echo json_encode(['success' => true, 'message' => 'Backup settings saved.']);
            break;

        default:
            throw new Exception('Invalid action: ' . $action);
            break;
    }
} catch (Exception $e) {
    error_log('Settings Handler Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($mysqli)) {
    $mysqli->close();
}
