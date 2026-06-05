<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$booking_id = $_GET['id'] ?? 0;

$stmt = $pdo->prepare("
    SELECT b.*, c.court_name, c.court_type, c.location,
           co.name as coach_name, co.price_per_hour as coach_price,
           p.payment_method, p.transaction_id, p.created_at as payment_date
    FROM bookings b 
    JOIN courts c ON b.court_id = c.id 
    LEFT JOIN coaches co ON b.coach_id = co.id
    LEFT JOIN payments p ON b.id = p.booking_id
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if($booking) {
    echo json_encode([
        'success' => true,
        'booking' => [
            'id' => $booking['id'],
            'court_name' => $booking['court_name'],
            'court_type' => $booking['court_type'],
            'location' => $booking['location'],
            'booking_date' => date('M j, Y', strtotime($booking['booking_date'])),
            'start_time' => date('h:i A', strtotime($booking['start_time'])),
            'end_time' => date('h:i A', strtotime($booking['end_time'])),
            'total_hours' => $booking['total_hours'],
            'total_price' => $booking['total_price'],
            'status' => $booking['status'],
            'coach_name' => $booking['coach_name'],
            'coach_hours' => $booking['coach_hours'],
            'payment_method' => $booking['payment_method'] ?? 'Wallet',
            'transaction_id' => $booking['transaction_id'] ?? 'N/A',
            'payment_date' => $booking['payment_date'] ? date('M j, Y h:i A', strtotime($booking['payment_date'])) : 'N/A',
            'cancellation_fee' => $booking['cancellation_fee'] ?? 0,
            'reschedule_count' => $booking['reschedule_count'] ?? 0
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
}
?>