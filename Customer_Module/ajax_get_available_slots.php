<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/holidays.php'; 
header('Content-Type: application/json');

$doctor_id = (int)($_GET['doctor_id'] ?? 0);
$date = $_GET['date'] ?? '';
if (!$doctor_id || !$date) {
    echo json_encode([]);
    exit;
}

// check if the date is a holiday
$holidays = getPublicHolidays();
if (in_array($date, $holidays)) {
    echo json_encode([]);
    exit;
}

// get doctor's availability for the given date
$day_of_week = (int)date('N', strtotime($date));
$stmt = $pdo->prepare("SELECT start_time, end_time, slot_duration FROM doctor_availability WHERE doctor_id = ? AND day_of_week = ?");
$stmt->execute([$doctor_id, $day_of_week]);
$availability = $stmt->fetch();

if (!$availability) {
    // even if the doctor has no specific availability, we can assume they work regular hours on weekdays
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

// available slots generation
$slots = [];
$start = strtotime($date . ' ' . $start_time);
$end = strtotime($date . ' ' . $end_time);
$interval = $slot_duration * 60;
for ($t = $start; $t < $end; $t += $interval) {
    $slots[] = date('H:i:s', $t);
}

//check booked slots
$stmt = $pdo->prepare("SELECT appointment_time FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND status NOT IN ('Cancelled', 'Completed')");
$stmt->execute([$doctor_id, $date]);
$booked = $stmt->fetchAll(PDO::FETCH_COLUMN);
$available_slots = array_diff($slots, $booked);

// if the date is today, filter out past time slots
if ($date === date('Y-m-d')) {
    $current_time = date('H:i:s');
    $available_slots = array_filter($available_slots, function($slot) use ($current_time) {
        return $slot > $current_time;
    });
    $available_slots = array_values($available_slots);
}

echo json_encode($available_slots);