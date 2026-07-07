<?php
    session_start();
    if(!isset($_SESSION['username'])){
        header("Location: ../LoginPage.php");
        exit();
    }

    /* Coach can only accept (Confirmed) their own bookings; decline goes via coach_decline.php */
    if(!in_array($_SESSION['role'],['Superadmin','Admin','Coach'])){
        header("Location: ../LoginPage.php");
        exit();
    }

    //Database Connection
    $conn = mysqli_connect("localhost", "root", "", "badminton_hub");

    require_once '../api/notification_helper.php';
    require_once __DIR__ . '/../log_activity.php';

    // Handle status change from dropdown
    if(isset($_GET['id']) && isset($_GET['status'])){

        $booking_id = (int)$_GET['id'];
        $new_status = mysqli_real_escape_string($conn, $_GET['status']);

        $allowed = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];

        if(!in_array($new_status, $allowed) || $booking_id <= 0){
            header("Location: ManageBookings.php");
            exit();
        }

        /* Coach can only set Confirmed on their own bookings via this file */
        if($_SESSION['role'] === 'Coach' && $new_status !== 'Confirmed'){
            header("Location: ManageBookings.php");
            exit();
        }

        // Get booking information
        $booking_query = mysqli_query($conn, "
            SELECT 
                b.*,
                u.name AS player_name,
                c.court_name,
                co.admin_id AS coach_admin_id
            FROM bookings b
            JOIN users u 
                ON b.user_id = u.id
            JOIN courts c 
                ON b.court_id = c.id
            LEFT JOIN coaches co 
                ON b.coach_id = co.id
            WHERE b.id = $booking_id
        ");

        $booking = mysqli_fetch_assoc($booking_query);

        if(!$booking){
            header("Location: ManageBookings.php");
            exit();
        }

        // Store old status
        $old_status = $booking['status'];

        // Booking details
        $player_name = $booking['player_name'];
        $court_name  = $booking['court_name'];

        $booking_date = date("d M Y", strtotime($booking['booking_date']));
        $start_time   = date("h:i A", strtotime($booking['start_time']));

        $has_coach = !empty($booking['coach_id']) && $booking['coach_id'] != 0;

        $coach_admin_id = (int)($booking['coach_admin_id'] ?? 0);

        // Update booking status
        mysqli_query($conn, "
            UPDATE bookings 
            SET status = '$new_status'
            WHERE id = $booking_id
        ");

        // Trigger notification only if status changed
        if($old_status !== $new_status){

            // =========================================
            // CONFIRMED
            // =========================================
            if($new_status === 'Confirmed'){

                createNotification(
                    $conn,
                    'Admin',
                    'Booking Confirmed',
                    "Booking #$booking_id for $player_name at $court_name on $booking_date $start_time has been confirmed.",
                    'confirmed',
                    $booking_id,
                    'booking'
                );

                createNotification(
                    $conn,
                    'Superadmin',
                    'Booking Confirmed',
                    "Booking #$booking_id for $player_name at $court_name on $booking_date $start_time has been confirmed.",
                    'confirmed',
                    $booking_id,
                    'booking'
                );

                // Notify coach
                if($has_coach && $coach_admin_id > 0){

                    createNotification(
                        $conn,
                        'Coach',
                        'Session Confirmed',
                        "Your coaching session at $court_name on $booking_date $start_time has been confirmed.",
                        'confirmed',
                        $booking_id,
                        'booking',
                        $coach_admin_id
                    );

                    // Send email to coach
                    sendCoachBookingEmail($conn, $booking_id);
                }
            }

            // =========================================
            // CANCELLED
            // =========================================
            elseif($new_status === 'Cancelled'){

                createNotification(
                    $conn,
                    'Admin',
                    'Booking Cancelled',
                    "Booking #$booking_id for $player_name at $court_name on $booking_date has been cancelled.",
                    'cancelled',
                    $booking_id,
                    'booking'
                );

                createNotification(
                    $conn,
                    'Superadmin',
                    'Booking Cancelled',
                    "Booking #$booking_id for $player_name at $court_name on $booking_date has been cancelled.",
                    'cancelled',
                    $booking_id,
                    'booking'
                );

                // Notify coach
                if($has_coach && $coach_admin_id > 0){

                    createNotification(
                        $conn,
                        'Coach',
                        'Session Cancelled',
                        "Your coaching session at $court_name on $booking_date $start_time has been cancelled.",
                        'cancelled',
                        $booking_id,
                        'booking',
                        $coach_admin_id
                    );
                }
            }

            // =========================================
            // PENDING
            // =========================================
            elseif($new_status === 'Pending'){

                createNotification(
                    $conn,
                    'Admin',
                    'New Booking Pending',
                    "Booking #$booking_id from $player_name for $court_name on $booking_date is waiting for confirmation.",
                    'new_booking',
                    $booking_id,
                    'booking'
                );

                createNotification(
                    $conn,
                    'Superadmin',
                    'New Booking Pending',
                    "Booking #$booking_id from $player_name for $court_name on $booking_date is waiting for confirmation.",
                    'new_booking',
                    $booking_id,
                    'booking'
                );
            }
        }

        logActivity($conn, 'Status Change', 'Booking Management',
                    "Booking #$booking_id ($player_name at $court_name): $old_status → $new_status");
        header("Location: ManageBookings.php?updated=1");
        exit();
    }

    // Handle booking edit form
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])){

        // Coach must never reach this branch (price edit is Admin/Superadmin only)
        if ($_SESSION['role'] === 'Coach') {
            header("Location: ManageBookings.php");
            exit();
        }

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

        // Block editing Cancelled or Completed bookings entirely
        $orig = mysqli_fetch_assoc(mysqli_query($conn, "SELECT status FROM bookings WHERE id = $booking_id"));
        if (!$orig || in_array($orig['status'], ['Cancelled', 'Completed'])) {
            header("Location: ManageBookings.php?locked=1");
            exit();
        }

        // Prevent editing a booking to a date/time that has already passed
        $booking_datetime = strtotime($booking_date . ' ' . $start_time);
        if ($booking_datetime < time()) {
            header("Location: ManageBookings.php?invalid_date=past");
            exit();
        }

        // End time must be after start time
        if ($end_time <= $start_time) {
            header("Location: ManageBookings.php?invalid_date=range");
            exit();
        }

        // Conflict detection — check if court is already booked at that time by ANOTHER booking
        $conflict = mysqli_query($conn, "
            SELECT id FROM bookings
            WHERE court_id = $court_id
            AND booking_date = '$booking_date'
            AND status NOT IN ('Cancelled')
            AND id != $booking_id
            AND (
                (start_time < '$end_time' AND end_time > '$start_time')
            )
        ");

        if (mysqli_num_rows($conflict) > 0) {
            header("Location: ManageBookings.php?conflict=1");
            exit();
        }

        // --- Recalculate AUTO price FIRST, before validating the manual price against it ---
        $start_ts    = strtotime($start_time);
        $end_ts      = strtotime($end_time);
        $total_hours = max(1, round(($end_ts - $start_ts) / 3600));

        $court_row    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price_off_peak, price_peak FROM courts WHERE id = $court_id"));
        $hour         = (int)date('G', $start_ts);
        $price_per_hr = ($hour >= 15) ? $court_row['price_peak'] : $court_row['price_off_peak'];
        $court_price  = $price_per_hr * $total_hours;

        $coach_price_total = 0;
        if ($coach_id > 0) {
            $coach_row         = mysqli_fetch_assoc(mysqli_query($conn, "SELECT price_per_hour FROM coaches WHERE id = $coach_id"));
            $coach_price_total = $coach_row['price_per_hour'] * $total_hours;
        }

        // Keep existing add-ons untouched, just fold their total back in
        $addon_sum_row = mysqli_fetch_assoc(mysqli_query($conn, "
            SELECT COALESCE(SUM(quantity * price), 0) AS addon_sum
            FROM booking_addons WHERE booking_id = $booking_id
        "));
        $addon_sum  = (float)$addon_sum_row['addon_sum'];
        $auto_price = $court_price + $coach_price_total + $addon_sum;

        // Manual override (Admin/Superadmin only, since Coach is already blocked above)
        $manual_price = (isset($_POST['total_price']) && $_POST['total_price'] !== '')
                        ? (float)$_POST['total_price'] : null;

        // Reject negative manual price
        if ($manual_price !== null && $manual_price < 0) {
            header("Location: ManageBookings.php?invalid_price=1");
            exit();
        }

        // Reject manual price lower than the auto-calculated price
        if ($manual_price !== null && $manual_price < $auto_price) {
            header("Location: ManageBookings.php?invalid_price=low&min=" . urlencode(number_format($auto_price, 2)));
            exit();
        }

        $final_price  = ($manual_price !== null) ? $manual_price : $auto_price;

        // Update booking in database — now includes recalculated price fields
        mysqli_query($conn, "
            UPDATE bookings SET
                booking_date      = '$booking_date',
                start_time        = '$start_time',
                end_time          = '$end_time',
                court_id          = $court_id,
                coach_id          = $coach_value,
                session_type      = '$session_type',
                notes             = '$notes',
                total_hours       = $total_hours,
                coach_hours       = $total_hours,
                coach_price_total = $coach_price_total,
                total_price       = $final_price
            WHERE id = $booking_id
        ");

        logActivity($conn, 'Update', 'Booking Management',
            "Booking #$booking_id price " . ($manual_price !== null ? "manually overridden" : "auto-recalculated") .
            " to RM " . number_format($final_price, 2) .
            " (auto price was RM " . number_format($auto_price, 2) . ") by {$_SESSION['username']}");

        header("Location: ManageBookings.php?edited=1");
        exit();
    }
?>