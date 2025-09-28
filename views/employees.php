<?php
ob_start();
ini_set('display_errors', 0);  // Set to 1 temporarily for debugging if needed
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'conn.php';

// QR Library: From root (views/ is subdir)
$qr_lib_path = '../phpqrcode/qrlib.php';
if (file_exists($qr_lib_path)) {
    require_once $qr_lib_path;
} else {
    error_log("QR Library missing: Place qrlib.php in root/phpqrcode/");
}

// Get connections
$db = null;
$mysqli = null;
try {
    $db = conn();  // ['mysqli', 'firebase']
    $mysqli = $db['mysqli'];
    if (!$mysqli || $mysqli->connect_error) {
        throw new Exception('MySQL connection failed: ' . ($mysqli ? $mysqli->connect_error : 'No connection'));
    }
} catch (Exception $e) {
    ob_end_clean();
    die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]));
}

// Helper: Generate QR Code and Return Web Path (uses root qrcodes/)
function generateQRCode($employee_data, $qr_dir = '../qrcodes/')
{
    global $mysqli;
    if (!class_exists('QRcode')) {
        error_log("QR Library not loaded – skipping QR generation");
        return ['path' => null, 'data' => null];
    }
    try {
        if (!is_dir($qr_dir)) {
            mkdir($qr_dir, 0755, true);
        }
        $id = (int)$employee_data['id'];
        $first = trim(preg_replace('/[^a-zA-Z]/', '', $employee_data['first_name'] ?? ''));  // Sanitize for filename
        $last = trim(preg_replace('/[^a-zA-Z]/', '', $employee_data['last_name'] ?? ''));
        $pos = trim($employee_data['position_name'] ?? 'N/A');  // From JOIN
        $joined = ($employee_data['date_joined'] === '0000-00-00' || empty($employee_data['date_joined'])) ? 'N/A' : $employee_data['date_joined'];

        $qr_data = "ID:$id|First:$first|Last:$last|Position:$pos|Joined:$joined";
        $filename = $first . $last . '.png';
        $counter = 1;
        while (file_exists($qr_dir . $filename)) {
            $filename = $first . $last . '_' . $id . '_' . $counter . '.png';
            $counter++;
        }
        $file_path = $qr_dir . $filename;
        $web_path = 'qrcodes/' . $filename;

        // Generate QR (L correction, size 10, PNG)
        QRcode::png($qr_data, $file_path, QR_ECLEVEL_L, 10, 2);
        error_log("QR Generated: $web_path for Employee ID $id (Data: $qr_data)");
        return ['path' => $web_path, 'data' => $qr_data];
    } catch (Exception $e) {
        error_log("QR Generation Failed for ID " . ($employee_data['id'] ?? 'unknown') . ": " . $e->getMessage());
        return ['path' => null, 'data' => null];
    }
}

