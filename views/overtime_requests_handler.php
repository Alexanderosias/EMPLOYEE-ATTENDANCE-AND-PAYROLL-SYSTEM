<?php
require_once 'conn.php';
require_once 'auth.php';

header('Content-Type: application/json');

function hasHeadAdminRole()
{
    if (isset($_SESSION['roles'])) {
        $userRoles = is_array($_SESSION['roles']) ? $_SESSION['roles'] : json_decode($_SESSION['roles'], true);
        return is_array($userRoles) && in_array('head_admin', $userRoles, true);
    } elseif (isset($_SESSION['role'])) {
        return $_SESSION['role'] === 'head_admin';
    }
    return false;
}

try {
    $db = conn();
    $mysqli = $db['mysqli'];

    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    if ($method === 'GET' && $action === 'list') {
        if (!hasHeadAdminRole()) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Unauthorized: Must have head_admin role.']);
            exit;
        }

        $statusFilter = $_GET['status'] ?? '';
        $allowedStatuses = ['Pending', 'Approved', 'Rejected', 'AutoApproved'];
        $where = '';
        if ($statusFilter && in_array($statusFilter, $allowedStatuses, true)) {
            $where = "WHERE ot.status = '" . $mysqli->real_escape_string($statusFilter) . "'";
        }

        $sql = "SELECT
                    ot.id,
                    ot.employee_id,
                    CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
                    ot.date,
                    ot.scheduled_end_time,
                    ot.actual_out_time,
                    ot.raw_ot_minutes,
                    ot.approved_ot_minutes,
                    ot.status,
                    ot.remarks
                FROM overtime_requests ot
                JOIN employees e ON ot.employee_id = e.id
                $where
                ORDER BY ot.date DESC, ot.actual_out_time DESC, ot.id DESC";

        $res = $mysqli->query($sql);
        if (!$res) {
            throw new Exception('Query failed: ' . $mysqli->error);
        }

        $rows = [];
        while ($r = $res->fetch_assoc()) {
            $rows[] = [
                'id' => (int)$r['id'],
                'employee_id' => (int)$r['employee_id'],
                'employee_name' => $r['employee_name'] ?? '',
                'date' => $r['date'] ?? '',
                'scheduled_end_time' => $r['scheduled_end_time'] ?? '',
                'actual_out_time' => $r['actual_out_time'] ?? '',
                'raw_ot_minutes' => (int)($r['raw_ot_minutes'] ?? 0),
                'approved_ot_minutes' => (int)($r['approved_ot_minutes'] ?? 0),
                'status' => $r['status'] ?? 'Pending',
                'remarks' => $r['remarks'] ?? '',
            ];
        }
        $res->free();

        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }

    if ($method === 'POST') {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $input = null;
        if (stripos($contentType, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true) ?? [];
        } else {
            $input = $_POST;
        }
        $action = $input['action'] ?? $action;

        if ($action === 'update') {
            if (!hasHeadAdminRole()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Unauthorized: Must have head_admin role.']);
                exit;
            }

            $id = isset($input['id']) ? (int)$input['id'] : 0;
            $newStatus = trim((string)($input['status'] ?? ''));
            $approvedMinutes = isset($input['approved_minutes']) ? (int)$input['approved_minutes'] : 0;
            $remarks = trim((string)($input['remarks'] ?? ''));

            if ($id <= 0) {
                throw new Exception('Invalid overtime request ID.');
            }

            $allowedStatus = ['Approved', 'Rejected'];
            if (!in_array($newStatus, $allowedStatus, true)) {
                throw new Exception('Invalid status. Only Approved or Rejected are allowed.');
            }

            $stmt = $mysqli->prepare("SELECT raw_ot_minutes FROM overtime_requests WHERE id = ? LIMIT 1");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();

            if (!$row) {
                throw new Exception('Overtime request not found.');
            }

            $rawOtMinutes = (int)$row['raw_ot_minutes'];

            if ($newStatus === 'Rejected') {
                $approvedMinutes = 0;
            } else {
                if ($approvedMinutes < 0) {
                    $approvedMinutes = 0;
                }
                if ($approvedMinutes > $rawOtMinutes) {
                    $approvedMinutes = $rawOtMinutes;
                }
            }

            $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            if (!$userId) {
                throw new Exception('Missing user session.');
            }

            $stmt = $mysqli->prepare("UPDATE overtime_requests SET approved_ot_minutes = ?, status = ?, approved_by = ?, approved_at = NOW(), remarks = ? WHERE id = ?");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $stmt->bind_param('isisi', $approvedMinutes, $newStatus, $userId, $remarks, $id);
            if (!$stmt->execute()) {
                $stmt->close();
                throw new Exception('Failed to update overtime request: ' . $stmt->error);
            }
            $stmt->close();

            echo json_encode(['success' => true, 'message' => 'Overtime request updated.']);
            exit;
        }

        throw new Exception('Invalid action.');
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($mysqli) && $mysqli) {
    $mysqli->close();
}
