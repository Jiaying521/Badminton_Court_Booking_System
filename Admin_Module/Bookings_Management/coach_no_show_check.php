<?php
/* coach_no_show_check.php — Handles coach no-shows (docx: "到时间了未通知顾客").
   A booking still 'Pending' with a coach assigned whose session start time has
   already passed means the coach never responded and never notified the
   customer. The booking is cancelled, the customer is fully refunded plus
   compensation, and the coach receives a strike with an escalating suspension
   (1st = 3 days, 2nd = 7 days, 3rd = permanent).
   Runs on page load — no cron needed. */

function handleCoachNoShows($conn) {

    require_once __DIR__ . '/../api/notification_helper.php';

    $now = date('Y-m-d H:i:s');

    /* Fixed RM10 compensation to the customer, per the cancellation policy */
    $compensation_fee = 10.00;

    /* Pending coach bookings whose session start time has already passed */
    $res = mysqli_query($conn, "
        SELECT b.id, b.user_id, b.coach_id, b.total_price, b.booking_date, b.start_time,
               u.name AS player_name, c.court_name,
               co.admin_id, co.late_cancel_strikes
        FROM bookings b
        JOIN users u    ON b.user_id  = u.id
        JOIN courts c   ON b.court_id = c.id
        JOIN coaches co ON b.coach_id = co.id
        WHERE b.status = 'Pending'
          AND b.coach_id IS NOT NULL
          AND TIMESTAMP(b.booking_date, b.start_time) < '$now'
    ");

    if (!$res) return;

    while ($b = mysqli_fetch_assoc($res)) {

        $bid    = (int)$b['id'];
        $uid    = (int)$b['user_id'];
        $cid    = (int)$b['coach_id'];
        $aid    = (int)$b['admin_id'];

        /* No-show = full refund plus compensation, same as a late cancellation */
        $refund = (float)$b['total_price'] + $compensation_fee;

        mysqli_query($conn, "UPDATE users SET wallet_balance = wallet_balance + $refund WHERE id = $uid");
        mysqli_query($conn, "UPDATE bookings SET status = 'Cancelled' WHERE id = $bid");

        /* One strike, with the escalating suspension ladder */
        $new_strikes = (int)$b['late_cancel_strikes'] + 1;

        /* 1st strike = 3 days, 2nd = 7 days, 3rd or more = permanent (NULL date = no auto-unban) */
        if ($new_strikes >= 3) {
            $until_sql = "NULL";
            $ban_text  = 'permanently suspended';
        } elseif ($new_strikes == 2) {
            $until_sql = "'" . date('Y-m-d', strtotime('+7 days')) . "'";
            $ban_text  = 'suspended for 7 days';
        } else {
            $until_sql = "'" . date('Y-m-d', strtotime('+3 days')) . "'";
            $ban_text  = 'suspended for 3 days';
        }

        mysqli_query($conn, "UPDATE coaches SET late_cancel_strikes = $new_strikes, is_active = 0 WHERE id = $cid");
        mysqli_query($conn, "UPDATE admins  SET status = 'Suspended', suspended_until = $until_sql WHERE id = $aid");

        $pname = $b['player_name'];
        $court = $b['court_name'];
        $pdate = date('d M Y', strtotime($b['booking_date']));
        $ptime = date('h:i A', strtotime($b['start_time']));
        $rstr  = number_format($refund, 2);

        foreach (['Admin', 'Superadmin'] as $role) {
            createNotification(
                $conn,
                $role,
                'Coach No-Show',
                "Booking #$bid for $pname at $court on $pdate $ptime was marked as a coach no-show (coach never responded). Customer refunded RM $rstr (incl. compensation). Coach has been $ban_text (strike #$new_strikes).",
                'cancelled',
                $bid,
                'booking'
            );
        }
    }
}
