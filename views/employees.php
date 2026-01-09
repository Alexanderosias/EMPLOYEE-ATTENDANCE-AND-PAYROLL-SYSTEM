<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

define('BASE_PATH', '/eaaps'); // XAMPP Apache base path

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'conn.php';  // Adjusted path for views/ location

// Use modern QR library via Composer (chillerlan/php-qrcode)
$vendorAutoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
} else {
    error_log('Composer autoload not found for QR library');
}

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

$db = null;
$mysqli = null;
try {
    $db = conn();
    $mysqli = $db['mysqli'];
    if (!$mysqli || $mysqli->connect_error) {
        throw new Exception('MySQL connection failed: ' . ($mysqli ? $mysqli->connect_error : 'No connection'));
    }
} catch (Exception $e) {
    ob_end_clean();
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

// Ensure new column exists for date of birth
@($mysqli && $mysqli->query("ALTER TABLE employees ADD COLUMN IF NOT EXISTS date_of_birth DATE NULL"));

function generateQRCode($employee_data, $qr_dir = '../qrcodes/') // Fixed default path
{
    // Ensure modern QR library is available
    if (!class_exists(QRCode::class)) {
        error_log('QR Library not loaded – chillerlan/php-qrcode QRCode class missing');
        return ['path' => null, 'data' => null];
    }

    try {
        // Ensure directory exists and is writable
        if (!is_dir($qr_dir)) {
            error_log("Creating QR dir: $qr_dir");
            if (!mkdir($qr_dir, 0755, true)) {
                error_log("Failed to create QR dir: $qr_dir");
                return ['path' => null, 'data' => null];
            }
        }
        if (!is_writable($qr_dir)) {
            @chmod($qr_dir, 0755);
            if (!is_writable($qr_dir)) {
                error_log("QR dir not writable: $qr_dir");
                return ['path' => null, 'data' => null];
            }
        }

        // Build data: First name, last name, position name, date joined (no ID)
        $id = (int)($employee_data['id'] ?? 0);
        // Allow spaces and other characters, only remove QR delimiters (| and :)
        $first = trim(preg_replace('/[|:]/', '', $employee_data['first_name'] ?? 'EMP'));
        if ($first === '') $first = 'EMP';
        $last  = trim(preg_replace('/[|:]/', '', $employee_data['last_name'] ?? ''));
        $pos = trim(preg_replace('/[|:]/', '', $employee_data['position_name'] ?? 'N/A'));  // Job position name from joined table
        $joined = (!empty($employee_data['date_joined']) && $employee_data['date_joined'] !== '0000-00-00')
            ? $employee_data['date_joined'] : 'N/A';

        $qr_data = "First:$first|Last:$last|Position:$pos|Joined:$joined";

        // Unique filename
        $base = $first . $last;
        if ($base === '') $base = 'EMP' . $id;
        $filename = $base . '.png';
        $dir = rtrim($qr_dir, '/\\') . '/';
        $counter = 1;
        while (file_exists($dir . $filename)) {
            $filename = $base . '_' . $id . '_' . $counter . '.png';
            $counter++;
        }
        $file_path = $dir . $filename;
        $web_path  = 'qrcodes/' . $filename;

        // Generate PNG data using chillerlan/php-qrcode and write it to file
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_IMAGE_PNG,
            'eccLevel'   => QRCode::ECC_L,
        ]);

        $imageData = (new QRCode($options))->render($qr_data);

        if ($imageData === null || $imageData === '') {
            error_log('QR generation returned empty image data');
            return ['path' => null, 'data' => null];
        }

        // The render() method returns a Base64 data URI (e.g., "data:image/png;base64,...")
        // We need to extract and decode the Base64 portion to get raw binary PNG data
        if (strpos($imageData, 'data:') === 0) {
            // Extract the Base64 portion after the comma
            $base64Data = substr($imageData, strpos($imageData, ',') + 1);
            $imageData = base64_decode($base64Data);
            if ($imageData === false) {
                error_log('Failed to decode Base64 QR image data');
                return ['path' => null, 'data' => null];
            }
        }

        if (file_put_contents($file_path, $imageData) === false) {
            error_log('Failed to write QR image file: ' . $file_path);
            return ['path' => null, 'data' => null];
        }

        if (!file_exists($file_path)) {
            error_log("QR generation reported success but file not found: $file_path");
            return ['path' => null, 'data' => null];
        }

        error_log("QR Generated successfully: $web_path");
        return ['path' => $web_path, 'data' => $qr_data];
    } catch (Throwable $e) {
        error_log("QR Generation Failed for ID " . ($employee_data['id'] ?? 'unknown') . ": " . $e->getMessage());
        return ['path' => null, 'data' => null];
    }
}

