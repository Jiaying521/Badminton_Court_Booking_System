<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';
if(!isLoggedIn()) redirect('homepage.php');

$user_id = $_SESSION['user_id'];

// 直接使用 functions.php 中的 getSetting() 函数
$cancellation_hours = getSetting('cancellation_hours', '2');
$cancellation_fee = getSetting('cancellation_fee', '10.00');

// 获取用户所有预订记录
$stmt = $pdo->prepare("
    SELECT b.*, c.court_name, c.court_type, c.location,
           co.name as coach_name
    FROM bookings b 
    JOIN courts c ON b.court_id = c.id 
    LEFT JOIN coaches co ON b.coach_id = co.id
    WHERE b.user_id = ? 
    ORDER BY b.booking_date DESC, b.start_time DESC
");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

// 获取用户信息（包含头像字段）
$userStmt = $pdo->prepare("SELECT name, profile_picture FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch();

// 获取用户头像
$profile_picture = isset($user['profile_picture']) ? $user['profile_picture'] : '';
$defaultAvatarPath = '../image/default_image.png';
$avatarPath = $defaultAvatarPath;

if (!empty($profile_picture)) {
    $fullPath = __DIR__ . '/../' . $profile_picture;
    if (file_exists($fullPath)) {
        $fileTime = filemtime($fullPath);
        $avatarPath = '../' . $profile_picture . '?v=' . $fileTime;
    }
}

// 确保默认头像存在
$defaultAvatarFullPath = __DIR__ . '/../image/default_image.png';
if (!file_exists($defaultAvatarFullPath)) {
    $imageDir = __DIR__ . '/../image/';
    if (!file_exists($imageDir)) {
        mkdir($imageDir, 0777, true);
    }
    $sourcePath = __DIR__ . '/../Pictures/Admin_Module/coaches/default.png';
    if (file_exists($sourcePath)) {
        copy($sourcePath, $defaultAvatarFullPath);
    }
}

// 获取取消次数（兼容旧数据库）
$cancellation_count = 0;
try {
    $checkCol = $pdo->query("SHOW COLUMNS FROM users LIKE 'cancellation_count'");
    if($checkCol->rowCount() > 0) {
        $stmt_cancel = $pdo->prepare("SELECT cancellation_count FROM users WHERE id = ?");
        $stmt_cancel->execute([$user_id]);
        $cancel_data = $stmt_cancel->fetch();
        $cancellation_count = $cancel_data['cancellation_count'] ?? 0;
    }
} catch(PDOException $e) {
    $cancellation_count = 0;
}
$user['cancellation_count'] = $cancellation_count;

// 获取钱包余额
$stmt_bal = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$stmt_bal->execute([$user_id]);
$balance_row = $stmt_bal->fetch();
$real_balance = $balance_row['wallet_balance'] ?? 0.00;

// 获取系统设置用于 footer 显示
$open_time = getSetting('open_time', '08:00');
$close_time = getSetting('close_time', '01:00');
$peak_start = getSetting('peak_start', '15:00');
$off_peak_price = getSetting('off_peak_price', '10');
$peak_price = getSetting('peak_price', '15');

// 格式化时间显示
$open_time_display = date('h:i A', strtotime($open_time));
$close_time_display = date('h:i A', strtotime($close_time));
$peak_start_display = date('h:i A', strtotime($peak_start));
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Bookings | Smash Arena</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        
        body { 
            font-family: 'Inter', 'Poppins', 'Montserrat', sans-serif; 
            background: radial-gradient(circle at 10% 20%, rgba(240,245,236,1) 0%, rgba(226,236,217,1) 100%);
            color: #1e2a2e; 
            padding: 2rem;
            min-height: 100vh;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(rgba(43,126,58,0.08) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }
        
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            position: relative;
            z-index: 1;
        }
        
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #e0e8dc; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #2b7e3a; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #1f5a2a; }
        
        /* Glassmorphism Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(15px);
            padding: 0.8rem 1.8rem;
            border-radius: 80px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.05);
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeInDown 0.6s ease-out;
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .logo-area { 
            display: flex; 
            align-items: center; 
            gap: 0.8rem; 
            text-decoration: none; 
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .logo-area::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #2b7e3a, #e67e22);
            transition: width 0.4s ease;
        }
        
        .logo-area:hover::after { width: 100%; }
        .logo-area:hover .logo-text { transform: scale(1.02); }
        .logo-area img { 
            height: 45px; 
            width: auto; 
            transition: transform 0.3s ease;
        }
        .logo-area:hover img { transform: scale(1.02) rotate(5deg); }
        .logo-text { 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-size: 1.5rem; 
            font-weight: 800; 
            background: linear-gradient(135deg, #2b7e3a 0%, #e67e22 80%);
            -webkit-background-clip: text; 
            background-clip: text; 
            color: transparent;
            letter-spacing: -1px;
            text-transform: uppercase;
        }
        .logo-text span { 
            background: linear-gradient(135deg, #e67e22 0%, #f39c12 100%); 
            -webkit-background-clip: text; 
            background-clip: text; 
            color: transparent;
        }
        
        .nav-links { 
            display: flex; 
            align-items: center; 
            gap: 0.5rem; 
            flex-wrap: wrap; 
        }
        .nav-links a { 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 600;
            letter-spacing: 0.2px;
            color: #2c4a2e; 
            text-decoration: none; 
            transition: all 0.3s ease;
            padding: 0.5rem 1.2rem;
            border-radius: 50px;
            position: relative;
            overflow: hidden;
        }
        
        .nav-links a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .nav-links a:hover::before { left: 100%; }
        .nav-links a:hover, .nav-links a.active { 
            background: #2b7e3a;
            color: white; 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(43,126,58,0.3);
            border-radius: 50px;
        }
        
        /* 用户头像区域 - 可点击跳转 profile */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            cursor: pointer;
            background: rgba(234,245,230,0.6);
            padding: 0.3rem 1rem 0.3rem 0.5rem;
            border-radius: 50px;
            transition: all 0.3s ease;
            text-decoration: none;
        }
        .user-profile:hover {
            background: rgba(43,126,58,0.15);
            transform: translateY(-2px);
            border-radius: 50px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            overflow: hidden;
            background: #2b7e3a;
        }
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .user-info {
            text-align: left;
        }
        .user-name {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: 0.85rem;
            color: #1e3a2a;
        }
        .user-balance {
            font-family: 'DM Sans', sans-serif;
            font-size: 0.7rem;
            color: #2b7e3a;
            font-weight: 600;
        }
        
        .btn-logout { 
            background: #fee2e2; 
            color: #e67e22; 
            padding: 0.5rem 1.2rem; 
            border-radius: 50px; 
            text-decoration: none; 
            font-size: 0.85rem; 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-logout:hover { 
            background: #e67e22; 
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(230,126,34,0.3);
            border-radius: 50px;
        }
        
        /* Page Header */
        .page-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 1.5rem; 
            flex-wrap: wrap; 
            gap: 1rem; 
            animation: fadeInUp 0.6s ease-out 0.1s both;
        }
        
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .page-header h1 { 
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-weight: 800;
            color: #1e3a2a; 
            font-size: 1.8rem; 
        }
        .page-header h1 i { color: #2b7e3a; margin-right: 0.5rem; }
        
        .btn-book { 
            background: linear-gradient(135deg, #2b7e3a, #1f5a2a);
            color: white; 
            border: none; 
            padding: 0.7rem 1.5rem; 
            border-radius: 60px; 
            cursor: pointer; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.5rem; 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 700; 
            transition: all 0.4s ease;
            box-shadow: 0 4px 12px rgba(43,126,58,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .btn-book::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-book:hover::before { left: 100%; }
        .btn-book:hover { 
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(43,126,58,0.4);
            border-radius: 60px;
        }
        
        /* Warning Banner */
        .warning-banner {
            background: linear-gradient(135deg, #fff3cd, #ffe69e);
            border-left: 5px solid #e67e22;
            padding: 0.8rem 1.2rem;
            border-radius: 16px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
            animation: fadeInScale 0.5s ease-out;
        }
        .warning-banner i {
            font-size: 1.5rem;
            color: #e67e22;
        }
        .warning-banner .text {
            flex: 1;
            color: #856404;
            font-size: 0.9rem;
        }
        .warning-banner .badge {
            background: #e67e22;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-weight: bold;
            font-size: 0.8rem;
        }
        
        /* Wallet Card */
        .wallet-card { 
            background: linear-gradient(135deg, #2b7e3a, #1b5e2a);
            color: white; 
            padding: 0.8rem 1.5rem; 
            border-radius: 60px; 
            display: inline-flex; 
            align-items: center; 
            gap: 1rem; 
            margin-bottom: 1.5rem;
            box-shadow: 0 8px 25px rgba(43,126,58,0.2);
            animation: fadeInScale 0.5s ease-out 0.15s both;
            position: relative;
            overflow: hidden;
        }
        
        .wallet-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.15), transparent);
            animation: pulse 4s ease-in-out infinite;
            pointer-events: none;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .wallet-card i { font-size: 1.2rem; }
        .wallet-card .amount { font-weight: 800; font-size: 1.1rem; }
        
        /* Stats Cards */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 1.2rem; 
            margin-bottom: 2rem; 
        }
        .stat-card { 
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(10px);
            border-radius: 28px; 
            padding: 1.3rem; 
            text-align: center; 
            box-shadow: 0 8px 20px rgba(0,0,0,0.04); 
            transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeInScale 0.5s ease-out both;
        }
        .stat-card:nth-child(1) { animation-delay: 0.05s; }
        .stat-card:nth-child(2) { animation-delay: 0.1s; }
        .stat-card:nth-child(3) { animation-delay: 0.15s; }
        .stat-card:nth-child(4) { animation-delay: 0.2s; }
        
        .stat-card:hover { 
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(43,126,58,0.15);
            background: white;
            border-radius: 28px;
        }
        .stat-number { 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 900;
            font-size: 2rem; 
            color: #2b7e3a; 
        }
        .stat-label { 
            color: #5a6e5c; 
            font-size: 0.85rem; 
            margin-top: 0.3rem;
            font-weight: 500;
        }
        
        /* Filter Tabs */
        .filter-tabs { 
            display: flex; 
            gap: 0.6rem; 
            margin-bottom: 1.5rem; 
            flex-wrap: wrap; 
        }
        .filter-btn { 
            background: rgba(232,240,229,0.8);
            backdrop-filter: blur(5px);
            border: none; 
            padding: 0.6rem 1.3rem; 
            border-radius: 50px; 
            cursor: pointer; 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 600; 
            transition: all 0.3s ease;
        }
        .filter-btn:hover { 
            background: #d0e0c8; 
            transform: translateY(-2px);
            border-radius: 50px;
        }
        .filter-btn.active { 
            background: #2b7e3a; 
            color: white; 
            box-shadow: 0 4px 12px rgba(43,126,58,0.3);
            border-radius: 50px;
        }
        
        /* Bookings Table */
        .bookings-table { 
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(10px);
            border-radius: 28px; 
            overflow-x: auto;
            box-shadow: 0 8px 25px rgba(0,0,0,0.05);
            border: 1px solid rgba(255,255,255,0.3);
        }
        table { width:100%; border-collapse:collapse; }
        th { 
            background: #2b7e3a; 
            color: white; 
            padding: 1rem; 
            text-align: left; 
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
        }
        td { 
            padding: 1rem; 
            border-bottom: 1px solid rgba(224,224,224,0.5); 
            vertical-align: middle;
        }
        tr { transition: background 0.3s; }
        tr:hover { background: rgba(43,126,58,0.05); }
        
        .status { 
            display: inline-block; 
            padding: 0.25rem 0.8rem; 
            border-radius: 50px; 
            font-size: 0.75rem; 
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
        }
        .status-Confirmed { background: #d4edda; color: #155724; }
        .status-Pending { background: #fff3cd; color: #856404; }
        .status-Cancelled { background: #f8d7da; color: #721c24; }
        .status-Completed { background: #cce5ff; color: #004085; }
        
        .court-badge { 
            display: inline-block; 
            background: #eaf5e6; 
            color: #2b7e3a; 
            padding: 0.2rem 0.6rem; 
            border-radius: 20px; 
            font-size: 0.7rem; 
            margin-top: 0.3rem;
            font-weight: 600;
        }
        
        .action-btns { 
            display: flex; 
            gap: 0.6rem; 
            flex-wrap: wrap;
            align-items: center;
        }
        .btn-view { 
            background: rgba(232,240,229,0.8);
            color: #2c4a2e; 
            border: none; 
            padding: 0.4rem 1rem; 
            border-radius: 50px; 
            cursor: pointer; 
            font-size: 0.75rem; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.3rem; 
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .btn-view::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(43,126,58,0.2), transparent);
            transition: left 0.5s ease;
        }
        .btn-view:hover::before { left: 100%; }
        .btn-view:hover { 
            background: #2b7e3a; 
            color: white; 
            transform: translateY(-2px);
            border-radius: 50px;
        }
        
        .btn-pay-now { 
            background: linear-gradient(135deg, #2b7e3a, #1f5a2a);
            color: white; 
            border: none; 
            padding: 0.4rem 1rem; 
            border-radius: 50px; 
            cursor: pointer; 
            font-size: 0.75rem; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.3rem; 
            font-family: 'Montserrat', sans-serif;
            font-weight: 700; 
            transition: all 0.3s ease;
            box-shadow: 0 2px 6px rgba(43,126,58,0.2);
            position: relative;
            overflow: hidden;
        }
        .btn-pay-now::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        .btn-pay-now:hover::before { left: 100%; }
        .btn-pay-now:hover { 
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(43,126,58,0.3);
            border-radius: 50px;
        }

        .btn-cancel { 
            background: #fee2e2; 
            color: #e67e22; 
            border: none; 
            padding: 0.4rem 1rem; 
            border-radius: 50px; 
            cursor: pointer; 
            font-size: 0.75rem; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.3rem; 
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-cancel:hover { 
            background: #e67e22; 
            color: white; 
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(230,126,34,0.3);
            border-radius: 50px;
        }
        .btn-cancel-disabled { 
            background: #e0e0e0; 
            color: #888; 
            border: none; 
            padding: 0.4rem 1rem; 
            border-radius: 50px; 
            cursor: not-allowed; 
            font-size: 0.75rem; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.3rem;
            font-family: 'Montserrat', sans-serif;
        }
        
        .btn-reschedule { 
            background: #e8f0e5; 
            color: #2b7e3a; 
            border: none; 
            padding: 0.4rem 1rem; 
            border-radius: 50px; 
            cursor: pointer; 
            font-size: 0.75rem; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.3rem; 
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-reschedule:hover { 
            background: #2b7e3a; 
            color: white; 
            transform: translateY(-2px);
            border-radius: 50px;
        }
        .btn-reschedule-disabled { 
            background: #e0e0e0; 
            color: #888; 
            border: none; 
            padding: 0.4rem 1rem; 
            border-radius: 50px; 
            cursor: not-allowed; 
            font-size: 0.75rem; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.3rem;
            font-family: 'Montserrat', sans-serif;
        }
        
        /* Modal */
        .modal { 
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background-color: rgba(0,0,0,0.5); 
            backdrop-filter: blur(4px);
        }
        .modal-content { 
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(10px);
            margin: 5% auto; 
            padding: 0; 
            width: 90%; 
            max-width: 500px; 
            border-radius: 32px; 
            overflow: hidden; 
            animation: slideUp 0.3s ease;
            border: 1px solid rgba(255,255,255,0.3);
        }
        @keyframes slideUp { 
            from { opacity: 0; transform: translateY(30px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        .modal-header { 
            background: linear-gradient(135deg, #2b7e3a, #1f5a2a);
            color: white; 
            padding: 1rem 1.5rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center;
        }
        .modal-header h3 { margin: 0; font-family: 'Montserrat', sans-serif; }
        .modal-close { background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; transition: transform 0.3s; }
        .modal-close:hover { transform: rotate(90deg); }
        .modal-body { padding: 1.5rem; max-height: 60vh; overflow-y: auto; }
        .receipt-row { display: flex; justify-content: space-between; padding: 0.6rem 0; border-bottom: 1px solid rgba(224,224,224,0.5); }
        .receipt-total { display: flex; justify-content: space-between; padding: 0.8rem 0 0; margin-top: 0.5rem; border-top: 2px solid #2b7e3a; font-weight: 800; font-size: 1.1rem; color: #2b7e3a; }
        .print-btn { 
            background: linear-gradient(135deg, #2b7e3a, #1f5a2a);
            color: white; 
            border: none; 
            padding: 0.7rem; 
            border-radius: 60px; 
            width: 100%; 
            margin-top: 1rem; 
            cursor: pointer; 
            font-family: 'Montserrat', sans-serif;
            font-weight: 700; 
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .print-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        .print-btn:hover::before { left: 100%; }
        .print-btn:hover { transform: translateY(-3px); box-shadow: 0 8px 20px rgba(43,126,58,0.3); border-radius: 60px; }
        
        /* Reschedule Modal specific */
        .form-group { margin-bottom: 1rem; }
        .form-group label { 
            display: block; 
            font-weight: 600; 
            margin-bottom: 0.5rem; 
            color: #1e3a2a;
            font-size: 0.85rem;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.7rem 1rem;
            border: 2px solid rgba(224,232,220,0.8);
            border-radius: 16px;
            background: rgba(254,253,248,0.9);
            font-family: 'Inter', sans-serif;
        }
        .btn-save {
            background: linear-gradient(135deg, #2b7e3a, #1f5a2a);
            color: white;
            border: none;
            padding: 0.8rem;
            border-radius: 50px;
            width: 100%;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            cursor: pointer;
            margin-top: 0.5rem;
            transition: all 0.3s ease;
        }
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(43,126,58,0.3);
            border-radius: 50px;
        }
        .message {
            padding: 0.8rem;
            border-radius: 16px;
            margin-bottom: 1rem;
            display: none;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #2b7e3a;
        }
        .message.error {
            background: #fee2dd;
            color: #b45f1b;
            border-left: 4px solid #e67e22;
        }
        
        /* Empty State */
        .empty-state { 
            text-align: center; 
            padding: 4rem; 
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(10px);
            border-radius: 28px;
            border: 1px solid rgba(255,255,255,0.3);
        }
        .empty-state i { font-size: 4rem; color: #cbd5c0; margin-bottom: 1rem; }
        .empty-state h3 { color: #5a6e5c; margin-bottom: 0.5rem; }
        .empty-state p { color: #888; margin-bottom: 1.5rem; }
        
        /* Footer */
        .footer { 
            background: #0f1f12; 
            color: #cbd5c0; 
            padding: 3rem 5% 1.5rem; 
            margin-top: 4rem;
            border-radius: 32px 32px 0 0;
        }
        .footer-container { max-width: 1400px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 2rem; margin-bottom: 2rem; }
        .footer-col h3, .footer-col h4 { font-family: 'Montserrat', sans-serif; font-weight: 700; color: #2b7e3a; margin-bottom: 1rem; }
        .footer-col p { margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.6rem; font-size: 0.9rem; }
        .footer-col a { color: #cbd5c0; text-decoration: none; display: block; margin-bottom: 0.6rem; transition: 0.2s; font-size: 0.9rem; }
        .footer-col a:hover { color: #2b7e3a; padding-left: 5px; transform: translateX(3px); }
        .social-icons { display: flex; gap: 1rem; margin-top: 1rem; }
        .social-icons a { background: #2c4a2e; width: 36px; height: 36px; display: flex; align-items: center; justify-content: center; border-radius: 50%; transition: all 0.3s ease; color: #cbd5c0; text-decoration: none; }
        .social-icons a:hover { background: #2b7e3a; transform: translateY(-5px) rotate(360deg); border-radius: 50%; }
        .footer-bottom { text-align: center; border-top: 1px solid #2c4a2e; padding-top: 1.5rem; font-size: 0.8rem; }
        
        @media (max-width: 768px) { 
            body { padding: 1rem; } 
            th, td { padding: 0.5rem; font-size: 0.8rem; } 
            .action-btns { flex-direction: column; align-items: flex-start; gap: 0.4rem; }
            .stats-grid { grid-template-columns: repeat(2, 1fr); } 
            .navbar { flex-direction: column; border-radius: 28px; }
            .logo-area img { height: 40px; }
            .logo-text { font-size: 1.1rem; }
            .footer-container { text-align: center; }
            .footer-col p { justify-content: center; }
            .social-icons { justify-content: center; }
            .user-profile { padding: 0.2rem 0.8rem 0.2rem 0.3rem; }
            .user-avatar { width: 32px; height: 32px; }
            .user-name { font-size: 0.75rem; }
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Navbar -->
    <div class="navbar">
        <a href="dashboard.php" class="logo-area">
            <img src="../Pictures/Admin_Module/logo.png" alt="Smash Arena" onerror="this.style.display='none'">
            <div class="logo-text">Smash <span>Arena</span></div>
        </a>
        <div class="nav-links">
            <a href="dashboard.php"><i class="fas fa-home"></i> Courts</a>
            <a href="my_bookings.php" class="active"><i class="fas fa-bookmark"></i> My Bookings</a>
            <a href="../Payment_Module/wallet.php"><i class="fas fa-wallet"></i> Wallet</a>
            <a href="coaches.php"><i class="fas fa-user-tie"></i> Coaches</a>
            <!-- 用户头像 + 名字区域（点击跳转 Edit Profile） -->
            <a href="edit_profile.php" class="user-profile">
                <div class="user-avatar">
                    <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Avatar">
                </div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user['name'] ?? 'Player'); ?></div>
                    <div class="user-balance">💰 RM <?php echo number_format($real_balance, 2); ?></div>
                </div>
            </a>
            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <div class="page-header">
        <h1><i class="fas fa-bookmark"></i> My Bookings</h1>
        <a href="dashboard.php" class="btn-book"><i class="fas fa-plus"></i> Book New Court</a>
    </div>
    
    <?php if($cancellation_count >= 1): ?>
    <div class="warning-banner">
        <i class="fas fa-exclamation-triangle"></i>
        <div class="text">
            <strong>⚠️ Cancellation Notice:</strong> 
            You have made <?php echo $cancellation_count; ?> cancellation<?php echo $cancellation_count > 1 ? 's' : ''; ?>.
            <?php if($cancellation_count >= 1): ?>
            Your next cancellation will incur an additional <strong>RM 5.00 penalty</strong> on top of the standard cancellation fee.
            <?php endif; ?>
        </div>
        <div class="badge"><?php echo $cancellation_count; ?>/2+</div>
    </div>
    <?php endif; ?>
    
    <div class="wallet-card">
        <i class="fas fa-wallet"></i>
        <span>Wallet Balance: <span class="amount">RM <?php echo number_format($real_balance, 2); ?></span></span>
    </div>
    
    <?php 
    $total_spent = 0;
    $completed_count = 0;
    $upcoming_count = 0;
    $today = date('Y-m-d');
    foreach($bookings as $b) {
        if($b['status'] == 'Confirmed' || $b['status'] == 'Completed') {
            $total_spent += $b['total_price'];
        }
        if($b['status'] == 'Completed') $completed_count++;
        if($b['booking_date'] >= $today && $b['status'] == 'Confirmed') $upcoming_count++;
    }
    ?>
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-number"><?php echo count($bookings); ?></div><div class="stat-label">Total Bookings</div></div>
        <div class="stat-card"><div class="stat-number"><?php echo $upcoming_count; ?></div><div class="stat-label">Upcoming</div></div>
        <div class="stat-card"><div class="stat-number"><?php echo $completed_count; ?></div><div class="stat-label">Completed</div></div>
        <div class="stat-card"><div class="stat-number">RM <?php echo number_format($total_spent, 2); ?></div><div class="stat-label">Total Spent</div></div>
    </div>
    
    <div class="filter-tabs">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="Confirmed">Confirmed</button>
        <button class="filter-btn" data-filter="Pending">Pending</button>
        <button class="filter-btn" data-filter="Completed">Completed</button>
        <button class="filter-btn" data-filter="Cancelled">Cancelled</button>
    </div>
    
    <?php if(count($bookings) > 0): ?>
    <div class="bookings-table">
        <table id="bookingsTable">
            <thead>
                <tr><th>Court</th><th>Date & Time</th><th>Duration</th><th>Coach</th><th>Total</th><th>Status</th><th>Action</th></tr>
            </thead>
            <tbody>
                <?php foreach($bookings as $b): 
                    $booking_date = date('M j, Y', strtotime($b['booking_date']));
                    $start_time = date('h:i A', strtotime($b['start_time']));
                    $end_time = date('h:i A', strtotime($b['end_time']));
                    $booking_datetime = $b['booking_date'] . ' ' . $b['start_time'];
                    $booking_timestamp = strtotime($booking_datetime);
                    $current_timestamp = time();
                    $hours_until_booking = ($booking_timestamp - $current_timestamp) / 3600;
                    $can_cancel = ($b['status'] == 'Pending' || $b['status'] == 'Confirmed') && $hours_until_booking >= 2;
                    $can_reschedule = ($b['status'] == 'Pending' || $b['status'] == 'Confirmed') && $hours_until_booking >= 24;
                    $reschedule_count = $b['reschedule_count'] ?? 0;
                    $has_rescheduled = $reschedule_count >= 1;
                ?>
                <tr data-status="<?php echo $b['status']; ?>">
                    <td><strong><?php echo htmlspecialchars($b['court_name']); ?></strong><div class="court-badge"><?php echo htmlspecialchars($b['court_type']); ?></div></td>
                    <td><?php echo $booking_date; ?><br><small><?php echo $start_time; ?> - <?php echo $end_time; ?></small></td>
                    <td><?php echo $b['total_hours']; ?> hour<?php echo $b['total_hours'] > 1 ? 's' : ''; ?></td>
                    <td><?php if($b['coach_id'] && $b['coach_id'] > 0): ?><i class="fas fa-chalkboard-user"></i> <?php echo htmlspecialchars($b['coach_name']); ?><br><small><?php echo $b['coach_hours']; ?> hour(s)</small><?php else: ?>-<?php endif; ?></td>
                    <td>RM <?php echo number_format($b['total_price'], 2); ?></td>
                    <td><span class="status status-<?php echo $b['status']; ?>"><?php echo $b['status']; ?></span></td>
                    <td class="action-btns">
                        <?php if($b['status'] === 'Pending'): ?>
                            <a href="../Payment_Module/checkout.php?booking_id=<?php echo $b['id']; ?>&amount=<?php echo $b['total_price']; ?>" class="btn-pay-now"><i class="fas fa-credit-card"></i> Pay Now</a>
                        <?php else: ?>
                            <button class="btn-view" onclick="viewReceipt(<?php echo $b['id']; ?>)"><i class="fas fa-receipt"></i> Receipt</button>
                        <?php endif; ?>
                        
                        <?php if($b['status'] == 'Pending' || $b['status'] == 'Confirmed'): ?>
                            <?php if($can_reschedule && !$has_rescheduled): ?>
                                <button class="btn-reschedule" onclick="openRescheduleModal(<?php echo $b['id']; ?>, <?php echo $b['court_id']; ?>, '<?php echo $b['booking_date']; ?>', '<?php echo $b['start_time']; ?>', <?php echo $b['total_hours']; ?>, <?php echo $reschedule_count; ?>)">
                                    <i class="fas fa-calendar-alt"></i> Reschedule
                                </button>
                            <?php elseif($has_rescheduled): ?>
                                <button class="btn-reschedule-disabled" disabled title="This booking has already been rescheduled. Each booking can only be rescheduled once.">
                                    <i class="fas fa-calendar-alt"></i> Rescheduled
                                </button>
                            <?php else: ?>
                                <button class="btn-reschedule-disabled" disabled title="Reschedule requires at least 24 hours notice">
                                    <i class="fas fa-calendar-alt"></i> Need 24h notice
                                </button>
                            <?php endif; ?>
                            
                            <?php if($can_cancel): ?>
                                <button class="btn-cancel" onclick="cancelBooking(<?php echo $b['id']; ?>, <?php echo $cancellation_count; ?>)">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            <?php else: ?>
                                <button class="btn-cancel-disabled" disabled title="Need <?php echo $cancellation_hours; ?> hours notice to cancel">
                                    <i class="fas fa-times"></i> Cancel (Need <?php echo $cancellation_hours; ?>h)
                                </button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
    <div class="empty-state"><i class="fas fa-calendar-alt"></i><h3>No Bookings Yet</h3><p>You haven't made any court bookings.</p><a href="dashboard.php" class="btn-book">Book Your First Court</a></div>
    <?php endif; ?>
</div>

<div id="receiptModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-receipt"></i> Booking Receipt</h3>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <div class="modal-body" id="receiptBody"></div>
    </div>
</div>

<div id="rescheduleModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-alt"></i> Reschedule Booking</h3>
            <button class="modal-close" onclick="closeRescheduleModal()">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="reschedule_booking_id">
            <input type="hidden" id="reschedule_court_id">
            <input type="hidden" id="reschedule_hours">
            <div class="form-group">
                <label>Select New Date</label>
                <input type="date" id="reschedule_date" class="form-control" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
            </div>
            <div class="form-group">
                <label>Select New Time</label>
                <select id="reschedule_time" class="form-control">
                    <option value="">Select date first</option>
                </select>
            </div>
            <div id="rescheduleMessage" class="message"></div>
            <button class="btn-save" onclick="confirmReschedule()">Confirm Reschedule</button>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-col">
            <h3>Smash Arena</h3>
            <p><i class="fas fa-map-marker-alt"></i> 123 Jalan Badminton, Kuala Lumpur</p>
            <p><i class="fas fa-phone-alt"></i> +603-1234 5678</p>
            <p><i class="fas fa-envelope"></i> smasharenabadminton@gmail.com</p>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <a href="dashboard.php">Find a Court</a>
            <a href="my_bookings.php">My Bookings</a>
            <a href="../Payment_Module/wallet.php">Wallet</a>
        </div>
        <div class="footer-col">
            <h4>Support</h4>
            <a href="faq.php">FAQs</a>
            <a href="cancellation_policy.php">Cancellation Policy</a>
            <a href="privacy_policy.php">Privacy Policy</a>
            <a href="terms_of_use.php">Terms of Use</a>
            <a href="contact_us.php">Contact Us</a>
        </div>
        <div class="footer-col">
            <h4>Operating Hours</h4>
            <p><i class="fas fa-clock"></i> Monday - Sunday: <?php echo $open_time_display; ?> - <?php echo $close_time_display; ?></p>
            <p><i class="fas fa-tag"></i> <?php echo $open_time_display; ?> - <?php echo $peak_start_display; ?>: RM <?php echo $off_peak_price; ?>/hour</p>
            <p><i class="fas fa-tag"></i> <?php echo $peak_start_display; ?> - <?php echo $close_time_display; ?>: RM <?php echo $peak_price; ?>/hour</p>
            <p><i class="fas fa-calendar-alt"></i> Open daily including public holidays</p>
        </div>
    </div>
    <div class="footer-bottom"><p>&copy; 2025 Smash Arena – Your Game, Our Court. All rights reserved.</p></div>
</footer>

<script>
    const filterBtns = document.querySelectorAll('.filter-btn');
    const tableRows = document.querySelectorAll('#bookingsTable tbody tr');
    filterBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            filterBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const filter = this.getAttribute('data-filter');
            tableRows.forEach(row => {
                row.style.display = (filter === 'all' || row.getAttribute('data-status') === filter) ? '' : 'none';
            });
        });
    });
    
    async function viewReceipt(bookingId) {
        try {
            const response = await fetch(`get_booking_details.php?id=${bookingId}`);
            const data = await response.json();
            if(data.success) {
                const b = data.booking;
                const isRefund = b.status === 'Cancelled' && b.cancellation_fee > 0;
                const refundAmount = isRefund ? (parseFloat(b.total_price) - parseFloat(b.cancellation_fee)) : 0;
                
                document.getElementById('receiptBody').innerHTML = `
                    <div style="text-align:center; margin-bottom:1rem;">
                        <img src="../Pictures/Admin_Module/logo.png" style="height:40px;">
                        <h2 style="color:#2b7e3a;">Smash Arena</h2>
                        <p>Official Booking Receipt</p>
                    </div>
                    <div class="receipt-row"><span>Receipt No.</span><span>#${String(b.id).padStart(6,'0')}</span></div>
                    <div class="receipt-row"><span>Transaction ID</span><span>${b.transaction_id || 'N/A'}</span></div>
                    <div class="receipt-row"><span>Court</span><span>${b.court_name} (${b.court_type})</span></div>
                    <div class="receipt-row"><span>Date</span><span>${b.booking_date}</span></div>
                    <div class="receipt-row"><span>Time</span><span>${b.start_time} - ${b.end_time}</span></div>
                    <div class="receipt-row"><span>Duration</span><span>${b.total_hours} hour(s)</span></div>
                    ${b.coach_name ? `<div class="receipt-row"><span>Coach</span><span>${b.coach_name} (${b.coach_hours} hour(s))</span></div>` : ''}
                    <div class="receipt-row"><span>Payment Method</span><span><i class="fas fa-wallet"></i> ${b.payment_method || 'Wallet'}</span></div>
                    <div class="receipt-row"><span>Payment Date</span><span>${b.payment_date || 'N/A'}</span></div>
                    <div class="receipt-row"><span>Status</span><span class="status status-${b.status}">${b.status}</span></div>
                    ${b.reschedule_count > 0 ? `<div class="receipt-row"><span>Reschedule Count</span><span>${b.reschedule_count} time(s)</span></div>` : ''}
                    ${b.cancellation_fee > 0 ? `<div class="receipt-row" style="color:#e67e22;"><span>Cancellation Fee</span><span>- RM ${parseFloat(b.cancellation_fee).toFixed(2)}</span></div>` : ''}
                    <div class="receipt-total">
                        <span>${isRefund ? 'Refund Amount' : 'Total Paid'}</span>
                        <span>${isRefund ? 'RM ' + refundAmount.toFixed(2) : 'RM ' + parseFloat(b.total_price).toFixed(2)}</span>
                    </div>
                    ${isRefund ? `<div class="receipt-row" style="font-size:0.8rem; color:#666;"><span>Note</span><span>Amount refunded to wallet</span></div>` : ''}
                    <button class="print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print Receipt</button>
                `;
                document.getElementById('receiptModal').style.display = 'block';
            } else {
                alert('Failed to load receipt details');
            }
        } catch(e) {
            console.error(e);
            alert('Error loading receipt');
        }
    }
    
    function closeModal() { 
        document.getElementById('receiptModal').style.display = 'none'; 
    }
    
    window.onclick = (e) => { 
        if(e.target === document.getElementById('receiptModal')) closeModal();
        if(e.target === document.getElementById('rescheduleModal')) closeRescheduleModal();
    };
    
    let currentBookingId = null;
    let currentCourtId = null;
    let currentHours = null;
    let currentDate = null;
    let currentTime = null;
    
    function openRescheduleModal(bookingId, courtId, date, time, hours, rescheduleCount) {
        if (rescheduleCount >= 1) {
            alert('⚠️ This booking has already been rescheduled.\n\nEach booking can only be rescheduled once.\n\nPlease make a new booking if you need a different time.');
            return;
        }
        
        currentBookingId = bookingId;
        currentCourtId = courtId;
        currentHours = hours;
        currentDate = date;
        currentTime = time;
        
        document.getElementById('reschedule_booking_id').value = bookingId;
        document.getElementById('reschedule_court_id').value = courtId;
        document.getElementById('reschedule_hours').value = hours;
        document.getElementById('reschedule_date').value = '';
        document.getElementById('reschedule_time').innerHTML = '<option value="">Select date first</option>';
        document.getElementById('rescheduleMessage').style.display = 'none';
        document.getElementById('rescheduleMessage').className = 'message';
        
        document.getElementById('rescheduleModal').style.display = 'block';
    }
    
    function closeRescheduleModal() {
        document.getElementById('rescheduleModal').style.display = 'none';
    }
    
    document.getElementById('reschedule_date').addEventListener('change', async function() {
        const date = this.value;
        const courtId = document.getElementById('reschedule_court_id').value;
        const bookingId = document.getElementById('reschedule_booking_id').value;
        const timeSelect = document.getElementById('reschedule_time');
        
        if (!date) {
            timeSelect.innerHTML = '<option value="">Select date first</option>';
            return;
        }
        
        timeSelect.innerHTML = '<option value="">Loading...</option>';
        
        try {
            const response = await fetch(`ajax_get_available_slots.php?court_id=${courtId}&date=${date}&exclude_booking_id=${bookingId}`);
            const slots = await response.json();
            
            timeSelect.innerHTML = '<option value="">Select time</option>';
            
            if (slots && slots.length > 0) {
                slots.forEach(slot => {
                    const option = document.createElement('option');
                    option.value = slot.time;
                    option.textContent = slot.display;
                    timeSelect.appendChild(option);
                });
            } else {
                timeSelect.innerHTML = '<option value="">No available slots</option>';
            }
        } catch(e) {
            console.error(e);
            timeSelect.innerHTML = '<option value="">Error loading slots</option>';
        }
    });
    
    async function confirmReschedule() {
        const bookingId = document.getElementById('reschedule_booking_id').value;
        const newDate = document.getElementById('reschedule_date').value;
        const newTime = document.getElementById('reschedule_time').value;
        const messageDiv = document.getElementById('rescheduleMessage');
        
        if (!newDate || !newTime) {
            messageDiv.style.display = 'block';
            messageDiv.className = 'message error';
            messageDiv.innerHTML = 'Please select new date and time';
            return;
        }
        
        messageDiv.style.display = 'block';
        messageDiv.className = 'message';
        messageDiv.innerHTML = 'Processing...';
        
        try {
            const response = await fetch('reschedule_booking.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    booking_id: bookingId, 
                    new_date: newDate, 
                    new_time: newTime 
                })
            });
            const data = await response.json();
            
            console.log('Response:', data);
            
            if (data.success) {
                messageDiv.className = 'message success';
                messageDiv.innerHTML = data.message.replace(/\n/g, '<br>');
                setTimeout(() => {
                    location.reload();
                }, 3000);
            } else {
                messageDiv.className = 'message error';
                messageDiv.innerHTML = data.message.replace(/\n/g, '<br>');
            }
        } catch(e) {
            console.error('Error:', e);
            messageDiv.className = 'message error';
            messageDiv.innerHTML = 'Error: ' + e.message;
        }
    }
    
    async function cancelBooking(bookingId, currentCancellationCount) {
        try {
            const response = await fetch(`get_booking_details.php?id=${bookingId}`);
            const data = await response.json();
            if(data.success) {
                const booking = data.booking;
                const bookingDateTime = new Date(booking.booking_date + ' ' + booking.start_time);
                const now = new Date();
                const hoursDiff = (bookingDateTime - now) / (1000 * 60 * 60);
                const hasCoach = booking.coach_name && booking.coach_name !== '';
                const addonsTotal = booking.addons_total || 0;
                
                let confirmMessage = '';
                
                if (hoursDiff >= 48) {
                    confirmMessage = `🏸 CANCELLATION POLICY (≥48 hours notice)\n\n` +
                        `Booking: ${booking.court_name}\n` +
                        `Date: ${booking.booking_date}\n` +
                        `Time: ${booking.start_time} - ${booking.end_time}\n\n` +
                        `✅ Full Refund: RM ${booking.total_price}\n\n` +
                        `Do you want to proceed with cancellation?`;
                } else if (hoursDiff >= 24) {
                    if (hasCoach) {
                        confirmMessage = `🏸 CANCELLATION POLICY (24-48 hours notice) - Training Mode\n\n` +
                            `Booking: ${booking.court_name}\n` +
                            `Date: ${booking.booking_date}\n` +
                            `Time: ${booking.start_time} - ${booking.end_time}\n\n` +
                            `✅ Full Refund: RM ${booking.total_price}\n\n` +
                            `Do you want to proceed with cancellation?`;
                    } else {
                        confirmMessage = `🏸 CANCELLATION POLICY (24-48 hours notice) - Play Only\n\n` +
                            `Booking: ${booking.court_name}\n` +
                            `Date: ${booking.booking_date}\n` +
                            `Time: ${booking.start_time} - ${booking.end_time}\n\n` +
                            `📌 RM 10.00 cancellation fee applies\n` +
                            `💰 Refund: RM ${(parseFloat(booking.total_price) - 10).toFixed(2)}\n\n` +
                            `Do you want to proceed with cancellation?`;
                    }
                } else if (hoursDiff >= 2) {
                    if (hasCoach) {
                        const coachFee = parseFloat(booking.coach_price_total || 0);
                        const coachRefund = coachFee * 0.5;
                        const refundAmount = coachRefund + addonsTotal;
                        confirmMessage = `🏸 CANCELLATION POLICY (2-24 hours notice) - Training Mode\n\n` +
                            `Booking: ${booking.court_name}\n` +
                            `Date: ${booking.booking_date}\n` +
                            `Time: ${booking.start_time} - ${booking.end_time}\n\n` +
                            `📌 Court fee: NOT refunded\n` +
                            `📌 Coach fee: 50% refunded (RM ${coachRefund.toFixed(2)})\n` +
                            `📌 Add-ons: FULLY refunded (RM ${addonsTotal.toFixed(2)})\n` +
                            `💰 Total refund: RM ${refundAmount.toFixed(2)}\n\n` +
                            `Do you want to proceed with cancellation?`;
                    } else {
                        confirmMessage = `🏸 CANCELLATION POLICY (2-24 hours notice) - Play Only\n\n` +
                            `Booking: ${booking.court_name}\n` +
                            `Date: ${booking.booking_date}\n` +
                            `Time: ${booking.start_time} - ${booking.end_time}\n\n` +
                            `📌 Court fee: NOT refunded\n` +
                            `📌 Add-ons: FULLY refunded (RM ${addonsTotal.toFixed(2)})\n` +
                            `💰 Total refund: RM ${addonsTotal.toFixed(2)}\n\n` +
                            `Do you want to proceed with cancellation?`;
                    }
                } else if (hoursDiff >= 1) {
                    if (hasCoach) {
                        confirmMessage = `🏸 CANCELLATION POLICY (1-2 hours notice) - Training Mode\n\n` +
                            `Booking: ${booking.court_name}\n` +
                            `Date: ${booking.booking_date}\n` +
                            `Time: ${booking.start_time} - ${booking.end_time}\n\n` +
                            `❌ Court fee: NOT refunded\n` +
                            `❌ Coach fee: NOT refunded (already paid to coach)\n` +
                            `✅ Add-ons: FULLY refunded (RM ${addonsTotal.toFixed(2)})\n\n` +
                            `💰 Refund: RM ${addonsTotal.toFixed(2)}\n\n` +
                            `Do you want to proceed with cancellation?`;
                    } else {
                        confirmMessage = `🏸 CANCELLATION POLICY (1-2 hours notice) - Play Only\n\n` +
                            `Booking: ${booking.court_name}\n` +
                            `Date: ${booking.booking_date}\n` +
                            `Time: ${booking.start_time} - ${booking.end_time}\n\n` +
                            `❌ NO REFUND will be issued\n\n` +
                            `Do you want to proceed with cancellation?`;
                    }
                } else {
                    confirmMessage = `🏸 CANCELLATION POLICY (<1 hour notice)\n\n` +
                        `Booking: ${booking.court_name}\n` +
                        `Date: ${booking.booking_date}\n` +
                        `Time: ${booking.start_time} - ${booking.end_time}\n\n` +
                        `❌ NO REFUND will be issued\n\n` +
                        `Do you want to proceed with cancellation?`;
                }
                
                if(confirm(confirmMessage)) {
                    await proceedCancel(bookingId);
                }
            } else {
                if(confirm('Are you sure you want to cancel this booking?')) {
                    await proceedCancel(bookingId);
                }
            }
        } catch(e) {
            console.error(e);
            if(confirm('Are you sure you want to cancel this booking?')) {
                await proceedCancel(bookingId);
            }
        }
    }
    
    async function proceedCancel(bookingId) {
        try {
            const response = await fetch(`cancel_booking.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ booking_id: bookingId })
            });
            const data = await response.json();
            if(data.success) {
                alert(data.message);
                location.reload();
            } else {
                alert(data.message || 'Failed to cancel booking');
            }
        } catch(e) {
            console.error(e);
            alert('Error cancelling booking');
        }
    }
</script>
</body>
</html>