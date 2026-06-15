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
$new_date = $data['new_date'] ?? '';
$new_time = $data['new_time'] ?? '';

if(!$booking_id || !$new_date || !$new_time) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    // 检查预订是否存在且属于当前用户
    $stmt = $pdo->prepare("
        SELECT b.*, c.court_name, c.court_type, 
               u.name as user_name, u.email as user_email,
               b.coach_id, b.coach_hours, b.coach_price_total
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

    // 检查状态
    if($booking['status'] == 'Cancelled') {
        echo json_encode(['success' => false, 'message' => 'Cancelled bookings cannot be rescheduled']);
        exit;
    }

    if($booking['status'] == 'Completed') {
        echo json_encode(['success' => false, 'message' => 'Completed bookings cannot be rescheduled']);
        exit;
    }

    // 检查是否已经改期过
    $reschedule_count = $booking['reschedule_count'] ?? 0;
    if($reschedule_count >= 1) {
        echo json_encode(['success' => false, 'message' => 'This booking has already been rescheduled. Each booking can only be rescheduled once.']);
        exit;
    }

    // 计算距离预订开始的小时数
    $booking_datetime = $booking['booking_date'] . ' ' . $booking['start_time'];
    $booking_timestamp = strtotime($booking_datetime);
    $current_timestamp = time();
    $hours_until_booking = ($booking_timestamp - $current_timestamp) / 3600;

    if ($hours_until_booking < 24) {
        echo json_encode(['success' => false, 'message' => 'Reschedule not allowed! Rescheduling is only allowed at least 24 hours before your booking time. Your booking starts in ' . round($hours_until_booking, 1) . ' hours.']);
        exit;
    }

    $min_date = date('Y-m-d', strtotime('+1 day'));
    if ($new_date < $min_date) {
        echo json_encode(['success' => false, 'message' => 'Please select a date from tomorrow onwards']);
        exit;
    }

    $new_end_time = date('H:i:s', strtotime($new_time) + ($booking['total_hours'] * 3600));

    // 检查新时间是否可用
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

    $old_date = $booking['booking_date'];
    $old_start_time = $booking['start_time'];
    $old_end_time = $booking['end_time'];
    
    $pdo->beginTransaction();
    
    $update = $pdo->prepare("
        UPDATE bookings 
        SET booking_date = ?, start_time = ?, end_time = ?
        WHERE id = ?
    ");
    $update->execute([$new_date, $new_time, $new_end_time, $booking_id]);
    
    // 更新改期次数
    $update2 = $pdo->prepare("UPDATE bookings SET reschedule_count = COALESCE(reschedule_count, 0) + 1 WHERE id = ?");
    $update2->execute([$booking_id]);
    
    $pdo->commit();

    $old_date_formatted = date('M j, Y', strtotime($old_date));
    $new_date_formatted = date('M j, Y', strtotime($new_date));
    $old_time_formatted = date('h:i A', strtotime($old_start_time)) . ' - ' . date('h:i A', strtotime($old_end_time));
    $new_time_formatted = date('h:i A', strtotime($new_time)) . ' - ' . date('h:i A', strtotime($new_end_time));
    
    // ========== 发送邮件给用户（HTML格式，Outlook兼容） ==========
    $userSubject = "Booking Rescheduled - #" . $booking_id;
    
    $userBody = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <title>Booking Rescheduled</title>
</head>
<body style='margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f0f2f0;'>
    <div style='max-width: 550px; margin: 0 auto; padding: 20px;'>
        <div style='background: white; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>
            
            <div style='background: #e67e22; padding: 20px 20px; text-align: center;'>
                <h1 style='color: white; font-size: 22px; font-weight: bold; margin: 0;'>Smash Arena</h1>
                <p style='color: #fdebd0; font-size: 13px; margin: 5px 0 0 0;'>Booking Rescheduled</p>
            </div>
            
            <div style='padding: 25px;'>
                <p style='color: #333; font-size: 14px; margin: 0 0 10px 0;'>
                    Dear <strong style='color: #e67e22;'>{$booking['user_name']}</strong>,
                </p>
                <p style='color: #666; font-size: 13px; margin: 0 0 20px 0;'>
                    Your booking has been successfully rescheduled. Here are the updated details:
                </p>
                
                <div style='background: #fef3e0; border-radius: 8px; padding: 15px; margin-bottom: 20px; border: 1px solid #fdebd0;'>
                    <p style='color: #b85c00; font-size: 12px; margin: 0 0 10px 0; font-weight: bold;'>[CANCELLED] Old Schedule</p>
                    <p style='color: #b85c00; font-size: 13px; margin: 0;'>Date: {$old_date_formatted}</p>
                    <p style='color: #b85c00; font-size: 13px; margin: 5px 0 0;'>Time: {$old_time_formatted}</p>
                </div>
                
                <div style='background: #e8f5e8; border-radius: 8px; padding: 15px; margin-bottom: 20px; border: 1px solid #c8e6c9;'>
                    <p style='color: #2e7d32; font-size: 12px; margin: 0 0 10px 0; font-weight: bold;'>[NEW] Updated Schedule</p>
                    <p style='color: #2e7d32; font-size: 13px; margin: 0;'>Date: {$new_date_formatted}</p>
                    <p style='color: #2e7d32; font-size: 13px; margin: 5px 0 0;'>Time: {$new_time_formatted}</p>
                </div>
                
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
    
    // ========== 获取教练邮箱并发送邮件（HTML格式） ==========
    $has_coach = ($booking['coach_id'] && $booking['coach_id'] > 0 && $booking['coach_hours'] > 0);
    
    if ($has_coach) {
        $stmt_coach_email = $pdo->prepare("
            SELECT a.email, a.username as coach_name
            FROM coaches c
            JOIN admins a ON c.admin_id = a.id
            WHERE c.id = ?
        ");
        $stmt_coach_email->execute([$booking['coach_id']]);
        $coach_data = $stmt_coach_email->fetch();
        
        if ($coach_data && !empty($coach_data['email'])) {
            $coachEmail = $coach_data['email'];
            $coachName = $coach_data['coach_name'];
            
            $coachSubject = "Session Rescheduled - #" . $booking_id;
            
            $coachBody = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <title>Session Rescheduled</title>
</head>
<body style='margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f0f2f0;'>
    <div style='max-width: 550px; margin: 0 auto; padding: 20px;'>
        <div style='background: white; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>
            
            <div style='background: #e67e22; padding: 20px 20px; text-align: center;'>
                <h1 style='color: white; font-size: 22px; font-weight: bold; margin: 0;'>Smash Arena</h1>
                <p style='color: #fdebd0; font-size: 13px; margin: 5px 0 0 0;'>Session Rescheduled</p>
            </div>
            
            <div style='padding: 25px;'>
                <p style='color: #333; font-size: 14px; margin: 0 0 10px 0;'>
                    Dear <strong style='color: #e67e22;'>{$coachName}</strong>,
                </p>
                <p style='color: #666; font-size: 13px; margin: 0 0 20px 0;'>
                    Your coaching session has been rescheduled by the customer. Here are the updated details:
                </p>
                
                <div style='background: #fef3e0; border-radius: 8px; padding: 15px; margin-bottom: 20px; border: 1px solid #fdebd0;'>
                    <p style='color: #b85c00; font-size: 12px; margin: 0 0 10px 0; font-weight: bold;'>[CANCELLED] Old Schedule</p>
                    <p style='color: #b85c00; font-size: 13px; margin: 0;'>Date: {$old_date_formatted}</p>
                    <p style='color: #b85c00; font-size: 13px; margin: 5px 0 0;'>Time: {$old_time_formatted}</p>
                </div>
                
                <div style='background: #e8f5e8; border-radius: 8px; padding: 15px; margin-bottom: 20px; border: 1px solid #c8e6c9;'>
                    <p style='color: #2e7d32; font-size: 12px; margin: 0 0 10px 0; font-weight: bold;'>[NEW] Updated Schedule</p>
                    <p style='color: #2e7d32; font-size: 13px; margin: 0;'>Date: {$new_date_formatted}</p>
                    <p style='color: #2e7d32; font-size: 13px; margin: 5px 0 0;'>Time: {$new_time_formatted}</p>
                </div>
                
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
                            <span style='color: #666; font-size: 13px;'>Customer:</span>
                         </td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                            <span style='color: #333; font-weight: 500;'>{$booking['user_name']}</span>
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
                
                <div style='text-align: center;'>
                    <p style='color: #999; font-size: 11px; margin: 0;'>Smash Arena - Your Game, Our Court</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>";
            
            queueEmail($coachEmail, $coachSubject, $coachBody, true);
            
            createNotification('Coach', $booking['coach_id'], 'rescheduled', 'Session Rescheduled', 
                "Your coaching session has been rescheduled to {$new_date_formatted} {$new_time_formatted}", 
                $booking_id, 'booking');
        } else {
            error_log("Coach email not found for coach_id: " . $booking['coach_id']);
        }
    }
    
    // ========== 发送邮件给 Admin（HTML格式） ==========
    $stmt_admins = $pdo->prepare("SELECT email FROM admins WHERE role IN ('Superadmin', 'Admin') AND status = 'Active'");
    $stmt_admins->execute();
    $admins = $stmt_admins->fetchAll();
    
    $adminSubject = "Booking Rescheduled - #" . $booking_id;
    
    $adminBody = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <title>Booking Rescheduled</title>
</head>
<body style='margin: 0; padding: 0; font-family: Arial, Helvetica, sans-serif; background-color: #f0f2f0;'>
    <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
        <div style='background: white; border-radius: 8px; overflow: hidden; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.05);'>
            
            <div style='background: #e67e22; padding: 20px 20px; text-align: center;'>
                <h1 style='color: white; font-size: 22px; font-weight: bold; margin: 0;'>Smash Arena</h1>
                <p style='color: #fdebd0; font-size: 13px; margin: 5px 0 0 0;'>Booking Rescheduled by Customer</p>
            </div>
            
            <div style='padding: 25px;'>
                <p style='color: #333; font-size: 14px; margin: 0 0 10px 0;'>
                    Booking <strong>#{$booking_id}</strong> has been <span style='color: #e67e22; font-weight: bold;'>RESCHEDULED</span> by customer.
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
                            <span style='color: #666; font-size: 13px;'>Duration:</span>
                         </td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                            <span style='color: #333; font-weight: 500;'>{$booking['total_hours']} hour(s)</span>
                         </td>
                    </tr>" . ($has_coach ? "
                    <tr>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea;'>
                            <span style='color: #666; font-size: 13px;'>Coach:</span>
                         </td>
                        <td style='padding: 10px 0; border-bottom: 1px solid #eef3ea; text-align: right;'>
                            <span style='color: #333; font-weight: 500;'>Yes (Coach ID: {$booking['coach_id']})</span>
                         </td>
                    </tr>" : "") . "
                </table>
                
                <div style='background: #fef3e0; border-radius: 8px; padding: 15px; margin: 20px 0; border: 1px solid #fdebd0;'>
                    <p style='color: #b85c00; font-size: 13px; margin: 0;'><strong>[OLD] Old Schedule:</strong> {$old_date_formatted} {$old_time_formatted}</p>
                </div>
                
                <div style='background: #e8f5e8; border-radius: 8px; padding: 15px; margin: 20px 0; border: 1px solid #c8e6c9;'>
                    <p style='color: #2e7d32; font-size: 13px; margin: 0;'><strong>[NEW] New Schedule:</strong> {$new_date_formatted} {$new_time_formatted}</p>
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
    
    // ========== 数据库通知给 Admin 和 Superadmin ==========
    $notificationMessage = "Booking #" . $booking_id . " at " . $booking['court_name'] . " has been RESCHEDULED by customer.\n\n[OLD] Old: " . $old_date_formatted . " " . $old_time_formatted . "\n[NEW] New: " . $new_date_formatted . " " . $new_time_formatted;
    
    createNotification('Admin', NULL, 'rescheduled', 'Booking Rescheduled', $notificationMessage, $booking_id, 'booking');
    createNotification('Superadmin', NULL, 'rescheduled', 'Booking Rescheduled', $notificationMessage, $booking_id, 'booking');

    echo json_encode(['success' => true, 'message' => 'Booking rescheduled successfully! Your new time has been updated.']);
    
} catch(Exception $e) {
    if(isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Failed to reschedule: ' . $e->getMessage()]);
}
?>