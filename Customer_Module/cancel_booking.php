<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';
header('Content-Type: application/json');

// 关闭错误输出，确保只返回 JSON
error_reporting(0);
ini_set('display_errors', 0);

if(!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$booking_id = $data['booking_id'] ?? 0;
$cancel_type = $data['cancel_type'] ?? 'all';

if(!$booking_id) {
    echo json_encode(['success' => false, 'message' => 'Missing booking ID']);
    exit;
}

try {
    // 检查预订是否存在且属于当前用户
    $stmt = $pdo->prepare("
        SELECT b.*, c.court_name, c.court_type, u.name as user_name, u.email as user_email
        FROM bookings b
        JOIN courts c ON b.court_id = c.id
        JOIN users u ON b.user_id = u.id
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

    // ========== 特殊处理：Pending 状态（未支付）直接取消，不退款，不发邮件 ==========
    if ($booking['status'] == 'Pending') {
        $pdo->beginTransaction();
        
        $update = $pdo->prepare("UPDATE bookings SET status = 'Cancelled', cancellation_fee = 0 WHERE id = ?");
        $update->execute([$booking_id]);
        
        $delete_addons = $pdo->prepare("DELETE FROM booking_addons WHERE booking_id = ?");
        $delete_addons->execute([$booking_id]);
        
        $pdo->commit();
        
        echo json_encode(['success' => true, 'message' => 'Booking cancelled successfully.']);
        exit;
    }

    // ========== 以下是 Confirmed 状态的处理 ==========
    
    // 获取add-on总金额
    $stmt_addons = $pdo->prepare("SELECT SUM(price * quantity) as total_addons FROM booking_addons WHERE booking_id = ?");
    $stmt_addons->execute([$booking_id]);
    $addons_total = $stmt_addons->fetchColumn() ?? 0;

    // 获取实际付款金额
    $actual_paid = $booking['total_price'];
    try {
        $stmt_paid = $pdo->prepare("
            SELECT final_amount FROM payments
            WHERE booking_id = ? AND payment_status = 'success' AND payment_method NOT LIKE 'Refund%'
            ORDER BY payment_id ASC LIMIT 1
        ");
        $stmt_paid->execute([$booking_id]);
        $paid_val = $stmt_paid->fetchColumn();
        if($paid_val !== false && $paid_val !== null) {
            $actual_paid = floatval($paid_val);
        }
    } catch(PDOException $e) {
        // 使用 total_price 作为 fallback
    }

    // 获取取消次数
    $cancellation_count = 0;
    try {
        $stmt_cancel_count = $pdo->prepare("SELECT COALESCE(cancellation_count, 0) FROM users WHERE id = ?");
        $stmt_cancel_count->execute([$_SESSION['user_id']]);
        $cancellation_count = (int)$stmt_cancel_count->fetchColumn();
    } catch(PDOException $e) {
        $cancellation_count = 0;
    }

    // ========== 仅取消 Add-ons ==========
    if ($cancel_type === 'addons') {
        if ($addons_total <= 0) {
            echo json_encode(['success' => false, 'message' => 'No add-on items found to cancel.']);
            exit;
        }

        $pdo->beginTransaction();

        $stmt_user = $pdo->prepare("SELECT wallet_balance, loyalty_points FROM users WHERE id = ?");
        $stmt_user->execute([$_SESSION['user_id']]);
        $user_row = $stmt_user->fetch();
        $new_balance = $user_row['wallet_balance'] + $addons_total;
        $points_to_deduct = floor($addons_total);
        $new_points = max(0, ($user_row['loyalty_points'] ?? 0) - $points_to_deduct);

        $update_wallet = $pdo->prepare("UPDATE users SET wallet_balance = ?, loyalty_points = ? WHERE id = ?");
        $update_wallet->execute([$new_balance, $new_points, $_SESSION['user_id']]);

        $delete_addons = $pdo->prepare("DELETE FROM booking_addons WHERE booking_id = ?");
        $delete_addons->execute([$booking_id]);

        $update_booking = $pdo->prepare("UPDATE bookings SET total_price = total_price - ? WHERE id = ?");
        $update_booking->execute([$addons_total, $booking_id]);

        $refund_trans_id = 'REF_ADD_' . time() . '_' . $booking_id;
        $stmt_payment = $pdo->prepare("
            INSERT INTO payments (booking_id, amount, final_amount, payment_method, payment_status, transaction_id, payment_date) 
            VALUES (?, 0, ?, 'Refund_Addons', 'success', ?, NOW())
        ");
        $stmt_payment->execute([$booking_id, $addons_total, $refund_trans_id]);

        $pdo->commit();

        $success_msg = "Add-ons Cancelled Successfully!\nRM " . number_format($addons_total, 2) . " has been refunded to your wallet.\nPoints Reversed: -" . $points_to_deduct . " Pts\nYour court booking remains active.";
        echo json_encode(['success' => true, 'message' => $success_msg, 'refund_amount' => $addons_total]);
        exit;
    }

    // ========== 完整取消（已支付） ==========
    $has_coach = ($booking['coach_id'] && $booking['coach_id'] > 0 && $booking['coach_hours'] > 0);
    $booking_datetime = $booking['booking_date'] . ' ' . $booking['start_time'];
    $booking_timestamp = strtotime($booking_datetime);
    $current_timestamp = time();
    $hours_until_booking = ($booking_timestamp - $current_timestamp) / 3600;

    $cancellation_fee = 0;
    $refund_amount = 0;
    $message = '';

    if ($hours_until_booking >= 48) {
        $refund_amount = $booking['total_price'];
        $cancellation_fee = 0;
        $message = "Full refund of RM " . number_format($refund_amount, 2) . " will be credited to your wallet.";
        
    } elseif ($hours_until_booking >= 24) {
        if ($has_coach) {
            $refund_amount = $booking['total_price'];
            $cancellation_fee = 0;
            $message = "Full refund of RM " . number_format($refund_amount, 2) . " will be credited to your wallet.";
        } else {
            $cancellation_fee = 10.00;
            $refund_amount = max(0, $booking['total_price'] - $cancellation_fee);
            $message = "RM 10.00 cancellation fee applies.\nRefund: RM " . number_format($refund_amount, 2);
        }
        
    } elseif ($hours_until_booking >= 2) {
        if ($has_coach) {
            $coach_fee = $booking['coach_price_total'] ?? 0;
            $coach_refund = $coach_fee * 0.5;
            $refund_amount = $coach_refund + $addons_total;
            $cancellation_fee = $booking['total_price'] - $refund_amount;
            $message = "Court fee: NOT refunded\nCoach fee: 50% refunded (RM " . number_format($coach_refund, 2) . ")\nAdd-ons: FULLY refunded (RM " . number_format($addons_total, 2) . ")\nTotal refund: RM " . number_format($refund_amount, 2);
        } else {
            $refund_amount = $addons_total;
            $cancellation_fee = $booking['total_price'] - $refund_amount;
            $message = "Court fee: NOT refunded\nAdd-ons: FULLY refunded (RM " . number_format($addons_total, 2) . ")\nTotal refund: RM " . number_format($refund_amount, 2);
        }
        
    } elseif ($hours_until_booking >= 1) {
        if ($has_coach) {
            $refund_amount = $addons_total;
            $cancellation_fee = $booking['total_price'] - $addons_total;
            $message = "Court fee: NOT refunded\nCoach fee: NOT refunded\nAdd-ons: FULLY refunded (RM " . number_format($addons_total, 2) . ")\nTotal refund: RM " . number_format($refund_amount, 2);
        } else {
            $refund_amount = 0;
            $cancellation_fee = $booking['total_price'];
            $message = "No refund will be issued.";
        }
        
    } else {
        $refund_amount = 0;
        $cancellation_fee = $booking['total_price'];
        $message = "No refund will be issued.";
    }

    // 应用第2次取消罚款
    if ($cancellation_count >= 1 && $refund_amount > 0) {
        $penalty = 5.00;
        $refund_amount = max(0, $refund_amount - $penalty);
        $cancellation_fee = $cancellation_fee + $penalty;
        $message .= "\n\nAdditional RM 5.00 penalty applied (2nd+ cancellation).";
    }

    $refund_amount = min($refund_amount, $actual_paid);

    $pdo->beginTransaction();
    
    $update = $pdo->prepare("UPDATE bookings SET status = 'Cancelled', cancellation_fee = ? WHERE id = ?");
    $update->execute([$cancellation_fee, $booking_id]);
    
    try {
        $checkCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'cancellation_count'");
        if($checkCol && $checkCol->rowCount() > 0) {
            $update_cancel_count = $pdo->prepare("UPDATE users SET cancellation_count = COALESCE(cancellation_count, 0) + 1 WHERE id = ?");
            $update_cancel_count->execute([$_SESSION['user_id']]);
        }
    } catch(PDOException $e) {
        // 忽略
    }
    
    if ($refund_amount > 0) {
        $stmt_user = $pdo->prepare("SELECT wallet_balance, loyalty_points FROM users WHERE id = ?");
        $stmt_user->execute([$_SESSION['user_id']]);
        $user_row = $stmt_user->fetch();
        $new_balance = $user_row['wallet_balance'] + $refund_amount;
        $points_to_deduct = floor($refund_amount);
        $new_points = max(0, ($user_row['loyalty_points'] ?? 0) - $points_to_deduct);

        $update_wallet = $pdo->prepare("UPDATE users SET wallet_balance = ?, loyalty_points = ? WHERE id = ?");
        $update_wallet->execute([$new_balance, $new_points, $_SESSION['user_id']]);
        
        $refund_transaction_id = 'REF_' . time() . '_' . $booking_id;
        $stmt_payment = $pdo->prepare("
            INSERT INTO payments (booking_id, amount, final_amount, payment_method, payment_status, transaction_id, payment_date) 
            VALUES (?, ?, ?, 'Refund', 'success', ?, NOW())
        ");
        $stmt_payment->execute([$booking_id, $cancellation_fee, $refund_amount, $refund_transaction_id]);
    }
    
    $pdo->commit();

    $date_formatted = date('M j, Y', strtotime($booking['booking_date']));
    $time_formatted = date('h:i A', strtotime($booking['start_time'])) . ' - ' . date('h:i A', strtotime($booking['end_time']));
    
    // ========== 发送邮件给用户（HTML格式，Outlook兼容） ==========
    $userSubject = "Booking Cancelled - #" . $booking_id;
    
    $refund_html = '';
    if ($refund_amount > 0) {
        $refund_html = "
            <div style='background: #e8f5e8; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center; border: 1px solid #c8e6c9;'>
                <p style='color: #2e7d32; font-size: 14px; font-weight: bold; margin: 0;'>Refund Amount: RM " . number_format($refund_amount, 2) . "</p>
                <p style='color: #2e7d32; font-size: 12px; margin: 5px 0 0;'>Amount has been credited to your wallet</p>
            </div>";
    }
    
    $userBody = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <title>Booking Cancelled</title>
</head>
<body style='margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f0f2f0;'>
    <div style='max-width: 550px; margin: 0 auto; padding: 20px;'>
        <div style='background: white; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>
            
            <div style='background: #dc2626; padding: 20px 20px; text-align: center;'>
                <h1 style='color: white; font-size: 22px; font-weight: bold; margin: 0;'>Smash Arena</h1>
                <p style='color: #fccccc; font-size: 13px; margin: 5px 0 0 0;'>Booking Cancelled</p>
            </div>
            
            <div style='padding: 25px;'>
                <p style='color: #333; font-size: 14px; margin: 0 0 10px 0;'>
                    Dear <strong style='color: #dc2626;'>{$booking['user_name']}</strong>,
                </p>
                <p style='color: #666; font-size: 13px; margin: 0 0 20px 0;'>
                    Your booking has been successfully cancelled.
                </p>
                
                <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                            <span style='color: #666; font-size: 13px;'>Court:</span>
                         </td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                            <span style='color: #333; font-weight: 500;'>{$booking['court_name']}</span>
                         </td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                            <span style='color: #666; font-size: 13px;'>Date:</span>
                         </td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                            <span style='color: #333; font-weight: 500;'>{$date_formatted}</span>
                         </td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                            <span style='color: #666; font-size: 13px;'>Time:</span>
                         </td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                            <span style='color: #333; font-weight: 500;'>{$time_formatted}</span>
                         </td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                            <span style='color: #666; font-size: 13px;'>Duration:</span>
                         </td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                            <span style='color: #333; font-weight: 500;'>{$booking['total_hours']} hour(s)</span>
                         </td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0;'>
                            <span style='color: #666; font-size: 13px;'>Booking ID:</span>
                         </td>
                        <td style='padding: 10px 0; text-align: right;'>
                            <span style='color: #999; font-size: 12px;'>#{$booking_id}</span>
                         </td>
                    </tr>
                </table>
                
                {$refund_html}
                
                <hr style='border: none; border-top: 1px solid #eef3ea; margin: 25px 0 15px 0;'>
                
                <div style='text-align: center;'>
                    <p style='color: #999; font-size: 11px; margin: 0;'>Smash Arena - Your Game, Our Court</p>
                    <p style='color: #bbb; font-size: 10px; margin: 5px 0 0;'>Need help? Contact us at support@smasharena.com</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
    
    queueEmail($booking['user_email'], $userSubject, $userBody, true);
    
    // ========== 发送邮件给 Admin（HTML格式） ==========
    $stmt_admins = $pdo->prepare("SELECT email FROM admins WHERE role IN ('Superadmin', 'Admin') AND status = 'Active'");
    $stmt_admins->execute();
    $admins = $stmt_admins->fetchAll();
    
    $adminSubject = "Booking Cancelled - #" . $booking_id;
    
    $adminBody = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <title>Booking Cancelled</title>
</head>
<body style='margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f0f2f0;'>
    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
        <div style='background: white; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>
            
            <div style='background: #dc2626; padding: 20px 20px; text-align: center;'>
                <h1 style='color: white; font-size: 22px; font-weight: bold; margin: 0;'>Smash Arena</h1>
                <p style='color: #fccccc; font-size: 13px; margin: 5px 0 0 0;'>Booking Cancelled by Customer</p>
            </div>
            
            <div style='padding: 25px;'>
                <p style='color: #333; font-size: 14px; margin: 0 0 10px 0;'>
                    Booking <strong>#{$booking_id}</strong> has been <span style='color: #dc2626; font-weight: bold;'>CANCELLED</span> by customer.
                </p>
                
                <hr style='border: none; border-top: 1px solid #eef3ea; margin: 20px 0;'>
                
                <h3 style='color: #333; font-size: 15px; margin: 0 0 15px 0;'>Customer Information</h3>
                <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                    <tr>
                        <td style='padding: 8px 0; border-bottom: 1px solid #eef3ea;'>
                            <span style='color: #666; font-size: 13px;'>Name:</span>
                         </td>
                        <td style='padding: 8px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                            <span style='color: #333; font-weight: 500;'>{$booking['user_name']}</span>
                         </td>
                    </tr>
                    <tr>
                        <td style='padding: 8px 0; border-bottom: 1px solid #eef3ea;'>
                            <span style='color: #666; font-size: 13px;'>Email:</span>
                         </td>
                        <td style='padding: 8px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                            <span style='color: #333;'>{$booking['user_email']}</span>
                         </td>
                    </tr>
                </table>
                
                <h3 style='color: #333; font-size: 15px; margin: 20px 0 15px 0;'>Booking Details</h3>
                <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                            <span style='color: #666; font-size: 13px;'>Court:</span>
                         </td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                            <span style='color: #333; font-weight: 500;'>{$booking['court_name']}</span>
                         </td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                            <span style='color: #666; font-size: 13px;'>Date:</span>
                         </td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                            <span style='color: #333; font-weight: 500;'>{$date_formatted}</span>
                         </td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                            <span style='color: #666; font-size: 13px;'>Time:</span>
                         </td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                            <span style='color: #333; font-weight: 500;'>{$time_formatted}</span>
                         </td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                            <span style='color: #666; font-size: 13px;'>Duration:</span>
                         </td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                            <span style='color: #333; font-weight: 500;'>{$booking['total_hours']} hour(s)</span>
                         </td>
                    </tr>
                    " . ($has_coach ? "<tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                            <span style='color: #666; font-size: 13px;'>Coach:</span>
                          </td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                            <span style='color: #333; font-weight: 500;'>Yes (Coach ID: {$booking['coach_id']})</span>
                          </td>
                    </tr>" : "") . "
                 </table>
                
                <div style='background: #fef3e0; border-radius: 8px; padding: 15px; margin: 20px 0; text-align: center; border: 1px solid #fdebd0;'>
                    <p style='color: #b85c00; font-size: 14px; font-weight: bold; margin: 0;'>Refund Amount: RM " . number_format($refund_amount, 2) . "</p>
                </div>
                
                <hr style='border: none; border-top: 1px solid #eef3ea; margin: 25px 0 15px 0;'>
                
                <div style='text-align: center; margin-top: 20px;'>
                    <p style='color: #999; font-size: 11px; margin: 0;'>Smash Arena - Your Game, Our Court</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
    
    foreach ($admins as $admin) {
        if (!empty($admin['email'])) {
            queueEmail($admin['email'], $adminSubject, $adminBody, true);
        }
    }
    
    // ========== 获取教练邮箱并发送邮件（HTML格式） ==========
    if ($has_coach && $booking['coach_id'] > 0) {
        $stmt_coach = $pdo->prepare("
            SELECT a.email, a.username as coach_name
            FROM coaches c
            JOIN admins a ON c.admin_id = a.id
            WHERE c.id = ?
        ");
        $stmt_coach->execute([$booking['coach_id']]);
        $coach = $stmt_coach->fetch();
        
        if ($coach && !empty($coach['email'])) {
            $coachSubject = "Session Cancelled - #" . $booking_id;
            
            $coachBody = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <title>Session Cancelled</title>
</head>
<body style='margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f0f2f0;'>
    <div style='max-width: 550px; margin: 0 auto; padding: 20px;'>
        <div style='background: white; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>
            
            <div style='background: #dc2626; padding: 20px 20px; text-align: center;'>
                <h1 style='color: white; font-size: 22px; font-weight: bold; margin: 0;'>Smash Arena</h1>
                <p style='color: #fccccc; font-size: 13px; margin: 5px 0 0 0;'>Session Cancelled</p>
            </div>
            
            <div style='padding: 25px;'>
                <p style='color: #333; font-size: 14px; margin: 0 0 10px 0;'>
                    Dear <strong style='color: #dc2626;'>{$coach['coach_name']}</strong>,
                </p>
                <p style='color: #666; font-size: 13px; margin: 0 0 20px 0;'>
                    Your coaching session has been cancelled by the customer.
                </p>
                
                <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                            <span style='color: #666; font-size: 13px;'>Court:</span>
                         </td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                            <span style='color: #333; font-weight: 500;'>{$booking['court_name']}</span>
                         </td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                            <span style='color: #666; font-size: 13px;'>Date:</span>
                         </td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                            <span style='color: #333; font-weight: 500;'>{$date_formatted}</span>
                         </td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                            <span style='color: #666; font-size: 13px;'>Time:</span>
                         </td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                            <span style='color: #333; font-weight: 500;'>{$time_formatted}</span>
                         </td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                            <span style='color: #666; font-size: 13px;'>Duration:</span>
                         </td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                            <span style='color: #333; font-weight: 500;'>{$booking['coach_hours']} hour(s)</span>
                         </td>
                    </tr>
                    <tr>
                        <td style='padding: 10px 0;'>
                            <span style='color: #666; font-size: 13px;'>Booking ID:</span>
                         </td>
                        <td style='padding: 10px 0; text-align: right;'>
                            <span style='color: #999; font-size: 12px;'>#{$booking_id}</span>
                         </td>
                    </tr>
                </table>
                
                <hr style='border: none; border-top: 1px solid #eef3ea; margin: 25px 0 15px 0;'>
                
                <div style='text-align: center; margin-top: 20px;'>
                    <p style='color: #999; font-size: 11px; margin: 0;'>Smash Arena - Your Game, Our Court</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
            
            queueEmail($coach['email'], $coachSubject, $coachBody, true);
        } else {
            error_log("Coach email not found for coach_id: " . $booking['coach_id']);
        }
    }
    
    // 数据库通知
    $notificationMessage = "Booking #{$booking_id} at {$booking['court_name']} has been cancelled. Refund: RM " . number_format($refund_amount, 2);
    createNotification('Admin', NULL, 'cancelled', 'Booking Cancelled', $notificationMessage, $booking_id, 'booking');

    if ($has_coach && $booking['coach_id'] > 0) {
        createNotification('Coach', $booking['coach_id'], 'cancelled', 'Session Cancelled', 
            "Your coaching session at {$booking['court_name']} has been cancelled.", $booking_id, 'booking');
    }

    echo json_encode(['success' => true, 'message' => "Booking cancelled successfully.\n\n" . $message, 'refund_amount' => $refund_amount]);

} catch(Exception $e) {
    if(isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

// ============================================================
// EMAIL QUEUE FUNCTION - Store emails for background sending
// ============================================================
function queueEmail($to, $subject, $body, $isHTML = false) {
    global $pdo;
    try {
        $checkTable = $pdo->query("SHOW TABLES LIKE 'email_queue'");
        if ($checkTable->rowCount() == 0) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `email_queue` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `to_email` varchar(255) NOT NULL,
                  `subject` varchar(255) NOT NULL,
                  `body` text NOT NULL,
                  `is_html` tinyint(1) DEFAULT 0,
                  `status` enum('pending','sent','failed') DEFAULT 'pending',
                  `retry_count` int(11) DEFAULT 0,
                  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
                  `sent_at` timestamp NULL DEFAULT NULL,
                  PRIMARY KEY (`id`),
                  KEY `idx_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
            ");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO email_queue (to_email, subject, body, is_html, status, created_at) 
            VALUES (?, ?, ?, ?, 'pending', NOW())
        ");
        return $stmt->execute([$to, $subject, $body, $isHTML ? 1 : 0]);
    } catch (Exception $e) {
        error_log("Queue email error: " . $e->getMessage());
        return false;
    }
}

// ============================================================
// CREATE NOTIFICATION FUNCTION
// ============================================================
function createNotification($recipient_role, $recipient_id, $type, $title, $message, $reference_id, $reference_type) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (recipient_role, recipient_id, type, title, message, reference_id, reference_type, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([$recipient_role, $recipient_id, $type, $title, $message, $reference_id, $reference_type]);
    } catch(PDOException $e) {
        // 忽略通知错误
    }
}
?>