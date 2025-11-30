<?php
// views/leave_handler.php - Backend for admin leave page
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

require_once 'auth.php'; // Authentication and role checks
require_once 'conn.php'; // Database connection

$db = conn();
$mysqli = $db['mysqli'];

header('Content-Type: application/json');
ob_start();

// Check if user is admin
$userRoles = $_SESSION['roles'] ?? [];
if (!in_array('admin', $userRoles) && !in_array('head_admin', $userRoles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
} else {
    $action = $_GET['action'] ?? '';
}

try {
    switch ($action) {
        case 'leave_requests':
            // Fetch all leave requests with employee details
            $search = $_GET['search'] ?? '';
            $status = $_GET['status'] ?? 'all';
            $startDate = $_GET['start_date'] ?? '';
            $endDate = $_GET['end_date'] ?? '';

            $query = "SELECT lr.id, lr.leave_type, lr.start_date, lr.end_date, lr.days, lr.reason, lr.proof_path, lr.status, lr.submitted_at, 
                             u.first_name, u.last_name, u.email, u.avatar_path 
                      FROM leave_requests lr 
                      JOIN employees e ON lr.employee_id = e.id 
                      JOIN users u ON e.user_id = u.id 
                      WHERE 1=1";

            $params = [];
            $types = '';

            if ($search) {
                $query .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)";
                $searchTerm = "%$search%";
                $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
                $types .= 'sss';
            }

            if ($status !== 'all') {
                $query .= " AND lr.status = ?";
                $params[] = $status;
                $types .= 's';
            }

            if ($startDate && $endDate) {
                $query .= " AND lr.start_date >= ? AND lr.end_date <= ?";
                $params = array_merge($params, [$startDate, $endDate]);
                $types .= 'ss';
            }

            $query .= " ORDER BY lr.submitted_at DESC";

            $stmt = $mysqli->prepare($query);
            if ($params) {
                $stmt->bind_param($types, ...$params);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $requests = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            // Handle avatars
            foreach ($requests as &$req) {
                $req['avatar_path'] = $req['avatar_path'] ?: 'img/user.jpg';
                if (strpos($req['avatar_path'], 'uploads/') === 0) {
                    $req['avatar_path'] = '../' . $req['avatar_path'];
                }
            }

            echo json_encode(['success' => true, 'data' => $requests]);
            break;

        case 'view_leave_request':
            $requestId = (int)$_GET['id'];
            error_log("Debug view_leave_request: requestId=$requestId");
            $stmt = $mysqli->prepare("SELECT lr.*, u.first_name, u.last_name, u.email, u.avatar_path 
                                      FROM leave_requests lr 
                                      JOIN employees e ON lr.employee_id = e.id 
                                      JOIN users u ON e.user_id = u.id 
                                      WHERE lr.id = ?");
            $stmt->bind_param('i', $requestId);
            $stmt->execute();
            $result = $stmt->get_result();
            $request = $result->fetch_assoc();
            $stmt->close();

            if (!$request) {
                throw new Exception('Request not found');
            }

            $request['avatar_path'] = $request['avatar_path'] ?: 'img/user.jpg';
            if (strpos($request['avatar_path'], 'uploads/') === 0) {
                $request['avatar_path'] = '../' . $request['avatar_path'];
            }

            echo json_encode(['success' => true, 'data' => $request]);
            break;

        case 'approve_leave':
            $requestId = (int)$_POST['id'];
            // Get leave_type, days, and employee_id
            $stmt = $mysqli->prepare("SELECT leave_type, days, employee_id FROM leave_requests WHERE id = ? AND status = 'Pending'");
            $stmt->bind_param('i', $requestId);
            $stmt->execute();
            $result = $stmt->get_result();
            $req = $result->fetch_assoc();
            $stmt->close();
            if (!$req) {
                throw new Exception('Request not found or not pending');
            }
            $leaveType = $req['leave_type'];
            $days = $req['days'];
            $employeeId = $req['employee_id'];

            // Update status and fields
            $stmt = $mysqli->prepare("UPDATE leave_requests SET status = 'Approved', deducted_from = ?, approved_at = NOW(), approved_by = ? WHERE id = ? AND status = 'Pending'");
            $stmt->bind_param('sii', $leaveType, $_SESSION['user_id'], $requestId);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                // Deduct from employee balance
                $balanceColumn = '';
                if ($leaveType === 'Paid') {
                    $balanceColumn = 'annual_paid_leave_days';
                } elseif ($leaveType === 'Unpaid') {
                    $balanceColumn = 'annual_unpaid_leave_days';
                } elseif ($leaveType === 'Sick') {
                    $balanceColumn = 'annual_sick_leave_days';
                }
                if ($balanceColumn) {
                    $stmt = $mysqli->prepare("UPDATE employees SET $balanceColumn = $balanceColumn - ? WHERE id = ?");
                    $stmt->bind_param('ii', $days, $employeeId);
                    $stmt->execute();
                    $stmt->close();
                }
                echo json_encode(['success' => true, 'message' => 'Request approved']);
            } else {
                throw new Exception('Failed to approve');
            }
            $stmt->close();
            break;

        case 'decline_leave':
            $requestId = (int)$_POST['id'];
            $stmt = $mysqli->prepare("UPDATE leave_requests SET status = 'Rejected', approved_at = NOW(), approved_by = ? WHERE id = ? AND status = 'Pending'");
            $stmt->bind_param('ii', $_SESSION['user_id'], $requestId);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Request declined']);
            } else {
                throw new Exception('Failed to decline or request not pending');
            }
            $stmt->close();
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$mysqli->close();
ob_end_flush();
