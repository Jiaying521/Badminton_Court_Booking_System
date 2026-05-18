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
$stmt = $pdo->prepare("
    SELECT id, status, booking_date, start_time, total_price, user_id 
    FROM bookings 
    WHERE id = ? AND user_id = ?
");
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

if($booking['status'] == 'Completed') {
    echo json_encode(['success' => false, 'message' => 'Completed booking cannot be cancelled']);
    exit;
}

// 计算取消时间限制
$booking_datetime = $booking['booking_date'] . ' ' . $booking['start_time'];
$booking_timestamp = strtotime($booking_datetime);
$current_timestamp = time();
$hours_until_booking = ($booking_timestamp - $current_timestamp) / 3600;
$cancellation_deadline_hours = 2;
$cancellation_fee = 10.00;

if ($hours_until_booking < $cancellation_deadline_hours) {
    echo json_encode([
        'success' => false, 
        'message' => "Cannot cancel. You need to cancel at least {$cancellation_deadline_hours} hours before your booking. Your booking starts in " . round($hours_until_booking, 1) . " hours."
    ]);
    exit;
}

// 计算退款金额
$refund_amount = max(0, $booking['total_price'] - $cancellation_fee);

try {
    $pdo->beginTransaction();
    
    // 更新预订状态
    $update = $pdo->prepare("UPDATE bookings SET status = 'Cancelled', cancellation_fee = ? WHERE id = ?");
    $update->execute([$cancellation_fee, $booking_id]);
    
    // 退还剩余金额到钱包
    if ($refund_amount > 0) {
        $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmt->execute([$booking['user_id']]);
        $user = $stmt->fetch();
        $new_balance = $user['wallet_balance'] + $refund_amount;
        
        $update_wallet = $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
        $update_wallet->execute([$new_balance, $booking['user_id']]);
        
        // 记录退款
        $stmt = $pdo->prepare("
            INSERT INTO payments (booking_id, amount, final_amount, payment_method, payment_status, transaction_id) 
            VALUES (?, ?, ?, 'Refund', 'success', ?)
        ");
        $refund_transaction_id = 'REF_' . time() . '_' . $booking_id;
        $stmt->execute([$booking_id, $cancellation_fee, $refund_amount, $refund_transaction_id]);
    }
    
    $pdo->commit();
    
    $message = "Booking cancelled successfully.";
    if ($cancellation_fee > 0) {
        $message .= " Cancellation fee of RM " . number_format($cancellation_fee, 2) . " has been charged.";
    }
    if ($refund_amount > 0) {
        $message .= " RM " . number_format($refund_amount, 2) . " has been refunded to your wallet.";
    }
    
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch(Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Failed to cancel booking: ' . $e->getMessage()]);
}
?>