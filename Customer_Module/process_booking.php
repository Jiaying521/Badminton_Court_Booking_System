<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';

if(!isLoggedIn()) redirect('homepage.php');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $court_id = $_POST['court_id'] ?? 0;
    $booking_date = $_POST['booking_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $total_price = $_POST['price'] ?? 0;
    $total_hours = $_POST['total_hours'] ?? 1;
    $notes = $_POST['notes'] ?? '';
    
    $coach_id = $_POST['coach_id'] ?? 0;
    $coach_hours = $_POST['coach_hours'] ?? 0;
    $coach_price_total = $_POST['coach_price_total'] ?? 0;
    
    if(!$court_id || !$booking_date || !$start_time) {
        $_SESSION['error'] = 'Missing required booking information';
        redirect('dashboard.php');
    }
    
    $end_time = date('H:i:s', strtotime($start_time) + ($total_hours * 3600));
    
    // get court details
    $court_stmt = $pdo->prepare("SELECT court_name, court_type FROM courts WHERE id = ?");
    $court_stmt->execute([$court_id]);
    $court = $court_stmt->fetch();
    $court_name = $court['court_name'] ?? 'Court';
    $court_type = $court['court_type'] ?? 'Standard';
    
    // get user details
    $user_stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $user_stmt->execute([$user_id]);
    $user = $user_stmt->fetch();
    $user_name = $user['name'] ?? 'Customer';
    $user_email = $user['email'] ?? '';
    
    // check if the time slot is available
    $check = $pdo->prepare("
        SELECT id FROM bookings 
        WHERE court_id = ? AND booking_date = ? AND status NOT IN ('Cancelled')
        AND (
            (start_time < ? AND end_time > ?) OR
            (start_time < ? AND end_time > ?) OR
            (start_time >= ? AND start_time < ?)
        )
    ");
    $check->execute([$court_id, $booking_date, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time]);
    
    if($check->fetch()) {
        $_SESSION['error'] = 'The selected time slot is no longer available';
        redirect('book_court.php?court_id=' . $court_id);
    }
    
    try {
        $pdo->beginTransaction();
        
        $stmt = $pdo->prepare("
            INSERT INTO bookings (
                user_id, court_id, booking_date, start_time, end_time, 
                total_hours, total_price, coach_id, coach_hours, coach_price_total,
                status, notes, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, NOW())
        ");
        $stmt->execute([
            $user_id, $court_id, $booking_date, $start_time, $end_time,
            $total_hours, $total_price, $coach_id, $coach_hours, $coach_price_total,
            $notes
        ]);
        
        $booking_id = $pdo->lastInsertId();
        $pdo->commit();
        
        // only redirect to the add-ons page, do not send any emails
        header("Location: addons.php?booking_id=" . $booking_id);
        exit;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $_SESSION['error'] = 'Failed to create booking: ' . $e->getMessage();
        redirect('book_court.php?court_id=' . $court_id);
    }
}
?>