<?php
    session_start();
    if(!isset($_SESSION['username'])){
        header("Location: LoginPage.php");
        exit();
    }

    //Check role only Superadmin and Admin can access
    if(!in_array($_SESSION['role'],['Superadmin','Admin'])){
        header("Location: LoginPage.php");
        exit();
    }

    //Database Connection
    $conn = mysqli_connect("localhost", "root", "", "badminton_hub");

    // Get POST data
    $booking_id   = (int)$_POST['booking_id'];
    $booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $start_time   = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time     = mysqli_real_escape_string($conn, $_POST['end_time']);
    $court_id     = (int)$_POST['court_id'];
    $coach_id     = (int)$_POST['coach_id'];
    $session_type = mysqli_real_escape_string($conn, $_POST['session_type']);
    $notes        = mysqli_real_escape_string($conn, $_POST['notes']);

    // Set coach_id to NULL if no coach selected
    $coach_value = ($coach_id === 0) ? "NULL" : $coach_id;

    // Update booking in database
    mysqli_query($conn, "
        UPDATE bookings SET
            booking_date = '$booking_date',
            start_time   = '$start_time',
            end_time     = '$end_time',
            court_id     = $court_id,
            coach_id     = $coach_value,
            session_type = '$session_type',
            notes        = '$notes'
        WHERE id = $booking_id
    ");

    // Redirect back with success message
    header("Location: ManageBookings.php?edited=1");
    exit();
?>