// Helper: Delete QR (record + file)
function deleteQRCode($mysqli, $employee_id)
{
    try {
        // Fetch path first
        $stmt = $mysqli->prepare("SELECT qr_image_path FROM qr_codes WHERE employee_id = ?");
        $stmt->bind_param('i', $employee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $qr = $result->fetch_assoc();
        $stmt->close();

        if ($qr && $qr['qr_image_path']) {
            $file_path = '../' . $qr['qr_image_path'];  // Root-relative
            if (file_exists($file_path)) {
                unlink($file_path);
                error_log("QR File Deleted: " . $qr['qr_image_path'] . " for Employee ID $employee_id");
            }
        }

        // Delete record
        $stmt = $mysqli->prepare("DELETE FROM qr_codes WHERE employee_id = ?");
        $stmt->bind_param('i', $employee_id);
        $stmt->execute();
        $stmt->close();
        error_log("QR Record Deleted for Employee ID $employee_id");
    } catch (Exception $e) {
        error_log("QR Delete Failed for ID $employee_id: " . $e->getMessage());
    }
}

// Helper: Sync to Firebase (added qr_data, qr_image_path)
function syncEmployeeToFirebase($db, $mysqli, $employee_id, $employee_data, $operation_type)
{
    // Use global hasFirebase from conn.php
    if (!function_exists('hasFirebase') || !hasFirebase($db)) {
        error_log("No Firebase – queuing Employee ID: $employee_id, Type: $operation_type");
        insertPendingOperation($mysqli, $operation_type, $employee_id, $employee_data);
        return false;
    }

    try {
        $database = $db['firebase'];
        $ref = $database->getReference('employees/' . $employee_id);

        if ($operation_type === 'delete') {
            $ref->remove();
            error_log("Firebase: Deleted Employee ID $employee_id");
            removePendingOperation($mysqli, $operation_type, $employee_id);
            return true;
        }

        // Exact mapping to employees table (no extras; skip if delete) - REMOVED: qr_data, qr_image_path
        $sync_data = [
            'id' => (int)($employee_data['id'] ?? 0),
            'first_name' => trim($employee_data['first_name'] ?? ''),
            'last_name' => trim($employee_data['last_name'] ?? ''),
            'address' => trim($employee_data['address'] ?? ''),
            'gender' => trim($employee_data['gender'] ?? ''),
            'marital_status' => trim($employee_data['marital_status'] ?? 'Single'),
            'status' => trim($employee_data['status'] ?? 'Active'),
            'email' => trim($employee_data['email'] ?? ''),
            'contact_number' => cleanPhone($employee_data['contact_number'] ?? ''),
            'emergency_contact_name' => trim($employee_data['emergency_contact_name'] ?? ''),
            'emergency_contact_phone' => cleanPhone($employee_data['emergency_contact_phone'] ?? ''),
            'emergency_contact_relationship' => trim($employee_data['emergency_contact_relationship'] ?? ''),
            'date_joined' => isset($employee_data['date_joined']) && $employee_data['date_joined'] && $employee_data['date_joined'] !== '0000-00-00' ? date('c', strtotime($employee_data['date_joined'])) : null,
            'department_id' => (int)($employee_data['department_id'] ?? 0),
            'job_position_id' => (int)($employee_data['job_position_id'] ?? 0),
            'rate_per_hour' => (float)($employee_data['rate_per_hour'] ?? 0),
            'annual_paid_leave_days' => (int)($employee_data['annual_paid_leave_days'] ?? 15),
            'annual_unpaid_leave_days' => (int)($employee_data['annual_unpaid_leave_days'] ?? 5),
            'annual_sick_leave_days' => (int)($employee_data['annual_sick_leave_days'] ?? 10),
            'avatar_path' => trim($employee_data['avatar_path'] ?? ''),
            // REMOVED: 'qr_data' => trim($employee_data['qr_data'] ?? ''),
            // REMOVED: 'qr_image_path' => trim($employee_data['qr_image_path'] ?? ''),
            'created_at' => isset($employee_data['created_at']) && $employee_data['created_at'] ? date('c', strtotime($employee_data['created_at'])) : null,
            'updated_at' => isset($employee_data['updated_at']) && $employee_data['updated_at'] ? date('c', strtotime($employee_data['updated_at'])) : null
        ];

        $ref->set($sync_data);
        error_log("Firebase: Synced Employee ID $employee_id ($operation_type): " . json_encode($sync_data));

        // Remove pending on success
        removePendingOperation($mysqli, $operation_type, $employee_id);
        return true;
    } catch (Exception $e) {
        error_log("Firebase Sync Fail for ID $employee_id ($operation_type): " . $e->getMessage());
        insertPendingOperation($mysqli, $operation_type, $employee_id, $employee_data);
        return false;
    }
}

// insertPendingOperation, removePendingOperation, processPendingSync, cleanPhone, validatePhone (unchanged from previous)
function insertPendingOperation($mysqli, $operation_type, $employee_id, $data = null)
{
    try {
        $data_json = $data ? json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null;
        $stmt = $mysqli->prepare("INSERT INTO pending_operations (operation_type, employee_id, data) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $mysqli->error);
        }
        $stmt->bind_param('sis', $operation_type, $employee_id, $data_json);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();
        error_log("Queued: $operation_type for ID $employee_id");
    } catch (Exception $e) {
        error_log("Queue Fail: " . $e->getMessage());
    }
}

// Remove pending (unchanged)
function removePendingOperation($mysqli, $operation_type, $employee_id)
{
    try {
        $stmt = $mysqli->prepare("DELETE FROM pending_operations WHERE operation_type = ? AND employee_id = ? AND synced = 0");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $mysqli->error);
        }
        $stmt->bind_param('si', $operation_type, $employee_id);
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $stmt->close();
        error_log("Removed pending: $operation_type for ID $employee_id");
    } catch (Exception $e) {
        error_log("Remove Pending Fail: " . $e->getMessage());
    }
}

