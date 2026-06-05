<?php
session_start();

if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

$role     = $_SESSION['role'];
$admin_id = (int)$_SESSION['id'];

if (!in_array($role, ['Coach', 'Admin', 'Superadmin'])) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit();
}

$conn   = mysqli_connect("localhost", "root", "", "badminton_hub");
$action = $_POST['action'] ?? $_GET['action'] ?? '';

/* Coach can only access their own records */
function getCoachIdFromSession($conn, $admin_id) {
    $res = mysqli_query($conn, "SELECT id FROM coaches WHERE admin_id = $admin_id LIMIT 1");
    if ($row = mysqli_fetch_assoc($res)) return (int)$row['id'];
    return null;
}

function logChange($conn, $coach_id, $date, $action, $old, $new, $changed_by, $role, $name) {
    $old_status     = isset($old['status'])     ? "'" . mysqli_real_escape_string($conn, $old['status'])     . "'" : 'NULL';
    $new_status     = isset($new['status'])     ? "'" . mysqli_real_escape_string($conn, $new['status'])     . "'" : 'NULL';
    $old_start      = isset($old['start_time']) ? "'" . mysqli_real_escape_string($conn, $old['start_time']) . "'" : 'NULL';
    $old_end        = isset($old['end_time'])   ? "'" . mysqli_real_escape_string($conn, $old['end_time'])   . "'" : 'NULL';
    $new_start      = isset($new['start_time']) ? "'" . mysqli_real_escape_string($conn, $new['start_time']) . "'" : 'NULL';
    $new_end        = isset($new['end_time'])   ? "'" . mysqli_real_escape_string($conn, $new['end_time'])   . "'" : 'NULL';
    $reason         = isset($new['reason'])     ? "'" . mysqli_real_escape_string($conn, $new['reason'])     . "'" : 'NULL';
    $esc_action     = mysqli_real_escape_string($conn, $action);
    $esc_date       = mysqli_real_escape_string($conn, $date);
    $esc_role       = mysqli_real_escape_string($conn, $role);
    $esc_name       = mysqli_real_escape_string($conn, $name);

    mysqli_query($conn, "
        INSERT INTO coach_availability_log
            (coach_id, date, action, old_status, new_status, old_start_time, old_end_time,
             new_start_time, new_end_time, reason, changed_by, changed_by_role, changed_by_name)
        VALUES
            ($coach_id, '$esc_date', '$esc_action', $old_status, $new_status, $old_start,
             $old_end, $new_start, $new_end, $reason, $changed_by, '$esc_role', '$esc_name')
    ");
}

/* ── get_month ─────────────────────────────── */
if ($action === 'get_month') {
    $coach_id = (int)($_GET['coach_id'] ?? 0);
    $year     = (int)($_GET['year']  ?? date('Y'));
    $month    = (int)($_GET['month'] ?? date('n'));

    if ($role === 'Coach') {
        $my_id = getCoachIdFromSession($conn, $admin_id);
        if ($my_id !== $coach_id) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit();
        }
    }

    $month_str = sprintf('%04d-%02d', $year, $month);

    $res = mysqli_query($conn, "
        SELECT date, status, start_time, end_time, reason
        FROM coach_availability
        WHERE coach_id = $coach_id
          AND DATE_FORMAT(date, '%Y-%m') = '$month_str'
        ORDER BY date, start_time
    ");

    $data = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $d = $row['date'];
        if (!isset($data[$d])) $data[$d] = [];
        $data[$d][] = [
            'status'     => $row['status'],
            'start_time' => $row['start_time'],
            'end_time'   => $row['end_time'],
            'reason'     => $row['reason'],
        ];
    }

    /* bookings count per day */
    $book_res = mysqli_query($conn, "
        SELECT DATE(booking_date) as bdate, COUNT(*) as cnt
        FROM bookings
        WHERE coach_id = $coach_id
          AND DATE_FORMAT(booking_date, '%Y-%m') = '$month_str'
          AND status NOT IN ('Cancelled','Rejected')
        GROUP BY DATE(booking_date)
    ");

    $bookings = [];
    while ($row = mysqli_fetch_assoc($book_res)) {
        $bookings[$row['bdate']] = (int)$row['cnt'];
    }

    echo json_encode(['success' => true, 'availability' => $data, 'bookings' => $bookings]);
    exit();
}

/* ── get_day ────────────────────────────────── */
if ($action === 'get_day') {
    $coach_id = (int)($_GET['coach_id'] ?? 0);
    $date     = mysqli_real_escape_string($conn, $_GET['date'] ?? '');

    if ($role === 'Coach') {
        $my_id = getCoachIdFromSession($conn, $admin_id);
        if ($my_id !== $coach_id) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit();
        }
    }

    $avail_res = mysqli_query($conn, "
        SELECT id, status, start_time, end_time, reason
        FROM coach_availability
        WHERE coach_id = $coach_id AND date = '$date'
        ORDER BY start_time
    ");

    $avail = [];
    while ($row = mysqli_fetch_assoc($avail_res)) {
        $avail[] = $row;
    }

    $book_res = mysqli_query($conn, "
        SELECT b.id, b.booking_date, b.start_time, b.end_time, b.status,
               c.court_name, u.name AS customer_name
        FROM bookings b
        JOIN courts c ON b.court_id = c.id
        JOIN users u  ON b.user_id  = u.id
        WHERE b.coach_id = $coach_id
          AND b.booking_date = '$date'
          AND b.status NOT IN ('Cancelled','Rejected')
        ORDER BY b.start_time
    ");

    $bookings = [];
    while ($row = mysqli_fetch_assoc($book_res)) {
        $bookings[] = $row;
    }

    echo json_encode(['success' => true, 'availability' => $avail, 'bookings' => $bookings]);
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

    if ($role === 'Coach') {
        $my_id = getCoachIdFromSession($conn, $admin_id);
        if ($my_id !== $coach_id) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit();
        }
    }

    if (!$date || !$status) {
        echo json_encode(['success' => false, 'message' => 'Date and status are required']);
        exit();
    }

    /* check for existing record on that date (full day only for non-Custom Hours) */
    $existing_res = mysqli_query($conn, "
        SELECT id, status, start_time, end_time FROM coach_availability
        WHERE coach_id = $coach_id AND date = '$date'
          AND start_time IS NULL
        LIMIT 1
    ");
    $existing = mysqli_fetch_assoc($existing_res);

    if ($status === 'Custom Hours') {
        if ($start_time === 'NULL' || $end_time === 'NULL') {
            echo json_encode(['success' => false, 'message' => 'Start and end time required for Custom Hours']);
            exit();
        }
        mysqli_query($conn, "
            INSERT INTO coach_availability (coach_id, date, status, start_time, end_time, reason, created_by, created_by_role)
            VALUES ($coach_id, '$date', '$status', $start_time, $end_time, $reason_sql, $admin_id, '$role')
        ");
    } else {
        /* full day status — upsert */
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

    /* sync coaches.availability_status if the date is today */
    if ($date === date('Y-m-d') && $status !== 'Custom Hours') {
        mysqli_query($conn, "UPDATE coaches SET availability_status = '$status' WHERE id = $coach_id");
    }

    /* check for booking conflicts */
    $conflict_res = mysqli_query($conn, "
        SELECT COUNT(*) as cnt FROM bookings
        WHERE coach_id = $coach_id AND booking_date = '$date'
          AND status NOT IN ('Cancelled','Rejected')
    ");
    $conflict = mysqli_fetch_assoc($conflict_res);
    $has_conflict = (int)$conflict['cnt'] > 0;

    echo json_encode(['success' => true, 'conflict' => $has_conflict, 'conflict_count' => (int)$conflict['cnt']]);
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

    if ($role === 'Coach') {
        $my_id = getCoachIdFromSession($conn, $admin_id);
        if ($my_id !== (int)$rec['coach_id']) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit();
        }
    }

    mysqli_query($conn, "DELETE FROM coach_availability WHERE id = $id");

    logChange($conn, $rec['coach_id'], $rec['date'], 'deleted',
        ['status' => $rec['status'], 'start_time' => $rec['start_time'], 'end_time' => $rec['end_time']],
        [], $admin_id, $role, $_SESSION['username']);

    /* if deleted a full-day record for today, reset availability_status */
    if ($rec['date'] === date('Y-m-d') && $rec['start_time'] === null) {
        mysqli_query($conn, "UPDATE coaches SET availability_status = 'Available' WHERE id = {$rec['coach_id']}");
    }

    echo json_encode(['success' => true]);
    exit();
}

/* ── save_working_hours ──────────────────────── */
if ($action === 'save_working_hours') {
    if ($role !== 'Coach') {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }

    $from = !empty($_POST['available_from']) ? "'" . mysqli_real_escape_string($conn, $_POST['available_from']) . "'" : 'NULL';
    $to   = !empty($_POST['available_to'])   ? "'" . mysqli_real_escape_string($conn, $_POST['available_to'])   . "'" : 'NULL';

    $coach_id = getCoachIdFromSession($conn, $admin_id);
    if (!$coach_id) {
        echo json_encode(['success' => false, 'message' => 'Coach not found']);
        exit();
    }

    mysqli_query($conn, "UPDATE coaches SET available_from = $from, available_to = $to WHERE id = $coach_id");

    echo json_encode(['success' => true]);
    exit();
}

/* ── save_range (leave date range) ──────────── */
if ($action === 'save_range') {
    $coach_id  = (int)$_POST['coach_id'];
    $from      = mysqli_real_escape_string($conn, $_POST['from'] ?? '');
    $to        = mysqli_real_escape_string($conn, $_POST['to']   ?? '');
    $status    = mysqli_real_escape_string($conn, $_POST['status'] ?? '');
    $reason    = mysqli_real_escape_string($conn, $_POST['reason'] ?? '');
    $reason_sql = $reason !== '' ? "'$reason'" : 'NULL';

    if ($role === 'Coach') {
        $my_id = getCoachIdFromSession($conn, $admin_id);
        if ($my_id !== $coach_id) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit();
        }
    }

    if (!$from || !$to || !$status || !$coach_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit();
    }

    $start = new DateTime($from);
    $end   = new DateTime($to);
    if ($end < $start) {
        echo json_encode(['success' => false, 'message' => '"To" date must be after "From" date']);
        exit();
    }

    $total_conflict = 0;
    $current = clone $start;

    while ($current <= $end) {
        $date = $current->format('Y-m-d');

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

        $conflict_res = mysqli_query($conn, "
            SELECT COUNT(*) as cnt FROM bookings
            WHERE coach_id = $coach_id AND booking_date = '$date'
              AND status NOT IN ('Cancelled','Rejected')
        ");
        $cf = mysqli_fetch_assoc($conflict_res);
        $total_conflict += (int)$cf['cnt'];

        if ($date === date('Y-m-d')) {
            mysqli_query($conn, "UPDATE coaches SET availability_status = '$status' WHERE id = $coach_id");
        }

        $current->modify('+1 day');
    }

    echo json_encode(['success' => true, 'conflict' => $total_conflict > 0, 'conflict_count' => $total_conflict]);
    exit();
}

/* ── get_upcoming_bookings ───────────────────── */
if ($action === 'get_upcoming_bookings') {
    $coach_id = (int)($_GET['coach_id'] ?? 0);

    if ($role === 'Coach') {
        $my_id = getCoachIdFromSession($conn, $admin_id);
        if ($my_id !== $coach_id) {
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            exit();
        }
    }

    $today = date('Y-m-d');
    $res   = mysqli_query($conn, "
        SELECT b.id, b.booking_date, b.start_time, b.end_time, b.status,
               c.court_name, u.name AS customer_name
        FROM bookings b
        JOIN courts c ON b.court_id = c.id
        JOIN users  u ON b.user_id  = u.id
        WHERE b.coach_id = $coach_id
          AND b.booking_date >= '$today'
          AND b.status NOT IN ('Cancelled','Rejected')
        ORDER BY b.booking_date, b.start_time
        LIMIT 20
    ");

    $bookings = [];
    while ($row = mysqli_fetch_assoc($res)) {
        $bookings[] = $row;
    }

    echo json_encode(['success' => true, 'bookings' => $bookings]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
