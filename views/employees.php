<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

define('BASE_PATH', ''); // Change to '' for localhost:8000, or '/newpath' for Hostinger

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'conn.php';  // Adjusted path for views/ location

$qr_lib_path = '../phpqrcode/qrlib.php'; // Fixed path
if (file_exists($qr_lib_path)) {
    require_once $qr_lib_path;
} else {
    error_log("QR Library missing: Place qrlib.php in root/phpqrcode/");
}

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

function generateQRCode($employee_data, $qr_dir = '../qrcodes/') // Fixed default path
{
    // Ensure library and GD are available
    if (!class_exists('QRcode')) {
        error_log("QR Library not loaded – class QRcode not found");
        return ['path' => null, 'data' => null];
    }
    if (!extension_loaded('gd') || !function_exists('imagepng')) {
        error_log("GD extension not available – skipping QR generation");
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

        // Build data: First name, last name, position name, date joined
        $id = (int)($employee_data['id'] ?? 0);
        $first = trim(preg_replace('/[^a-zA-Z0-9]/', '', $employee_data['first_name'] ?? 'EMP'));
        if ($first === '') $first = 'EMP';
        $last  = trim(preg_replace('/[^a-zA-Z0-9]/', '', $employee_data['last_name'] ?? ''));
        $pos = trim($employee_data['position_name'] ?? 'N/A');  // Job position name from joined table
        $joined = (!empty($employee_data['date_joined']) && $employee_data['date_joined'] !== '0000-00-00')
            ? $employee_data['date_joined'] : 'N/A';

        $qr_data = "ID:$id|First:$first|Last:$last|Position:$pos|Joined:$joined";

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

        // Prevent accidental output from library breaking JSON
        $obLevel = ob_get_level();
        ob_start();
        $ecc = defined('QR_ECLEVEL_L') ? QR_ECLEVEL_L : 0;
        QRcode::png($qr_data, $file_path, $ecc, 10, 2);
        while (ob_get_level() > $obLevel) {
            ob_end_clean();
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
                SELECT e.*, d.name AS department_name, jp.name AS position_name,
                       qc.qr_data, qc.qr_image_path
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN job_positions jp ON e.job_position_id = jp.id
                LEFT JOIN qr_codes qc ON e.id = qc.employee_id
                ORDER BY e.last_name, e.first_name
            ";
            $result = $mysqli->query($query);
            if (!$result) {
                throw new Exception('Query failed: ' . $mysqli->error);
            }
            $employees = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();

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
                SELECT e.*, d.name AS department_name, jp.name AS position_name,
                       qc.qr_data, qc.qr_image_path
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN job_positions jp ON e.job_position_id = jp.id
                LEFT JOIN qr_codes qc ON e.id = qc.employee_id
                WHERE e.id = ?
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

            if (empty($first_name) || empty($last_name) || empty($address) || empty($gender) || empty($email) || empty($contact_number) || empty($emergency_contact_name) || empty($emergency_contact_phone) || empty($emergency_contact_relationship) || $department_id <= 0 || $job_position_id <= 0) {
                throw new Exception('All required fields must be provided.');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format.');
            }
            if (!validatePhone($contact_number) || !validatePhone($emergency_contact_phone)) {
                throw new Exception('Phone numbers must be 11 digits.');
            }

            // Check if email exists in users or employees
            $check_stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? UNION SELECT id FROM employees WHERE email = ?");
            $check_stmt->bind_param('ss', $email, $email);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                throw new Exception('Email already exists.');
            }
            $check_stmt->close();

            // Validate department and position
            $dept_check = $mysqli->prepare("SELECT id FROM departments WHERE id = ?");
            $dept_check->bind_param('i', $department_id);
            $dept_check->execute();
            if ($dept_check->get_result()->num_rows === 0) {
                throw new Exception('Invalid department.');
            }
            $dept_check->close();

            $pos_check = $mysqli->prepare("SELECT id, rate_per_hour FROM job_positions WHERE id = ?");
            $pos_check->bind_param('i', $job_position_id);
            $pos_check->execute();
            $pos_result = $pos_check->get_result();
            if ($pos_result->num_rows === 0) {
                throw new Exception('Invalid job position.');
            }
            $pos_data = $pos_result->fetch_assoc();
            $rate_per_hour = (float)($pos_data['rate_per_hour'] ?? 0);
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

            // Insert into users first
            $password_hash = password_hash('12345678', PASSWORD_DEFAULT);
            $user_stmt = $mysqli->prepare("INSERT INTO users (first_name, last_name, email, phone_number, address, department_id, role, password_hash) VALUES (?, ?, ?, ?, ?, ?, 'employee', ?)");
            $user_stmt->bind_param('sssssis', $first_name, $last_name, $email, $contact_number, $address, $department_id, $password_hash);
            if (!$user_stmt->execute()) {
                throw new Exception('Failed to create user account.');
            }
            $user_id = $mysqli->insert_id;
            $user_stmt->close();

            // Insert into employees
            $query = "INSERT INTO employees (
                user_id, first_name, last_name, address, gender, marital_status, status, email,
                contact_number, emergency_contact_name, emergency_contact_phone,
                emergency_contact_relationship, date_joined, department_id, job_position_id,
                rate_per_hour, annual_paid_leave_days, annual_unpaid_leave_days,
                annual_sick_leave_days, avatar_path
            ) VALUES (?, ?, ?, ?, ?, ?, 'Active', ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $mysqli->prepare($query);
            $types = 'issssssssssiidiiis';
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
                $department_id,
                $job_position_id,
                $rate_per_hour,
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
                SELECT e.*, d.name AS department_name, jp.name AS position_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN job_positions jp ON e.job_position_id = jp.id
                WHERE e.id = ?
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
            // Get current employee and user_id
            $current_stmt = $mysqli->prepare("
                SELECT e.*, u.id AS user_id, d.name AS department_name, jp.name AS position_name,
                       qc.qr_data, qc.qr_image_path
                FROM employees e
                LEFT JOIN users u ON e.user_id = u.id
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN job_positions jp ON e.job_position_id = jp.id
                LEFT JOIN qr_codes qc ON e.id = qc.employee_id
                WHERE e.id = ?
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
            $job_position_id = (int)(getUpdateValue('job_position_id', $current_employee['job_position_id']));
            $annual_paid_leave_days = (int)(getUpdateValue('annual_paid_leave_days', $current_employee['annual_paid_leave_days']));
            $annual_unpaid_leave_days = (int)(getUpdateValue('annual_unpaid_leave_days', $current_employee['annual_unpaid_leave_days']));
            $annual_sick_leave_days = (int)(getUpdateValue('annual_sick_leave_days', $current_employee['annual_sick_leave_days']));
            $date_joined = getUpdateValue('date_joined', $current_employee['date_joined']);

            // Fetch rate_per_hour based on selected job_position_id
            $pos_check = $mysqli->prepare("SELECT rate_per_hour FROM job_positions WHERE id = ?");
            $pos_check->bind_param('i', $job_position_id);
            $pos_check->execute();
            $pos_result = $pos_check->get_result();
            if ($pos_result->num_rows === 0) {
                throw new Exception('Invalid job position selected.');
            }
            $pos_data = $pos_result->fetch_assoc();
            $rate_per_hour = (float)($pos_data['rate_per_hour'] ?? 0);
            $pos_check->close();

            // Check if QR needs regeneration
            $qr_changed = ($first_name !== $current_employee['first_name'] ||
                $last_name !== $current_employee['last_name'] ||
                $job_position_id !== (int)$current_employee['job_position_id'] ||
                $date_joined !== $current_employee['date_joined']);

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
                $check_stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? AND id != ? UNION SELECT id FROM employees WHERE email = ? AND id != ?");
                $check_stmt->bind_param('sisi', $email, $user_id, $email, $id);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    throw new Exception('Email already exists.');
                }
                $check_stmt->close();
            }

            // Validate department
            if ($department_id !== (int)$current_employee['department_id']) {
                $dept_check = $mysqli->prepare("SELECT id FROM departments WHERE id = ?");
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

            // Update users table
            $user_update = $mysqli->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone_number = ?, address = ?, department_id = ? WHERE id = ?");
            $user_update->bind_param('sssssii', $first_name, $last_name, $email, $contact_number, $address, $department_id, $user_id);
            $user_update->execute();
            $user_update->close();

            // Update employees table
            $query = "UPDATE employees SET 
                first_name = ?, last_name = ?, address = ?, gender = ?, marital_status = ?,
                email = ?, contact_number = ?, emergency_contact_name = ?, emergency_contact_phone = ?,
                emergency_contact_relationship = ?, department_id = ?, job_position_id = ?,
                rate_per_hour = ?, annual_paid_leave_days = ?, annual_unpaid_leave_days = ?,
                annual_sick_leave_days = ?, date_joined = ?, avatar_path = ?
                WHERE id = ?";
            $stmt = $mysqli->prepare($query);
            $types = 'ssssssssssiidiiissi';
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
                $annual_paid_leave_days,
                $annual_unpaid_leave_days,
                $annual_sick_leave_days,
                $date_joined,
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
                        SELECT e.*, d.name AS department_name, jp.name AS position_name
                        FROM employees e
                        LEFT JOIN departments d ON e.department_id = d.id
                        LEFT JOIN job_positions jp ON e.job_position_id = jp.id
                        WHERE e.id = ?
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
                LEFT JOIN qr_codes qc ON e.id = qc.employee_id
                WHERE e.id = ?
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

                // Delete from employees
                $stmt = $mysqli->prepare("DELETE FROM employees WHERE id = ?");
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $stmt->close();

                // Delete from users
                $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
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

    case 'departments':
        try {
            $query = "SELECT id, name FROM departments ORDER BY name";
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
            $query = "SELECT id, name, rate_per_hour FROM job_positions ORDER BY name";
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

    default:
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
        break;
}

if ($mysqli) {
    $mysqli->close();
}
ob_end_flush();
