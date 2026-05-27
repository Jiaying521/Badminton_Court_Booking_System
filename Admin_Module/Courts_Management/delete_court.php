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

// intval() makes sure the ID is a clean integer, no SQL tricks possible.
$court_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($court_id > 0) {
    // If this court already has bookings, don't actually delete it —
    // just mark it inactive so historical bookings keep their reference.
    $booking_check = mysqli_query($conn, "SELECT id FROM bookings WHERE court_id = $court_id LIMIT 1");

    if ($booking_check && mysqli_num_rows($booking_check) > 0) {
        mysqli_query($conn, "UPDATE courts SET is_active = 0 WHERE id = $court_id");
        header("Location: ManageCourts.php?deleted=1");
        exit();
    }

    // No bookings — safe to remove the row entirely.
    mysqli_query($conn, "DELETE FROM courts WHERE id = $court_id");
}

header("Location: ManageCourts.php?deleted=1");
exit();
?>
