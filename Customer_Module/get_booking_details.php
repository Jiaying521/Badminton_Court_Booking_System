<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$booking_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT b.*, c.court_name, c.court_type, co.name as coach_name
    FROM bookings b 
    JOIN courts c ON b.court_id = c.id 
    LEFT JOIN coaches co ON b.coach_id = co.id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if($booking) {
    $booking['booking_date'] = date('M j, Y', strtotime($booking['booking_date']));
    $booking['start_time'] = date('h:i A', strtotime($booking['start_time']));
    $booking['end_time'] = date('h:i A', strtotime($booking['end_time']));
    echo json_encode(['success' => true, 'booking' => $booking]);
} else {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
}
?>