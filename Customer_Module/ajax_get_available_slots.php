<?php
require_once 'config.php';
$court_id = $_GET['court_id'] ?? 0;
$date = $_GET['date'] ?? '';
if(!$court_id || !$date) die(json_encode([]));

$day_of_week = date('N', strtotime($date)); // 1=Mon ... 7=Sun
// 获取该场地的可用时段
$avail = $pdo->prepare("SELECT start_time, end_time FROM court_availability WHERE court_id=? AND day_of_week=?");
$avail->execute([$court_id, $day_of_week]);
$row = $avail->fetch();
if(!$row) die(json_encode([]));
$start = strtotime($row['start_time']);
$end = strtotime($row['end_time']);
$slots = [];
for($t=$start; $t<$end; $t+=3600) {
    $slot_time = date('H:i:s', $t);
    // 检查是否已被预订
    $check = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE court_id=? AND booking_date=? AND start_time=? AND status NOT IN ('Cancelled')");
    $check->execute([$court_id, $date, $slot_time]);
    if($check->fetchColumn() == 0) {
        $slots[] = date('H:i', $t);
    }
}
echo json_encode($slots);
?>