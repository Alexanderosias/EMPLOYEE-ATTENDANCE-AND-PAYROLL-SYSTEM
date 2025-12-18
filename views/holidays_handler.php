<?php
require_once 'auth.php';
require_once 'conn.php';

header('Content-Type: application/json');
ob_start();

// Get database connection
$db = conn();
$mysqli = $db['mysqli'];

if (!$mysqli || $mysqli->connect_error) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    switch ($action) {
        case 'list_all':
            $search = $_GET['search'] ?? '';
            $type = $_GET['type'] ?? 'all';
            $startDate = $_GET['start_date'] ?? '';
            $endDate = $_GET['end_date'] ?? '';
            $holidays = [];
            $events = [];
            // Fetch holidays (fixed bind_param: 6 params for 6 placeholders)
            $stmt = $mysqli->prepare("SELECT holiday_id AS id, holiday_name AS name, holiday_type AS type, start_date, end_date, 'holidays' as `table` FROM holidays WHERE (? = '' OR holiday_name LIKE ?) AND (? = '' OR start_date >= ?) AND (? = '' OR end_date <= ?) AND (? = 'all' OR holiday_type = ?)");
            $searchParam = "%$search%";
            $stmt->bind_param('ssssssss', $search, $searchParam, $startDate, $startDate, $endDate, $endDate, $type, $type);
            $stmt->execute();
            $holidays = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            // Fetch events (fixed bind_param: 6 params for 6 placeholders)
            $stmt = $mysqli->prepare("SELECT event_id AS id, event_name AS name, start_date, end_date, paid, description, 'special_events' as `table` FROM special_events WHERE (? = '' OR event_name LIKE ?) AND (? = '' OR start_date >= ?) AND (? = '' OR end_date <= ?) AND (? = 'all' OR paid = ?)");
            $stmt->bind_param('ssssssss', $search, $searchParam, $startDate, $startDate, $endDate, $endDate, $type, $type);
            $stmt->execute();
            $events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            $data = array_merge($holidays, $events);
            echo json_encode(['success' => true, 'data' => $data]);
            break;

        case 'add_holiday':
            $name = trim($_POST['name']);
            $type = $_POST['type'];
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];

            if (!$name || !$type || !$startDate || !$endDate) throw new Exception('All fields required.');
            if (!in_array($type, ['regular', 'special_non_working', 'special_working'])) throw new Exception('Invalid type.');
            if ($startDate > $endDate) throw new Exception('Invalid dates.');

            $stmt = $mysqli->prepare("INSERT INTO holidays (holiday_name, holiday_type, start_date, end_date) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('ssss', $name, $type, $startDate, $endDate);
            $stmt->execute();
            $id = $mysqli->insert_id;
            $stmt->close();
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'add_event':
            $name = trim($_POST['name']);
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];
            $paid = $_POST['paid'];
            $description = trim($_POST['description']);

            if (!$name || !$startDate || !$endDate || !$paid) throw new Exception('Required fields missing.');
            if (!in_array($paid, ['yes', 'no', 'partial'])) throw new Exception('Invalid paid status.');
            if ($startDate > $endDate) throw new Exception('Invalid dates.');

            $stmt = $mysqli->prepare("INSERT INTO special_events (event_name, start_date, end_date, paid, description) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param('sssss', $name, $startDate, $endDate, $paid, $description);
            $stmt->execute();
            $id = $mysqli->insert_id;
            $stmt->close();
            echo json_encode(['success' => true, 'id' => $id]);
            break;

        case 'view_holiday':
            $id = (int)$_GET['id'];
            $stmt = $mysqli->prepare("SELECT holiday_id AS id, holiday_name AS name, holiday_type AS type, start_date, end_date FROM holidays WHERE holiday_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$result) throw new Exception('Holiday not found.');
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        case 'view_event':
            $id = (int)$_GET['id'];
            $stmt = $mysqli->prepare("SELECT event_id AS id, event_name AS name, start_date, end_date, paid, description FROM special_events WHERE event_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            if (!$result) throw new Exception('Event not found.');
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        case 'edit_holiday':
            $id = (int)$_POST['id'];
            $name = trim($_POST['name']);
            $type = $_POST['type'];
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];

            if (!$name || !$type || !$startDate || !$endDate) throw new Exception('All fields required.');
            if (!in_array($type, ['regular', 'special_non_working', 'special_working'])) throw new Exception('Invalid type.');
            if ($startDate > $endDate) throw new Exception('Invalid dates.');

            $stmt = $mysqli->prepare("UPDATE holidays SET holiday_name = ?, holiday_type = ?, start_date = ?, end_date = ? WHERE holiday_id = ?");
            $stmt->bind_param('ssssi', $name, $type, $startDate, $endDate, $id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            if ($affected === 0) throw new Exception('Holiday not found or no changes.');
            echo json_encode(['success' => true]);
            break;

        case 'edit_event':
            $id = (int)$_POST['id'];
            $name = trim($_POST['name']);
            $startDate = $_POST['start_date'];
            $endDate = $_POST['end_date'];
            $paid = $_POST['paid'];
            $description = trim($_POST['description']);

            if (!$name || !$startDate || !$endDate || !$paid) throw new Exception('Required fields missing.');
            if (!in_array($paid, ['yes', 'no', 'partial'])) throw new Exception('Invalid paid status.');
            if ($startDate > $endDate) throw new Exception('Invalid dates.');

            $stmt = $mysqli->prepare("UPDATE special_events SET event_name = ?, start_date = ?, end_date = ?, paid = ?, description = ? WHERE event_id = ?");
            $stmt->bind_param('sssssi', $name, $startDate, $endDate, $paid, $description, $id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            if ($affected === 0) throw new Exception('Event not found or no changes.');
            echo json_encode(['success' => true]);
            break;

        case 'delete_holiday':
            $id = (int)$_GET['id'];
            $stmt = $mysqli->prepare("DELETE FROM holidays WHERE holiday_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            if ($affected === 0) throw new Exception('Holiday not found.');
            echo json_encode(['success' => true]);
            break;

        case 'delete_event':
            $id = (int)$_GET['id'];
            $stmt = $mysqli->prepare("DELETE FROM special_events WHERE event_id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $affected = $stmt->affected_rows;
            $stmt->close();
            if ($affected === 0) throw new Exception('Event not found.');
            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

if (isset($mysqli)) {
    $mysqli->close();
}
ob_end_flush();
