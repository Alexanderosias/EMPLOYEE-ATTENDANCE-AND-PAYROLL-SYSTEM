<?php
ob_start();  // Buffer all output for clean JSON
// Suppress error display to prevent HTML output breaking JSON (logs to error_log instead)
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');  // Adjust for security (e.g., specific domain)
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

// Include your database connection file
require_once 'conn.php';

// Helper: Regenerate QR Code for Employee (delete old, generate new)
function regenerateQRCode($mysqli, $employee_id, $first_name, $last_name, $job_position_id, $old_data = null)
{
    $qr_regenerated = false;
    try {
        // Include QR library
        require_once '../phpqrcode/qrlib.php';

        // First, delete old QR if exists (from qr_codes table and file)
        $old_qr_stmt = $mysqli->prepare("SELECT qr_image_path FROM qr_codes WHERE employee_id = ?");
        if ($old_qr_stmt) {
            $old_qr_stmt->bind_param('i', $employee_id);
            $old_qr_stmt->execute();
            $old_qr_result = $old_qr_stmt->get_result();
            $old_qr = $old_qr_result->fetch_assoc();
            $old_qr_stmt->close();
            if ($old_qr) {
                // Delete file if exists
                $old_path = '../' . $old_qr['qr_image_path'];
                if (file_exists($old_path)) {
                    unlink($old_path);
                }
                // Delete DB entry
                $del_stmt = $mysqli->prepare("DELETE FROM qr_codes WHERE employee_id = ?");
                if ($del_stmt) {
                    $del_stmt->bind_param('i', $employee_id);
                    $del_stmt->execute();
                    $del_stmt->close();
                }
            }
        }

        // Fetch updated employee details for new QR (id, first_name, last_name, date_joined, position)
        $emp_query = "
            SELECT 
                e.id, e.first_name, e.last_name, e.date_joined,
                jp.name AS position_name
            FROM employees e
            LEFT JOIN job_positions jp ON e.job_position_id = jp.id
            WHERE e.id = ?
        ";
        $emp_stmt = $mysqli->prepare($emp_query);
        if (!$emp_stmt) {
            throw new Exception('QR Fetch failed: ' . $mysqli->error);
        }
        $emp_stmt->bind_param('i', $employee_id);
        $emp_stmt->execute();
        $emp_result = $emp_stmt->get_result();
        $emp_data = $emp_result->fetch_assoc();
        $emp_stmt->close();

        if (!$emp_data) {
            throw new Exception('Employee data not found for QR generation');
        }

        // Prepare new QR data as JSON (separate first_name and last_name)
        $qr_data = json_encode([
            'id' => (int)$emp_data['id'],
            'first_name' => trim($emp_data['first_name'] ?? ''),
            'last_name' => trim($emp_data['last_name'] ?? ''),
            'job_position' => trim($emp_data['position_name'] ?? 'Unassigned'),
            'date_joined' => trim($emp_data['date_joined'] ?? date('Y-m-d H:i:s'))
        ], JSON_UNESCAPED_SLASHES);

        // QR filename based on updated first_name + last_name (sanitized) + ID
        $safe_first = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower(trim($first_name ?? '')));
        $safe_last = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower(trim($last_name ?? '')));
        if (empty($safe_first) || empty($safe_last)) {
            $qr_filename = 'qr_emp_' . $employee_id . '.png';
        } else {
            $qr_filename = 'qr_' . $safe_first . '_' . $safe_last . '_' . $employee_id . '.png';
        }

        // QR Settings
        $qr_dir = '../qrcodes/';
        if (!is_dir($qr_dir)) {
            mkdir($qr_dir, 0755, true);
        }
        $qr_path = $qr_dir . $qr_filename;
        $qr_relative_path = 'qrcodes/' . $qr_filename;

        // Generate and save new QR PNG
        QRcode::png($qr_data, $qr_path, QR_ECLEVEL_M, 4, 2);

        if (!file_exists($qr_path)) {
            throw new Exception('QR image not generated');
        }

        // Insert new QR into qr_codes table
        $qr_insert = "INSERT INTO qr_codes (employee_id, qr_data, qr_image_path, generated_at) VALUES (?, ?, ?, NOW())";
        $qr_stmt = $mysqli->prepare($qr_insert);
        if (!$qr_stmt) {
            throw new Exception('QR Insert prepare failed: ' . $mysqli->error);
        }
        $qr_stmt->bind_param('iss', $employee_id, $qr_data, $qr_relative_path);
        $qr_stmt->execute();
        $qr_stmt->close();

        $qr_regenerated = true;
        error_log("QR Regenerated Successfully for Employee ID: " . $employee_id . " | Filename: " . $qr_filename . " | Data: " . $qr_data);
    } catch (Exception $qr_error) {
        error_log("QR Regeneration Error for Employee ID " . $employee_id . ": " . $qr_error->getMessage());
        // Do not fail the edit; just log
    }
    return $qr_regenerated;
}


