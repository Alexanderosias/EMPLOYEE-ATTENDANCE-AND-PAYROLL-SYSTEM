<?php
header('Content-Type: application/json');
require_once 'conn.php';  // Now returns mysqli via conn()

try {
    $mysqli = conn();  // Get mysqli connection
    $action = $_GET['action'] ?? ($_POST['action'] ?? null);

    if (!$action) {
        throw new Exception('No action specified.');
    }

    switch ($action) {
        case 'list_departments':
            // Fetch departments with employee count (mysqli version)
            $query = "
                SELECT d.id, d.name, COALESCE(COUNT(e.id), 0) as employee_count
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
            echo json_encode($departments);
            break;

        case 'list_positions':
            // Fetch job positions with employee count (mysqli version)
            $query = "
                SELECT jp.id, jp.name, COALESCE(COUNT(e.id), 0) as employee_count
                FROM job_positions jp
                LEFT JOIN employees e ON jp.id = e.job_position_id
                GROUP BY jp.id, jp.name
                ORDER BY jp.name
            ";
            $result = $mysqli->query($query);
            if (!$result) {
                throw new Exception('Query failed: ' . $mysqli->error);
            }
            $positions = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($positions);
            break;

        case 'add_department':
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                throw new Exception('Department name is required.');
            }
            // Check for duplicates (case-insensitive)
            $checkStmt = $mysqli->prepare("SELECT id FROM departments WHERE LOWER(name) = LOWER(?)");
            if (!$checkStmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $checkStmt->bind_param('s', $name);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            if ($checkResult->num_rows > 0) {
                throw new Exception('Department name already exists.');
            }
            $checkStmt->close();
            // Insert
            $stmt = $mysqli->prepare("INSERT INTO departments (name) VALUES (?)");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $newId = $mysqli->insert_id;
            $stmt->close();
            echo json_encode([
                'success' => true,
                'message' => 'Department added successfully.',
                'id' => $newId
            ]);
            break;

        case 'add_position':
            $name = trim($_POST['name'] ?? '');
            if (empty($name)) {
                throw new Exception('Job position name is required.');
            }
            // Check for duplicates (case-insensitive)
            $checkStmt = $mysqli->prepare("SELECT id FROM job_positions WHERE LOWER(name) = LOWER(?)");
            if (!$checkStmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $checkStmt->bind_param('s', $name);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            if ($checkResult->num_rows > 0) {
                throw new Exception('Job position name already exists.');
            }
            $checkStmt->close();
            // Insert
            $stmt = $mysqli->prepare("INSERT INTO job_positions (name) VALUES (?)");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $stmt->bind_param('s', $name);
            $stmt->execute();
            $newId = $mysqli->insert_id;
            $stmt->close();
            echo json_encode([
                'success' => true,
                'message' => 'Job position added successfully.',
                'id' => $newId
            ]);
            break;

        case 'delete_department':
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid department ID.');
            }
            // FIXED: Pre-check employee count before allowing delete
            $countStmt = $mysqli->prepare("SELECT COUNT(e.id) as emp_count FROM employees e WHERE e.department_id = ?");
            if (!$countStmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $countStmt->bind_param('i', $id);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countData = $countResult->fetch_assoc();
            $empCount = (int)$countData['emp_count'];
            $countStmt->close();

            if ($empCount > 0) {
                // Prevention: Do not delete; return error
                throw new Exception("Cannot delete department: {$empCount} employee(s) are assigned to this department. Please reassign them first.");
            }

            // If count === 0, delete directly (no unassign needed)
            $deleteStmt = $mysqli->prepare("DELETE FROM departments WHERE id = ?");
            if (!$deleteStmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $deleteStmt->bind_param('i', $id);
            $deleteStmt->execute();
            if ($deleteStmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Department deleted successfully.']);
            } else {
                throw new Exception('Department not found.');
            }
            $deleteStmt->close();
            break;

        case 'delete_position':
            $id = intval($_GET['id'] ?? 0);
            if ($id <= 0) {
                throw new Exception('Invalid job position ID.');
            }
            // FIXED: Pre-check employee count before allowing delete
            $countStmt = $mysqli->prepare("SELECT COUNT(e.id) as emp_count FROM employees e WHERE e.job_position_id = ?");
            if (!$countStmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $countStmt->bind_param('i', $id);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countData = $countResult->fetch_assoc();
            $empCount = (int)$countData['emp_count'];
            $countStmt->close();

            if ($empCount > 0) {
                // Prevention: Do not delete; return error
                throw new Exception("Cannot delete job position: {$empCount} employee(s) are assigned to this job position. Please reassign them first.");
            }

            // If count === 0, delete directly (no unassign needed)
            $deleteStmt = $mysqli->prepare("DELETE FROM job_positions WHERE id = ?");
            if (!$deleteStmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $deleteStmt->bind_param('i', $id);
            $deleteStmt->execute();
            if ($deleteStmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Job position deleted successfully.']);
            } else {
                throw new Exception('Job position not found.');
            }
            $deleteStmt->close();
            break;

        default:
            throw new Exception('Invalid action.');
    }
} catch (Exception $e) {
    error_log('Departments/Positions API Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
