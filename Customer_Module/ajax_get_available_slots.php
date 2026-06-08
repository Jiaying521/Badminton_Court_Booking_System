<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode([]);
    exit;
}

$court_id = $_GET['court_id'] ?? 0;
$date = $_GET['date'] ?? '';
$exclude_booking_id = $_GET['exclude_booking_id'] ?? 0;

if(!$court_id || !$date) {
    echo json_encode([]);
    exit;
}

// 获取该球场当天已有的预订（排除当前预订）
if ($exclude_booking_id && $exclude_booking_id > 0) {
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

// 获取已预订的时间段
$booked_times = [];
foreach($booked_slots as $slot) {
    $start_hour = (int)date('H', strtotime($slot['start_time']));
    $end_hour = (int)date('H', strtotime($slot['end_time']));
    for($h = $start_hour; $h < $end_hour; $h++) {
        $booked_times[] = sprintf("%02d:00:00", $h);
    }
}

// 生成可用时间段 (8 AM - 10 PM)
$available_slots = [];
for($hour = 8; $hour <= 22; $hour++) {
    $time = sprintf("%02d:00:00", $hour);
    if(!in_array($time, $booked_times)) {
        $available_slots[] = [
            'time' => $time,
            'display' => date('h:i A', strtotime($time))
        ];
    }
}

echo json_encode($available_slots);
?>