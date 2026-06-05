<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

// 开启错误日志
error_reporting(E_ALL);
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

    // 计算新结束时间
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

    // 开始事务
    $pdo->beginTransaction();
    
    // 更新预订 - 保持原状态，不修改价格
    $update = $pdo->prepare("
        UPDATE bookings 
        SET booking_date = ?, start_time = ?, end_time = ?
        WHERE id = ?
    ");
    $update->execute([$new_date, $new_time, $new_end_time, $booking_id]);
    
    // 如果有reschedule_count字段，更新它
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
    
    echo json_encode(['success' => true, 'message' => 'Booking rescheduled successfully! Your new time has been updated.']);
    
} catch(Exception $e) {
    if(isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Failed to reschedule: ' . $e->getMessage()]);
}
?>