// Process pending with retry (unchanged)
function processPendingSync($db, $mysqli)
{
    $synced = 0;
    $failed = 0;
    $max_attempts = 5;
    try {
        $pending_query = "SELECT * FROM pending_operations WHERE synced = 0 AND attempts < $max_attempts ORDER BY created_at ASC";
        $result = $mysqli->query($pending_query);
        if (!$result) {
            throw new Exception("Query failed: " . $mysqli->error);
        }
        $pendings = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();

        foreach ($pendings as $pending) {
            $id = $pending['employee_id'];
            $type = $pending['operation_type'];
            $data = json_decode($pending['data'], true) ?? [];

            // For add/update: Fetch latest from MySQL (with position_name for QR if needed)
            if ($type !== 'delete') {
                $fetch_query = "
                    SELECT e.*, d.name AS department_name, jp.name AS position_name, qc.qr_data, qc.qr_image_path
                    FROM employees e
                    LEFT JOIN departments d ON e.department_id = d.id
                    LEFT JOIN job_positions jp ON e.job_position_id = jp.id
                    LEFT JOIN qr_codes qc ON e.id = qc.employee_id
                    WHERE e.id = ?
                ";
                $fetch_stmt = $mysqli->prepare($fetch_query);
                if (!$fetch_stmt) {
                    error_log("Fetch latest prepare failed for ID $id: " . $mysqli->error);
                    $failed++;
                    continue;
                }
                $fetch_stmt->bind_param('i', $id);
                $fetch_stmt->execute();
                $fetch_result = $fetch_stmt->get_result();
                $latest = $fetch_result ? $fetch_result->fetch_assoc() : null;
                $fetch_stmt->close();
                if ($latest) {
                    $data = $latest;
                } else {
                    $failed++;
                    continue;
                }
            }

            // Sync (uses conn.php's hasFirebase)
            $success = syncEmployeeToFirebase($db, $mysqli, $id, $data, $type);

            // Update attempts/synced
            $synced_val = $success ? 1 : 0;
            $update_stmt = $mysqli->prepare("UPDATE pending_operations SET synced = ?, attempts = attempts + 1 WHERE id = ?");
            if ($update_stmt) {
                $update_stmt->bind_param('ii', $synced_val, $pending['id']);
                $update_stmt->execute();
                $update_stmt->close();
            }

            if ($success) {
                $synced++;
                removePendingOperation($mysqli, $type, $id);
            } else {
                $failed++;
                if (($pending['attempts'] ?? 0) + 1 >= $max_attempts) {
                    error_log("Max retries reached for ID $id ($type) – manual intervention needed");
                }
            }
        }
    } catch (Exception $e) {
        error_log("Pending Sync Error: " . $e->getMessage());
        $failed = count($pendings ?? []);
    }
    return ['synced' => $synced, 'failed' => $failed];
}

// Clean phone (unchanged)
function cleanPhone($phone)
{
    $phone = $phone ?? '';
    return preg_replace('/\D/', '', $phone);
}

// Validate phone (unchanged)
function validatePhone($phone)
{
    $clean = cleanPhone($phone);
    return strlen($clean) === 11 && ctype_digit($clean);
}