function deleteQRCode($mysqli, $employee_id)
{
    try {
        $stmt = $mysqli->prepare("SELECT qr_image_path FROM qr_codes WHERE employee_id = ?");
        $stmt->bind_param('i', $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $qr = $result->fetch_assoc();
        $stmt->close();

        if ($qr && $qr['qr_image_path']) {
            $file_path = '../' . $qr['qr_image_path'];
            if (file_exists($file_path)) {
                unlink($file_path);
                error_log("QR File Deleted: " . $qr['qr_image_path'] . " for Employee ID $employee_id");
            } else {
                error_log("QR File not found for deletion: $file_path");
            }
        }

        $stmt = $mysqli->prepare("DELETE FROM qr_codes WHERE employee_id = ?");
        $stmt->bind_param('i', $employee_id);
        $stmt->execute();
        $stmt->close();
        error_log("QR Record Deleted for Employee ID $employee_id");
    } catch (Exception $e) {
        error_log("QR Delete Failed for ID $employee_id: " . $e->getMessage());
    }
}

function cleanPhone($phone)
{
    $phone = $phone ?? '';
    return preg_replace('/\D/', '', $phone);
}

function validatePhone($phone)
{
    $clean = cleanPhone($phone);
    return strlen($clean) === 11 && ctype_digit($clean);
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list_employees':
        try {
            $query = "
                SELECT e.employee_id AS id, e.user_id, e.first_name, e.last_name, e.department_id, 
                       e.position_id AS job_position_id, e.hire_date AS date_joined, e.address, e.gender, 
                       e.marital_status, e.status, e.email, e.contact_number, e.emergency_contact_name, 
                       e.emergency_contact_phone, e.emergency_contact_relationship, e.rate_per_hour, 
                       e.rate_per_day, e.annual_paid_leave_days, e.annual_unpaid_leave_days, 
                       e.annual_sick_leave_days, e.avatar_path, e.created_at, e.updated_at, e.date_of_birth,
                       d.department_name AS department_name, jp.position_name AS position_name,
                       qc.qr_data, qc.qr_image_path
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.department_id
                LEFT JOIN job_positions jp ON e.position_id = jp.position_id
                LEFT JOIN qr_codes qc ON e.employee_id = qc.employee_id
                ORDER BY e.last_name, e.first_name
            ";
            $result = $mysqli->query($query);
            if (!$result) {
                throw new Exception('Query failed: ' . $mysqli->error);
            }
            $employees = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();

            // Compute per-employee status for *today* based on attendance and approved leave
            $today = date('Y-m-d');
            $todayAttendance = [];
            $onLeaveToday = [];
            $hasScheduleToday = [];
            $todayDow = date('l');

            // Attendance logs for today: track whether employee has timed in and/or out
            if ($stmtAtt = $mysqli->prepare("SELECT employee_id, time_in, time_out FROM attendance_logs WHERE attendance_date = ?")) {
                $stmtAtt->bind_param('s', $today);
                if ($stmtAtt->execute()) {
                    $attRes = $stmtAtt->get_result();
                    while ($row = $attRes->fetch_assoc()) {
                        $eid = (int)($row['employee_id'] ?? 0);
                        if ($eid <= 0) {
                            continue;
                        }
                        if (!isset($todayAttendance[$eid])) {
                            $todayAttendance[$eid] = ['has_in' => false, 'has_out' => false];
                        }
                        if (!empty($row['time_in'])) {
                            $todayAttendance[$eid]['has_in'] = true;
                        }
                        if (!empty($row['time_out'])) {
                            $todayAttendance[$eid]['has_out'] = true;
                        }
                    }
                }
                $stmtAtt->close();
            }

            // Employees with approved leave that covers today
            if ($stmtLeave = $mysqli->prepare("SELECT employee_id FROM leave_requests WHERE status = 'Approved' AND start_date <= ? AND end_date >= ?")) {
                $stmtLeave->bind_param('ss', $today, $today);
                if ($stmtLeave->execute()) {
                    $leaveRes = $stmtLeave->get_result();
                    while ($row = $leaveRes->fetch_assoc()) {
                        $eid = (int)($row['employee_id'] ?? 0);
                        if ($eid > 0) {
                            $onLeaveToday[$eid] = true;
                        }
                    }
                }
                $stmtLeave->close();
            }

            if ($stmtSched = $mysqli->prepare("SELECT DISTINCT employee_id FROM employee_schedules WHERE day_of_week = ? AND is_working = 1")) {
                $stmtSched->bind_param('s', $todayDow);
                if ($stmtSched->execute()) {
                    $schedRes = $stmtSched->get_result();
                    while ($row = $schedRes->fetch_assoc()) {
                        $eid = (int)($row['employee_id'] ?? 0);
                        if ($eid > 0) {
                            $hasScheduleToday[$eid] = true;
                        }
                    }
                }
                $stmtSched->close();
            }

            foreach ($employees as &$emp) {
                $emp['id'] = (int)($emp['id'] ?? 0);
                $emp['first_name'] = trim($emp['first_name'] ?? '');
                $emp['last_name'] = trim($emp['last_name'] ?? '');
                $emp['address'] = trim($emp['address'] ?? '');
                $emp['gender'] = trim($emp['gender'] ?? '');
                $emp['marital_status'] = trim($emp['marital_status'] ?? 'Single');
                $emp['status'] = trim($emp['status'] ?? 'Active');
                $emp['email'] = trim($emp['email'] ?? '');
                $emp['contact_number'] = cleanPhone($emp['contact_number'] ?? '');
                $emp['emergency_contact_name'] = trim($emp['emergency_contact_name'] ?? '');
                $emp['emergency_contact_phone'] = cleanPhone($emp['emergency_contact_phone'] ?? '');
                $emp['emergency_contact_relationship'] = trim($emp['emergency_contact_relationship'] ?? '');
                $emp['date_joined'] = ($emp['date_joined'] === '0000-00-00' || empty($emp['date_joined'])) ? '' : trim($emp['date_joined']);
                $emp['department_id'] = (int)($emp['department_id'] ?? 0);
                $emp['job_position_id'] = (int)($emp['job_position_id'] ?? 0);
                $emp['rate_per_hour'] = (float)($emp['rate_per_hour'] ?? 0);
                $emp['rate_per_day'] = (float)($emp['rate_per_day'] ?? 0);
                $emp['annual_paid_leave_days'] = (int)($emp['annual_paid_leave_days'] ?? 15);
                $emp['annual_unpaid_leave_days'] = (int)($emp['annual_unpaid_leave_days'] ?? 5);
                $emp['annual_sick_leave_days'] = (int)($emp['annual_sick_leave_days'] ?? 10);
                $emp['avatar_path'] = trim($emp['avatar_path'] ?? '');
                $emp['qr_data'] = trim($emp['qr_data'] ?? '');
                $emp['qr_image_path'] = trim($emp['qr_image_path'] ?? '');
                $emp['created_at'] = trim($emp['created_at'] ?? '');
                $emp['updated_at'] = trim($emp['updated_at'] ?? '');
                $emp['department_name'] = trim($emp['department_name'] ?? 'Unassigned');
                $emp['position_name'] = trim($emp['position_name'] ?? 'Unassigned');

                // Derive a simple per-day status used by the UI
                $eid = (int)$emp['id'];
                if (!empty($onLeaveToday[$eid])) {
                    $emp['today_status'] = 'on_leave';
                } elseif (!empty($todayAttendance[$eid])) {
                    $hasIn = !empty($todayAttendance[$eid]['has_in']);
                    $hasOut = !empty($todayAttendance[$eid]['has_out']);
                    if ($hasIn && !$hasOut) {
                        $emp['today_status'] = 'present_in';
                    } elseif ($hasOut) {
                        $emp['today_status'] = 'present_out';
                    } else {
                        $emp['today_status'] = 'no_log';
                    }
                } else {
                    if (!empty($hasScheduleToday[$eid])) {
                        $emp['today_status'] = 'no_log';
                    } else {
                        $emp['today_status'] = 'no_schedule';
                    }
                }
            }
            unset($emp);

            ob_end_clean();
            echo json_encode(['success' => true, 'data' => $employees], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("List Employees Error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to fetch employees: ' . $e->getMessage()]);
        }
        break;

    case 'get_employee':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'ID required']);
            break;
        }
        try {
            $query = "
                SELECT e.employee_id AS id, e.user_id, e.first_name, e.last_name, e.department_id, 
                       e.position_id AS job_position_id, e.hire_date AS date_joined, e.address, e.gender, 
                       e.marital_status, e.status, e.email, e.contact_number, e.emergency_contact_name, 
                       e.emergency_contact_phone, e.emergency_contact_relationship, e.rate_per_hour, 
                       e.rate_per_day, e.annual_paid_leave_days, e.annual_unpaid_leave_days, 
                       e.annual_sick_leave_days, e.avatar_path, e.created_at, e.updated_at, e.date_of_birth,
                       d.department_name AS department_name, jp.position_name AS position_name,
                       qc.qr_data, qc.qr_image_path
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.department_id
                LEFT JOIN job_positions jp ON e.position_id = jp.position_id
                LEFT JOIN qr_codes qc ON e.employee_id = qc.employee_id
                WHERE e.employee_id = ?
            ";
            $stmt = $mysqli->prepare($query);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $emp = $result->fetch_assoc();
            $stmt->close();

            if (!$emp) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Employee not found']);
                break;
            }

            $emp['id'] = (int)$emp['id'];
            $emp['first_name'] = trim($emp['first_name'] ?? '');
            $emp['last_name'] = trim($emp['last_name'] ?? '');
            $emp['address'] = trim($emp['address'] ?? '');
            $emp['gender'] = trim($emp['gender'] ?? '');
            $emp['marital_status'] = trim($emp['marital_status'] ?? 'Single');
            $emp['status'] = trim($emp['status'] ?? 'Active');
            $emp['email'] = trim($emp['email'] ?? '');
            $emp['contact_number'] = cleanPhone($emp['contact_number'] ?? '');
            $emp['emergency_contact_name'] = trim($emp['emergency_contact_name'] ?? '');
            $emp['emergency_contact_phone'] = cleanPhone($emp['emergency_contact_phone'] ?? '');
            $emp['emergency_contact_relationship'] = trim($emp['emergency_contact_relationship'] ?? '');
            $emp['date_joined'] = ($emp['date_joined'] === '0000-00-00' || empty($emp['date_joined'])) ? '' : trim($emp['date_joined']);
            $emp['department_id'] = (int)$emp['department_id'];
            $emp['job_position_id'] = (int)$emp['job_position_id'];
            $emp['rate_per_hour'] = (float)$emp['rate_per_hour'];
            $emp['rate_per_day'] = (float)($emp['rate_per_day'] ?? 0);
            $emp['annual_paid_leave_days'] = (int)($emp['annual_paid_leave_days'] ?? 15);
            $emp['annual_unpaid_leave_days'] = (int)($emp['annual_unpaid_leave_days'] ?? 5);
            $emp['annual_sick_leave_days'] = (int)($emp['annual_sick_leave_days'] ?? 10);
            $emp['avatar_path'] = trim($emp['avatar_path'] ?? '');
            $emp['qr_data'] = trim($emp['qr_data'] ?? '');
            $emp['qr_image_path'] = trim($emp['qr_image_path'] ?? '');
            $emp['created_at'] = trim($emp['created_at'] ?? '');
            $emp['updated_at'] = trim($emp['updated_at'] ?? '');
            $emp['department_name'] = trim($emp['department_name'] ?? 'Unassigned');
            $emp['position_name'] = trim($emp['position_name'] ?? 'Unassigned');

            ob_end_clean();
            echo json_encode(['success' => true, 'data' => $emp], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Get Employee Error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to fetch employee: ' . $e->getMessage()]);
        }
        break;

    case 'add_employee':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'POST required']);
            break;
        }

        // Check if user has head_admin role
        session_start();
        $userRoles = $_SESSION['roles'] ?? [];
        if (!in_array('head_admin', $userRoles)) {
            ob_end_clean();
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Only Head Admin can add employees']);
            break;
        }

        $mysqli->begin_transaction();
        try {
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $gender = trim($_POST['gender'] ?? '');
            $marital_status = trim($_POST['marital_status'] ?? 'Single');
            $email = trim($_POST['email'] ?? '');
            $contact_number = cleanPhone($_POST['contact_number'] ?? '');
            $emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
            $emergency_contact_phone = cleanPhone($_POST['emergency_contact_phone'] ?? '');
            $emergency_contact_relationship = trim($_POST['emergency_contact_relationship'] ?? '');
            $department_id = (int)($_POST['department_id'] ?? 0);
            $job_position_id = (int)($_POST['job_position_id'] ?? 0);
            $annual_paid_leave_days = (int)($_POST['annual_paid_leave_days'] ?? 15);
            $annual_unpaid_leave_days = (int)($_POST['annual_unpaid_leave_days'] ?? 5);
            $annual_sick_leave_days = (int)($_POST['annual_sick_leave_days'] ?? 10);

            // Detailed validation with specific error messages
            if (empty($first_name)) throw new Exception('First name is required.');
            if (empty($last_name)) throw new Exception('Last name is required.');
            if (empty($address)) throw new Exception('Address is required.');
            if (empty($gender)) throw new Exception('Gender is required.');
            if (empty($email)) throw new Exception('Email is required.');
            if (empty($contact_number)) throw new Exception('Contact number is required.');
            if (empty($emergency_contact_name)) throw new Exception('Emergency contact name is required.');
            if (empty($emergency_contact_phone)) throw new Exception('Emergency contact phone is required.');
            if (empty($emergency_contact_relationship)) throw new Exception('Emergency contact relationship is required.');
            if ($department_id <= 0) throw new Exception('Department is required.');
            if ($job_position_id <= 0) throw new Exception('Job position is required.');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format.');
            }
            if (!validatePhone($contact_number) || !validatePhone($emergency_contact_phone)) {
                throw new Exception('Phone numbers must be 11 digits.');
            }

            // Check if email exists in users_employee or employees
            $check_stmt = $mysqli->prepare("SELECT id FROM users_employee WHERE email = ? UNION SELECT employee_id FROM employees WHERE email = ?");
            $check_stmt->bind_param('ss', $email, $email);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                throw new Exception('Email already exists.');
            }
            $check_stmt->close();

            // Validate department and position
            $dept_check = $mysqli->prepare("SELECT department_id FROM departments WHERE department_id = ?");
            $dept_check->bind_param('i', $department_id);
            $dept_check->execute();
            if ($dept_check->get_result()->num_rows === 0) {
                throw new Exception('Invalid department.');
            }
            $dept_check->close();

            $pos_check = $mysqli->prepare("SELECT position_id, rate_per_hour, rate_per_day FROM job_positions WHERE position_id = ?");
            $pos_check->bind_param('i', $job_position_id);
            $pos_check->execute();
            $pos_result = $pos_check->get_result();
            if ($pos_result->num_rows === 0) {
                throw new Exception('Invalid job position.');
            }
            $pos_data = $pos_result->fetch_assoc();
            $rate_per_hour = (float)($pos_data['rate_per_hour'] ?? 0);
            $rate_per_day = (float)($pos_data['rate_per_day'] ?? 0);
            $pos_check->close();

            // Handle avatar upload
            $avatar_path = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/avatars/'; // Fixed path
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $file_ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename = 'emp_' . time() . '_' . uniqid() . '.' . $file_ext;
                $target_path = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_path)) {
                    $avatar_path = 'uploads/avatars/' . $filename;
                } else {
                    throw new Exception('Failed to upload avatar.');
                }
            }

            // Insert into users_employee first
            $password_hash = password_hash('12345678', PASSWORD_DEFAULT);
            $user_stmt = $mysqli->prepare("INSERT INTO users_employee (first_name, last_name, email, phone_number, address, department_id, roles, password_hash) VALUES (?, ?, ?, ?, ?, ?, '[\"employee\"]', ?)");
            $user_stmt->bind_param('sssssis', $first_name, $last_name, $email, $contact_number, $address, $department_id, $password_hash);
            if (!$user_stmt->execute()) {
                throw new Exception('Failed to create user account.');
            }
            $user_id = $mysqli->insert_id;
            $user_stmt->close();

            // Insert into employees
            // Optional Date of Birth
            $date_of_birth = trim($_POST['date_of_birth'] ?? '');
            if ($date_of_birth === '') {
                $date_of_birth = null;
            } else {
                // Basic validation: YYYY-MM-DD and not in the future
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_of_birth)) {
                    throw new Exception('Invalid Date of Birth format. Use YYYY-MM-DD.');
                }
                if (strtotime($date_of_birth) > time()) {
                    throw new Exception('Date of Birth cannot be in the future.');
                }
            }

            $query = "INSERT INTO employees (
            user_id, first_name, last_name, address, gender, marital_status, status, email,
            contact_number, emergency_contact_name, emergency_contact_phone,
            emergency_contact_relationship, date_of_birth, hire_date, department_id, position_id,
            rate_per_hour, rate_per_day, annual_paid_leave_days, annual_unpaid_leave_days,
            annual_sick_leave_days, avatar_path, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, 'Active', ?, ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $stmt = $mysqli->prepare($query);
            $types = 'isssssssssssiiddiiis';
            $params = [
                $user_id,
                $first_name,
                $last_name,
                $address,
                $gender,
                $marital_status,
                $email,
                $contact_number,
                $emergency_contact_name,
                $emergency_contact_phone,
                $emergency_contact_relationship,
                $date_of_birth,
                $department_id,
                $job_position_id,
                $rate_per_hour,
                $rate_per_day,
                $annual_paid_leave_days,
                $annual_unpaid_leave_days,
                $annual_sick_leave_days,
                $avatar_path
            ];
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) {
                throw new Exception('Failed to insert employee.');
            }
            $new_id = $mysqli->insert_id;
            $stmt->close();

            if (!$new_id) {
                throw new Exception('Failed to insert employee.');
            }

            // Generate QR
            $fetch_query = "
                SELECT e.employee_id AS id, e.first_name, e.last_name, e.position_id AS job_position_id,
                       e.hire_date AS date_joined, d.department_name, jp.position_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.department_id
                LEFT JOIN job_positions jp ON e.position_id = jp.position_id
                WHERE e.employee_id = ?
            ";
            $fetch_stmt = $mysqli->prepare($fetch_query);
            $fetch_stmt->bind_param('i', $new_id);
            $fetch_stmt->execute();
            $result = $fetch_stmt->get_result();
            $new_employee = $result->fetch_assoc();
            $fetch_stmt->close();

            if (!$new_employee) {
                throw new Exception('Failed to fetch new employee data.');
            }

            $qr_result = generateQRCode($new_employee);
            $qr_path = $qr_result['path'];
            $qr_data = $qr_result['data'];
            if ($qr_data && $qr_path) {
                $qr_stmt = $mysqli->prepare("INSERT INTO qr_codes (employee_id, qr_data, qr_image_path) VALUES (?, ?, ?)");
                $qr_stmt->bind_param('iss', $new_id, $qr_data, $qr_path);
                $qr_stmt->execute();
                $qr_stmt->close();
                error_log("QR Record Inserted for Employee ID $new_id");
            }

            $mysqli->commit();
            $msg = 'Employee added successfully';
            if ($avatar_path) {
                $msg .= ' with avatar';
            }
            if ($qr_path) {
                $msg .= ' and QR code';
            }
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => $msg, 'data' => ['id' => $new_id]]);
        } catch (Exception $e) {
            $mysqli->rollback();
            error_log("Add Employee Error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'edit_employee':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'POST required']);
            break;
        }
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Employee ID required']);
            break;
        }
        $mysqli->begin_transaction();
        try {
            // Get current employee and user_id (systemintegration schema)
            $current_stmt = $mysqli->prepare("
                SELECT e.*, u.id AS user_id, d.department_name AS department_name, jp.position_name AS position_name,
                       qc.qr_data, qc.qr_image_path
                FROM employees e
                LEFT JOIN users_employee u ON e.user_id = u.id
                LEFT JOIN departments d ON e.department_id = d.department_id
                LEFT JOIN job_positions jp ON e.position_id = jp.position_id
                LEFT JOIN qr_codes qc ON e.employee_id = qc.employee_id
                WHERE e.employee_id = ?
            ");
            $current_stmt->bind_param('i', $id);
            $current_stmt->execute();
            $current_result = $current_stmt->get_result();
            $current_employee = $current_result->fetch_assoc();
            $current_stmt->close();

            if (!$current_employee) {
                throw new Exception('Employee not found.');
            }
            $user_id = $current_employee['user_id'];

            function getUpdateValue($post_key, $current_val)
            {
                $value = $_POST[$post_key] ?? null;
                if ($value === null) {
                    $update_key = 'update-' . str_replace('_', '-', $post_key);
                    $value = $_POST[$update_key] ?? $current_val;
                }
                return $value;
            }

            // Get updated values
            $first_name = trim(getUpdateValue('first_name', $current_employee['first_name']));
            $last_name = trim(getUpdateValue('last_name', $current_employee['last_name']));
            $address = trim(getUpdateValue('address', $current_employee['address']));
            $gender = trim(getUpdateValue('gender', $current_employee['gender']));
            $marital_status = trim(getUpdateValue('marital_status', $current_employee['marital_status']));
            $email = trim(getUpdateValue('email', $current_employee['email']));
            $contact_number = cleanPhone($_POST['contact_number'] ?? $current_employee['contact_number']);
            $emergency_contact_name = trim(getUpdateValue('emergency_contact_name', $current_employee['emergency_contact_name']));
            $emergency_contact_phone = cleanPhone($_POST['emergency_contact_phone'] ?? $current_employee['emergency_contact_phone']);
            $emergency_contact_relationship = trim(getUpdateValue('emergency_contact_relationship', $current_employee['emergency_contact_relationship']));
            $department_id = (int)(getUpdateValue('department_id', $current_employee['department_id']));
            // In systemintegration schema the column is position_id, but the form field is job_position_id
            $job_position_id = (int)(getUpdateValue('job_position_id', $current_employee['position_id']));
            $annual_paid_leave_days = (int)(getUpdateValue('annual_paid_leave_days', $current_employee['annual_paid_leave_days']));
            $annual_unpaid_leave_days = (int)(getUpdateValue('annual_unpaid_leave_days', $current_employee['annual_unpaid_leave_days']));
            $annual_sick_leave_days = (int)(getUpdateValue('annual_sick_leave_days', $current_employee['annual_sick_leave_days']));
            // hire_date is the canonical column; date_joined is the form field
            $date_joined = getUpdateValue('date_joined', $current_employee['hire_date']);
            $date_of_birth = getUpdateValue('date_of_birth', $current_employee['date_of_birth']);
            if ($date_of_birth !== null && $date_of_birth !== '') {
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_of_birth)) {
                    throw new Exception('Invalid Date of Birth format. Use YYYY-MM-DD.');
                }
                if (strtotime($date_of_birth) > time()) {
                    throw new Exception('Date of Birth cannot be in the future.');
                }
            }

            // Fetch rate_per_hour based on selected job_position_id
            $pos_check = $mysqli->prepare("SELECT rate_per_hour, rate_per_day FROM job_positions WHERE position_id = ?");
            $pos_check->bind_param('i', $job_position_id);
            $pos_check->execute();
            $pos_result = $pos_check->get_result();
            if ($pos_result->num_rows === 0) {
                throw new Exception('Invalid job position selected.');
            }
            $pos_data = $pos_result->fetch_assoc();
            $rate_per_hour = (float)($pos_data['rate_per_hour'] ?? 0);
            $rate_per_day = (float)($pos_data['rate_per_day'] ?? 0);
            $pos_check->close();

            // Check if QR needs regeneration (name, position, or hire date changed)
            $qr_changed = (
                $first_name !== $current_employee['first_name'] ||
                $last_name !== $current_employee['last_name'] ||
                $job_position_id !== (int)$current_employee['position_id'] ||
                $date_joined !== $current_employee['hire_date']
            );

            // Validate
            if (empty($first_name) || empty($last_name) || empty($address) || empty($gender) || empty($email) || $department_id <= 0 || $job_position_id <= 0 || $rate_per_hour < 0) {
                throw new Exception('Required fields cannot be empty.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format.');
            }
            if (!empty($contact_number) && !validatePhone($contact_number)) {
                throw new Exception('Contact number must be 11 digits.');
            }
            if (!empty($emergency_contact_phone) && !validatePhone($emergency_contact_phone)) {
                throw new Exception('Emergency phone must be 11 digits.');
            }

            // Check email uniqueness
            if ($email !== $current_employee['email']) {
                $check_stmt = $mysqli->prepare("SELECT id FROM users_employee WHERE email = ? AND id != ? UNION SELECT employee_id FROM employees WHERE email = ? AND employee_id != ?");
                $check_stmt->bind_param('sisi', $email, $user_id, $email, $id);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    throw new Exception('Email already exists.');
                }
                $check_stmt->close();
            }

            // Validate department
            if ($department_id !== (int)$current_employee['department_id']) {
                $dept_check = $mysqli->prepare("SELECT department_id FROM departments WHERE department_id = ?");
                $dept_check->bind_param('i', $department_id);
                $dept_check->execute();
                if ($dept_check->get_result()->num_rows === 0) {
                    throw new Exception('Invalid department selected.');
                }
                $dept_check->close();
            }

            // Handle avatar
            $avatar_path = $current_employee['avatar_path'];
            $avatar_changed = false;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                if ($avatar_path && file_exists('../' . $avatar_path)) {
                    unlink('../' . $avatar_path);
                }
                $upload_dir = '../uploads/avatars/'; // Fixed path
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $file_ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                $filename = 'emp_' . $id . '_' . time() . '.' . $file_ext;
                $target_path = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['avatar']['tmp_name'], $target_path)) {
                    $avatar_path = 'uploads/avatars/' . $filename;
                    $avatar_changed = true;
                } else {
                    throw new Exception('Failed to upload new avatar.');
                }
            }

            // Update users_employee table
            $user_update = $mysqli->prepare("UPDATE users_employee SET first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, department_id = ?, avatar_path = ? WHERE id = ?");
            $user_update->bind_param('sssssisi', $first_name, $last_name, $email, $contact_number, $address, $department_id, $avatar_path, $user_id);
            $user_update->execute();
            $user_update->close();

            // Update employees table (also bump updated_at)
            $query = "UPDATE employees SET 
                first_name = ?, last_name = ?, address = ?, gender = ?, marital_status = ?,
                email = ?, contact_number = ?, emergency_contact_name = ?, emergency_contact_phone = ?,
                emergency_contact_relationship = ?, department_id = ?, position_id = ?,
                rate_per_hour = ?, rate_per_day = ?, annual_paid_leave_days = ?, annual_unpaid_leave_days = ?,
                annual_sick_leave_days = ?, hire_date = ?, date_of_birth = ?, avatar_path = ?,
                updated_at = NOW()
                WHERE employee_id = ?";
            $stmt = $mysqli->prepare($query);
            $types = 'ssssssssssiiddiiisssi';
            $params = [
                $first_name,
                $last_name,
                $address,
                $gender,
                $marital_status,
                $email,
                $contact_number,
                $emergency_contact_name,
                $emergency_contact_phone,
                $emergency_contact_relationship,
                $department_id,
                $job_position_id,
                $rate_per_hour,
                $rate_per_day,
                $annual_paid_leave_days,
                $annual_unpaid_leave_days,
                $annual_sick_leave_days,
                $date_joined,
                $date_of_birth,
                $avatar_path,
                $id
            ];
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);
            }
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected === 0) {
                throw new Exception('No changes made or employee not found.');
            }

            // Handle QR regeneration
            $qr_path = null;
            $qr_data = null;
            if ($qr_changed) {
                try {
                    deleteQRCode($mysqli, $id);
                    $updated_query = "
                        SELECT e.employee_id AS id, e.first_name, e.last_name, e.position_id AS job_position_id,
                               e.hire_date AS date_joined, d.department_name, jp.position_name
                        FROM employees e
                        LEFT JOIN departments d ON e.department_id = d.department_id
                        LEFT JOIN job_positions jp ON e.position_id = jp.position_id
                        WHERE e.employee_id = ?
                    ";
                    $updated_stmt = $mysqli->prepare($updated_query);
                    $updated_stmt->bind_param('i', $id);
                    $updated_stmt->execute();
                    $updated_result = $updated_stmt->get_result();
                    $updated_employee_for_qr = $updated_result->fetch_assoc();
                    $updated_stmt->close();

                    if ($updated_employee_for_qr) {
                        $qr_result = generateQRCode($updated_employee_for_qr);
                        $qr_path = $qr_result['path'];
                        $qr_data = $qr_result['data'];

                        if ($qr_data && $qr_path) {
                            $qr_stmt = $mysqli->prepare("INSERT INTO qr_codes (employee_id, qr_data, qr_image_path) VALUES (?, ?, ?)");
                            $qr_stmt->bind_param('iss', $id, $qr_data, $qr_path);
                            $qr_stmt->execute();
                            $qr_stmt->close();
                            error_log("QR Regenerated and Inserted for Employee ID $id");
                        }
                    }
                } catch (Exception $e) {
                    error_log("QR Regeneration Failed for Employee ID $id: " . $e->getMessage());
                }
            }

            $mysqli->commit();
            $msg = 'Employee updated successfully';
            if ($avatar_changed) {
                $msg .= ' with new avatar';
            }
            if ($qr_changed && $qr_path) {
                $msg .= ' and updated QR code';
            }
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => $msg]);
        } catch (Exception $e) {
            $mysqli->rollback();
            error_log("Edit Employee Error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_employee':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'POST required']);
            break;
        }

        // Check if user has head_admin role
        session_start();
        $userRoles = $_SESSION['roles'] ?? [];
        if (!in_array('head_admin', $userRoles)) {
            ob_end_clean();
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Only Head Admin can delete employees']);
            break;
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Employee ID required']);
            break;
        }
        $mysqli->begin_transaction();
        try {
            // Get user_id and cleanup data
            $cleanup_query = "
                SELECT e.user_id, e.avatar_path, qc.qr_image_path
                FROM employees e
                LEFT JOIN qr_codes qc ON e.employee_id = qc.employee_id
                WHERE e.employee_id = ?
            ";
            $stmt = $mysqli->prepare($cleanup_query);
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $emp = $result->fetch_assoc();
            $stmt->close();

            if ($emp) {
                $user_id = $emp['user_id'];

                // Delete avatar
                if ($emp['avatar_path'] && file_exists('../' . $emp['avatar_path'])) {
                    if (!unlink('../' . $emp['avatar_path'])) {
                        error_log("Failed to delete avatar: ../" . $emp['avatar_path']);
                    } else {
                        error_log("Avatar Deleted for Employee ID $id");
                    }
                }

                // Delete QR
                deleteQRCode($mysqli, $id);

                // Delete leave requests associated with this employee
                $stmt = $mysqli->prepare("DELETE FROM leave_requests WHERE employee_id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $deleted_requests = $stmt->affected_rows;
                $stmt->close();
                if ($deleted_requests > 0) {
                    error_log("Deleted $deleted_requests leave request(s) for Employee ID $id");
                }

                // Delete from employees
                $stmt = $mysqli->prepare("DELETE FROM employees WHERE employee_id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();

                // Delete from users_employee
                $stmt = $mysqli->prepare("DELETE FROM users_employee WHERE id = ?");
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $stmt->close();
            }

            $mysqli->commit();
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Employee deleted successfully']);
        } catch (Exception $e) {
            $mysqli->rollback();
            error_log("Delete Employee Error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'check_last_head_admin':
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Employee ID required']);
            break;
        }
        try {
            // Get user_id for the employee
            $stmt = $mysqli->prepare("SELECT user_id FROM employees WHERE employee_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $emp = $result->fetch_assoc();
            $stmt->close();

            if (!$emp || !$emp['user_id']) {
                ob_end_clean();
                echo json_encode(['success' => true, 'is_last_head_admin' => false]);
                break;
            }

            $user_id = $emp['user_id'];

            // Check if this user has head_admin role
            $role_stmt = $mysqli->prepare("SELECT JSON_CONTAINS(roles, '\"head_admin\"') as is_head_admin FROM users_employee WHERE id = ?");
            $role_stmt->bind_param('i', $user_id);
            $role_stmt->execute();
            $role_result = $role_stmt->get_result();
            $user = $role_result->fetch_assoc();
            $role_stmt->close();

            if (!$user || !$user['is_head_admin']) {
                ob_end_clean();
                echo json_encode(['success' => true, 'is_last_head_admin' => false]);
                break;
            }

            // Check if there are other active head admins
            $count_stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM users_employee WHERE JSON_CONTAINS(roles, '\"head_admin\"') AND is_active = 1 AND id != ?");
            $count_stmt->bind_param('i', $user_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $other_head_admins = $count_result->fetch_assoc()['count'];
            $count_stmt->close();

            $is_last = $other_head_admins === 0;

            ob_end_clean();
            echo json_encode(['success' => true, 'is_last_head_admin' => $is_last]);
        } catch (Exception $e) {
            error_log("Check Last Head Admin Error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'departments':
        try {
            $query = "SELECT department_id AS id, department_name AS name FROM departments ORDER BY department_name";
            $result = $mysqli->query($query);
            if (!$result) {
                throw new Exception('Query failed: ' . $mysqli->error);
            }
            $departments = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
            ob_end_clean();
            echo json_encode(['success' => true, 'data' => $departments], JSON_UNESCAPED_SLASHES);
        } catch (Exception $e) {
            error_log("Departments Error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to fetch departments: ' . $e->getMessage()]);
        }
        break;

    case 'positions':
        try {
            $query = "SELECT position_id AS id, position_name AS name, rate_per_hour, rate_per_day FROM job_positions ORDER BY position_name";
            $result = $mysqli->query($query);
            if (!$result) {
                throw new Exception('Query failed: ' . $mysqli->error);
            }
            $positions = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
            ob_end_clean();
            echo json_encode(['success' => true, 'data' => $positions], JSON_UNESCAPED_SLASHES);
        } catch (Exception $e) {
            error_log("Positions Error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to fetch job positions: ' . $e->getMessage()]);
        }
        break;

    case 'check_email':
        $email = $_GET['email'] ?? '';
        if (!$email) {
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Email required']);
            break;
        }
        try {
            // Check if email exists in employees
            $emp_stmt = $mysqli->prepare("SELECT * FROM employees WHERE email = ?");
            $emp_stmt->bind_param('s', $email);
            $emp_stmt->execute();
            $emp_result = $emp_stmt->get_result();
            $employee = $emp_result->fetch_assoc();
            $emp_stmt->close();

            // Check if email exists in users_employee
            $user_stmt = $mysqli->prepare("SELECT id FROM users_employee WHERE email = ?");
            $user_stmt->bind_param('s', $email);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            $user_exists = $user_result->num_rows > 0;
            $user_stmt->close();

            $response = [
                'success' => true,
                'exists' => $employee ? true : false,
                'user_exists' => $user_exists,
                'data' => $employee
            ];

            ob_end_clean();
            echo json_encode($response);
        } catch (Exception $e) {
            error_log("Check Email Error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'get_school_settings':
        try {
            $query = "SELECT annual_paid_leave_days, annual_unpaid_leave_days, annual_sick_leave_days FROM eaaps_school_settings WHERE id = 1";
            $result = $mysqli->query($query);
            if (!$result) {
                throw new Exception('Query failed: ' . $mysqli->error);
            }
            $settings = $result->fetch_assoc();
            $result->free();

            if (!$settings) {
                throw new Exception('School settings not found');
            }

            ob_end_clean();
            echo json_encode(['success' => true, 'data' => $settings], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (Exception $e) {
            error_log("Get School Settings Error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
        break;
}

if ($mysqli) {
    $mysqli->close();
}
ob_end_flush();