// Get MySQLi connection
$mysqli = null;
try {
    $mysqli = conn();
} catch (Exception $e) {
    ob_end_clean();
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

// Function to clean phone (remove non-digits; backup since JS cleans)
function cleanPhone($phone)
{
    return preg_replace('/\D/', '', $phone);  // Keep only digits
}

// Function to validate phone (11 digits)
function validatePhone($phone)
{
    $clean = cleanPhone($phone);
    return strlen($clean) === 11 && ctype_digit($clean);
}

// Handle requests
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list_employees':
        try {
            $query = "
                SELECT 
                    e.id, e.first_name, e.last_name, e.email, e.address, e.gender, 
                    e.marital_status, e.contact_number, e.rate_per_hour, e.avatar_path,
                    e.annual_paid_leave_days, e.annual_unpaid_leave_days, e.annual_sick_leave_days,
                    e.emergency_contact_name, e.emergency_contact_phone, e.emergency_contact_relationship,
                    e.status, e.date_joined,
                    d.name AS department_name,
                    jp.name AS position_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN job_positions jp ON e.job_position_id = jp.id
                ORDER BY e.last_name, e.first_name
            ";
            $result = $mysqli->query($query);
            if (!$result) {
                throw new Exception('Query failed: ' . $mysqli->error);
            }
            $employees = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();

            // Process each: Clean phones, cast types, handle nulls
            foreach ($employees as &$emp) {
                $emp['id'] = (int)$emp['id'];
                $emp['first_name'] = trim($emp['first_name'] ?? '');
                $emp['last_name'] = trim($emp['last_name'] ?? '');
                $emp['email'] = trim($emp['email'] ?? '');
                $emp['address'] = trim($emp['address'] ?? '');
                $emp['gender'] = trim($emp['gender'] ?? '');
                $emp['marital_status'] = trim($emp['marital_status'] ?? '');
                $emp['contact_number'] = cleanPhone($emp['contact_number'] ?? '');
                $emp['emergency_contact_name'] = trim($emp['emergency_contact_name'] ?? '');
                $emp['emergency_contact_phone'] = cleanPhone($emp['emergency_contact_phone'] ?? '');
                $emp['emergency_contact_relationship'] = trim($emp['emergency_contact_relationship'] ?? '');
                $emp['rate_per_hour'] = (float)$emp['rate_per_hour'];
                $emp['annual_paid_leave_days'] = (int)($emp['annual_paid_leave_days'] ?? 0);
                $emp['annual_unpaid_leave_days'] = (int)($emp['annual_unpaid_leave_days'] ?? 0);
                $emp['annual_sick_leave_days'] = (int)($emp['annual_sick_leave_days'] ?? 0);
                $emp['status'] = trim($emp['status'] ?? 'Active');
                $emp['date_joined'] = trim($emp['date_joined'] ?? '');
                $emp['avatar_path'] = trim($emp['avatar_path'] ?? '');
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
            echo json_encode(['success' => false, 'message' => 'Employee ID required']);
            break;
        }
        try {
            $query = "
                SELECT 
                    e.*, d.name AS department_name, jp.name AS position_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN job_positions jp ON e.job_position_id = jp.id
                WHERE e.id = ?
            ";
            $stmt = $mysqli->prepare($query);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $employee = $result->fetch_assoc();
            $stmt->close();

            if (!$employee) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Employee not found']);
                break;
            }
            // Process: Clean phones, cast types, handle nulls (same as list)
            $employee['id'] = (int)$employee['id'];
            $employee['first_name'] = trim($employee['first_name'] ?? '');
            $employee['last_name'] = trim($employee['last_name'] ?? '');
            $employee['email'] = trim($employee['email'] ?? '');
            $employee['address'] = trim($employee['address'] ?? '');
            $employee['gender'] = trim($employee['gender'] ?? '');
            $employee['marital_status'] = trim($employee['marital_status'] ?? '');
            $employee['contact_number'] = cleanPhone($employee['contact_number'] ?? '');
            $employee['emergency_contact_name'] = trim($employee['emergency_contact_name'] ?? '');
            $employee['emergency_contact_phone'] = cleanPhone($employee['emergency_contact_phone'] ?? '');
            $employee['emergency_contact_relationship'] = trim($employee['emergency_contact_relationship'] ?? '');
            $employee['rate_per_hour'] = (float)$employee['rate_per_hour'];
            $employee['annual_paid_leave_days'] = (int)($employee['annual_paid_leave_days'] ?? 0);
            $employee['annual_unpaid_leave_days'] = (int)($employee['annual_unpaid_leave_days'] ?? 0);
            $employee['annual_sick_leave_days'] = (int)($employee['annual_sick_leave_days'] ?? 0);
            $employee['status'] = trim($employee['status'] ?? 'Active');
            $employee['date_joined'] = trim($employee['date_joined'] ?? '');
            $employee['avatar_path'] = trim($employee['avatar_path'] ?? '');
            $employee['department_name'] = trim($employee['department_name'] ?? 'Unassigned');
            $employee['position_name'] = trim($employee['position_name'] ?? 'Unassigned');
            $employee['department_id'] = (int)$employee['department_id'];
            $employee['job_position_id'] = (int)$employee['job_position_id'];

            ob_end_clean();
            echo json_encode(['success' => true, 'data' => $employee], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
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
        try {
            // DEBUG: Log received POST data (remove after testing)
            error_log("Add POST: " . print_r($_POST, true));

            // Sanitize inputs (match HTML/JS underscore keys; phones from JS overrides)
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $gender = $_POST['gender'] ?? '';
            $marital_status = $_POST['marital_status'] ?? 'Single';
            $contact_number = cleanPhone($_POST['contact_number'] ?? '');  // From JS override (11 digits)
            $emergency_name = trim($_POST['emergency_contact_name'] ?? '');
            $emergency_phone = cleanPhone($_POST['emergency_contact_phone'] ?? '');  // From JS override
            $emergency_relationship = trim($_POST['emergency_contact_relationship'] ?? '');
            $department_id = (int)($_POST['department_id'] ?? 0);
            $job_position_id = (int)($_POST['job_position_id'] ?? 0);
            $rate_per_hour = (float)($_POST['rate_per_hour'] ?? 0);
            $annual_paid = (int)($_POST['annual_paid_leave_days'] ?? 15);
            $annual_unpaid = (int)($_POST['annual_unpaid_leave_days'] ?? 5);
            $annual_sick = (int)($_POST['annual_sick_leave_days'] ?? 10);

            // Validation (all required for add)
            if (
                empty($first_name) || empty($last_name) || empty($email) || empty($address) || empty($gender) ||
                !validatePhone($contact_number) || empty($emergency_name) || !validatePhone($emergency_phone) ||
                empty($emergency_relationship) || !$department_id || !$job_position_id || $rate_per_hour < 0
            ) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Missing or invalid required fields (e.g., phones must be 11 digits)']);
                break;
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                break;
            }

            // Handle avatar upload (via $_FILES['avatar'])
            $avatar_path = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['avatar'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024;  // 2MB
                if (!in_array($file['type'], $allowed_types) || $file['size'] > $max_size) {
                    ob_end_clean();
                    echo json_encode(['success' => false, 'message' => 'Invalid avatar: Must be JPG/PNG/GIF under 2MB']);
                    break;
                }
                $upload_dir = '../uploads/avatars/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'emp_' . time() . '_' . uniqid() . '.' . $ext;
                $target = $upload_dir . $filename;
                if (move_uploaded_file($file['tmp_name'], $target)) {
                    $avatar_path = 'uploads/avatars/' . $filename;  // Relative path for JSON
                } else {
                    ob_end_clean();
                    echo json_encode(['success' => false, 'message' => 'Failed to upload avatar']);
                    break;
                }
            }

            // Dynamic INSERT: Build columns, placeholders, types, params (include status and date_joined)
            $columns = [
                'first_name',
                'last_name',
                'email',
                'address',
                'gender',
                'marital_status',
                'contact_number',
                'rate_per_hour',
                'department_id',
                'job_position_id',
                'avatar_path',
                'annual_paid_leave_days',
                'annual_unpaid_leave_days',
                'annual_sick_leave_days',
                'emergency_contact_name',
                'emergency_contact_phone',
                'emergency_contact_relationship',
                'status',
                'date_joined'
            ];
            $types = '';
            $params = [];
            $types .= 's';
            $params[] = $first_name;
            $types .= 's';
            $params[] = $last_name;
            $types .= 's';
            $params[] = $email;
            $types .= 's';
            $params[] = $address;
            $types .= 's';
            $params[] = $gender;
            $types .= 's';
            $params[] = $marital_status;
            $types .= 's';
            $params[] = $contact_number;
            $types .= 'd';
            $params[] = $rate_per_hour;
            $types .= 'i';
            $params[] = $department_id;
            $types .= 'i';
            $params[] = $job_position_id;
            $types .= 's';
            $params[] = $avatar_path;
            $types .= 'i';
            $params[] = $annual_paid;
            $types .= 'i';
            $params[] = $annual_unpaid;
            $types .= 'i';
            $params[] = $annual_sick;
            $types .= 's';
            $params[] = $emergency_name;
            $types .= 's';
            $params[] = $emergency_phone;
            $types .= 's';
            $params[] = $emergency_relationship;
            $types .= 's';
            $params[] = 'Active';  // Default status
            $types .= 's';
            $params[] = date('Y-m-d H:i:s');  // Current timestamp for date_joined

            $placeholders = str_repeat('?, ', count($params) - 1) . '?';
            $query = "INSERT INTO employees (" . implode(', ', $columns) . ") VALUES ($placeholders)";
            $stmt = $mysqli->prepare($query);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $new_id = $mysqli->insert_id;
            $stmt->close();

            // UPDATED: Generate QR Code Automatically After Employee Addition (Filename from first/last name)
            $qr_generated = false;
            try {
                // Include QR library (from views/ to root/phpqrcode/)
                require_once '../phpqrcode/qrlib.php';

                // Fetch only necessary employee details for QR (id, first_name, last_name, job_position, date_joined)
                $emp_query = "
                SELECT 
                    e.id, e.first_name, e.last_name, e.date_joined,
                    jp.name AS position_name
                FROM employees e
                LEFT JOIN job_positions jp ON e.job_position_id = jp.id
                WHERE e.id = ?
            ";
                $emp_stmt = $mysqli->prepare($emp_query);
                if (!$emp_stmt) {
                    throw new Exception('QR Fetch failed: ' . $mysqli->error);
                }
                $emp_stmt->bind_param('i', $new_id);
                $emp_stmt->execute();
                $emp_result = $emp_stmt->get_result();
                $emp_data = $emp_result->fetch_assoc();
                $emp_stmt->close();

                if (!$emp_data) {
                    throw new Exception('Employee data not found for QR generation');
                }

                // Prepare simplified QR data as JSON (separate first_name and last_name)
                $qr_data = json_encode([
                    'id' => (int)$emp_data['id'],
                    'first_name' => trim($emp_data['first_name'] ?? ''),
                    'last_name' => trim($emp_data['last_name'] ?? ''),
                    'job_position' => trim($emp_data['position_name'] ?? 'Unassigned'),
                    'date_joined' => trim($emp_data['date_joined'] ?? date('Y-m-d H:i:s'))
                ], JSON_UNESCAPED_SLASHES);

                // UPDATED: QR filename based on first_name + last_name (sanitized) + ID for uniqueness
                $safe_first = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower(trim($first_name ?? '')));  // Sanitize: alphanumeric + '_', lowercase
                $safe_last = preg_replace('/[^a-zA-Z0-9]/', '_', strtolower(trim($last_name ?? '')));
                if (empty($safe_first) || empty($safe_last)) {
                    // Fallback if names empty (unlikely)
                    $qr_filename = 'qr_emp_' . $new_id . '.png';
                } else {
                    $qr_filename = 'qr_' . $safe_first . '_' . $safe_last . '_' . $new_id . '.png';
                }

                // QR Settings: ECC_LEVEL_M (medium error correction), 4 (pixel size for ~200x200px)
                $qr_dir = '../qrcodes/';
                if (!is_dir($qr_dir)) {
                    mkdir($qr_dir, 0755, true);
                }
                $qr_path = $qr_dir . $qr_filename;  // Full server path
                $qr_relative_path = 'qrcodes/' . $qr_filename;  // For DB storage (relative to root)

                // Generate and save QR PNG
                QRcode::png($qr_data, $qr_path, QR_ECLEVEL_M, 4, 2);  // data, output file, level, size, margin

                if (!file_exists($qr_path)) {
                    throw new Exception('QR image not generated');
                }

                // Insert into qr_codes table (your table name)
                $qr_insert = "INSERT INTO qr_codes (employee_id, qr_data, qr_image_path, generated_at) VALUES (?, ?, ?, NOW())";
                $qr_stmt = $mysqli->prepare($qr_insert);
                if (!$qr_stmt) {
                    throw new Exception('QR Insert prepare failed: ' . $mysqli->error);
                }
                $qr_stmt->bind_param('iss', $new_id, $qr_data, $qr_relative_path);
                $qr_stmt->execute();
                $qr_stmt->close();

                $qr_generated = true;
                error_log("QR Generated Successfully for Employee ID: " . $new_id . " | Filename: " . $qr_filename . " | Data: " . $qr_data . " | Path: " . $qr_relative_path);
            } catch (Exception $qr_error) {
                error_log("QR Generation Error for Employee ID " . $new_id . ": " . $qr_error->getMessage());
                // Do not fail the employee add; just log and continue
            }

            // Success response (include QR status in message)
            $success_msg = 'Employee added successfully';
            if ($qr_generated) {
                $success_msg .= ' with QR code generated';
            } else {
                $success_msg .= ' (QR generation skipped due to error)';
            }

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => $success_msg, 'data' => ['id' => $new_id]]);
        } catch (Exception $e) {
            error_log("Add Employee Error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to add employee: ' . $e->getMessage()]);
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
        try {
            // DEBUG: Log received POST data (remove after testing)
            error_log("Edit POST: " . print_r($_POST, true));

            // Sanitize inputs (match HTML/JS underscore keys; allow empty for no-change on optional)
            $first_name = trim($_POST['first_name'] ?? '');
            $last_name = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $gender = $_POST['gender'] ?? '';
            $marital_status = $_POST['marital_status'] ?? '';
            $contact_number = cleanPhone($_POST['contact_number'] ?? '');
            $emergency_name = trim($_POST['emergency_contact_name'] ?? '');
            $emergency_phone = cleanPhone($_POST['emergency_contact_phone'] ?? '');
            $emergency_relationship = trim($_POST['emergency_contact_relationship'] ?? '');
            $department_id = (int)($_POST['department_id'] ?? 0);
            $job_position_id = (int)($_POST['job_position_id'] ?? 0);
            $rate_per_hour = (float)($_POST['rate_per_hour'] ?? 0);
            $annual_paid = $_POST['annual_paid_leave_days'] !== '' ? (int)$_POST['annual_paid_leave_days'] : null;
            $annual_unpaid = $_POST['annual_unpaid_leave_days'] !== '' ? (int)$_POST['annual_unpaid_leave_days'] : null;
            $annual_sick = $_POST['annual_sick_leave_days'] !== '' ? (int)$_POST['annual_sick_leave_days'] : null;

            // Validation (core required; phones/emergency optional if empty, but validate if provided)
            if (
                empty($first_name) || empty($last_name) || empty($email) || empty($address) || empty($gender) ||
                !empty($contact_number) && !validatePhone($contact_number) ||
                !empty($emergency_phone) && !validatePhone($emergency_phone) ||
                !$department_id || !$job_position_id || $rate_per_hour < 0
            ) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Invalid required fields (e.g., phones must be 11 digits if provided)']);
                break;
            }
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Invalid email format']);
                break;
            }

            // Fetch current employee data for comparison (old names/position) and avatar cleanup
            $old_data = null;
            $old_avatar = null;
            $stmt = $mysqli->prepare("
            SELECT avatar_path, first_name, last_name, job_position_id 
            FROM employees 
            WHERE id = ?
        ");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $old_data = $result->fetch_assoc();
                $old_avatar = $old_data['avatar_path'] ?? null;
                $stmt->close();
            }
            if (!$old_data) {
                throw new Exception('Employee not found');
            }

            // Check if QR-relevant fields changed (for conditional regen)
            $name_or_position_changed = (
                trim($old_data['first_name']) !== $first_name ||
                trim($old_data['last_name']) !== $last_name ||
                (int)$old_data['job_position_id'] !== $job_position_id
            );

            // Handle avatar update (optional; delete old if new) - via $_FILES['avatar']
            $avatar_path = null;
            $include_avatar = false;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['avatar'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 2 * 1024 * 1024;
                if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
                    $upload_dir = '../uploads/avatars/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filename = 'emp_' . $id . '_' . time() . '_' . uniqid() . '.' . $ext;
                    $target = $upload_dir . $filename;
                    if (move_uploaded_file($file['tmp_name'], $target)) {
                        $avatar_path = 'uploads/avatars/' . $filename;
                        $include_avatar = true;
                        // Delete old avatar if exists
                        if ($old_avatar && file_exists('../' . $old_avatar)) {
                            unlink('../' . $old_avatar);
                        }
                    } else {
                        ob_end_clean();
                        echo json_encode(['success' => false, 'message' => 'Failed to upload avatar']);
                        break;
                    }
                } else {
                    ob_end_clean();
                    echo json_encode(['success' => false, 'message' => 'Invalid avatar: Must be JPG/PNG/GIF under 2MB']);
                    break;
                }
            }

            // Build UPDATE dynamically (only include changed/optional fields)
            $set_parts = [];
            $types = '';
            $params = [];

            // Base fields (always update if provided)
            $set_parts[] = 'first_name = ?';
            $types .= 's';
            $params[] = $first_name;
            $set_parts[] = 'last_name = ?';
            $types .= 's';
            $params[] = $last_name;
            $set_parts[] = 'email = ?';
            $types .= 's';
            $params[] = $email;
            $set_parts[] = 'address = ?';
            $types .= 's';
            $params[] = $address;
            $set_parts[] = 'gender = ?';
            $types .= 's';
            $params[] = $gender;
            $set_parts[] = 'marital_status = ?';
            $types .= 's';
            $params[] = $marital_status;
            $set_parts[] = 'contact_number = ?';
            $types .= 's';
            $params[] = $contact_number;
            $set_parts[] = 'rate_per_hour = ?';
            $types .= 'd';
            $params[] = $rate_per_hour;
            $set_parts[] = 'department_id = ?';
            $types .= 'i';
            $params[] = $department_id;
            $set_parts[] = 'job_position_id = ?';
            $types .= 'i';
            $params[] = $job_position_id;
            $set_parts[] = 'emergency_contact_name = ?';
            $types .= 's';
            $params[] = $emergency_name;
            $set_parts[] = 'emergency_contact_phone = ?';
            $types .= 's';
            $params[] = $emergency_phone;
            $set_parts[] = 'emergency_contact_relationship = ?';
            $types .= 's';
            $params[] = $emergency_relationship;

            // Optional fields (only if provided/changed)
            if ($annual_paid !== null) {
                $set_parts[] = 'annual_paid_leave_days = ?';
                $types .= 'i';
                $params[] = $annual_paid;
            }
            if ($annual_unpaid !== null) {
                $set_parts[] = 'annual_unpaid_leave_days = ?';
                $types .= 'i';
                $params[] = $annual_unpaid;
            }
            if ($annual_sick !== null) {
                $set_parts[] = 'annual_sick_leave_days = ?';
                $types .= 'i';
                $params[] = $annual_sick;
            }
            if ($include_avatar) {
                $set_parts[] = 'avatar_path = ?';
                $types .= 's';
                $params[] = $avatar_path;
            }

            $sql = 'UPDATE employees SET ' . implode(', ', $set_parts) . ' WHERE id = ?';
            $types .= 'i';
            $params[] = $id;

            $stmt = $mysqli->prepare($sql);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $affected_rows = $stmt->affected_rows;
            $stmt->close();

            if ($affected_rows > 0) {
                // UPDATED: Regenerate QR if name or job_position changed (or no existing QR)
                $qr_regenerated = false;
                if ($name_or_position_changed) {
                    $qr_regenerated = regenerateQRCode($mysqli, $id, $first_name, $last_name, $job_position_id, $old_data);
                }

                // Success response (include QR status if regenerated)
                $success_msg = 'Employee updated successfully';
                if ($qr_regenerated) {
                    $success_msg .= ' and QR code regenerated';
                } elseif ($name_or_position_changed) {
                    $success_msg .= ' (QR regeneration skipped due to error)';
                }

                ob_end_clean();
                echo json_encode(['success' => true, 'message' => $success_msg]);
            } else {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'No changes made or employee not found']);
            }
        } catch (Exception $e) {
            error_log("Edit Employee Error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to update employee: ' . $e->getMessage()]);
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
        try {
            // Fetch avatar for cleanup
            $stmt = $mysqli->prepare("SELECT avatar_path FROM employees WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param('i', $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $emp = $result->fetch_assoc();
                $stmt->close();

                if ($emp && !empty($emp['avatar_path'])) {
                    $file_path = '../' . $emp['avatar_path'];
                    if (file_exists($file_path)) {
                        unlink($file_path);
                    }
                }
            }

            // Delete record
            $stmt = $mysqli->prepare("DELETE FROM employees WHERE id = ?");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $affected_rows = $stmt->affected_rows;
            $stmt->close();

            if ($affected_rows > 0) {
                ob_end_clean();
                echo json_encode(['success' => true, 'message' => 'Employee deleted successfully']);
            } else {
                ob_end_clean();
                echo json_encode(['success' => false, 'message' => 'Employee not found or already deleted']);
            }
        } catch (Exception $e) {
            error_log("Delete Employee Error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to delete employee: ' . $e->getMessage()]);
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
            $query = "SELECT id, name FROM job_positions ORDER BY name";
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

// Close MySQLi connection (good practice)
if ($mysqli) {
    $mysqli->close();
}
ob_end_flush();  // Flush any remaining buffer
