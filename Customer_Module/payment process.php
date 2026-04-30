<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$booking_id = $data['booking_id'] ?? 0;
$payment_method = $data['payment_method'] ?? '';
$payment_method_name = $data['payment_method_name'] ?? '';

if(!$booking_id || !$payment_method) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

// 获取预订信息
$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ? AND status = 'Pending'");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if(!$booking) {
    echo json_encode(['success' => false, 'message' => 'Booking not found or already processed']);
    exit;
}

try {
    // 开始事务
    $pdo->beginTransaction();
    
    // 生成交易ID
    $transaction_id = 'TXN' . time() . rand(1000, 9999);
    
    // 更新预订状态为 Confirmed
    $updateStmt = $pdo->prepare("UPDATE bookings SET status = 'Confirmed' WHERE id = ?");
    $updateStmt->execute([$booking_id]);
    
    // 插入支付记录
    $paymentStmt = $pdo->prepare("
        INSERT INTO payments (booking_id, amount, discount_applied, final_amount, payment_method, payment_status, transaction_id) 
        VALUES (?, ?, 0, ?, ?, 'success', ?)
    ");
    $paymentStmt->execute([
        $booking_id,
        $booking['total_price'],
        $booking['total_price'],
        $payment_method_name,
        $transaction_id
    ]);
    
    // 提交事务
    $pdo->commit();
    
    echo json_encode(['success' => true]);
    
} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Payment processing failed: ' . $e->getMessage()]);
}
?>