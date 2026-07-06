<?php
// ajax_check_coach_availability.php

error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config.php';

if (!isLoggedIn()) {
    header('Content-Type: application/json');
    echo json_encode(['available' => false, 'count' => 0]);
    exit();
}

$coach_id = isset($_GET['coach_id']) ? (int)$_GET['coach_id'] : 0;
$date = isset($_GET['date']) ? $_GET['date'] : '';
$start_time = isset($_GET['start_time']) ? $_GET['start_time'] : '';
$duration = isset($_GET['duration']) ? (int)$_GET['duration'] : 1;

if (!$coach_id || !$date || !$start_time) {
    header('Content-Type: application/json');
    echo json_encode(['available' => true, 'count' => 0]);
    exit();
}

$end_time = date('H:i:s', strtotime($start_time) + ($duration * 3600));

try {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM bookings 
        WHERE coach_id = ? 
        AND booking_date = ? 
        AND status IN ('Pending', 'Confirmed')
        AND (
            (start_time <= ? AND end_time > ?) OR
            (start_time < ? AND end_time >= ?) OR
            (start_time >= ? AND start_time < ?)
        )
    ");
    $stmt->execute([
        $coach_id, 
        $date,
        $start_time, $start_time,
        $end_time, $end_time,
        $start_time, $end_time
    ]);

    $result = $stmt->fetch();
    $count = (int)$result['count'];
    $available = ($count === 0);

    header('Content-Type: application/json');
    echo json_encode([
        'available' => $available,
        'count' => $count
    ]);
} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'available' => false,
        'count' => 0
    ]);
}
?>