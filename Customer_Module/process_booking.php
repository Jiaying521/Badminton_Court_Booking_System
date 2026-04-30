<?php
require_once __DIR__ . '/../config.php';
if(!isLoggedIn()) redirect('homepage.php');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $court_id = $_POST['court_id'];
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $total_price = $_POST['price'];
    $total_hours = $_POST['total_hours'];
    $notes = $_POST['notes'] ?? '';
    
    // 获取教练信息
    $coach_id = $_POST['coach_id'] ?? 0;
    $coach_hours = $_POST['coach_hours'] ?? 0;
    $coach_price_total = $_POST['coach_price_total'] ?? 0;
    
    // 正确计算结束时间（开始时间 + 预订小时数）
    $end_time = date('H:i:s', strtotime($start_time) + ($total_hours * 3600));
    
    // 检查该时段是否已被预订（检查整个时间段内是否有重叠）
    $check = $pdo->prepare("
        SELECT id FROM bookings 
        WHERE court_id = ? AND booking_date = ? AND status NOT IN ('Cancelled')
        AND (
            (start_time < ? AND end_time > ?) OR
            (start_time < ? AND end_time > ?) OR
            (start_time >= ? AND start_time < ?)
        )
    ");
    $check->execute([$court_id, $booking_date, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time]);
    
    if($check->fetch()) {
        die("This time slot is already booked. Please go back and select another.");
    }
    
    // 插入预订
    $stmt = $pdo->prepare("
        INSERT INTO bookings (
            user_id, court_id, booking_date, start_time, end_time, 
            total_hours, total_price, coach_id, coach_hours, coach_price_total,
            session_type, status, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Casual Play', 'Pending', ?)
    ");
    $stmt->execute([
        $user_id, $court_id, $booking_date, $start_time, $end_time,
        $total_hours, $total_price, $coach_id, $coach_hours, $coach_price_total,
        $notes
    ]);
    
    $booking_id = $pdo->lastInsertId();
    
    header("Location: payment.php?booking_id=$booking_id");
    exit;
}
?>