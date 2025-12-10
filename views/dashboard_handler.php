<?php
require_once 'auth.php';
require_once 'conn.php';
header('Content-Type: application/json');

try {
    $db = conn();
    $mysqli = $db['mysqli'];

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    if ($action === 'metrics') {
        $today = date('Y-m-d');

        // Total employees (active only)
        $res = $mysqli->query("SELECT COUNT(*) AS c FROM employees WHERE status = 'Active'");
        $row = $res ? $res->fetch_assoc() : ['c' => 0];
        $totalEmployees = (int)($row['c'] ?? 0);

        // Present and Late today
        $stmt = $mysqli->prepare("SELECT status, COUNT(*) AS c FROM attendance_logs WHERE date = ? GROUP BY status");
        $stmt->bind_param('s', $today);
        $stmt->execute();
        $rs = $stmt->get_result();
        $map = ['Present' => 0, 'Late' => 0];
        while ($r = $rs->fetch_assoc()) {
            $st = $r['status'] ?? '';
            $cnt = (int)($r['c'] ?? 0);
            if ($st === 'Present' || $st === 'Late') {
                $map[$st] = $cnt;
            }
        }
        $stmt->close();

        // Pending payroll (unpaid payroll rows)
        $res2 = $mysqli->query("SELECT COUNT(*) AS c FROM payroll WHERE paid_status = 'Unpaid'");
        $row2 = $res2 ? $res2->fetch_assoc() : ['c' => 0];
        $pendingPayroll = (int)($row2['c'] ?? 0);

        echo json_encode([
            'success' => true,
            'data' => [
                'total_employees' => $totalEmployees,
                'present_today' => $map['Present'] ?? 0,
                'late_today' => $map['Late'] ?? 0,
                'pending_payroll' => $pendingPayroll,
            ]
        ]);
        exit;
    }

    if ($action === 'attendance_chart') {
        $period = strtolower(trim($_GET['period'] ?? $_POST['period'] ?? 'weekly'));
        $valid = ['daily','weekly','monthly'];
        if (!in_array($period, $valid, true)) $period = 'weekly';
        $yearParam = isset($_GET['year']) ? (int)$_GET['year'] : (isset($_POST['year']) ? (int)$_POST['year'] : (int)date('Y'));

        $labels = [];
        $present = [];
        $late = [];

        if ($period === 'daily') {
            // Last 7 days
            $end = new DateTime();
            $start = (clone $end)->modify('-6 days');
            $startStr = $start->format('Y-m-d');
            $endStr = $end->format('Y-m-d');
            $sql = "SELECT date, SUM(status='Present') AS present_cnt, SUM(status='Late') AS late_cnt
                    FROM attendance_logs
                    WHERE date BETWEEN ? AND ?
                    GROUP BY date
                    ORDER BY date";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('ss', $startStr, $endStr);
            $stmt->execute();
            $res = $stmt->get_result();
            $map = [];
            while ($r = $res->fetch_assoc()) {
                $map[$r['date']] = ['p' => (int)$r['present_cnt'], 'l' => (int)$r['late_cnt']];
            }
            $stmt->close();
            $iter = clone $start;
            while ($iter <= $end) {
                $d = $iter->format('Y-m-d');
                $labels[] = $d;
                $present[] = $map[$d]['p'] ?? 0;
                $late[] = $map[$d]['l'] ?? 0;
                $iter->modify('+1 day');
            }
        } elseif ($period === 'weekly') {
            // Last 8 ISO weeks
            $end = new DateTime();
            $start = (clone $end)->modify('-7 weeks');
            $startStr = $start->format('Y-m-d');
            $endStr = $end->format('Y-m-d');
            $sql = "SELECT YEARWEEK(date, 1) AS yw, CONCAT('W', LPAD(WEEK(date, 1),2,'0')) AS wlabel,
                           SUM(status='Present') AS present_cnt, SUM(status='Late') AS late_cnt
                    FROM attendance_logs
                    WHERE date BETWEEN ? AND ?
                    GROUP BY yw, wlabel
                    ORDER BY yw";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('ss', $startStr, $endStr);
            $stmt->execute();
            $res = $stmt->get_result();
            $map = [];
            while ($r = $res->fetch_assoc()) {
                $map[$r['yw']] = ['label' => $r['wlabel'], 'p' => (int)$r['present_cnt'], 'l' => (int)$r['late_cnt']];
            }
            $stmt->close();

            // Build week sequence
            $iter = clone $start;
            for ($i=0; $i<8; $i++) {
                $key = (int)$iter->format('oW'); // ISO year+week, matches YEARWEEK(...,1)
                $weekNum = (int)$iter->format('W');
                $label = 'W' . str_pad((string)$weekNum, 2, '0', STR_PAD_LEFT);
                $labels[] = $map[$key]['label'] ?? $label;
                $present[] = $map[$key]['p'] ?? 0;
                $late[] = $map[$key]['l'] ?? 0;
                $iter->modify('+1 week');
            }
        } else { // monthly
            // Selected year by month
            $year = (int)$yearParam;
            $sql = "SELECT DATE_FORMAT(date, '%Y-%m') AS ym,
                           SUM(status='Present') AS present_cnt, SUM(status='Late') AS late_cnt
                    FROM attendance_logs
                    WHERE YEAR(date) = ?
                    GROUP BY ym
                    ORDER BY ym";
            $stmt = $mysqli->prepare($sql);
            $stmt->bind_param('i', $year);
            $stmt->execute();
            $res = $stmt->get_result();
            $map = [];
            while ($r = $res->fetch_assoc()) {
                $map[$r['ym']] = ['p' => (int)$r['present_cnt'], 'l' => (int)$r['late_cnt']];
            }
            $stmt->close();
            for ($m=1; $m<=12; $m++) {
                $ym = sprintf('%04d-%02d', $year, $m);
                $labels[] = date('M', strtotime($ym . '-01'));
                $present[] = $map[$ym]['p'] ?? 0;
                $late[] = $map[$ym]['l'] ?? 0;
            }
        }

        echo json_encode(['success' => true, 'data' => [
            'labels' => $labels,
            'present' => $present,
            'late' => $late,
        ]]);
        exit;
    }

    if ($action === 'events') {
        $y = isset($_GET['year']) ? (int)$_GET['year'] : (isset($_POST['year']) ? (int)$_POST['year'] : (int)date('Y'));
        $m = isset($_GET['month']) ? (int)$_GET['month'] : (isset($_POST['month']) ? (int)$_POST['month'] : (int)date('n'));
        if ($m < 1 || $m > 12) { $m = (int)date('n'); }
        $first = DateTime::createFromFormat('Y-n-j', $y . '-' . $m . '-1');
        if (!$first) { $first = new DateTime('first day of this month'); }
        $last = (clone $first)->modify('last day of this month');
        $firstStr = $first->format('Y-m-d');
        $lastStr = $last->format('Y-m-d');

        $events = [];
        // Holidays
        $stmt = $mysqli->prepare("SELECT id, name, type, start_date, end_date FROM holidays WHERE end_date >= ? AND start_date <= ?");
        $stmt->bind_param('ss', $firstStr, $lastStr);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $events[] = [
                'name' => $r['name'],
                'type' => $r['type'],
                'start_date' => $r['start_date'],
                'end_date' => $r['end_date'],
                'category' => 'holiday'
            ];
        }
        $stmt->close();
        // Special events
        $stmt = $mysqli->prepare("SELECT id, name, start_date, end_date, paid FROM special_events WHERE end_date >= ? AND start_date <= ?");
        $stmt->bind_param('ss', $firstStr, $lastStr);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($r = $res->fetch_assoc()) {
            $events[] = [
                'name' => $r['name'],
                'type' => $r['paid'],
                'start_date' => $r['start_date'],
                'end_date' => $r['end_date'],
                'category' => 'event'
            ];
        }
        $stmt->close();

        echo json_encode(['success' => true, 'data' => $events]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
