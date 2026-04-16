<?php
// Customer_Module/ajax_get_available_slots.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/holidays.php';  // 公共假期列表
header('Content-Type: application/json');

$doctor_id = (int)($_GET['doctor_id'] ?? 0);
$date = $_GET['date'] ?? '';
if (!$doctor_id || !$date) {
    echo json_encode([]);
    exit;
}

// 1. 检查是否为公共假期
$holidays = getPublicHolidays();
if (in_array($date, $holidays)) {
    echo json_encode([]);
    exit;
}

// 2. 获取医生该天的工作时间
$day_of_week = (int)date('N', strtotime($date));
$stmt = $pdo->prepare("SELECT start_time, end_time, slot_duration FROM doctor_availability WHERE doctor_id = ? AND day_of_week = ?");
$stmt->execute([$doctor_id, $day_of_week]);
$availability = $stmt->fetch();

if (!$availability) {
    // 默认工作时间：周一至周五 09:00-17:00，周末不营业
    if ($day_of_week >= 6) {
        echo json_encode([]);
        exit;
    }
    $start_time = '09:00:00';
    $end_time = '17:00:00';
    $slot_duration = 30;
} else {
    $start_time = $availability['start_time'];
    $end_time = $availability['end_time'];
    $slot_duration = $availability['slot_duration'] ?? 30;
}

// 3. 生成所有可能的时间段
$slots = [];
$start = strtotime($date . ' ' . $start_time);
$end = strtotime($date . ' ' . $end_time);
$interval = $slot_duration * 60;
for ($t = $start; $t < $end; $t += $interval) {
    $slots[] = date('H:i:s', $t);
}

// 4. 查询已被预约的时间段（排除已取消或完成的）
$stmt = $pdo->prepare("SELECT appointment_time FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status NOT IN ('Cancelled', 'Completed')");
$stmt->execute([$doctor_id, $date]);
$booked = $stmt->fetchAll(PDO::FETCH_COLUMN);
$available_slots = array_diff($slots, $booked);

// 5. 关键修改：如果选择的日期是今天，只显示当前时间之后的时段
if ($date === date('Y-m-d')) {
    $current_time = date('H:i:s');
    $available_slots = array_filter($available_slots, function($slot) use ($current_time) {
        return $slot > $current_time;
    });
    $available_slots = array_values($available_slots);
}

// 6. 返回结果
echo json_encode($available_slots);