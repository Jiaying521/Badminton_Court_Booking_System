<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$court_id = $_GET['court_id'] ?? 0;
$date = $_GET['date'] ?? '';

if (!$court_id || !$date) {
    echo json_encode(['error' => 'Missing court_id or date']);
    exit;
}

$day_of_week = date('N', strtotime($date));

// 获取该场地的可用时段
$avail = $pdo->prepare("SELECT start_time, end_time FROM court_availability WHERE court_id = ? AND day_of_week = ?");
$avail->execute([$court_id, $day_of_week]);
$row = $avail->fetch();

if (!$row) {
    echo json_encode(['error' => 'No availability found for this court on this day']);
    exit;
}

// 开始时间 08:00，结束时间 01:00（第二天凌晨1点）
$start = strtotime($date . ' ' . $row['start_time']);
$end = strtotime($date . ' ' . $row['end_time']);

// 如果结束时间小于开始时间（跨天），则结束时间加一天
if ($end < $start) {
    $end = strtotime('+1 day', $end);
}

$slots = [];

for ($t = $start; $t < $end; $t += 3600) {
    $slot_time = date('H:i:s', $t);
    
    // 格式化显示时间
    $hour = (int)date('H', $t);
    $ampm = $hour >= 12 ? 'PM' : 'AM';
    $hour12 = $hour % 12;
    if ($hour12 == 0) $hour12 = 12;
    $slot_display = $hour12 . ':00 ' . $ampm;
    
    // 检查是否已被预订
    $check = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE court_id = ? AND booking_date = ? AND start_time = ? AND status != 'Cancelled'");
    $check->execute([$court_id, $date, $slot_time]);
    $booked = $check->fetchColumn();
    
    if ($booked == 0) {
        $slots[] = [
            'time' => $slot_time,
            'display' => $slot_display
        ];
    }
}

echo json_encode($slots);
?>