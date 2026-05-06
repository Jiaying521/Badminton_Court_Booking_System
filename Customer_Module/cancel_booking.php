<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$booking_id = $data['booking_id'] ?? 0;

// 检查预订是否存在且属于当前用户
$stmt = $pdo->prepare("SELECT id, status FROM bookings WHERE id = ? AND user_id = ?");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if(!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking not found']);
    exit;
}

if($booking['status'] == 'Cancelled') {
    echo json_encode(['success' => false, 'message' => 'Booking already cancelled']);
    exit;
}

// 更新状态为 Cancelled
$update = $pdo->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
$update->execute([$booking_id]);

echo json_encode(['success' => true]);
?>