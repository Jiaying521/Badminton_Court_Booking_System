<?php
require_once 'config.php';
if(!isLoggedIn()) redirect('homepage.php');
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $court_id = $_POST['court_id'];
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $end_time = date('H:i:s', strtotime($start_time) + 3600);
    $session_type = $_POST['session_type'];
    $notes = $_POST['notes'] ?? '';

    $court = $pdo->prepare("SELECT price_per_hour FROM courts WHERE id=?")->execute([$court_id])->fetch();
    $total_price = $court['price_per_hour'];

    // 检查该时段是否已被预订（防止并发）
    $check = $pdo->prepare("SELECT id FROM bookings WHERE court_id=? AND booking_date=? AND start_time=? AND status NOT IN ('Cancelled')");
    $check->execute([$court_id, $booking_date, $start_time]);
    if($check->fetch()) {
        die("This time slot is already booked. Please go back and select another.");
    }

    $stmt = $pdo->prepare("INSERT INTO bookings (user_id, court_id, booking_date, start_time, end_time, session_type, total_price, status, notes) VALUES (?,?,?,?,?,?,?,'Pending',?)");
    $stmt->execute([$user_id, $court_id, $booking_date, $start_time, $end_time, $session_type, $total_price, $notes]);
    $booking_id = $pdo->lastInsertId();

    header("Location: payment.php?booking_id=$booking_id");
    exit;
}
?>