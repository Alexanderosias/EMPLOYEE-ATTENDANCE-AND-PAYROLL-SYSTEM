<?php
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

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
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list_employees_qr_snapshots':
        try {
            $query = "
                SELECT 
                    e.employee_id AS id,
                    e.first_name,
                    e.last_name,
                    qc.qr_image_path,
                    al.snapshot_path,
                    (
                        SELECT GROUP_CONCAT(
                            JSON_OBJECT(
                                'id', s.snapshot_id,
                                'image_path', s.image_path,
                                'captured_at', COALESCE(s.captured_at, s.created_at)
                            ) SEPARATOR '|||'
                        )
                        FROM snapshots s
                        JOIN attendance_logs al2 ON s.attendance_log_id = al2.log_id
                        WHERE al2.employee_id = e.employee_id
                    ) AS snapshots_json
                FROM employees e
                LEFT JOIN qr_codes qc ON e.employee_id = qc.employee_id
                LEFT JOIN attendance_logs al 
                    ON e.employee_id = al.employee_id 
                   AND al.log_id = (
                        SELECT MAX(log_id) FROM attendance_logs WHERE employee_id = e.employee_id
                    )
                WHERE e.status = 'Active'
                GROUP BY e.employee_id
                ORDER BY e.last_name, e.first_name
            ";
            $result = $mysqli->query($query);
            if (!$result) {
                throw new Exception('Query failed: ' . $mysqli->error);
            }
            $employees = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();

            foreach ($employees as &$emp) {
                $emp['snapshots'] = [];
                if ($emp['snapshots_json']) {
                    $snapshots = explode('|||', $emp['snapshots_json']);  // Use ||| separator
                    foreach ($snapshots as $snap) {
                        $decoded = json_decode($snap, true);
                        if ($decoded && isset($decoded['image_path']) && isset($decoded['captured_at'])) {  // Validate required fields
                            $emp['snapshots'][] = $decoded;
                        }
                    }
                }
                unset($emp['snapshots_json']);
            }

            ob_end_clean();
            echo json_encode(['success' => true, 'data' => $employees]);
        } catch (Exception $e) {
            error_log("List Employees QR Snapshots Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to fetch data: ' . $e->getMessage()]);
        }
        break;

    case 'delete_snapshot':
        try {
            $id = $_GET['id'] ?? null;
            if (!$id || !is_numeric($id)) {
                throw new Exception('Invalid snapshot ID');
            }

            // Fetch the image path before deleting
            $stmt = $mysqli->prepare("SELECT image_path FROM snapshots WHERE snapshot_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $snapshot = $result->fetch_assoc();
            $stmt->close();

            if (!$snapshot) {
                throw new Exception('Snapshot not found');
            }

            // Build the file path (matches your working test script)
            $filePath = $_SERVER['DOCUMENT_ROOT'] . BASE_PATH . '/' . $snapshot['image_path'];

            // Log for debugging (optional, remove after testing)
            error_log("DOCUMENT_ROOT: " . $_SERVER['DOCUMENT_ROOT']);
            error_log("image_path: " . $snapshot['image_path']);
            error_log("Full file path: " . $filePath);

            // Delete the file
            if (file_exists($filePath)) {
                if (unlink($filePath)) {
                    error_log("File deleted successfully: " . $filePath);
                } else {
                    error_log("Failed to delete file (permissions?): " . $filePath);
                    // Continue with DB deletion even if file deletion fails
                }
            } else {
                error_log("File not found: " . $filePath);
            }

            // Delete from DB
            $stmt = $mysqli->prepare("DELETE FROM snapshots WHERE snapshot_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();

            ob_end_clean();
            echo json_encode(['success' => true, 'message' => 'Snapshot deleted']);
        } catch (Exception $e) {
            error_log("Delete Snapshot Error: " . $e->getMessage());
            ob_end_clean();
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        break;

    default:
        ob_end_clean();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}

if ($mysqli) {
    $mysqli->close();
}
ob_end_flush();
