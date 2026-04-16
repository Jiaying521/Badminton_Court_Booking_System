<?php
// Customer_Module/process_appointment.php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/holidays.php';   // 正确路径

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Unauthorized');
}

$user_id = $_SESSION['user_id'];
$doctor_id = $_POST['doctor_id'] ?? 0;
$date = $_POST['appointment_date'] ?? '';
$time = $_POST['appointment_time'] ?? '';
$type = $_POST['appointment_type'] ?? 'Consultation';
$notes = $_POST['notes'] ?? '';

if (!$doctor_id || !$date || !$time) {
    die('Missing required fields.');
}

// 1. 验证日期范围（两年内）
$maxDate = date('Y-m-d', strtotime('+2 years'));
if ($date > $maxDate || $date < date('Y-m-d')) {
    die('Invalid date. Please select a date within the next two years.');
}

// 2. 检查是否为公共假期
$holidays = getPublicHolidays();
if (in_array($date, $holidays)) {
    die('Clinic is closed on public holidays. Please choose another date.');
}

// 3. 验证医生是否存在且是医生
$stmt = $pdo->prepare("SELECT id, username FROM admins WHERE id = ? AND is_doctor = 1");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();
if (!$doctor) {
    die('Invalid doctor selected.');
}

// 4. 如果日期是今天，检查时间是否已过
if ($date === date('Y-m-d')) {
    $current_time = date('H:i:s');
    if ($time <= $current_time) {
        die('Cannot book a time that has already passed today.');
    }
}

// 5. 检查该时间段是否已被预约（冲突检测）
$stmt = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status NOT IN ('Cancelled', 'Completed')");
$stmt->execute([$doctor_id, $date, $time]);
if ($stmt->fetchColumn() > 0) {
    die('This time slot is already taken. Please choose another time.');
}

// 6. 插入预约
$stmt = $pdo->prepare("INSERT INTO appointments (user_id, doctor_id, appointment_date, appointment_time, appointment_type, notes, status) VALUES (?, ?, ?, ?, ?, ?, 'Pending')");
$stmt->execute([$user_id, $doctor_id, $date, $time, $type, $notes]);

// 7. 可选：创建支付记录（示例金额）
$appointment_id = $pdo->lastInsertId();
$amount = 50.00;
if ($type == 'Health Screening') $amount = 120.00;
elseif ($type == 'Vaccination') $amount = 80.00;
// 如果 payments 表有 appointment_id 字段，请确保已添加
$stmt = $pdo->prepare("INSERT INTO payments (appointment_id, amount, final_amount, payment_status) VALUES (?, ?, ?, 'Pending')");
$stmt->execute([$appointment_id, $amount, $amount]);

header('Location: my_appointments.php?success=1');
exit;