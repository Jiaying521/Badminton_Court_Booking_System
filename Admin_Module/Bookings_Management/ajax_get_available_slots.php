<?php
session_start();
header('Content-Type: application/json');
error_reporting(0);
ini_set('display_errors', 0);

try {
    if (!isset($_SESSION['username'])) {
        throw new Exception('Not logged in');
    }

    $conn = mysqli_connect("localhost", "root", "", "badminton_hub");

    $court_id           = isset($_GET['court_id']) ? (int)$_GET['court_id'] : 0;
    $date               = isset($_GET['date']) ? $_GET['date'] : '';
    $exclude_booking_id = isset($_GET['exclude_booking_id']) ? (int)$_GET['exclude_booking_id'] : 0;

    if ($court_id <= 0 || empty($date)) {
        throw new Exception('Invalid parameters');
    }

    $date_esc = mysqli_real_escape_string($conn, $date);

    if ($exclude_booking_id > 0) {
        $result = mysqli_query($conn, "
            SELECT start_time, end_time
            FROM bookings
            WHERE court_id = $court_id
            AND booking_date = '$date_esc'
            AND status != 'Cancelled'
            AND id != $exclude_booking_id
        ");
    } else {
        $result = mysqli_query($conn, "
            SELECT start_time, end_time
            FROM bookings
            WHERE court_id = $court_id
            AND booking_date = '$date_esc'
            AND status != 'Cancelled'
        ");
    }

    $booked_hours = [];
    while ($slot = mysqli_fetch_assoc($result)) {
        $start = (int)date('H', strtotime($slot['start_time']));
        $end   = (int)date('H', strtotime($slot['end_time']));
        for ($h = $start; $h < $end; $h++) {
            $booked_hours[] = $h;
        }
    }

    // Same fixed business window as the customer side (8 AM - 10 PM)
    $available_slots = [];
    for ($hour = 8; $hour <= 22; $hour++) {
        if (in_array($hour, $booked_hours)) continue;

        $time    = sprintf("%02d:00:00", $hour);
        $display = ($hour < 12) ? $hour . ':00 AM' : ($hour == 12 ? '12:00 PM' : ($hour - 12) . ':00 PM');

        $available_slots[] = ['time' => $time, 'display' => $display];
    }

    echo json_encode($available_slots);

} catch (Exception $e) {
    echo json_encode([]);
}