// Handle action
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

            // Process (clean/cast) - ADDED: qr_data, qr_image_path
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
                $emp['qr_data'] = trim($emp['qr_data'] ?? '');  // NEW
                $emp['qr_image_path'] = trim($emp['qr_image_path'] ?? '');  // NEW
                $emp['created_at'] = trim($emp['created_at'] ?? '');
                $emp['updated_at'] = trim($emp['updated_at'] ?? '');
                $emp['department_name'] = trim($emp['department_name'] ?? 'Unassigned');
                $emp['position_name'] = trim($emp['position_name'] ?? 'Unassigned');
            }
            unset($emp);  // Clean reference

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

            // Process same as list - ADDED: qr_data, qr_image_path
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
            $emp['qr_data'] = trim($emp['qr_data'] ?? '');  // NEW
            $emp['qr_image_path'] = trim($emp['qr_image_path'] ?? '');  // NEW
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
        try {
            // Sanitize inputs (all required fields) - FIXED: Match JS FormData keys (underscores, e.g., department_id)
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
            $department_id = (int)($_POST['department_id'] ?? 0);  // FIXED: department_id (not 'department')
            $job_position_id = (int)($_POST['job_position_id'] ?? 0);  // FIXED: job_position_id (not 'job-position')
            $rate_per_hour = (float)($_POST['rate_per_hour'] ?? 0);  // FIXED: rate_per_hour (not 'rate-per-hour')
            $annual_paid_leave_days = (int)($_POST['annual_paid_leave_days'] ?? 15);
            $annual_unpaid_leave_days = (int)($_POST['annual_unpaid_leave_days'] ?? 5);
            $annual_sick_leave_days = (int)($_POST['annual_sick_leave_days'] ?? 10);

            // Validation - FIXED: Explicit checks (e.g., department_id > 0, not just !0)
            if (empty($first_name) || empty($last_name) || empty($address) || empty($gender) || empty($email) || empty($contact_number) || empty($emergency_contact_name) || empty($emergency_contact_phone) || empty($emergency_contact_relationship) || $department_id <= 0 || $job_position_id <= 0 || $rate_per_hour < 0) {
                throw new Exception('All required fields must be provided (names, address, gender, email, phones, emergency details, department, position, non-negative rate).');
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Invalid email format.');
            }
            if (!validatePhone($contact_number) || !validatePhone($emergency_contact_phone)) {
                throw new Exception('Phone numbers must be 11 digits (e.g., 09305909175).');
            }
            // Check email unique
            $check_stmt = $mysqli->prepare("SELECT id FROM employees WHERE email = ?");
            if (!$check_stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $check_stmt->bind_param('s', $email);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                throw new Exception('Email already exists.');
            }
            $check_stmt->close();

            // Check department/position exist
            $dept_check = $mysqli->prepare("SELECT id FROM departments WHERE id = ?");
            if (!$dept_check) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $dept_check->bind_param('i', $department_id);
            $dept_check->execute();
            if ($dept_check->get_result()->num_rows === 0) {
                throw new Exception('Invalid department selected.');
            }
            $dept_check->close();

            $pos_check = $mysqli->prepare("SELECT id FROM job_positions WHERE id = ?");
            if (!$pos_check) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $pos_check->bind_param('i', $job_position_id);
            $pos_check->execute();
            if ($pos_check->get_result()->num_rows === 0) {
                throw new Exception('Invalid job position selected.');
            }
            $pos_check->close();

            // Avatar upload (optional)
            $avatar_path = null;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $upload_dir = '../uploads/avatars/';
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

            // In add_employee case, replace the INSERT query and bind_param block with this:
            $query = "INSERT INTO employees (
    first_name, last_name, address, gender, marital_status, status, email,
    contact_number, emergency_contact_name, emergency_contact_phone,
    emergency_contact_relationship, date_joined, department_id, job_position_id,
    rate_per_hour, annual_paid_leave_days, annual_unpaid_leave_days,
    annual_sick_leave_days, avatar_path
) VALUES (?, ?, ?, ?, ?, 'Active', ?, ?, ?, ?, ?, CURDATE(), ?, ?, ?, ?, ?, ?, ?)";  // FIXED: 5? pre-'Active' + 5? (email-relationship) + CURDATE() + 7? (dept-avatar) = 17? + 2 literals = 19 values

            $stmt = $mysqli->prepare($query);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            // Types unchanged: 10s (strings) + i(dept) i(pos) d(rate) i(paid) i(unpaid) i(sick) s(avatar) = 'ssssssssssiidiiis'
            $types = 'ssssssssssiidiiis';
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
                $avatar_path
            ];  // 17 params - FIXED: Ensure $avatar_path is last (null if no upload)
            $stmt->bind_param($types, ...$params);
            if (!$stmt->execute()) {
                throw new Exception('Execute failed: ' . $stmt->error);  // This will now catch the mismatch if any
            }
            $new_id = $mysqli->insert_id;
            $stmt->close();


            if (!$new_id) {
                throw new Exception('Failed to insert employee.');
            }

            // Fetch full data for QR and sync (with position_name via JOIN)
            $fetch_query = "
                SELECT e.*, d.name AS department_name, jp.name AS position_name
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN job_positions jp ON e.job_position_id = jp.id
                WHERE e.id = ?
            ";
            $fetch_stmt = $mysqli->prepare($fetch_query);
            if (!$fetch_stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $fetch_stmt->bind_param('i', $new_id);
            $fetch_stmt->execute();
            $result = $fetch_stmt->get_result();
            $new_employee = $result->fetch_assoc();
            $fetch_stmt->close();

            if (!$new_employee) {
                throw new Exception('Failed to fetch new employee data.');
            }

            // Generate QR Code (on add: always generate)
            $qr_result = generateQRCode($new_employee);
            $qr_path = $qr_result['path'];
            $qr_data = $qr_result['data'];

            // Insert into qr_codes
            if ($qr_data && $qr_path) {
                $qr_stmt = $mysqli->prepare("INSERT INTO qr_codes (employee_id, qr_data, qr_image_path) VALUES (?, ?, ?)");
                if ($qr_stmt) {
                    $qr_stmt->bind_param('iss', $new_id, $qr_data, $qr_path);
                    $qr_stmt->execute();
                    $qr_stmt->close();
                    error_log("QR Record Inserted for Employee ID $new_id");
                }
            }

            // Sync to Firebase (non-blocking; queue if fail)
            $sync_success = syncEmployeeToFirebase($db, $mysqli, $new_id, $new_employee, 'add');

            // Message
            $msg = 'Employee added successfully';
            if ($avatar_path) {
                $msg .= ' with avatar';
            }
            if ($qr_path) {
                $msg .= ' and QR code';
            }
            if ($sync_success) {
                $msg .= ' and synced to cloud';
            } else {
                $msg .= ' (queued for sync – will retry on reconnect)';
            }

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => $msg, 'data' => ['id' => $new_id]]);
        } catch (Exception $e) {
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
        try {
            // Fetch current to check existence, handle avatar/QR update, and compare for QR regen
            $current_query = "
                SELECT e.*, d.name AS department_name, jp.name AS position_name,
                       qc.qr_data, qc.qr_image_path
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN job_positions jp ON e.job_position_id = jp.id
                LEFT JOIN qr_codes qc ON e.id = qc.employee_id
                WHERE e.id = ?
            ";
            $current_stmt = $mysqli->prepare($current_query);
            if (!$current_stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $current_stmt->bind_param('i', $id);
            $current_stmt->execute();
            $current_result = $current_stmt->get_result();
            $current_employee = $current_result->fetch_assoc();
            $current_stmt->close();

            if (!$current_employee) {
                throw new Exception('Employee not found.');
            }

            // Helper: Strip 'update-' prefix if present (common in update forms)
            function getUpdateValue($post_key, $current_val)
            {
                $value = $_POST[$post_key] ?? null;
                if ($value === null) {
                    // Try with 'update-' prefix
                    $update_key = 'update-' . str_replace('_', '-', $post_key);  // e.g., 'first_name' -> 'update-first-name'
                    $value = $_POST[$update_key] ?? $current_val;
                }
                return $value;
            }

            // Sanitize inputs (status/date_joined unchanged) - FIXED: Handle both underscore and 'update-' hyphenated keys
            $first_name = trim(getUpdateValue('first_name', $current_employee['first_name']));
            $last_name = trim(getUpdateValue('last_name', $current_employee['last_name']));
            $address = trim(getUpdateValue('address', $current_employee['address']));
            $gender = trim(getUpdateValue('gender', $current_employee['gender']));
            $marital_status = trim(getUpdateValue('marital_status', $current_employee['marital_status']));
            $email = trim(getUpdateValue('email', $current_employee['email']));
            $contact_number = cleanPhone($_POST['contact_number'] ?? $current_employee['contact_number']);  // JS overrides to no prefix
            $emergency_contact_name = trim(getUpdateValue('emergency_contact_name', $current_employee['emergency_contact_name']));
            $emergency_contact_phone = cleanPhone($_POST['emergency_contact_phone'] ?? $current_employee['emergency_contact_phone']);  // JS overrides
            $emergency_contact_relationship = trim(getUpdateValue('emergency_contact_relationship', $current_employee['emergency_contact_relationship']));
            $department_id = (int)(getUpdateValue('department_id', $current_employee['department_id']));  // FIXED: department_id
            $job_position_id = (int)(getUpdateValue('job_position_id', $current_employee['job_position_id']));  // FIXED: job_position_id
            $rate_per_hour = (float)(getUpdateValue('rate_per_hour', $current_employee['rate_per_hour']));  // FIXED: rate_per_hour
            $annual_paid_leave_days = (int)(getUpdateValue('annual_paid_leave_days', $current_employee['annual_paid_leave_days']));
            $annual_unpaid_leave_days = (int)(getUpdateValue('annual_unpaid_leave_days', $current_employee['annual_unpaid_leave_days']));
            $annual_sick_leave_days = (int)(getUpdateValue('annual_sick_leave_days', $current_employee['annual_sick_leave_days']));

            // Check if QR needs regeneration (name or position changed)
            $qr_changed = ($first_name !== $current_employee['first_name'] ||
                $last_name !== $current_employee['last_name'] ||
                $job_position_id !== (int)$current_employee['job_position_id']);

            // Validation (similar to add, but allow empty for optional updates) - FIXED: >0 checks
            if (empty($first_name) || empty($last_name) || empty($address) || empty($gender) || empty($email) || $department_id <= 0 || $job_position_id <= 0 || $rate_per_hour < 0) {
                throw new Exception('Required fields cannot be empty (names, address, gender, email, department, position, non-negative rate).');
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
            // Email unique check (allow same if unchanged)
            if ($email !== $current_employee['email']) {
                $check_stmt = $mysqli->prepare("SELECT id FROM employees WHERE email = ? AND id != ?");
                if (!$check_stmt) {
                    throw new Exception('Prepare failed: ' . $mysqli->error);
                }
                $check_stmt->bind_param('si', $email, $id);
                $check_stmt->execute();
                if ($check_stmt->get_result()->num_rows > 0) {
                    throw new Exception('Email already exists.');
                }
                $check_stmt->close();
            }

            // Check new department/position exist (if changed)
            if ($department_id !== (int)$current_employee['department_id']) {
                $dept_check = $mysqli->prepare("SELECT id FROM departments WHERE id = ?");
                $dept_check->bind_param('i', $department_id);
                $dept_check->execute();
                if ($dept_check->get_result()->num_rows === 0) {
                    throw new Exception('Invalid department selected.');
                }
                $dept_check->close();
            }
            if ($job_position_id !== (int)$current_employee['job_position_id']) {
                $pos_check = $mysqli->prepare("SELECT id FROM job_positions WHERE id = ?");
                $pos_check->bind_param('i', $job_position_id);
                $pos_check->execute();
                if ($pos_check->get_result()->num_rows === 0) {
                    throw new Exception('Invalid job position selected.');
                }
                $pos_check->close();
            }

            // Avatar update (optional)
            $avatar_path = $current_employee['avatar_path'];
            $avatar_changed = false;
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                // Delete old avatar if exists
                if ($avatar_path && file_exists('../' . $avatar_path)) {
                    unlink('../' . $avatar_path);
                }
                // Upload new
                $upload_dir = '../uploads/avatars/';
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

            // UPDATE employees (exclude status, date_joined, created_at) - VERIFIED: 18 params, types 'ssssssssssiidiiisi'
            $query = "UPDATE employees SET 
                first_name = ?, last_name = ?, address = ?, gender = ?, marital_status = ?,
                email = ?, contact_number = ?, emergency_contact_name = ?, emergency_contact_phone = ?,
                emergency_contact_relationship = ?, department_id = ?, job_position_id = ?,
                rate_per_hour = ?, annual_paid_leave_days = ?, annual_unpaid_leave_days = ?,
                annual_sick_leave_days = ?, avatar_path = ?
                WHERE id = ?";
            $stmt = $mysqli->prepare($query);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            // Types: 10s + 2i + d + 3i + s (avatar) + i (WHERE id) = 18
            $types = 'ssssssssssiidiiisi';
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

            // If QR changed, delete old and generate new
            if ($qr_changed) {
                deleteQRCode($mysqli, $id);  // Deletes old record and file

                // Fetch updated employee for new QR (with new position_name)
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
                        if ($qr_stmt) {
                            $qr_stmt->bind_param('iss', $id, $qr_data, $qr_path);
                            $qr_stmt->execute();
                            $qr_stmt->close();
                            error_log("QR Regenerated and Inserted for Employee ID $id");
                        }
                    }
                }
            }

            // Fetch updated data for sync (full with QR)
            $fetch_query = "
                SELECT e.*, d.name AS department_name, jp.name AS position_name,
                       qc.qr_data, qc.qr_image_path
                FROM employees e
                LEFT JOIN departments d ON e.department_id = d.id
                LEFT JOIN job_positions jp ON e.job_position_id = jp.id
                LEFT JOIN qr_codes qc ON e.id = qc.employee_id
                WHERE e.id = ?
            ";
            $fetch_stmt = $mysqli->prepare($fetch_query);
            if (!$fetch_stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $fetch_stmt->bind_param('i', $id);
            $fetch_stmt->execute();
            $result = $fetch_stmt->get_result();
            $updated_employee = $result->fetch_assoc();
            $fetch_stmt->close();

            // Sync
            $sync_success = syncEmployeeToFirebase($db, $mysqli, $id, $updated_employee, 'update');

            // Message
            $msg = 'Employee updated successfully';
            if ($avatar_changed) {
                $msg .= ' with new avatar';
            }
            if ($qr_changed && $qr_path) {
                $msg .= ' and updated QR code';
            }
            if ($sync_success) {
                $msg .= ' and synced to cloud';
            } else {
                $msg .= ' (queued for sync – will retry on reconnect)';
            }

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => $msg]);
        } catch (Exception $e) {
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
        try {
            // Fetch for cleanup (avatar and QR paths)
            $cleanup_query = "
                SELECT e.avatar_path, qc.qr_image_path
                FROM employees e
                LEFT JOIN qr_codes qc ON e.id = qc.employee_id
                WHERE e.id = ?
            ";
            $stmt = $mysqli->prepare($cleanup_query);
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $emp = $result->fetch_assoc();
            $stmt->close();

            // Cleanup avatar
            if ($emp && $emp['avatar_path'] && file_exists('../' . $emp['avatar_path'])) {
                unlink('../' . $emp['avatar_path']);
                error_log("Avatar Deleted for Employee ID $id");
            }

            // Cleanup QR (uses helper: deletes record and file)
            deleteQRCode($mysqli, $id);

            // DELETE from employees
            $stmt = $mysqli->prepare("DELETE FROM employees WHERE id = ?");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected === 0) {
                throw new Exception('Employee not found or already deleted.');
            }

            // Sync delete (empty data)
            $sync_success = syncEmployeeToFirebase($db, $mysqli, $id, [], 'delete');

            // Message
            $msg = 'Employee deleted successfully';
            if ($sync_success) {
                $msg .= ' and removed from cloud';
            } else {
                $msg .= ' (queued for sync – will retry on reconnect)';
            }

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => $msg]);
        } catch (Exception $e) {
            error_log("Delete Employee Error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'sync_pending':
        try {
            $stats = processPendingSync($db, $mysqli);
            $msg = 'Pending sync completed: ' . $stats['synced'] . ' synced, ' . $stats['failed'] . ' failed (will retry later)';
            ob_end_clean();
            echo json_encode([
                'success' => true,
                'message' => $msg,
                'data' => $stats
            ], JSON_UNESCAPED_SLASHES);
        } catch (Exception $e) {
            error_log("Sync Pending Error: " . $e->getMessage());
            ob_end_clean();
            echo json_encode(['success' => false, 'message' => 'Failed to process pending sync: ' . $e->getMessage()]);
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

// Close MySQLi connection
if ($mysqli) {
    $mysqli->close();
}
ob_end_flush();
