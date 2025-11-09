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

require_once 'conn.php';  // Assumes this returns ['mysqli' => $mysqli]

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

    case 'list_positions':
        try {
            $query = "
                SELECT jp.id, jp.name, jp.rate_per_hour, COALESCE(COUNT(e.id), 0) AS employee_count
                FROM job_positions jp
                LEFT JOIN employees e ON jp.id = e.job_position_id
                GROUP BY jp.id, jp.name, jp.rate_per_hour
                ORDER BY jp.name
            ";
            $result = $mysqli->query($query);
            if (!$result) {
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
            $name = trim($_POST['name'] ?? '');
            $ratePerHour = floatval($_POST['rate_per_hour'] ?? 0);
            if (empty($name)) {
                throw new Exception('Job position name is required.');
            }
            if ($ratePerHour < 0) {
                throw new Exception('Rate per hour must be a non-negative number.');
            }
            // Check for duplicates (case-insensitive)
            $checkStmt = $mysqli->prepare("SELECT id FROM job_positions WHERE LOWER(name) = LOWER(?)");
            $checkStmt->bind_param('s', $name);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                throw new Exception('Job position name already exists.');
            }
            $checkStmt->close();
            // Insert
            $stmt = $mysqli->prepare("INSERT INTO job_positions (name, rate_per_hour) VALUES (?, ?)");
            $stmt->bind_param('sd', $name, $ratePerHour);
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
