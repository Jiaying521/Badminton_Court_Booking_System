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
    SELECT b.*, c.court_name
    FROM bookings b
    JOIN courts c ON b.court_id = c.id
    WHERE b.id = ? AND b.user_id = ?
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

// 获取用户的取消次数（兼容旧数据库）
$user_cancellation_count = 0;
try {
    $checkCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'cancellation_count'");
    if($checkCol->rowCount() > 0) {
        $stmt_user = $pdo->prepare("SELECT cancellation_count FROM users WHERE id = ?");
        $stmt_user->execute([$_SESSION['user_id']]);
        $user_data = $stmt_user->fetch();
        $user_cancellation_count = $user_data['cancellation_count'] ?? 0;
    }
} catch(PDOException $e) {
    $user_cancellation_count = 0;
}

// 检查是否是第二次取消（额外罚款RM5）
$is_second_cancellation = ($user_cancellation_count >= 1);

// 计算取消时间
$booking_datetime = $booking['booking_date'] . ' ' . $booking['start_time'];
$booking_timestamp = strtotime($booking_datetime);
$current_timestamp = time();
$hours_until_booking = ($booking_timestamp - $current_timestamp) / 3600;

// 取消政策
$cancellation_fee = 0;
$extra_penalty = 0;
$refund_amount = 0;
$can_cancel = false;
$message = '';

if ($hours_until_booking >= 48) {
    $cancellation_fee = 0;
    if ($is_second_cancellation) {
        $extra_penalty = 5.00;
        $refund_amount = max(0, $booking['total_price'] - $extra_penalty);
        $can_cancel = true;
        $message = "⚠️ This is your 2nd cancellation!\n\n📌 Additional penalty of RM " . number_format($extra_penalty, 2) . " applies.\n💰 RM " . number_format($refund_amount, 2) . " will be refunded to your wallet.";
    } else {
        $refund_amount = $booking['total_price'];
        $can_cancel = true;
        $message = "✅ Full refund of RM " . number_format($refund_amount, 2) . " will be credited to your wallet.";
    }
} elseif ($hours_until_booking >= 24) {
    $cancellation_fee = 10.00;
    $extra_penalty = $is_second_cancellation ? 5.00 : 0;
    $total_deduction = $cancellation_fee + $extra_penalty;
    $refund_amount = max(0, $booking['total_price'] - $total_deduction);
    $can_cancel = true;
    $message = "📌 Cancellation fee of RM " . number_format($cancellation_fee, 2) . " applies.";
    if ($extra_penalty > 0) {
        $message .= "\n⚠️ 2nd cancellation penalty: RM " . number_format($extra_penalty, 2);
        $message .= "\n💰 Total refund: RM " . number_format($refund_amount, 2);
    } else {
        $message .= "\n💰 Refund: RM " . number_format($refund_amount, 2);
    }
} elseif ($hours_until_booking >= 2) {
    $cancellation_fee = 10.00;
    $extra_penalty = $is_second_cancellation ? 5.00 : 0;
    $total_deduction = $cancellation_fee + $extra_penalty;
    $refund_amount = max(0, $booking['total_price'] - $total_deduction);
    $can_cancel = true;
    $message = "📌 Cancellation fee of RM " . number_format($cancellation_fee, 2) . " applies.";
    if ($extra_penalty > 0) {
        $message .= "\n⚠️ 2nd cancellation penalty: RM " . number_format($extra_penalty, 2);
        $message .= "\n💰 Total refund: RM " . number_format($refund_amount, 2);
    } else {
        $message .= "\n💰 Refund: RM " . number_format($refund_amount, 2);
    }
} else {
    $can_cancel = false;
    $message = "⚠️ Cannot cancel booking within 2 hours of start time.\n\nCourt fee will not be refunded.";
}

if (!$can_cancel) {
    echo json_encode([
        'success' => false, 
        'message' => $message,
        'hours_left' => round($hours_until_booking, 1)
    ]);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // 更新预订状态
    $total_fee = $cancellation_fee + $extra_penalty;
    
    try {
        $checkCol = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'cancellation_fee'");
        if($checkCol->rowCount() > 0) {
            $update = $pdo->prepare("UPDATE bookings SET status = 'Cancelled', cancellation_fee = ? WHERE id = ?");
            $update->execute([$total_fee, $booking_id]);
        } else {
            $update = $pdo->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
            $update->execute([$booking_id]);
        }
    } catch(PDOException $e) {
        $update = $pdo->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ?");
        $update->execute([$booking_id]);
    }
    
    // 增加用户的取消次数
    try {
        $checkCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'cancellation_count'");
        if($checkCol->rowCount() > 0) {
            $new_cancellation_count = $user_cancellation_count + 1;
            $update_cancel_count = $pdo->prepare("UPDATE users SET cancellation_count = ? WHERE id = ?");
            $update_cancel_count->execute([$new_cancellation_count, $_SESSION['user_id']]);
        }
    } catch(PDOException $e) {
        // 忽略
    }
    
    // 退还金额到钱包
    if ($refund_amount > 0) {
        $stmt = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmt->execute([$booking['user_id']]);
        $user = $stmt->fetch();
        $new_balance = $user['wallet_balance'] + $refund_amount;
        
        $update_wallet = $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
        $update_wallet->execute([$new_balance, $booking['user_id']]);
        
        // 检查payments表有哪些字段，避免插入不存在的列
        try {
            $checkPaymentCols = $pdo->query("SHOW COLUMNS FROM payments");
            $paymentColumns = $checkPaymentCols->fetchAll(PDO::FETCH_COLUMN);
            
            // 根据现有字段动态构建INSERT语句
            if (in_array('created_at', $paymentColumns)) {
                $stmt = $pdo->prepare("
                    INSERT INTO payments (booking_id, amount, final_amount, payment_method, payment_status, transaction_id, created_at) 
                    VALUES (?, ?, ?, 'Refund', 'success', ?, NOW())
                ");
                $refund_transaction_id = 'REF_' . time() . '_' . $booking_id;
                $stmt->execute([$booking_id, $total_fee, $refund_amount, $refund_transaction_id]);
            } else {
                // 如果没有created_at字段，使用当前时间作为默认值或者不插入
                $stmt = $pdo->prepare("
                    INSERT INTO payments (booking_id, amount, final_amount, payment_method, payment_status, transaction_id) 
                    VALUES (?, ?, ?, 'Refund', 'success', ?)
                ");
                $refund_transaction_id = 'REF_' . time() . '_' . $booking_id;
                $stmt->execute([$booking_id, $total_fee, $refund_amount, $refund_transaction_id]);
            }
        } catch(PDOException $e) {
            // 如果插入失败，至少记录日志（这里简单处理，不影响主流程）
            error_log("Failed to record refund payment: " . $e->getMessage());
        }
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => $message]);
    
} catch(Exception $e) {
    if($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Failed to cancel booking: ' . $e->getMessage()]);
}
?>