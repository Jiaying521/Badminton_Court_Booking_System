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
    
    $coach_id = $_POST['coach_id'] ?? 0;
    $coach_hours = $_POST['coach_hours'] ?? 0;
    $coach_price_total = $_POST['coach_price_total'] ?? 0;
    
    // 计算结束时间
    $end_time = date('H:i:s', strtotime($start_time) + ($total_hours * 3600));
    
    // 检查时段是否已被预订
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
            status, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)
    ");
    $stmt->execute([
        $user_id, $court_id, $booking_date, $start_time, $end_time,
        $total_hours, $total_price, $coach_id, $coach_hours, $coach_price_total,
        $notes
    ]);
    
    $booking_id = $pdo->lastInsertId();
    
    // ========== 跳转到队友的支付页面 ==========
    // 传递 booking_id 和金额到 checkout.php
    header("Location: checkout.php?booking_id=$booking_id&amount=$total_price");
    exit;
}
?>