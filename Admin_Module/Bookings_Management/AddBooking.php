<?php
    session_start();
    if(!isset($_SESSION['username'])){
        header("Location: LoginPage.php");
        exit();
    }

    // Only Superadmin and Admin can add bookings
    if(!in_array($_SESSION['role'], ['Superadmin', 'Admin'])){
        header("Location: LoginPage.php");
        exit();
    }

    // Database Connection
    $conn = mysqli_connect("localhost", "root", "", "badminton_hub");

    // Get POST data
    $user_id      = (int)$_POST['user_id'];
    $court_id     = (int)$_POST['court_id'];
    $booking_date = mysqli_real_escape_string($conn, $_POST['booking_date']);
    $start_time   = mysqli_real_escape_string($conn, $_POST['start_time']);
    $end_time     = mysqli_real_escape_string($conn, $_POST['end_time']);
    $coach_id     = (int)$_POST['coach_id'];
    $session_type = mysqli_real_escape_string($conn, $_POST['session_type']);
    $notes        = mysqli_real_escape_string($conn, $_POST['notes']);

    // Set coach_id to NULL if no coach selected
    $coach_value = ($coach_id === 0) ? "NULL" : $coach_id;

    // Conflict detection — check if court is already booked at that time
    $conflict = mysqli_query($conn, "
        SELECT id FROM bookings
        WHERE court_id = $court_id
        AND booking_date = '$booking_date'
        AND status NOT IN ('Cancelled')
        AND (
            (start_time < '$end_time' AND end_time > '$start_time')
        )
    ");

    if(mysqli_num_rows($conflict) > 0){
        header("Location: ManageBookings.php?conflict=1");
        exit();
    }

    // Calculate total hours
    $start_ts    = strtotime($start_time);
    $end_ts      = strtotime($end_time);
    $total_hours = max(1, round(($end_ts - $start_ts) / 3600));

    // Get court pricing
    $court_row    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price_off_peak, price_peak FROM courts WHERE id = $court_id"));
    $hour         = (int)date('G', $start_ts);
    $price_per_hr = ($hour >= 15) ? $court_row['price_peak'] : $court_row['price_off_peak'];
    $total_price  = $price_per_hr * $total_hours;

    // Get coach price if coach selected
    $coach_price_total = 0;
    if($coach_id > 0){
        $coach_row         = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price_per_hour FROM coaches WHERE id = $coach_id"));
        $coach_price_total = $coach_row['price_per_hour'] * $total_hours;
        $total_price      += $coach_price_total;
    }

    // Insert booking
    mysqli_query($conn, "
        INSERT INTO bookings (
            user_id, court_id, booking_date, start_time, end_time,
            total_hours, coach_id, coach_hours, coach_price_total,
            session_type, total_price, status, notes
        ) VALUES (
            $user_id, $court_id, '$booking_date', '$start_time', '$end_time',
            $total_hours, $coach_value, $total_hours, $coach_price_total,
            '$session_type', $total_price, 'Confirmed', '$notes'
        )
    ");

    header("Location: ManageBookings.php?added=1");
    exit();
?>
