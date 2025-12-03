<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once 'auth.php';
require_once 'conn.php';  

define('BASE_PATH', ''); // Change to '' for localhost:8000, or '/newpath' for Hostinger

$db = null;
$mysqli = null;
try {
    $db = conn();
    $mysqli = $db['mysqli'];
    if (!$mysqli || $mysqli->connect_error) {
        throw new Exception('MySQL connection failed: ' . ($mysqli ? $mysqli->connect_error : 'No connection'));
    }
    // Ensure payroll_frequency column exists (support servers without IF NOT EXISTS)
    $hasPayrollFreq = false;
    if ($desc = $mysqli->query("DESCRIBE job_positions")) {
        while ($r = $desc->fetch_assoc()) {
            if (isset($r['Field']) && $r['Field'] === 'payroll_frequency') { $hasPayrollFreq = true; break; }
        }
        $desc->free();
    }
    if (!$hasPayrollFreq) {
        @$mysqli->query("ALTER TABLE job_positions ADD COLUMN payroll_frequency ENUM('daily','weekly','bi-weekly','monthly') NOT NULL DEFAULT 'bi-weekly'");
    }
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'list_departments':
        try {
            $query = "
                SELECT d.id, d.name, COALESCE(COUNT(e.id), 0) AS employee_count
                FROM departments d
                LEFT JOIN employees e ON d.id = e.department_id
                GROUP BY d.id, d.name
                ORDER BY d.name
            ";
            $result = $mysqli->query($query);
            if (!$result) {
                throw new Exception('Query failed: ' . $mysqli->error);
            }
            $departments = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();

            ob_end_clean();
            echo json_encode(['success' => true, 'data' => $departments]);
        } catch (Exception $e) {
            error_log("List Departments Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch departments: ' . $e->getMessage()]);
        }
        break;

    case 'update_position':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'POST required']);
            break;
        }
        try {
            $userRoles = $_SESSION['roles'] ?? [];
            if (!in_array('head_admin', $userRoles)) {
                ob_end_clean();
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only Head Admin can update job positions']);
                break;
            }
            $id = intval($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $ratePerDay = floatval($_POST['rate_per_day'] ?? 0);
            $payrollFrequency = strtolower(trim($_POST['payroll_frequency'] ?? 'bi-weekly'));
            $allowedFreq = ['daily','weekly','bi-weekly','monthly'];
            if (!in_array($payrollFrequency, $allowedFreq, true)) { $payrollFrequency = 'bi-weekly'; }

            if ($id <= 0) throw new Exception('Invalid job position ID.');
            if (empty($name)) throw new Exception('Job position name is required.');
            if ($ratePerDay < 0) throw new Exception('Rate per day must be a non-negative number.');

            // Dup name check (exclude current id)
            $checkStmt = $mysqli->prepare("SELECT id FROM job_positions WHERE LOWER(name) = LOWER(?) AND id <> ?");
            $checkStmt->bind_param('si', $name, $id);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                throw new Exception('Job position name already exists.');
            }
            $checkStmt->close();

            // Get working hours per day for this position, fallback to settings
            $wh = 8.0;
            $whStmt = $mysqli->prepare("SELECT working_hours_per_day FROM job_positions WHERE id = ?");
            $whStmt->bind_param('i', $id);
            $whStmt->execute();
            $whRes = $whStmt->get_result()->fetch_assoc();
            $whStmt->close();
            if ($whRes && isset($whRes['working_hours_per_day']) && (float)$whRes['working_hours_per_day'] > 0) {
                $wh = (float)$whRes['working_hours_per_day'];
            } else {
                $whRow2 = $mysqli->query("SELECT company_hours_per_day FROM time_date_settings LIMIT 1");
                $row2 = $whRow2 ? $whRow2->fetch_assoc() : null;
                if ($row2 && (float)$row2['company_hours_per_day'] > 0) $wh = (float)$row2['company_hours_per_day'];
            }
            $ratePerHour = $wh > 0 ? $ratePerDay / $wh : 0;

            $stmt = $mysqli->prepare("UPDATE job_positions SET name = ?, rate_per_day = ?, rate_per_hour = ?, payroll_frequency = ? WHERE id = ?");
            $stmt->bind_param('sddsi', $name, $ratePerDay, $ratePerHour, $payrollFrequency, $id);
            if (!$stmt->execute()) {
                throw new Exception('Update failed: ' . $stmt->error);
            }
            $stmt->close();
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Job position updated successfully.']);
        } catch (Exception $e) {
            error_log("Update Position Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'update_position_payroll':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'POST required']);
            break;
        }
        try {
            $userRoles = $_SESSION['roles'] ?? [];
            if (!in_array('head_admin', $userRoles)) {
                ob_end_clean();
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only Head Admin can edit payroll frequency']);
                break;
            }
            $id = intval($_POST['id'] ?? 0);
            $freq = strtolower(trim($_POST['payroll_frequency'] ?? ''));
            $allowed = ['daily','weekly','bi-weekly','monthly'];
            if ($id <= 0 || !in_array($freq, $allowed, true)) {
                throw new Exception('Invalid parameters.');
            }
            $stmt = $mysqli->prepare("UPDATE job_positions SET payroll_frequency = ? WHERE id = ?");
            $stmt->bind_param('si', $freq, $id);
            if (!$stmt->execute()) {
                throw new Exception('Update failed: ' . $stmt->error);
            }
            $stmt->close();
            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Payroll frequency updated.']);
        } catch (Exception $e) {
            error_log("Update Position Payroll Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'list_positions':
        try {
            // First, check what columns exist in job_positions table
            $checkColumns = $mysqli->query("DESCRIBE job_positions");
            $columns = [];
            if ($checkColumns) {
                while ($row = $checkColumns->fetch_assoc()) {
                    $columns[] = $row['Field'];
                }
                $checkColumns->free();
            }

            // Build SELECT based on available columns
            $selectFields = "jp.id, jp.name";
            if (in_array('rate_per_day', $columns)) {
                $selectFields .= ", jp.rate_per_day";
            } else {
                $selectFields .= ", 0 as rate_per_day";
            }
            if (in_array('rate_per_hour', $columns)) {
                $selectFields .= ", jp.rate_per_hour";
            } else {
                $selectFields .= ", 0 as rate_per_hour";
            }
            if (in_array('working_hours_per_day', $columns)) {
                $selectFields .= ", jp.working_hours_per_day";
            } else {
                $selectFields .= ", 8 as working_hours_per_day";
            }
            if (in_array('payroll_frequency', $columns)) {
                $selectFields .= ", jp.payroll_frequency";
            } else {
                $selectFields .= ", 'monthly' as payroll_frequency";
            }

            $query = "
                SELECT $selectFields, COALESCE(COUNT(e.id), 0) AS employee_count
                FROM job_positions jp
                LEFT JOIN employees e ON jp.id = e.job_position_id
                GROUP BY jp.id
                ORDER BY jp.name
            ";

            error_log("List Positions Query: " . $query);

            $result = $mysqli->query($query);
            if (!$result) {
                error_log("MySQL Error: " . $mysqli->error);
                throw new Exception('Query failed: ' . $mysqli->error);
            }
            $positions = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();

            ob_end_clean();
            echo json_encode(['success' => true, 'data' => $positions]);
        } catch (Exception $e) {
            error_log("List Positions Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch job positions: ' . $e->getMessage()]);
        }
        break;

    case 'add_department':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'POST required']);
            break;
        }
        try {
            $userRoles = $_SESSION['roles'] ?? [];
            if (!in_array('head_admin', $userRoles)) {
                ob_end_clean();
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only Head Admin can add departments']);
                break;
            }
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                throw new Exception('Department name is required.');
            }
            // Check for duplicates (case-insensitive)
            $checkStmt = $mysqli->prepare("SELECT id FROM departments WHERE LOWER(name) = LOWER(?)");
            $checkStmt->bind_param('s', $name);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                throw new Exception('Department name already exists.');
            }
            $checkStmt->close();
            // Insert
            $stmt = $mysqli->prepare("INSERT INTO departments (name) VALUES (?)");
            $stmt->bind_param('s', $name);
            if (!$stmt->execute()) {
                throw new Exception('Insert failed: ' . $stmt->error);
            }
            $newId = $mysqli->insert_id;
            $stmt->close();

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Department added successfully.', 'data' => ['id' => $newId]]);
        } catch (Exception $e) {
            error_log("Add Department Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'add_position':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'POST required']);
            break;
        }
        try {
            $userRoles = $_SESSION['roles'] ?? [];
            if (!in_array('head_admin', $userRoles)) {
                ob_end_clean();
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only Head Admin can add job positions']);
                break;
            }
            $name = trim($_POST['name'] ?? '');
            $ratePerDay = floatval($_POST['rate_per_day'] ?? 0);
            $payrollFrequency = strtolower(trim($_POST['payroll_frequency'] ?? 'monthly'));
            $allowedFreq = ['weekly','biweekly','semimonthly','monthly'];
            if (!in_array($payrollFrequency, $allowedFreq, true)) { $payrollFrequency = 'monthly'; }

            if (empty($name)) {
                throw new Exception('Job position name is required.');
            }
            if ($ratePerDay < 0) {
                throw new Exception('Rate per day must be a non-negative number.');
            }

            // Check for duplicates (case-insensitive)
            $checkStmt = $mysqli->prepare("SELECT id FROM job_positions WHERE LOWER(name) = LOWER(?)");
            $checkStmt->bind_param('s', $name);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                throw new Exception('Job position name already exists.');
            }
            $checkStmt->close();

            // Get default working_hour_per_day from settings (fallback 8)
            $whRes = $mysqli->query("SELECT company_hours_per_day FROM time_date_settings LIMIT 1");
            $whRow = $whRes ? $whRes->fetch_assoc() : null;
            $workingHourPerDay = isset($whRow['company_hours_per_day']) ? (float)$whRow['company_hours_per_day'] : 8;

            // Compute rate_per_hour = rate_per_day / working_hour_per_day
            $ratePerHour = $workingHourPerDay > 0 ? $ratePerDay / $workingHourPerDay : 0;

            // Insert with payroll_frequency
            $stmt = $mysqli->prepare("INSERT INTO job_positions (name, rate_per_day, rate_per_hour, working_hours_per_day, payroll_frequency) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sddds', $name, $ratePerDay, $ratePerHour, $workingHourPerDay, $payrollFrequency);
            if (!$stmt->execute()) {
                throw new Exception('Insert failed: ' . $stmt->error);
            }
            $newId = $mysqli->insert_id;
            $stmt->close();

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Job position added successfully.', 'data' => ['id' => $newId]]);
        } catch (Exception $e) {
            error_log("Add Position Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_department':
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            ob_end_clean();
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'DELETE required']);
            break;
        }
        $userRoles = $_SESSION['roles'] ?? [];
        if (!in_array('head_admin', $userRoles)) {
            ob_end_clean();
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Only Head Admin can delete departments']);
            break;
        }
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Department ID required']);
            break;
        }
        try {
            // Check employee count
            $countStmt = $mysqli->prepare("SELECT COUNT(e.id) AS emp_count FROM employees e WHERE e.department_id = ?");
            $countStmt->bind_param('i', $id);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countData = $countResult->fetch_assoc();
            $empCount = (int)$countData['emp_count'];
            $countStmt->close();

            if ($empCount > 0) {
                throw new Exception("Cannot delete department: {$empCount} employee(s) are assigned. Reassign them first.");
            }

            $stmt = $mysqli->prepare("DELETE FROM departments WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected === 0) {
                throw new Exception('Department not found.');
            }

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Department deleted successfully.']);
        } catch (Exception $e) {
            error_log("Delete Department Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    case 'delete_position':
        if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
            ob_end_clean();
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'DELETE required']);
            break;
        }
        $userRoles = $_SESSION['roles'] ?? [];
        if (!in_array('head_admin', $userRoles)) {
            ob_end_clean();
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Only Head Admin can delete job positions']);
            break;
        }
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Job position ID required']);
            break;
        }
        try {
            // Check employee count
            $countStmt = $mysqli->prepare("SELECT COUNT(e.id) AS emp_count FROM employees e WHERE e.job_position_id = ?");
            $countStmt->bind_param('i', $id);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countData = $countResult->fetch_assoc();
            $empCount = (int)$countData['emp_count'];
            $countStmt->close();

            if ($empCount > 0) {
                throw new Exception("Cannot delete job position: {$empCount} employee(s) are assigned. Reassign them first.");
            }

            $stmt = $mysqli->prepare("DELETE FROM job_positions WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected === 0) {
                throw new Exception('Job position not found.');
            }

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Job position deleted successfully.']);
        } catch (Exception $e) {
            error_log("Delete Position Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
        break;
}

if ($mysqli) {
    $mysqli->close();
}
ob_end_flush();
