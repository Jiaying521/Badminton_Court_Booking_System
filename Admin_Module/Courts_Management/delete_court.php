<?php
session_start();
if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['Superadmin', 'Admin'])) {
    header("Location: LoginPage.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "badminton_hub");
$court_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($court_id > 0) {
    $booking_check = mysqli_query($conn, "SELECT id FROM bookings WHERE court_id = $court_id LIMIT 1");

    if ($booking_check && mysqli_num_rows($booking_check) > 0) {
        mysqli_query($conn, "UPDATE courts SET is_active = 0 WHERE id = $court_id");
        header("Location: ManageCourts.php?deleted=1");
        exit();
    }

    mysqli_query($conn, "DELETE FROM courts WHERE id = $court_id");
}

header("Location: ManageCourts.php?deleted=1");
exit();
?>
