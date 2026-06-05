<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/functions.php';
if (!isLoggedIn()) redirect('homepage.php');

$user_id = $_SESSION['user_id'];

// 获取用户信息
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) {
    session_destroy();
    redirect('homepage.php');
}

// 获取钱包余额
$stmt_bal = $pdo->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$stmt_bal->execute([$user_id]);
$balance_row = $stmt_bal->fetch();
$real_balance = $balance_row['wallet_balance'] ?? 0.00;

// 获取统计数据
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ? AND booking_date >= CURDATE() AND status IN ('Pending', 'Confirmed')");
$stmt->execute([$user_id]);
$upcomingCount = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE user_id = ?");
$stmt->execute([$user_id]);
$totalBookings = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(total_price) FROM bookings WHERE user_id = ? AND status = 'Confirmed'");
$stmt->execute([$user_id]);
$totalSpent = $stmt->fetchColumn() ?? 0;

// 积分 — read directly from users.loyalty_points (single source of truth, same as redeem_voucher.php)
$stmt_pts = $pdo->prepare("SELECT loyalty_points FROM users WHERE id = ?");
$stmt_pts->execute([$user_id]);
$currentPointsBalance = (int)($stmt_pts->fetchColumn() ?? 0);

// 获取所有场地类型
$types = [];
$typeResult = $pdo->query("SELECT DISTINCT court_type FROM courts WHERE is_active = 1");
if ($typeResult) {
    $types = $typeResult->fetchAll(PDO::FETCH_COLUMN);
}

// 筛选条件
$court_type = $_GET['court_type'] ?? '';
$facility = $_GET['facility'] ?? '';
$court_name = $_GET['court_name'] ?? '';

$sql = "SELECT * FROM courts WHERE is_active = 1";
$params = [];
if ($court_type) {
    $sql .= " AND court_type = ?";
    $params[] = $court_type;
}
if ($facility) {
    $sql .= " AND facilities LIKE ?";
    $params[] = "%$facility%";
}
if ($court_name) {
    $sql .= " AND court_name LIKE ?";
    $params[] = "%$court_name%";
}
$sql .= " ORDER BY court_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courts = $stmt->fetchAll();

// 获取所有设施
$facilities = [];
$facRes = $pdo->query("SELECT DISTINCT facilities FROM courts WHERE is_active = 1 AND facilities IS NOT NULL AND facilities != ''");
if ($facRes) {
    while ($row = $facRes->fetch(PDO::FETCH_ASSOC)) {
        if ($row && isset($row['facilities'])) {
            $items = explode(',', $row['facilities']);
            foreach ($items as $item) {
                $item = trim($item);
                if ($item && !in_array($item, $facilities)) {
                    $facilities[] = $item;
                }
            }
        }
    }
}
sort($facilities);

