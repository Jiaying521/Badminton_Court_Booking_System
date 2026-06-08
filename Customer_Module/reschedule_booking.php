<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$booking_id = $data['booking_id'] ?? 0;
$new_date = $data['new_date'] ?? '';
$new_time = $data['new_time'] ?? '';

if(!$booking_id || !$new_date || !$new_time) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // 检查预订是否存在且属于当前用户
    $stmt = $pdo->prepare("
        SELECT b.*, c.court_name, c.price_off_peak, c.price_peak
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

    // 检查状态
    if($booking['status'] == 'Cancelled') {
        echo json_encode(['success' => false, 'message' => 'Cancelled bookings cannot be rescheduled']);
        exit;
    }

    // 检查是否已经改期过
    if(isset($booking['reschedule_count']) && $booking['reschedule_count'] >= 1) {
        echo json_encode(['success' => false, 'message' => 'This booking has already been rescheduled. Each booking can only be rescheduled once.']);
        exit;
    }

    // 计算距离预订开始的小时数
    $booking_datetime = $booking['booking_date'] . ' ' . $booking['start_time'];
    $booking_timestamp = strtotime($booking_datetime);
    $current_timestamp = time();
    $hours_until_booking = ($booking_timestamp - $current_timestamp) / 3600;

    // 根据文档政策检查是否允许改期（≥24小时允许）
    if ($hours_until_booking < 24) {
        echo json_encode(['success' => false, 'message' => '❌ Reschedule not allowed!\n\n' .
                         'According to our policy, rescheduling is only allowed for cancellations made at least 24 hours before your booking time.\n\n' .
                         'Your booking starts in ' . round($hours_until_booking, 1) . ' hours.\n\n' .
                         'Please contact us if you need assistance.']);
        exit;
    }

    // 获取价格设置
    $off_peak_price = $booking['price_off_peak'];
    $peak_price = $booking['price_peak'];
    $coach_rate = getSetting('coach_rate', '20.00');

    // 计算新价格
    $new_total_price = 0;
    $hour = (int)date('H', strtotime($new_time));
    $total_hours = $booking['total_hours'];
    
    for ($i = 0; $i < $total_hours; $i++) {
        $current_hour = $hour + $i;
        if ($current_hour >= 8 && $current_hour < 15) {
            $new_total_price += floatval($off_peak_price);
        } else {
            $new_total_price += floatval($peak_price);
        }
    }
    
    // 添加教练费用
    if ($booking['coach_id'] && $booking['coach_id'] > 0 && $booking['coach_hours'] > 0) {
        $new_total_price += $booking['coach_hours'] * floatval($coach_rate);
    }
    
    $old_total_price = floatval($booking['total_price']);
    $price_diff = $new_total_price - $old_total_price;
    
    // 计算新结束时间
    $new_end_time = date('H:i:s', strtotime($new_time) + ($total_hours * 3600));

    // 检查新时间是否可用（排除当前预订）
    $stmt_check = $pdo->prepare("
        SELECT COUNT(*) FROM bookings 
        WHERE court_id = ? AND booking_date = ? 
        AND status != 'Cancelled'
        AND id != ?
        AND (
            (start_time < ? AND end_time > ?) OR
            (start_time >= ? AND start_time < ?)
        )
    ");
    $stmt_check->execute([
        $booking['court_id'], $new_date,
        $booking_id,
        $new_end_time, $new_time,
        $new_time, $new_end_time
    ]);
    $conflict_count = $stmt_check->fetchColumn();

    if($conflict_count > 0) {
        echo json_encode(['success' => false, 'message' => 'The selected time slot is already booked for this court']);
        exit;
    }

    // 开始事务
    $pdo->beginTransaction();
    
    $response_message = "";
    
    // 处理差价
    if ($price_diff < 0) {
        // 新时间段更便宜，退还差价到钱包
        $refund_amount = abs($price_diff);
        
        $stmt_wallet = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmt_wallet->execute([$_SESSION['user_id']]);
        $user_wallet = $stmt_wallet->fetch();
        $new_balance = $user_wallet['wallet_balance'] + $refund_amount;
        
        $update_wallet = $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
        $update_wallet->execute([$new_balance, $_SESSION['user_id']]);
        
        // 记录退款
        $stmt_payment = $pdo->prepare("
            INSERT INTO payments (booking_id, amount, final_amount, payment_method, payment_status, transaction_id, payment_date) 
            VALUES (?, ?, ?, 'Refund', 'success', ?, NOW())
        ");
        $transaction_id = 'RESCHEDULE_REFUND_' . time() . '_' . $booking_id;
        $stmt_payment->execute([$booking_id, $refund_amount, $refund_amount, $transaction_id]);
        
        $response_message = "✅ Booking rescheduled successfully!\n";
        $response_message .= "💰 Refund of RM " . number_format($refund_amount, 2) . " has been credited to your wallet.\n";
        $response_message .= "New total: RM " . number_format($new_total_price, 2) . " (was RM " . number_format($old_total_price, 2) . ")";
        
    } elseif ($price_diff > 0) {
        // 新时间段更贵，需要用户补差价
        $additional_amount = $price_diff;
        
        $stmt_wallet = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
        $stmt_wallet->execute([$_SESSION['user_id']]);
        $user_wallet = $stmt_wallet->fetch();
        
        if ($user_wallet['wallet_balance'] < $additional_amount) {
            $pdo->rollBack();
            echo json_encode([
                'success' => false, 
                'message' => "Insufficient wallet balance!\n\nNeed additional: RM " . number_format($additional_amount, 2) . "\nYour balance: RM " . number_format($user_wallet['wallet_balance'], 2) . "\n\nPlease top up your wallet and try again.",
                'need_payment' => true,
                'additional_amount' => $additional_amount
            ]);
            exit;
        }
        
        $new_balance = $user_wallet['wallet_balance'] - $additional_amount;
        $update_wallet = $pdo->prepare("UPDATE users SET wallet_balance = ? WHERE id = ?");
        $update_wallet->execute([$new_balance, $_SESSION['user_id']]);
        
        // 记录额外付款
        $stmt_payment = $pdo->prepare("
            INSERT INTO payments (booking_id, amount, final_amount, payment_method, payment_status, transaction_id, payment_date) 
            VALUES (?, ?, ?, 'Wallet', 'success', ?, NOW())
        ");
        $transaction_id = 'RESCHEDULE_TOPUP_' . time() . '_' . $booking_id;
        $stmt_payment->execute([$booking_id, $additional_amount, $additional_amount, $transaction_id]);
        
        $response_message = "✅ Booking rescheduled successfully!\n";
        $response_message .= "💰 Additional RM " . number_format($additional_amount, 2) . " has been deducted from your wallet.\n";
        $response_message .= "New total: RM " . number_format($new_total_price, 2) . " (was RM " . number_format($old_total_price, 2) . ")";
        
    } else {
        // 价格相同
        $response_message = "✅ Booking rescheduled successfully!\nNo price difference.";
    }
    
    // 更新预订
    $update = $pdo->prepare("
        UPDATE bookings 
        SET booking_date = ?, start_time = ?, end_time = ?, total_price = ?
        WHERE id = ?
    ");
    $update->execute([$new_date, $new_time, $new_end_time, $new_total_price, $booking_id]);
    
    // 更新改期次数
    try {
        $checkCol = $pdo->query("SHOW COLUMNS FROM bookings LIKE 'reschedule_count'");
        if($checkCol->rowCount() > 0) {
            $update2 = $pdo->prepare("
                UPDATE bookings 
                SET reschedule_count = COALESCE(reschedule_count, 0) + 1
                WHERE id = ?
            ");
            $update2->execute([$booking_id]);
        }
    } catch(PDOException $e) {
        // 忽略
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => $response_message, 'new_total' => $new_total_price, 'price_diff' => $price_diff]);
    
} catch(Exception $e) {
    if(isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Failed to reschedule: ' . $e->getMessage()]);
}
?>