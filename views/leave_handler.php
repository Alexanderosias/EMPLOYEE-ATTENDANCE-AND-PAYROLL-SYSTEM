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
            // Get leave_type, days, employee_id, and date range
            $stmt = $mysqli->prepare("SELECT leave_type, days, employee_id, start_date, end_date FROM leave_requests WHERE id = ? AND status = 'Pending'");
            $stmt->bind_param('i', $requestId);
            $stmt->execute();
            $result = $stmt->get_result();
            $req = $result->fetch_assoc();
            $stmt->close();
            if (!$req) {
                throw new Exception('Request not found or not pending');
            }
            $leaveType = $req['leave_type'];
            $days = (int)$req['days'];
            $employeeId = (int)$req['employee_id'];
            $startDateStr = $req['start_date'] ?? null;
            $endDateStr = $req['end_date'] ?? null;

            // Compute effective days to deduct based on employee schedules
            $effectiveDays = $days;
            if ($startDateStr && $endDateStr) {
                try {
                    $startDate = new DateTime($startDateStr);
                    $endDate = new DateTime($endDateStr);
                    if ($startDate > $endDate) {
                        $tmp = $startDate;
                        $startDate = $endDate;
                        $endDate = $tmp;
                    }

                    // Fetch working days of week for this employee
                    $stmt = $mysqli->prepare("SELECT DISTINCT day_of_week FROM schedules WHERE employee_id = ? AND is_working = 1");
                    $stmt->bind_param('i', $employeeId);
                    $stmt->execute();
                    $schedResult = $stmt->get_result();
                    $workingDays = [];
                    while ($row = $schedResult->fetch_assoc()) {
                        $workingDays[$row['day_of_week']] = true;
                    }
                    $stmt->close();

                    // Build a set of dates that are holidays or special events within the range
                    $specialDates = [];

                    // Holidays
                    $stmt = $mysqli->prepare("SELECT start_date, end_date FROM holidays WHERE NOT (end_date < ? OR start_date > ?)");
                    if ($stmt) {
                        $stmt->bind_param('ss', $startDateStr, $endDateStr);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        while ($row = $res->fetch_assoc()) {
                            $hStart = new DateTime($row['start_date']);
                            $hEnd = new DateTime($row['end_date']);
                            if ($hStart > $hEnd) {
                                $tmpH = $hStart;
                                $hStart = $hEnd;
                                $hEnd = $tmpH;
                            }
                            $hEndInclusive = (clone $hEnd)->modify('+1 day');
                            $hPeriod = new DatePeriod($hStart, new DateInterval('P1D'), $hEndInclusive);
                            foreach ($hPeriod as $d) {
                                $specialDates[$d->format('Y-m-d')] = true;
                            }
                        }
                        $stmt->close();
                    }

                    // Special events
                    $stmt = $mysqli->prepare("SELECT start_date, end_date FROM special_events WHERE NOT (end_date < ? OR start_date > ?)");
                    if ($stmt) {
                        $stmt->bind_param('ss', $startDateStr, $endDateStr);
                        $stmt->execute();
                        $res = $stmt->get_result();
                        while ($row = $res->fetch_assoc()) {
                            $eStart = new DateTime($row['start_date']);
                            $eEnd = new DateTime($row['end_date']);
                            if ($eStart > $eEnd) {
                                $tmpE = $eStart;
                                $eStart = $eEnd;
                                $eEnd = $tmpE;
                            }
                            $eEndInclusive = (clone $eEnd)->modify('+1 day');
                            $ePeriod = new DatePeriod($eStart, new DateInterval('P1D'), $eEndInclusive);
                            foreach ($ePeriod as $d) {
                                $specialDates[$d->format('Y-m-d')] = true;
                            }
                        }
                        $stmt->close();
                    }

                    if (!empty($workingDays)) {
                        $effectiveDays = 0;
                        $periodEnd = (clone $endDate)->modify('+1 day');
                        $period = new DatePeriod($startDate, new DateInterval('P1D'), $periodEnd);
                        foreach ($period as $date) {
                            $dayName = $date->format('l');
                            $dateStrLoop = $date->format('Y-m-d');

                            // Skip if this date is a holiday or special event
                            if (!empty($specialDates[$dateStrLoop])) {
                                continue;
                            }

                            if (!empty($workingDays[$dayName])) {
                                $effectiveDays++;
                            }
                        }
                    } else {
                        // No schedules found; do not deduct anything
                        $effectiveDays = 0;
                    }
                } catch (Exception $e) {
                    // On any error, fall back to original days value
                    $effectiveDays = $days;
                }
            }

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
                if ($balanceColumn && $effectiveDays > 0) {
                    $stmt = $mysqli->prepare("UPDATE employees SET $balanceColumn = $balanceColumn - ? WHERE id = ?");
                    $stmt->bind_param('ii', $effectiveDays, $employeeId);
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
            $feedback = trim($_POST['admin_feedback'] ?? '');
            $stmt = $mysqli->prepare("UPDATE leave_requests SET status = 'Rejected', admin_feedback = ?, approved_at = NOW(), approved_by = ? WHERE id = ? AND status = 'Pending'");
            if (!$stmt) {
                throw new Exception('Prepare failed: ' . $mysqli->error);
            }
            $stmt->bind_param('sii', $feedback, $_SESSION['user_id'], $requestId);
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
