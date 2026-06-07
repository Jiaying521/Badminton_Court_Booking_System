<?php
/* coach_decline.php — A coach declines one of their own sessions.
   On-time decline (>= 24h before): customer gets a full refund, no penalty.
   Late decline (< 24h before, or session already started): customer gets a
   full refund plus compensation, and the coach is suspended on an escalating
   ladder (1st strike = 3 days, 2nd = 7 days, 3rd = permanent).
   Only accessible to the Coach role. */

session_start();

if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Coach') {
    header("Location: ../LoginPage.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "badminton_hub");

$admin_id   = (int)$_SESSION['id'];
$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($booking_id <= 0) {
    header("Location: ManageBookings.php");
    exit();
}

/* Fetch this coach's record */
$coach_row = mysqli_fetch_assoc(
    mysqli_query($conn, "SELECT id, late_cancel_strikes FROM coaches WHERE admin_id = $admin_id")
);
$my_coach_id = $coach_row ? (int)$coach_row['id'] : 0;

if ($my_coach_id === 0) {
    header("Location: ManageBookings.php");
    exit();
}

/* Fetch the booking — must belong to this coach and still be active */
$booking = mysqli_fetch_assoc(mysqli_query($conn, "
    SELECT b.*, u.name AS player_name, c.court_name
    FROM bookings b
    JOIN users u  ON b.user_id  = u.id
    JOIN courts c ON b.court_id = c.id
    WHERE b.id = $booking_id
      AND b.coach_id = $my_coach_id
    LIMIT 1
"));

if (!$booking || !in_array($booking['status'], ['Pending', 'Confirmed'])) {
    header("Location: ManageBookings.php");
    exit();
}

/* Hours left until the session starts (negative means it already started) */
$session_ts  = strtotime($booking['booking_date'] . ' ' . $booking['start_time']);
$hours_until = ($session_ts - time()) / 3600;
$is_late     = $hours_until < 24;

$user_id       = (int)$booking['user_id'];
$refund_amount = (float)$booking['total_price'];

/* Fixed RM10 compensation to the customer, per the cancellation policy */
$compensation_fee = 10.00;

/* Late decline adds the compensation on top of the full refund */
if ($is_late) {
    $refund_amount += $compensation_fee;
}

/* Refund the customer wallet and cancel the booking */
mysqli_query($conn, "UPDATE users SET wallet_balance = wallet_balance + $refund_amount WHERE id = $user_id");
mysqli_query($conn, "UPDATE bookings SET status = 'Cancelled' WHERE id = $booking_id");

require_once '../api/notification_helper.php';

$player_name  = $booking['player_name'];
$court_name   = $booking['court_name'];
$booking_date = date("d M Y", strtotime($booking['booking_date']));
$start_time   = date("h:i A", strtotime($booking['start_time']));
$refund_str   = number_format($refund_amount, 2);

/* Late decline = one strike, with an escalating suspension */
$ban_days = 0;
if ($is_late) {

    $new_strikes = (int)$coach_row['late_cancel_strikes'] + 1;
    mysqli_query($conn, "UPDATE coaches SET late_cancel_strikes = $new_strikes WHERE id = $my_coach_id");

    /* 1st strike = 3 days, 2nd = 7 days, 3rd or more = permanent (NULL date = no auto-unban) */
    if ($new_strikes >= 3) {
        $ban_days  = -1;
        $until_sql = "NULL";
    } elseif ($new_strikes == 2) {
        $ban_days  = 7;
        $until_sql = "'" . date('Y-m-d', strtotime('+7 days')) . "'";
    } else {
        $ban_days  = 3;
        $until_sql = "'" . date('Y-m-d', strtotime('+3 days')) . "'";
    }

    /* Suspend the account — login is blocked until the unban date (or admin reactivates it) */
    mysqli_query($conn, "UPDATE coaches SET is_active = 0 WHERE id = $my_coach_id");
    mysqli_query($conn, "UPDATE admins  SET status = 'Suspended', suspended_until = $until_sql WHERE id = $admin_id");

    $ban_text = ($ban_days === -1) ? 'permanently suspended' : "suspended for $ban_days days";

    createNotification(
        $conn,
        'Admin',
        'Coach Late Cancellation',
        "Coach declined booking #$booking_id for $player_name at $court_name on $booking_date $start_time less than 24 hours before the session. Customer refunded RM $refund_str (incl. compensation). Coach has been $ban_text (strike #$new_strikes).",
        'cancelled',
        $booking_id,
        'booking'
    );

    createNotification(
        $conn,
        'Superadmin',
        'Coach Late Cancellation',
        "Coach declined booking #$booking_id for $player_name at $court_name on $booking_date $start_time less than 24 hours before the session. Customer refunded RM $refund_str (incl. compensation). Coach has been $ban_text (strike #$new_strikes).",
        'cancelled',
        $booking_id,
        'booking'
    );

} else {

    createNotification(
        $conn,
        'Admin',
        'Session Declined by Coach',
        "Coach declined booking #$booking_id for $player_name at $court_name on $booking_date $start_time. Customer refunded RM $refund_str. No penalty (declined with enough notice).",
        'cancelled',
        $booking_id,
        'booking'
    );

    createNotification(
        $conn,
        'Superadmin',
        'Session Declined by Coach',
        "Coach declined booking #$booking_id for $player_name at $court_name on $booking_date $start_time. Customer refunded RM $refund_str. No penalty (declined with enough notice).",
        'cancelled',
        $booking_id,
        'booking'
    );
}

mysqli_close($conn);

/* Redirect with the right outcome so ManageBookings can show the matching toast */
if ($is_late) {
    $ban_param = ($ban_days === -1) ? 'perm' : $ban_days;
    header("Location: ManageBookings.php?declined=late&ban=$ban_param");
} else {
    header("Location: ManageBookings.php?declined=ontime");
}
exit();
