<?php
session_start();

if(!isset($_SESSION['username'])){
    header("Location: LoginPage.php");
    exit();
}

if(!in_array($_SESSION['role'], ['Superadmin', 'Admin'])){
    header("Location: LoginPage.php");
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "badminton_hub");

// Validate both params exist
if(isset($_GET['id']) && isset($_GET['status'])){

    $id     = intval($_GET['id']);
    $status = mysqli_real_escape_string($conn, $_GET['status']);

    // Only allow these 4 statuses (security check)
    $allowed = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];

    if(in_array($status, $allowed)){
        mysqli_query($conn, "UPDATE bookings SET status = '$status' WHERE id = $id");
    }
}

// Redirect back to ManageBookings with success message
header("Location: ManageBookings.php?updated=1");
exit();
?>