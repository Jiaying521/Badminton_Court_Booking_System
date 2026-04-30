<?php
require_once __DIR__ . '/../config.php';
if(!isLoggedIn()) redirect('homepage.php');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $court_id = $_POST['court_id'];
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $price = $_POST['price']; // 从前端获取时段价格
    $end_time = date('H:i:s', strtotime($start_time) + 3600);
    $session_type = $_POST['session_type'];
    $notes = $_POST['notes'] ?? '';
    
    $total_price = $price; // 使用时段价格，不是固定价格
    
    // 检查该时段是否已被预订
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