<?php
// Standalone delete endpoint. ManageCourts.php has its own delete handler
// inside the edit modal — this file is only used when the trash icon is
// clicked from a direct link (?id=X).

session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['Superadmin', 'Admin'])) {
    header("Location: ../LoginPage.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "badminton_hub");
require_once __DIR__ . '/../log_activity.php';

$court_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($court_id > 0) {
    $court_row     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT court_name FROM courts WHERE id = $court_id"));
    $court_label   = $court_row ? $court_row['court_name'] : "ID $court_id";
    $booking_check = mysqli_query($conn, "SELECT id FROM bookings WHERE court_id = $court_id LIMIT 1");

    if ($booking_check && mysqli_num_rows($booking_check) > 0) {
        mysqli_query($conn, "UPDATE courts SET is_active = 0 WHERE id = $court_id");
        logActivity($conn, 'Delete', 'Court Management', "Deactivated court (has bookings): $court_label");
        header("Location: ManageCourts.php?deleted=1");
        exit();
    }

    mysqli_query($conn, "DELETE FROM courts WHERE id = $court_id");
    logActivity($conn, 'Delete', 'Court Management', "Deleted court: $court_label");
}

header("Location: ManageCourts.php?deleted=1");
exit();
?>
