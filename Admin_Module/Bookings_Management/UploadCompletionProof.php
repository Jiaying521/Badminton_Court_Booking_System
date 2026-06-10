<?php
    session_start();
    if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Coach') {
        header("Location: ../LoginPage.php");
        exit();
    }

    $conn = mysqli_connect("localhost", "root", "", "badminton_hub");

    $booking_id = (int)($_POST['booking_id'] ?? 0);
    if ($booking_id <= 0) {
        header("Location: ManageBookings.php");
        exit();
    }

    $coach_q   = mysqli_query($conn, "SELECT id FROM coaches WHERE admin_id = " . (int)$_SESSION['id']);
    $coach_row = mysqli_fetch_assoc($coach_q);
    $my_coach_id = $coach_row ? (int)$coach_row['id'] : 0;

    $booking_q = mysqli_query($conn, "
        SELECT id FROM bookings
        WHERE id = $booking_id AND coach_id = $my_coach_id AND status = 'Confirmed'
    ");
    if (!mysqli_fetch_assoc($booking_q)) {
        header("Location: ManageBookings.php");
        exit();
    }

    if (!isset($_FILES['proof_photo']) || $_FILES['proof_photo']['error'] !== UPLOAD_ERR_OK) {
        header("Location: ManageBookings.php?proof_error=1");
        exit();
    }

    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $file_type = mime_content_type($_FILES['proof_photo']['tmp_name']);
    if (!in_array($file_type, $allowed_types)) {
        header("Location: ManageBookings.php?proof_error=1");
        exit();
    }

    if ($_FILES['proof_photo']['size'] > 5 * 1024 * 1024) {
        header("Location: ManageBookings.php?proof_error=1");
        exit();
    }

    $ext      = strtolower(pathinfo($_FILES['proof_photo']['name'], PATHINFO_EXTENSION));
    $filename = 'proof_' . $booking_id . '_' . time() . '.' . $ext;
    $dir      = __DIR__ . '/../../Pictures/Admin_Module/booking_proofs/';

    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    if (!move_uploaded_file($_FILES['proof_photo']['tmp_name'], $dir . $filename)) {
        header("Location: ManageBookings.php?proof_error=1");
        exit();
    }

    $filename_safe = mysqli_real_escape_string($conn, $filename);
    mysqli_query($conn, "
        UPDATE bookings
        SET status = 'Completed', completion_photo = '$filename_safe'
        WHERE id = $booking_id AND coach_id = $my_coach_id
    ");

    require_once '../api/notification_helper.php';
    require_once __DIR__ . '/../log_activity.php';
    $bq = mysqli_query($conn, "
        SELECT b.*, u.name AS player_name, c.court_name
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN courts c ON b.court_id = c.id
        WHERE b.id = $booking_id
    ");
    $bk = mysqli_fetch_assoc($bq);
    if ($bk) {
        $player_name  = $bk['player_name'];
        $court_name   = $bk['court_name'];
        $booking_date = date("d M Y", strtotime($bk['booking_date']));
        $start_time   = date("h:i A", strtotime($bk['start_time']));
        createNotification($conn, 'Admin', 'Session Completed',
            "Booking #$booking_id for $player_name at $court_name on $booking_date $start_time has been marked completed by coach.",
            'completed', $booking_id, 'booking');
        createNotification($conn, 'Superadmin', 'Session Completed',
            "Booking #$booking_id for $player_name at $court_name on $booking_date $start_time has been marked completed by coach.",
            'completed', $booking_id, 'booking');
    }

    logActivity($conn, 'Update', 'Booking Management', "Uploaded completion proof for booking #$booking_id, status set to Completed");
    header("Location: ManageBookings.php?updated=1");
    exit();
?>
