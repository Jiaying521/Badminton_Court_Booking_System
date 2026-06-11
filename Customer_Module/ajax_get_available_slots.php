<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

// 确保只返回 JSON，不输出任何其他内容
error_reporting(0);
ini_set('display_errors', 0);

try {
    if(!isLoggedIn()) {
        throw new Exception('Not logged in');
    }

    $court_id = isset($_GET['court_id']) ? (int)$_GET['court_id'] : 0;
    $date = isset($_GET['date']) ? $_GET['date'] : '';
    $exclude_booking_id = isset($_GET['exclude_booking_id']) ? (int)$_GET['exclude_booking_id'] : 0;

    if($court_id <= 0 || empty($date)) {
        throw new Exception('Invalid parameters');
    }

    // 获取该球场当天已有的预订
    if($exclude_booking_id > 0) {
        $stmt = $pdo->prepare("
            SELECT start_time, end_time 
            FROM bookings 
            WHERE court_id = ? AND booking_date = ? 
            AND status != 'Cancelled'
            AND id != ?
        ");
        $stmt->execute([$court_id, $date, $exclude_booking_id]);
    } else {
        $stmt = $pdo->prepare("
            SELECT start_time, end_time 
            FROM bookings 
            WHERE court_id = ? AND booking_date = ? 
            AND status != 'Cancelled'
        ");
        $stmt->execute([$court_id, $date]);
    }
    $booked_slots = $stmt->fetchAll();

    // 标记已预订的时间段
    $booked_hours = [];
    foreach($booked_slots as $slot) {
        $start = (int)date('H', strtotime($slot['start_time']));
        $end = (int)date('H', strtotime($slot['end_time']));
        for($h = $start; $h < $end; $h++) {
            $booked_hours[] = $h;
        }
    }

    // 生成可用时间段 (8 AM - 10 PM)
    $available_slots = [];
    for($hour = 8; $hour <= 22; $hour++) {
        if(in_array($hour, $booked_hours)) {
            continue;
        }
        
        $time = sprintf("%02d:00:00", $hour);
        $display = ($hour < 12) ? $hour . ':00 AM' : ($hour == 12 ? '12:00 PM' : ($hour - 12) . ':00 PM');
        
        $available_slots[] = [
            'time' => $time,
            'display' => $display
        ];
    }

    echo json_encode($available_slots);
    
} catch(Exception $e) {
    echo json_encode([]);
}
?>