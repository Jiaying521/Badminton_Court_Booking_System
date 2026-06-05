<?php
session_start();

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$role     = $_SESSION['role'];
$admin_id = (int)$_SESSION['id'];

if (!in_array($role, ['Admin', 'Superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$conn   = mysqli_connect("localhost", "root", "", "badminton_hub");
$action = $_POST['action'] ?? $_GET['action'] ?? '';

function logChange($conn, $coach_id, $date, $action, $old, $new, $changed_by, $role, $name) {
    $old_status = isset($old['status'])     ? "'" . mysqli_real_escape_string($conn, $old['status'])     . "'" : 'NULL';
    $new_status = isset($new['status'])     ? "'" . mysqli_real_escape_string($conn, $new['status'])     . "'" : 'NULL';
    $old_start  = isset($old['start_time']) ? "'" . mysqli_real_escape_string($conn, $old['start_time']) . "'" : 'NULL';
    $old_end    = isset($old['end_time'])   ? "'" . mysqli_real_escape_string($conn, $old['end_time'])   . "'" : 'NULL';
    $new_start  = isset($new['start_time']) ? "'" . mysqli_real_escape_string($conn, $new['start_time']) . "'" : 'NULL';
    $new_end    = isset($new['end_time'])   ? "'" . mysqli_real_escape_string($conn, $new['end_time'])   . "'" : 'NULL';
    $reason     = isset($new['reason'])     ? "'" . mysqli_real_escape_string($conn, $new['reason'])     . "'" : 'NULL';
    $esc_action = mysqli_real_escape_string($conn, $action);
    $esc_date   = mysqli_real_escape_string($conn, $date);
    $esc_role   = mysqli_real_escape_string($conn, $role);
    $esc_name   = mysqli_real_escape_string($conn, $name);

    mysqli_query($conn, "
        INSERT INTO coach_availability_log
            (coach_id, date, action, old_status, new_status, old_start_time, old_end_time,
             new_start_time, new_end_time, reason, changed_by, changed_by_role, changed_by_name)
        VALUES
            ($coach_id, '$esc_date', '$esc_action', $old_status, $new_status, $old_start,
             $old_end, $new_start, $new_end, $reason, $changed_by, '$esc_role', '$esc_name')
    ");
}

/* ── month_overview ─────────────────────────── */
if ($action === 'month_overview') {
    $year  = (int)($_GET['year']  ?? date('Y'));
    $month = (int)($_GET['month'] ?? date('n'));

    $month_str = sprintf('%04d-%02d', $year, $month);

    /* all unavailability records for the month, for all coaches */
    $res = mysqli_query($conn, "
        SELECT ca.coach_id, ca.date, ca.status, ca.start_time, ca.end_time,
               c.name AS coach_name, c.profile_img
        FROM coach_availability ca
        JOIN coaches c ON ca.coach_id = c.id
        WHERE DATE_FORMAT(ca.date, '%Y-%m') = '$month_str'
        ORDER BY ca.date, c.name
    ");

    $data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $d = $row['date'];
        if (!isset($data[$d])) $data[$d] = [];
        $data[$d][] = [
            'coach_id'   => (int)$row['coach_id'],
            'coach_name' => $row['coach_name'],
            'profile_img'=> $row['profile_img'],
            'status'     => $row['status'],
            'start_time' => $row['start_time'],
            'end_time'   => $row['end_time'],
        ];
    }

    echo json_encode(['success' => true, 'data' => $data]);
    exit();
}

/* ── day_detail ─────────────────────────────── */
if ($action === 'day_detail') {
    $date = mysqli_real_escape_string($conn, $_GET['date'] ?? '');

    $avail_res = mysqli_query($conn, "
        SELECT ca.id, ca.coach_id, ca.status, ca.start_time, ca.end_time, ca.reason,
               c.name AS coach_name, c.profile_img
        FROM coach_availability ca
        JOIN coaches c ON ca.coach_id = c.id
        WHERE ca.date = '$date'
        ORDER BY c.name, ca.start_time
    ");

    $avails = [];
    while ($row = mysqli_fetch_assoc($avail_res)) {
        $avails[] = $row;
    }

    echo json_encode(['success' => true, 'avails' => $avails]);
    exit();
}

/* ── save ───────────────────────────────────── */
if ($action === 'save') {
    $coach_id   = (int)$_POST['coach_id'];
    $date       = mysqli_real_escape_string($conn, $_POST['date'] ?? '');
    $status     = mysqli_real_escape_string($conn, $_POST['status'] ?? '');
    $start_time = !empty($_POST['start_time']) ? "'" . mysqli_real_escape_string($conn, $_POST['start_time']) . "'" : 'NULL';
    $end_time   = !empty($_POST['end_time'])   ? "'" . mysqli_real_escape_string($conn, $_POST['end_time'])   . "'" : 'NULL';
    $reason     = mysqli_real_escape_string($conn, $_POST['reason'] ?? '');
    $reason_sql = $reason !== '' ? "'$reason'" : 'NULL';

    if (!$date || !$status || !$coach_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }

    if ($status === 'Custom Hours') {
        if ($start_time === 'NULL' || $end_time === 'NULL') {
            echo json_encode(['success' => false, 'message' => 'Start and end time required for Custom Hours']);
            exit();
        }
        mysqli_query($conn, "
            INSERT INTO coach_availability (coach_id, date, status, start_time, end_time, reason, created_by, created_by_role)
            VALUES ($coach_id, '$date', '$status', $start_time, $end_time, $reason_sql, $admin_id, '$role')
        ");
        logChange($conn, $coach_id, $date, 'created', [], ['status' => $status, 'start_time' => $_POST['start_time'] ?? '', 'end_time' => $_POST['end_time'] ?? '', 'reason' => $reason], $admin_id, $role, $_SESSION['username']);
    } else {
        $existing_res = mysqli_query($conn, "
            SELECT id, status FROM coach_availability
            WHERE coach_id = $coach_id AND date = '$date' AND start_time IS NULL
            LIMIT 1
        ");
        $existing = mysqli_fetch_assoc($existing_res);

        if ($existing) {
            $old = ['status' => $existing['status']];
            mysqli_query($conn, "
                UPDATE coach_availability
                SET status = '$status', reason = $reason_sql, created_by = $admin_id, created_by_role = '$role'
                WHERE id = {$existing['id']}
            ");
            logChange($conn, $coach_id, $date, 'updated', $old, ['status' => $status, 'reason' => $reason], $admin_id, $role, $_SESSION['username']);
        } else {
            mysqli_query($conn, "
                INSERT INTO coach_availability (coach_id, date, status, reason, created_by, created_by_role)
                VALUES ($coach_id, '$date', '$status', $reason_sql, $admin_id, '$role')
            ");
            logChange($conn, $coach_id, $date, 'created', [], ['status' => $status, 'reason' => $reason], $admin_id, $role, $_SESSION['username']);
        }
    }

    if ($date === date('Y-m-d') && $status !== 'Custom Hours') {
        mysqli_query($conn, "UPDATE coaches SET availability_status = '$status' WHERE id = $coach_id");
    }

    $conflict_res = mysqli_query($conn, "
        SELECT COUNT(*) as cnt FROM bookings
        WHERE coach_id = $coach_id AND booking_date = '$date'
          AND status NOT IN ('Cancelled','Rejected')
    ");
    $conflict = mysqli_fetch_assoc($conflict_res);

    echo json_encode(['success' => true, 'conflict' => (int)$conflict['cnt'] > 0, 'conflict_count' => (int)$conflict['cnt']]);
    exit();
}

/* ── delete ─────────────────────────────────── */
if ($action === 'delete') {
    $id = (int)$_POST['id'];

    $rec_res = mysqli_query($conn, "SELECT * FROM coach_availability WHERE id = $id LIMIT 1");
    $rec     = mysqli_fetch_assoc($rec_res);

    if (!$rec) {
        echo json_encode(['success' => false, 'message' => 'Record not found']);
        exit();
    }

    mysqli_query($conn, "DELETE FROM coach_availability WHERE id = $id");

    logChange($conn, $rec['coach_id'], $rec['date'], 'deleted',
        ['status' => $rec['status'], 'start_time' => $rec['start_time'], 'end_time' => $rec['end_time']],
        [], $admin_id, $role, $_SESSION['username']);

    if ($rec['date'] === date('Y-m-d') && $rec['start_time'] === null) {
        mysqli_query($conn, "UPDATE coaches SET availability_status = 'Available' WHERE id = {$rec['coach_id']}");
    }

    echo json_encode(['success' => true]);
    exit();
}

/* ── history ────────────────────────────────── */
if ($action === 'history') {
    $coach_id = isset($_GET['coach_id']) && (int)$_GET['coach_id'] > 0 ? (int)$_GET['coach_id'] : null;
    $from     = mysqli_real_escape_string($conn, $_GET['from'] ?? date('Y-m-01'));
    $to       = mysqli_real_escape_string($conn, $_GET['to']   ?? date('Y-m-d'));

    $where = "WHERE l.date BETWEEN '$from' AND '$to'";
    if ($coach_id) $where .= " AND l.coach_id = $coach_id";

    $res = mysqli_query($conn, "
        SELECT l.*, c.name AS coach_name
        FROM coach_availability_log l
        JOIN coaches c ON l.coach_id = c.id
        $where
        ORDER BY l.created_at DESC
        LIMIT 200
    ");

    $logs = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $logs[] = $row;
    }

    echo json_encode(['success' => true, 'logs' => $logs]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