// 获取最近预订
$stmt = $pdo->prepare("
    SELECT b.*, c.court_name 
    FROM bookings b 
    JOIN courts c ON b.court_id = c.id 
    WHERE b.user_id = ? 
    ORDER BY b.created_at DESC 
    LIMIT 5
");
$stmt->execute([$user_id]);
$recentBookings = $stmt->fetchAll();

// 获取场地图片路径
function getCourtImage($court) {
    $imageField = isset($court['court_image']) ? $court['court_image'] : null;
    
    if (!empty($imageField)) {
        $imagePath = $imageField;
        $possiblePaths = [
            '../Pictures/Admin_Module/courts/' . $imagePath,
            '../Pictures/Customer_Module/court/' . $imagePath,
            $imagePath,
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists(__DIR__ . '/' . $path)) {
                return $path;
            }
        }
        return '../Pictures/Admin_Module/courts/' . $imagePath;
    }
    
    $courtName = $court['court_name'];
    $possibleImageNames = [
        strtolower(str_replace(' ', '_', $courtName)) . '.png',
        strtolower(str_replace(' ', '_', $courtName)) . '.jpg',
        'court_' . strtolower(str_replace(' ', '_', $courtName)) . '.png',
    ];
    
    $imagePaths = [
        '../Pictures/Admin_Module/courts/',
        '../Pictures/Customer_Module/court/',
    ];
    
    foreach ($imagePaths as $basePath) {
        foreach ($possibleImageNames as $imgName) {
            $fullPath = $basePath . $imgName;
            if (file_exists(__DIR__ . '/' . $fullPath)) {
                return $fullPath;
            }
        }
    }
    
    return null;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Smash Arena | Dashboard</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- 时尚字体导入 -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
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
        
        /* 动态粒子背景效果 */
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
        
        /* 自定义滚动条 */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #e0e8dc; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #2b7e3a; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #1f5a2a; }
        
        /* 玻璃态导航栏 */
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
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
        
        .logo-area:hover::after {
            width: 100%;
        }
        
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
            transition: transform 0.3s ease;
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
        
        .nav-links a:hover::before {
            left: 100%;
        }
        
        .nav-links a:hover, .nav-links a.active { 
            background: #2b7e3a;
            color: white; 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(43,126,58,0.3);
        }
        
        .user-greeting { 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 600;
            color: #2b7e3a; 
            background: #eaf5e6;
            padding: 0.4rem 1rem;
            border-radius: 50px;
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
        }
        
        /* 欢迎横幅 - 玻璃态 + 动态光效 */
        .welcome-banner { 
            background: linear-gradient(135deg, #2b7e3a, #1b5e2a);
            color: white; 
            padding: 2rem 2rem; 
            border-radius: 32px; 
            margin-bottom: 2rem; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            flex-wrap: wrap; 
            gap: 1rem;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out 0.1s both;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite;
            pointer-events: none;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .welcome-banner h1 { 
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-weight: 700;
            letter-spacing: -0.3px;
            font-size: 1.8rem; 
            margin-bottom: 0.3rem; 
        }
        .welcome-banner p { opacity: 0.9; }
        .wallet-info { 
            background: rgba(255,255,255,0.15); 
            padding: 0.5rem 1.2rem; 
            border-radius: 50px; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.5rem; 
            margin-top: 0.5rem;
            backdrop-filter: blur(4px);
        }
        .btn-wallet { 
            color: #aaffaa; 
            text-decoration: underline; 
            font-size: 0.8rem; 
            margin-left: 0.5rem;
            font-weight: 600;
            transition: color 0.3s;
        }
        .btn-wallet:hover { color: #ffffff; }
        
        .btn-my-bookings { 
            background: white; 
            color: #2b7e3a; 
            border: none; 
            padding: 0.8rem 1.8rem; 
            border-radius: 50px; 
            cursor: pointer; 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 700; 
            text-decoration: none; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.5rem; 
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .btn-my-bookings:hover { 
            transform: translateY(-5px); 
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }
        
        /* 统计卡片 - 3D悬浮效果 */
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 1.2rem; 
            margin-bottom: 2rem; 
        }
        .stat-card { 
            background: rgba(255,255,255,0.8);
            backdrop-filter: blur(10px);
            border-radius: 28px; 
            padding: 1.3rem; 
            display: flex; 
            align-items: center; 
            gap: 1rem; 
            box-shadow: 0 8px 20px rgba(0,0,0,0.04); 
            transition: all 0.4s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            border: 1px solid rgba(255,255,255,0.3);
            cursor: pointer;
            animation: fadeInScale 0.5s ease-out both;
        }
        
        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }
        
        .stat-card:nth-child(1) { animation-delay: 0.1s; }
        .stat-card:nth-child(2) { animation-delay: 0.2s; }
        .stat-card:nth-child(3) { animation-delay: 0.3s; }
        .stat-card:nth-child(4) { animation-delay: 0.4s; }
        
        .stat-card:hover { 
            transform: translateY(-8px) scale(1.02); 
            box-shadow: 0 20px 40px rgba(43,126,58,0.2);
            border-color: rgba(43,126,58,0.3);
            background: white;
        }
        .stat-icon { 
            width: 55px; 
            height: 55px; 
            background: linear-gradient(145deg, #eaf5e6, #d4e8cd);
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 1.6rem; 
            color: #2b7e3a;
            transition: transform 0.3s;
        }
        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
        }
        .stat-info h3 { 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 900;
            font-size: 1.8rem; 
            color: #1e3a2a; 
        }
        .stat-info p { 
            color: #5a6e5c; 
            font-size: 0.85rem; 
            font-weight: 500;
        }
        
        /* 筛选表单 */
        .filter-form { 
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(10px);
            padding: 1.5rem; 
            border-radius: 28px; 
            margin-bottom: 2rem; 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
            gap: 1rem; 
            align-items: end; 
            border: 1px solid rgba(255,255,255,0.3);
            box-shadow: 0 4px 15px rgba(0,0,0,0.02);
            animation: fadeInUp 0.5s ease-out 0.2s both;
        }
        .filter-group label { 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 600; 
            color: #2c4a2e; 
            display: block; 
            margin-bottom: 0.3rem; 
            font-size: 0.8rem;
        }
        .filter-group select, .filter-group input { 
            width: 100%; 
            padding: 0.7rem 1rem; 
            border: 2px solid rgba(224,232,220,0.8); 
            border-radius: 60px; 
            background: rgba(254,253,248,0.9); 
            font-family: 'Inter', sans-serif; 
            transition: all 0.3s;
        }
        .filter-group select:focus, .filter-group input:focus { 
            outline: none; 
            border-color: #2b7e3a; 
            box-shadow: 0 0 0 3px rgba(43,126,58,0.1);
        }
        .search-btn, .reset-btn { 
            background: linear-gradient(135deg, #2b7e3a, #1f5a2a);
            color: white; 
            border: none; 
            padding: 0.7rem 1.2rem; 
            border-radius: 60px; 
            cursor: pointer; 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 600; 
            transition: all 0.3s ease;
        }
        .reset-btn { 
            background: #cbd5c0; 
            color: #2c4a2e; 
        }
        .search-btn:hover, .reset-btn:hover { 
            transform: translateY(-3px);
            filter: brightness(1.02);
            box-shadow: 0 6px 14px rgba(43,126,58,0.3);
        }
        
        /* 场地网格 */
        .courts-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); 
            gap: 1.8rem; 
            margin-top: 1rem; 
        }
        .court-card { 
            background: white; 
            border-radius: 28px; 
            overflow: hidden; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.05); 
            transition: all 0.5s cubic-bezier(0.2, 0.9, 0.4, 1.1); 
            border-bottom: 4px solid #2b7e3a;
            animation: fadeInScale 0.5s ease-out both;
        }
        .court-card:nth-child(1) { animation-delay: 0.05s; }
        .court-card:nth-child(2) { animation-delay: 0.1s; }
        .court-card:nth-child(3) { animation-delay: 0.15s; }
        .court-card:nth-child(4) { animation-delay: 0.2s; }
        .court-card:nth-child(5) { animation-delay: 0.25s; }
        .court-card:nth-child(6) { animation-delay: 0.3s; }
        
        .court-card:hover { 
            transform: translateY(-12px) scale(1.01); 
            box-shadow: 0 30px 50px rgba(43,126,58,0.2);
        }
        
        .court-image { 
            height: 200px; 
            overflow: hidden; 
            background: linear-gradient(135deg, #2b7e3a, #1a5c2a); 
            position: relative; 
        }
        .court-image img { 
            width: 100%; 
            height: 100%; 
            object-fit: cover; 
            transition: transform 0.7s cubic-bezier(0.2, 0.9, 0.4, 1.1); 
        }
        .court-card:hover .court-image img { 
            transform: scale(1.1); 
        }
        .court-image .placeholder { 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            height: 100%; 
        }
        .court-image .placeholder .court-icon { 
            font-size: 4rem; 
            color: white; 
            margin-bottom: 0.5rem;
            animation: bounce 2s ease-in-out infinite;
        }
        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .court-image .placeholder .court-name-big { 
            font-size: 1.3rem; 
            font-weight: 700; 
            color: white; 
        }
        .court-type-badge { 
            position: absolute; 
            top: 15px; 
            right: 15px; 
            background: rgba(0,0,0,0.6); 
            backdrop-filter: blur(4px); 
            color: white; 
            padding: 0.3rem 0.8rem; 
            border-radius: 50px; 
            font-size: 0.7rem; 
            font-family: 'Montserrat', sans-serif;
            font-weight: 600; 
            z-index: 2;
        }
        
        .court-info { 
            padding: 1.3rem; 
        }
        .court-name { 
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-weight: 800;
            letter-spacing: -0.5px;
            font-size: 1.3rem; 
            color: #2b7e3a; 
            margin-bottom: 0.3rem;
        }
        .court-details { 
            color: #5a6e5c; 
            font-size: 0.85rem; 
            margin-bottom: 0.3rem; 
            display: flex; 
            align-items: center; 
            gap: 0.5rem; 
        }
        .court-price { 
            background: #f8faf5; 
            padding: 0.8rem; 
            border-radius: 20px; 
            margin: 0.8rem 0; 
        }
        .price-row { 
            display: flex; 
            justify-content: space-between; 
            font-size: 0.85rem; 
            margin-bottom: 0.3rem; 
        }
        .price-offpeak { 
            font-family: 'DM Sans', 'Inter', sans-serif;
            font-weight: 700;
            color: #2b7e3a; 
        }
        .price-peak { 
            font-family: 'DM Sans', 'Inter', sans-serif;
            font-weight: 700;
            color: #e67e22; 
        }
        .btn-book { 
            background: linear-gradient(135deg, #2b7e3a, #1f5a2a);
            color: white; 
            border: none; 
            padding: 0.8rem; 
            border-radius: 60px; 
            width: 100%; 
            cursor: pointer; 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 700; 
            margin-top: 0.5rem; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            gap: 0.5rem; 
            text-decoration: none; 
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
        .btn-book:hover::before {
            left: 100%;
        }
        .btn-book:hover { 
            transform: translateY(-5px);
            box-shadow: 0 12px 25px rgba(43,126,58,0.4);
        }
        
        /* 最近预订表格 */
        .recent-section { margin-top: 2.5rem; }
        .section-header { 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            margin-bottom: 1rem; 
        }
        .section-header h2 { 
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 1.5rem; 
            color: #1e3a2a;
        }
        .recent-table { 
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(10px);
            border-radius: 28px; 
            overflow-x: auto;
            box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            border: 1px solid rgba(255,255,255,0.3);
        }
        table { width: 100%; border-collapse: collapse; }
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
        .status-Completed { background: #cce5ff; color: #004085; }
        .status-Cancelled { background: #f8d7da; color: #721c24; }
        
        /* 快捷操作 */
        .quick-actions { 
            display: flex; 
            gap: 1rem; 
            flex-wrap: wrap; 
            margin-top: 2rem; 
        }
        .action-btn { 
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(5px);
            border: 1.5px solid rgba(43,126,58,0.3); 
            padding: 0.7rem 1.5rem; 
            border-radius: 60px; 
            color: #2b7e3a; 
            text-decoration: none; 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 600; 
            transition: all 0.3s ease; 
            display: inline-flex; 
            align-items: center; 
            gap: 0.5rem;
        }
        .action-btn:hover { 
            background: #2b7e3a; 
            color: white; 
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 10px 25px rgba(43,126,58,0.3);
            border-color: transparent;
        }
        
        /* 页脚 */
        .footer { 
            background: #0f1f12; 
            color: #cbd5c0; 
            padding: 3rem 5% 1.5rem; 
            margin-top: 4rem;
            border-radius: 32px 32px 0 0;
        }
        .footer-container { 
            max-width: 1400px; 
            margin: 0 auto; 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 2rem; 
            margin-bottom: 2rem; 
        }
        .footer-col h3, .footer-col h4 { 
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: #2b7e3a; 
            margin-bottom: 1rem;
        }
        .footer-col p { 
            margin-bottom: 0.5rem; 
            display: flex; 
            align-items: center; 
            gap: 0.6rem; 
            font-size: 0.9rem; 
        }
        .footer-col a { 
            color: #cbd5c0; 
            text-decoration: none; 
            display: block; 
            margin-bottom: 0.6rem; 
            transition: 0.2s; 
            font-size: 0.9rem; 
        }
        .footer-col a:hover { 
            color: #2b7e3a; 
            padding-left: 5px; 
            transform: translateX(3px);
        }
        .social-icons { 
            display: flex; 
            gap: 1rem; 
            margin-top: 1rem; 
        }
        .social-icons a { 
            background: #2c4a2e; 
            width: 36px; 
            height: 36px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            border-radius: 50%; 
            transition: all 0.3s ease; 
            color: #cbd5c0; 
            text-decoration: none;
        }
        .social-icons a:hover { 
            background: #2b7e3a; 
            transform: translateY(-5px) rotate(360deg);
        }
        .footer-bottom { 
            text-align: center; 
            border-top: 1px solid #2c4a2e; 
            padding-top: 1.5rem; 
            font-size: 0.8rem; 
        }

        @media (max-width: 768px) { 
            body { padding: 1rem; } 
            .filter-form { grid-template-columns: 1fr; } 
            .courts-grid { grid-template-columns: 1fr; } 
            .navbar { flex-direction: column; border-radius: 28px; }
            .footer-container { text-align: center; }
            .footer-col p { justify-content: center; }
            .social-icons { justify-content: center; }
            .stat-card { padding: 1rem; }
            .stat-icon { width: 45px; height: 45px; font-size: 1.3rem; }
            .stat-info h3 { font-size: 1.3rem; }
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
            <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Courts</a>
            <a href="my_bookings.php"><i class="fas fa-bookmark"></i> My Bookings</a>
            <a href="../Payment_Module/wallet.php"><i class="fas fa-wallet"></i> Wallet</a>
            <a href="coaches.php"><i class="fas fa-user-tie"></i> Coaches</a>
            <span class="user-greeting">🏸 <?php echo htmlspecialchars($user['name'] ?? 'Player'); ?></span>
            <a href="edit_profile.php" class="action-btn" style="padding:0.3rem 1rem; background:#eaf5e6;"><i class="fas fa-user-edit"></i> Profile</a>
            <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>
    
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div>
            <h1>Ready to play, <?php echo htmlspecialchars($user['name'] ?? 'Player'); ?>! 🏸</h1>
            <p>Find your perfect court below and start your game</p>
            <div class="wallet-info">
                <i class="fas fa-wallet"></i> Balance: <strong>RM <?php echo number_format($real_balance, 2); ?></strong>
                <a href="../Payment_Module/wallet.php" class="btn-wallet">Top Up</a>
            </div>
        </div>
        <a href="my_bookings.php" class="btn-my-bookings"><i class="fas fa-bookmark"></i> My Bookings</a>
    </div>
    
    <!-- Stats Cards -->
    <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-calendar-check"></i></div><div class="stat-info"><h3><?php echo $upcomingCount; ?></h3><p>Upcoming Bookings</p></div></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-history"></i></div><div class="stat-info"><h3><?php echo $totalBookings; ?></h3><p>Total Bookings</p></div></div>
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-coins"></i></div><div class="stat-info"><h3>RM <?php echo number_format($totalSpent, 2); ?></h3><p>Total Spent</p></div></div>
        <div class="stat-card" onclick="window.location.href='../Payment_Module/redeem_voucher.php';" style="cursor: pointer;">
            <div class="stat-icon"><i class="fas fa-star"></i></div>
            <div class="stat-info">
                <h3><?php echo $currentPointsBalance; ?></h3>
                <p>Reward Points</p>
            </div>
        </div>
    </div>
    
    <!-- Filter Form -->
    <div class="filter-form">
        <form method="GET" style="display:contents;">
            <div class="filter-group">
                <label><i class="fas fa-tag"></i> Court Type</label>
                <select name="court_type">
                    <option value="">All Types</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?php echo htmlspecialchars($t); ?>" <?php echo ($court_type == $t) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-building"></i> Facility</label>
                <select name="facility">
                    <option value="">All Facilities</option>
                    <?php foreach ($facilities as $f): ?>
                        <option value="<?php echo htmlspecialchars($f); ?>" <?php echo ($facility == $f) ? 'selected' : ''; ?>><?php echo htmlspecialchars($f); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fas fa-search"></i> Court Name</label>
                <input type="text" name="court_name" value="<?php echo htmlspecialchars($court_name); ?>" placeholder="Search court...">
            </div>
            <div class="filter-group">
                <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
                <button type="button" class="reset-btn" onclick="window.location.href='dashboard.php'"><i class="fas fa-undo-alt"></i> Reset</button>
            </div>
        </form>
    </div>
    
    <!-- Courts Grid -->
    <div class="courts-grid">
        <?php if (count($courts) > 0): ?>
            <?php foreach ($courts as $c): 
                $imagePath = getCourtImage($c);
            ?>
                <div class="court-card">
                    <div class="court-image">
                        <?php if ($imagePath && file_exists(__DIR__ . '/' . $imagePath)): ?>
                            <img src="<?php echo $imagePath; ?>" alt="<?php echo htmlspecialchars($c['court_name']); ?>">
                        <?php else: ?>
                            <div class="placeholder">
                                <?php $icon = ($c['court_type'] == 'Training') ? '🏋️‍♂️' : '🏸'; ?>
                                <div class="court-icon"><?php echo $icon; ?></div>
                                <div class="court-name-big"><?php echo htmlspecialchars($c['court_name']); ?></div>
                                <div class="court-location"><?php echo htmlspecialchars($c['location'] ?? 'Main Hall'); ?></div>
                            </div>
                        <?php endif; ?>
                        <span class="court-type-badge"><?php echo htmlspecialchars($c['court_type']); ?></span>
                    </div>
                    <div class="court-info">
                        <div class="court-name">🏸 <?php echo htmlspecialchars($c['court_name']); ?></div>
                        <div class="court-details"><i class="fas fa-tools"></i> <?php echo htmlspecialchars($c['facilities'] ?? 'Shower, Locker'); ?></div>
                        <div class="court-price">
                            <div class="price-row"><span><i class="fas fa-sun"></i> 8am - 2pm</span><span class="price-offpeak">RM <?php echo number_format($c['price_off_peak'], 2); ?> / hour</span></div>
                            <div class="price-row"><span><i class="fas fa-moon"></i> 3pm - 1am</span><span class="price-peak">RM <?php echo number_format($c['price_peak'], 2); ?> / hour</span></div>
                        </div>
                        <a href="book_court.php?court_id=<?php echo $c['id']; ?>" class="btn-book"><i class="fas fa-calendar-check"></i> Book Now →</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="court-card" style="text-align:center; padding:2rem; color:#888;">No courts found. Try different filters.</div>
        <?php endif; ?>
    </div>
    
    <!-- Recent Bookings -->
    <?php if(count($recentBookings) > 0): ?>
    <div class="recent-section">
        <div class="section-header">
            <h2><i class="fas fa-clock"></i> Recent Bookings</h2>
            <a href="my_bookings.php" style="color:#2b7e3a; font-weight:600;">View All →</a>
        </div>
        <div class="recent-table">
            <table>
                <thead>
                    <tr><th>Court</th><th>Date</th><th>Time</th><th>Hours</th><th>Total</th><th>Status</th><th></th></tr>
                </thead>
                <tbody>
                    <?php foreach($recentBookings as $b): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($b['court_name']); ?></strong></td>
                        <td><?php echo date('M j, Y', strtotime($b['booking_date'])); ?></td>
                        <td><?php echo date('h:i A', strtotime($b['start_time'])); ?> - <?php echo date('h:i A', strtotime($b['end_time'])); ?></td>
                        <td><?php echo $b['total_hours']; ?>h</td>
                        <td>RM <?php echo number_format($b['total_price'], 2); ?></td>
                        <td><span class="status status-<?php echo $b['status']; ?>"><?php echo $b['status']; ?></span></td>
                        <td><a href="booking_details.php?id=<?php echo $b['id']; ?>" style="color:#2b7e3a;"><i class="fas fa-eye"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Quick Actions -->
    <div class="quick-actions">
        <a href="dashboard.php" class="action-btn"><i class="fas fa-plus"></i> Book a Court</a>
        <a href="my_bookings.php" class="action-btn"><i class="fas fa-list"></i> My Bookings</a>
        <a href="../Payment_Module/wallet.php" class="action-btn"><i class="fas fa-wallet"></i> Top Up Wallet</a>
        <a href="edit_profile.php" class="action-btn"><i class="fas fa-user-cog"></i> Edit Profile</a>
        <a href="change_password.php" class="action-btn"><i class="fas fa-key"></i> Change Password</a>
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
            <p><i class="fas fa-clock"></i> Monday - Sunday: <?php echo getOperatingHours(); ?></p>
            <p><i class="fas fa-tag"></i> 8am - <?php echo date('h:i A', strtotime(getSetting('peak_start', '15:00'))); ?>: RM <?php echo getSetting('off_peak_price', '10'); ?>/hour</p>
            <p><i class="fas fa-tag"></i> <?php echo date('h:i A', strtotime(getSetting('peak_start', '15:00'))); ?> - <?php echo date('h:i A', strtotime(getSetting('close_time', '01:00'))); ?>: RM <?php echo getSetting('peak_price', '15'); ?>/hour</p>
            <p><i class="fas fa-calendar-alt"></i> Open daily including public holidays</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2025 Smash Arena – Your Game, Our Court. All rights reserved.</p>
    </div>
</footer>

</body>
</html>