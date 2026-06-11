<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$booking_id = $data['booking_id'] ?? 0;
$cancel_type = $data['cancel_type'] ?? 'all'; // EASY HUMAN COMMENT: Detect choice payload parameters safely

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

// 获取add-on总金额
$stmt_addons = $pdo->prepare("SELECT SUM(price * quantity) as total_addons FROM booking_addons WHERE booking_id = ?");
$stmt_addons->execute([$booking_id]);
$addons_total = $stmt_addons->fetchColumn() ?? 0;

// 获取实际付款金额（顾客真正付出去的，扣除 voucher 折扣后）
$stmt_paid = $pdo->prepare("
    SELECT final_amount FROM payments
    WHERE booking_id = ? AND payment_status = 'success' AND payment_method NOT LIKE 'Refund%'
    ORDER BY id ASC LIMIT 1
");
$stmt_paid->execute([$booking_id]);
$actual_paid = (float)($stmt_paid->fetchColumn() ?? $booking['total_price']);


// NEW ADDITION: PATH 1 — CANCEL ADD-ONS ITEMS ONLY
if ($cancel_type === 'addons') {
    if ($addons_total <= 0) {
        echo json_encode(['success' => false, 'message' => 'No add-on items found to cancel.']);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Give the add-on money back to the player's wallet balance
        $stmt_user = $pdo->prepare("SELECT wallet_balance, loyalty_points FROM users WHERE id = ?");
        $stmt_user->execute([$_SESSION['user_id']]);
        $user_row = $stmt_user->fetch();
        $new_balance = $user_row['wallet_balance'] + $addons_total;
        
        // EASY HUMAN COMMENT: Calculate points to reverse (RM 1 = 1 Point). Ensure balance never goes below 0.
        $points_to_deduct = floor($addons_total);
        $new_points = max(0, ($user_row['loyalty_points'] ?? 0) - $points_to_deduct);

        // Update user row with both modified wallet balance and deducted loyalty points
        $update_wallet = $pdo->prepare("UPDATE users SET wallet_balance = ?, loyalty_points = ? WHERE id = ?");
        $update_wallet->execute([$new_balance, $new_points, $_SESSION['user_id']]);

        // 2. Drop the extra add-on record lines completely out of the items ledger
        $delete_addons = $pdo->prepare("DELETE FROM booking_addons WHERE booking_id = ?");
        $delete_addons->execute([$booking_id]);

        // 3. Deduct the items price from the master bill, keeping court status Confirmed
        $update_booking = $pdo->prepare("UPDATE bookings SET total_price = total_price - ? WHERE id = ?");
        $update_booking->execute([$addons_total, $booking_id]);

        // 4. Register a clean trace ID row into payments for accounting transparency
        $stmt_payment = $pdo->prepare("
            INSERT INTO payments (booking_id, amount, final_amount, payment_method, payment_status, transaction_id, payment_date) 
            VALUES (?, 0, ?, 'Refund_Addons', 'success', ?, NOW())
        ");
        $refund_trans_id = 'REF_ADD_' . time() . '_' . $booking_id;
        $stmt_payment->execute([$booking_id, $addons_total, $refund_trans_id]);

        $pdo->commit();

        $success_msg = "✅ Add-ons Cancelled Successfully!\n💰 RM " . number_format($addons_total, 2) . " has been refunded back to your wallet.\n📉 Points Reversed: -" . $points_to_deduct . " Pts\n🏸 Your court booking remains active.";
        echo json_encode(['success' => true, 'message' => $success_msg, 'refund_amount' => $addons_total]);
        exit;

    } catch(Exception $e) {
        if($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Failed to drop equipment items: ' . $e->getMessage()]);
        exit;
    }
}


// 判断是否有教练
$has_coach = ($booking['coach_id'] && $booking['coach_id'] > 0 && $booking['coach_hours'] > 0);

// 计算取消时间
$booking_datetime = $booking['booking_date'] . ' ' . $booking['start_time'];
$booking_timestamp = strtotime($booking_datetime);
$current_timestamp = time();
$hours_until_booking = ($booking_timestamp - $current_timestamp) / 3600;

// 根据文档政策设置退款规则
$cancellation_fee = 0;
$refund_amount = 0;
$can_cancel = false;
$message = '';

if ($hours_until_booking >= 48) {
    // ≥ 48小时：全额退款
    $cancellation_fee = 0;
    $refund_amount = $booking['total_price'];
    $can_cancel = true;
    $message = "✅ Full refund of RM " . number_format($refund_amount, 2) . " will be credited to your wallet.\n\n📌 Cancellation Policy: ≥48 hours notice = Full refund.";
    
} elseif ($hours_until_booking >= 24) {
    // 24-48小时
    if ($has_coach) {
        // 有教练模式：全额退款
        $cancellation_fee = 0;
        $refund_amount = $booking['total_price'];
        $message = "✅ Full refund of RM " . number_format($refund_amount, 2) . " will be credited to your wallet.\n\n📌 Training Mode: ≥24 hours notice = Full refund.";
    } else {
        // 纯打球模式：扣除 RM10 手续费
        $cancellation_fee = 10.00;
        $refund_amount = max(0, $booking['total_price'] - $cancellation_fee);
        $message = "📌 RM 10.00 cancellation fee applies.\n💰 Refund: RM " . number_format($refund_amount, 2) . " will be credited to your wallet.\n\n📌 Play Only Mode: 24-48 hours notice = RM10 fee.";
    }
    $can_cancel = true;
    
} elseif ($hours_until_booking >= 2) {
    // 2-24小时
    if ($has_coach) {
        // 有教练模式：扣除 50% 教练费，球场费不退，add-on全额退款
        $coach_fee = $booking['coach_price_total'] ?? 0;
        $coach_refund = $coach_fee * 0.5;
        $refund_amount = $coach_refund + $addons_total;
        $cancellation_fee = $booking['total_price'] - $refund_amount;
        $message = "📌 Training Mode Cancellation Policy (2-24 hours):\n" .
                   "   • Court fee: NOT refunded\n" .
                   "   • Coach fee: 50% refunded (RM " . number_format($coach_refund, 2) . ")\n" .
                   "   • Add-ons: FULLY refunded (RM " . number_format($addons_total, 2) . ")\n" .
                   "💰 Total refund: RM " . number_format($refund_amount, 2);
    } else {
        // 纯打球模式：球场费不退，仅退add-on
        $refund_amount = $addons_total;
        $cancellation_fee = $booking['total_price'] - $refund_amount;
        $message = "📌 Play Only Mode Cancellation Policy (2-24 hours):\n" .
                   "   • Court fee: NOT refunded\n" .
                   "   • Add-ons: FULLY refunded (RM " . number_format($addons_total, 2) . ")\n" .
                   "💰 Total refund: RM " . number_format($refund_amount, 2);
    }
    $can_cancel = true;
    
} elseif ($hours_until_booking >= 1) {
    // 1-2小时
    if ($has_coach) {
        // 有教练模式：add-on全额退款，球场和教练费不退
        $refund_amount = $addons_total;
        $cancellation_fee = $booking['total_price'] - $addons_total;
        $message = "📌 Training Mode Cancellation Policy (1-2 hours):\n" .
                   "   • Court fee: NOT refunded\n" .
                   "   • Coach fee: NOT refunded (already paid to coach)\n" .
                   "   • Add-ons: FULLY refunded (RM " . number_format($addons_total, 2) . ")\n" .
                   "💰 Total refund: RM " . number_format($refund_amount, 2);
    } else {
        // 纯打球模式：完全不退款
        $refund_amount = 0;
        $cancellation_fee = $booking['total_price'];
        $message = "❌ Play Only Mode: No refund for cancellations within 2 hours of start time.\n\n" .
                   "   • Court fee: NOT refunded\n" .
                   "   • Add-ons: NOT refunded\n\n" .
                   "💰 No refund will be issued.";
    }
    $can_cancel = true;
    
} else {
    // < 1小时：完全不退款
    $can_cancel = true;
    $refund_amount = 0;
    $cancellation_fee = $booking['total_price'];
    
    if ($has_coach) {
        $message = "❌ Training Mode: No refund for cancellations within 1 hour of start time.\n\n" .
                   "   • Court fee: NOT refunded\n" .
                   "   • Coach fee: NOT refunded (already paid to coach)\n" .
                   "   • Add-ons: NOT refunded\n\n" .
                   "💰 No refund will be issued.";
    } else {
        $message = "❌ Play Only Mode: No refund for cancellations within 1 hour of start time.\n\n" .
                   "💰 No refund will be issued.";
    }
}

// Cap refund at what the customer actually paid (voucher discount is not refunded)
$refund_amount = min($refund_amount, $actual_paid);

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
    $update = $pdo->prepare("UPDATE bookings SET status = 'Cancelled', cancellation_fee = ? WHERE id = ?");
    $update->execute([$cancellation_fee, $booking_id]);
    
    // 增加用户取消次数（记录到数据库）
    try {
        $checkCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'cancellation_count'");
        if($checkCol->rowCount() > 0) {
            $update_cancel_count = $pdo->prepare("UPDATE users SET cancellation_count = COALESCE(cancellation_count, 0) + 1 WHERE id = ?");
            $update_cancel_count->execute([$_SESSION['user_id']]);
        }
    } catch(PDOException $e) {
        // 如果字段不存在，忽略（兼容旧数据库）
    }
    
    // 退还金额到钱包
    if ($refund_amount > 0) {
        $stmt = $pdo->prepare("SELECT wallet_balance, loyalty_points FROM users WHERE id = ?");
        $stmt->execute([$booking['user_id']]);
        $user = $stmt->fetch();
        $new_balance = $user['wallet_balance'] + $refund_amount;

        $points_to_deduct = floor($refund_amount);
        $new_points = max(0, ($user['loyalty_points'] ?? 0) - $points_to_deduct);

        $update_wallet = $pdo->prepare("UPDATE users SET wallet_balance = ?, loyalty_points = ? WHERE id = ?");
        $update_wallet->execute([$new_balance, $new_points, $booking['user_id']]);
        
        // 记录退款
        $stmt_payment = $pdo->prepare("
            INSERT INTO payments (booking_id, amount, final_amount, payment_method, payment_status, transaction_id, payment_date) 
            VALUES (?, ?, ?, 'Refund', 'success', ?, NOW())
        ");
        $refund_transaction_id = 'REF_' . time() . '_' . $booking_id;
        $stmt_payment->execute([$booking_id, $cancellation_fee, $refund_amount, $refund_transaction_id]);
    }
    
    $pdo->commit();
    
    echo json_encode(['success' => true, 'message' => $message, 'refund_amount' => $refund_amount]);
    
} catch(Exception $e) {
    if($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Failed to cancel booking: ' . $e->getMessage()]);
}
?>