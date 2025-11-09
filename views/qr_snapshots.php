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
                SELECT e.id, e.first_name, e.last_name, qc.qr_image_path,
                       GROUP_CONCAT(JSON_OBJECT('image_path', s.image_path, 'captured_at', s.captured_at) SEPARATOR ',') AS snapshots_json
                FROM employees e
                LEFT JOIN qr_codes qc ON e.id = qc.employee_id
                LEFT JOIN attendance_logs al ON e.id = al.employee_id
                LEFT JOIN snapshots s ON al.id = s.attendance_log_id
                WHERE e.status = 'Active'
                GROUP BY e.id
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
                    $snapshots = explode(',', $emp['snapshots_json']);
                    foreach ($snapshots as $snap) {
                        $emp['snapshots'][] = json_decode($snap, true);
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