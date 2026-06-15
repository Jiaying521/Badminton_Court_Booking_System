<?php
/**
 * Booking Reminder Script
 *
 * Run this daily via cron/task scheduler, e.g.:
 *   0 8 * * * php /path/to/Admin_Module/api/send_reminders.php
 *
 * Or trigger via browser with the secret token:
 *   http://yoursite/Admin_Module/api/send_reminders.php?token=YOUR_SECRET
 *
 * What it does:
 * - Finds all Confirmed bookings with a coach scheduled for TOMORROW
 * - Sends a reminder email to each coach (once per booking)
 * - Creates an in-app reminder notification for the coach
 */

define('REMINDER_SECRET', 'smash_arena_reminder_2024');

// Allow CLI or browser with token
if (php_sapi_name() !== 'cli') {
    $token = $_GET['token'] ?? '';
    if ($token !== REMINDER_SECRET) {
        http_response_code(403);
        exit('Unauthorized');
    }
}

$conn = mysqli_connect('localhost', 'root', '', 'badminton_hub');
if (!$conn) {
    error_log('send_reminders.php: DB connection failed');
    exit('DB error');
}

require_once __DIR__ . '/notification_helper.php';

// Find confirmed bookings with a coach happening tomorrow
$tomorrow = date('Y-m-d', strtotime('+1 day'));

$result = mysqli_query($conn, "
    SELECT
        b.id AS booking_id,
        b.booking_date, b.start_time, b.end_time,
        co.admin_id AS coach_admin_id,
        c.court_name,
        a.username AS coach_name
    FROM bookings b
    JOIN courts c  ON b.court_id  = c.id
    JOIN coaches co ON b.coach_id = co.id
    JOIN admins a  ON co.admin_id  = a.id
    WHERE b.status   = 'Confirmed'
      AND b.coach_id IS NOT NULL
      AND b.coach_id != 0
      AND b.booking_date = '$tomorrow'
");

if (!$result) {
    error_log('send_reminders.php: query failed – ' . mysqli_error($conn));
    exit('Query error');
}

$sent = 0;
$skipped = 0;

while ($row = mysqli_fetch_assoc($result)) {

    $booking_id     = (int)$row['booking_id'];
    $coach_admin_id = (int)$row['coach_admin_id'];
    $court_name     = $row['court_name'];
    $coach_name     = $row['coach_name'];
    $date_fmt       = date('d M Y', strtotime($row['booking_date']));
    $time_fmt       = date('h:i A', strtotime($row['start_time']));

    // Skip if reminder notification already exists for this booking
    $check = mysqli_query($conn, "
        SELECT id FROM notifications
        WHERE type = 'reminder'
          AND reference_id  = $booking_id
          AND reference_type = 'booking'
        LIMIT 1
    ");
    if (mysqli_num_rows($check) > 0) {
        $skipped++;
        continue;
    }

    // 获取正确的 coaches.id（因为通知表存的是 coaches.id，不是 admins.id）
    $coach_id_query = mysqli_query($conn, "SELECT id FROM coaches WHERE admin_id = $coach_admin_id");
    $coach_id_data = mysqli_fetch_assoc($coach_id_query);
    $actual_coach_id = $coach_id_data ? (int)$coach_id_data['id'] : 0;

    // Create in-app reminder notification for coach
    if ($actual_coach_id > 0) {
        createNotification(
            $conn,
            'Coach',
            'Upcoming Session Tomorrow',
            "Reminder: Your coaching session at $court_name is scheduled for tomorrow, $date_fmt at $time_fmt.",
            'reminder',
            $booking_id,
            'booking',
            $actual_coach_id
        );
    }

    // Send reminder email
    sendCoachReminderEmail($conn, $booking_id);

    $sent++;
    echo "Reminder sent for Booking #$booking_id ($coach_name – $date_fmt)\n";
}

echo "\nDone. Sent: $sent | Already sent (skipped): $skipped\n";
mysqli_close($conn);