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

    // Notification helper
    require_once 'api/notification_helper.php';

    // Handle status change from dropdown
    if(isset($_GET['id']) && isset($_GET['status'])){

        $booking_id = (int)$_GET['id'];
        $new_status = mysqli_real_escape_string($conn, $_GET['status']);

        $allowed = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];

        if(!in_array($new_status, $allowed) || $booking_id <= 0){
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

        header("Location: ManageBookings.php?updated=1");
        exit();
    }
    
    // Handle booking edit form
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'])){

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
    }
?>