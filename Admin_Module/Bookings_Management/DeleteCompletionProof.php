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

    /* Verify this booking belongs to the coach and is Completed */
    $coach_q   = mysqli_query($conn, "SELECT id FROM coaches WHERE admin_id = " . (int)$_SESSION['id']);
    $coach_row = mysqli_fetch_assoc($coach_q);
    $my_coach_id = $coach_row ? (int)$coach_row['id'] : 0;

    $booking_q = mysqli_query($conn, "
        SELECT id, completion_photo FROM bookings
        WHERE id = $booking_id AND coach_id = $my_coach_id AND status = 'Completed'
    ");
    $booking = mysqli_fetch_assoc($booking_q);

    if (!$booking) {
        header("Location: ManageBookings.php");
        exit();
    }

    /* Delete the physical file */
    if (!empty($booking['completion_photo'])) {
        $filepath = __DIR__ . '/../../Pictures/Admin_Module/booking_proofs/' . $booking['completion_photo'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    /* Revert status to Confirmed and clear photo */
    mysqli_query($conn, "
        UPDATE bookings
        SET status = 'Confirmed', completion_photo = NULL
        WHERE id = $booking_id AND coach_id = $my_coach_id
    ");

    header("Location: ManageBookings.php?updated=1");
    exit();
?>
