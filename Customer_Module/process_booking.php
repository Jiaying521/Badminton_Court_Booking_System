<?php
require_once __DIR__ . '/../config.php';
if(!isLoggedIn()) redirect('homepage.php');

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id = $_SESSION['user_id'];
    $court_id = $_POST['court_id'];
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $total_price = $_POST['price'];
    $total_hours = $_POST['total_hours'];
    $notes = $_POST['notes'] ?? '';
    
    $coach_id = $_POST['coach_id'] ?? 0;
    $coach_hours = $_POST['coach_hours'] ?? 0;
    $coach_price_total = $_POST['coach_price_total'] ?? 0;
    
    $end_time = date('H:i:s', strtotime($start_time) + ($total_hours * 3600));
    
    // 获取场地信息
    $court_stmt = $pdo->prepare("SELECT court_name FROM courts WHERE id = ?");
    $court_stmt->execute([$court_id]);
    $court = $court_stmt->fetch();
    $court_name = $court['court_name'] ?? 'Court';
    
    // 检查时段是否已被预订
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
        // 显示漂亮的错误页面
        $formatted_date = date('F j, Y', strtotime($booking_date));
        $formatted_start = date('h:i A', strtotime($start_time));
        $formatted_end = date('h:i A', strtotime($end_time));
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Time Slot Unavailable | Smash Arena</title>
            <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
            <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
            <style>
                * { margin:0; padding:0; box-sizing:border-box; }
                body {
                    font-family: 'Inter', sans-serif;
                    background: linear-gradient(145deg, #f5f9f0 0%, #e8efe2 100%);
                    min-height: 100vh;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    padding: 2rem;
                }
                .error-container { max-width: 500px; width: 100%; animation: fadeInUp 0.5s ease; }
                @keyframes fadeInUp {
                    from { opacity: 0; transform: translateY(30px); }
                    to { opacity: 1; transform: translateY(0); }
                }
                .error-card { background: white; border-radius: 32px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.1); text-align: center; }
                .error-header { background: linear-gradient(135deg, #e67e22, #d35400); padding: 2rem; color: white; }
                .error-icon { font-size: 4rem; margin-bottom: 1rem; }
                .error-header h1 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.3rem; }
                .error-header p { opacity: 0.9; font-size: 0.9rem; }
                .error-body { padding: 2rem; }
                .error-message { font-size: 1rem; color: #1e2a2e; margin-bottom: 1rem; line-height: 1.5; }
                .booking-details { background: #f8faf5; border-radius: 20px; padding: 1.2rem; margin: 1rem 0; text-align: left; border-left: 4px solid #e67e22; }
                .booking-details h3 { font-size: 0.85rem; color: #888; margin-bottom: 0.8rem; text-transform: uppercase; letter-spacing: 1px; }
                .detail-row { display: flex; align-items: center; gap: 0.8rem; padding: 0.5rem 0; font-size: 0.9rem; }
                .detail-row i { width: 24px; color: #2b7e3a; }
                .detail-row span:last-child { color: #1e2a2e; font-weight: 500; }
                .suggestion { background: #fff8e1; border-radius: 16px; padding: 1rem; margin: 1rem 0; font-size: 0.85rem; color: #856404; text-align: left; }
                .suggestion i { margin-right: 0.5rem; }
                .btn-back { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; background: #2b7e3a; color: white; border: none; padding: 0.9rem 1.5rem; border-radius: 50px; font-size: 1rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s; width: 100%; margin-top: 1rem; }
                .btn-back:hover { background: #1f5a2a; transform: translateY(-2px); box-shadow: 0 6px 14px rgba(43,126,58,0.3); }
                .btn-dashboard { display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; background: transparent; border: 1.5px solid #2b7e3a; color: #2b7e3a; padding: 0.8rem 1.5rem; border-radius: 50px; font-size: 0.9rem; font-weight: 500; cursor: pointer; text-decoration: none; transition: all 0.2s; width: 100%; margin-top: 0.8rem; }
                .btn-dashboard:hover { background: #eaf5e6; transform: translateY(-2px); }
                @media (max-width: 768px) { body { padding: 1rem; } .error-body { padding: 1.5rem; } }
            </style>
        </head>
        <body>
        <div class="error-container">
            <div class="error-card">
                <div class="error-header">
                    <div class="error-icon"><i class="fas fa-calendar-times"></i></div>
                    <h1>Time Slot Unavailable</h1>
                    <p>This time has already been booked</p>
                </div>
                <div class="error-body">
                    <div class="error-message">
                        Sorry, the time slot you selected is no longer available.
                    </div>
                    <div class="booking-details">
                        <h3><i class="fas fa-info-circle"></i> Your Selection</h3>
                        <div class="detail-row">
                            <i class="fas fa-shuttlecock"></i>
                            <span><?php echo htmlspecialchars($court_name); ?></span>
                        </div>
                        <div class="detail-row">
                            <i class="fas fa-calendar-alt"></i>
                            <span><?php echo $formatted_date; ?></span>
                        </div>
                        <div class="detail-row">
                            <i class="fas fa-clock"></i>
                            <span><?php echo $formatted_start; ?> - <?php echo $formatted_end; ?> (<?php echo $total_hours; ?> hour<?php echo $total_hours > 1 ? 's' : ''; ?>)</span>
                        </div>
                    </div>
                    <div class="suggestion">
                        <i class="fas fa-lightbulb"></i>
                        <strong>Suggestions:</strong><br>
                        • Try selecting a different time slot<br>
                        • Choose another date for your booking<br>
                        • Contact us if you need assistance
                    </div>
                    <a href="book_court.php?court_id=<?php echo $court_id; ?>" class="btn-back">
                        <i class="fas fa-arrow-left"></i> Try Different Time
                    </a>
                    <a href="dashboard.php" class="btn-dashboard">
                        <i class="fas fa-home"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
        </body>
        </html>
        <?php
        exit;
    }
    
    // 插入预订
    $stmt = $pdo->prepare("
        INSERT INTO bookings (
            user_id, court_id, booking_date, start_time, end_time, 
            total_hours, total_price, coach_id, coach_hours, coach_price_total,
            status, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?)
    ");
    $stmt->execute([
        $user_id, $court_id, $booking_date, $start_time, $end_time,
        $total_hours, $total_price, $coach_id, $coach_hours, $coach_price_total,
        $notes
    ]);
    
    $booking_id = $pdo->lastInsertId();
    
    header("Location: addons.php?booking_id=$booking_id");
    exit;
}
?>