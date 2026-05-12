<?php
require_once __DIR__ . '/../config.php';
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
function getCourtImage($courtName) {
    // 转换场地名称为文件名 (Court A -> court_a.png)
    $fileName = strtolower(str_replace(' ', '_', $courtName)) . '.png';
    $imagePath = 'images/court/' . $fileName;
    
    // 检查文件是否存在
    if (file_exists(__DIR__ . '/' . $imagePath)) {
        return $imagePath;
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        
        body { font-family:'Inter',sans-serif; background:linear-gradient(145deg,#f5f9f0 0%,#e8efe2 100%); color:#1e2a2e; padding:2rem; }
        .container { max-width:1400px; margin:0 auto; }
        
        /* Navbar */
        .navbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; flex-wrap:wrap; gap:1rem; padding-bottom:1rem; border-bottom:1px solid rgba(43,126,58,0.15); }
        .logo img { height: 65px; width: auto; }
        .nav-links { display:flex; align-items:center; gap:1rem; flex-wrap:wrap; }
        .nav-links a { color:#2c4a2e; text-decoration:none; font-weight:500; transition:0.2s; }
        .nav-links a:hover, .nav-links a.active { color:#2b7e3a; }
        .user-greeting { color:#2b7e3a; font-weight:500; }
        .btn-logout { background:#fee2e2; color:#e67e22; padding:0.3rem 1rem; border-radius:50px; text-decoration:none; font-size:0.8rem; transition:0.2s; }
        .btn-logout:hover { background:#e67e22; color:white; }
        
        /* Welcome Banner */
        .welcome-banner { background:linear-gradient(135deg,#2b7e3a,#1b5e2a); color:white; padding:2rem; border-radius:32px; margin-bottom:2rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; }
        .welcome-banner h1 { font-size:1.8rem; margin-bottom:0.3rem; }
        .welcome-banner p { opacity:0.9; }
        .wallet-info { background:rgba(255,255,255,0.2); padding:0.5rem 1rem; border-radius:50px; display:inline-flex; align-items:center; gap:0.5rem; margin-top:0.5rem; }
        .wallet-info i { font-size:0.9rem; }
        .btn-wallet { color:#aaffaa; text-decoration:underline; font-size:0.8rem; margin-left:0.5rem; }
        .btn-my-bookings { background:white; color:#2b7e3a; border:none; padding:0.6rem 1.2rem; border-radius:50px; cursor:pointer; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem; transition:0.2s; }
        .btn-my-bookings:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(0,0,0,0.15); }
        
        /* Stats Grid */
        .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem; margin-bottom:2rem; }
        .stat-card { background:white; border-radius:24px; padding:1.2rem; display:flex; align-items:center; gap:1rem; box-shadow:0 4px 15px rgba(0,0,0,0.03); transition:0.3s; }
        .stat-card:hover { transform:translateY(-3px); box-shadow:0 8px 25px rgba(43,126,58,0.1); }
        .stat-icon { width:50px; height:50px; background:#eaf5e6; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.5rem; color:#2b7e3a; }
        .stat-info h3 { font-size:1.6rem; font-weight:800; color:#1e3a2a; }
        .stat-info p { color:#5a6e5c; font-size:0.8rem; }
        
        /* Filter Form */
        .filter-form { background:white; padding:1.5rem; border-radius:28px; margin-bottom:2rem; display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem; align-items:end; border:1px solid rgba(43,126,58,0.1); }
        .filter-group label { font-weight:600; color:#2c4a2e; display:block; margin-bottom:0.3rem; font-size:0.85rem; }
        .filter-group select, .filter-group input { width:100%; padding:0.6rem 1rem; border:1.5px solid #e0e8dc; border-radius:50px; background:#fefdf8; family:'Inter',sans-serif; }
        .filter-group select:focus, .filter-group input:focus { outline:none; border-color:#2b7e3a; }
        .search-btn, .reset-btn { background:#2b7e3a; color:white; border:none; padding:0.6rem 1.2rem; border-radius:50px; cursor:pointer; font-weight:600; transition:0.2s; }
        .reset-btn { background:#cbd5c0; color:#2c4a2e; }
        .search-btn:hover, .reset-btn:hover { transform:translateY(-2px); }
        
        /* Courts Grid */
        .courts-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(360px,1fr)); gap:1.5rem; margin-top:1rem; }
        .court-card { background:white; border-radius:28px; overflow:hidden; box-shadow:0 8px 20px rgba(0,0,0,0.05); transition:0.3s; border-bottom:4px solid #2b7e3a; }
        .court-card:hover { transform:translateY(-5px); box-shadow:0 16px 32px rgba(43,126,58,0.12); }
        
        .court-image { height: 200px; overflow:hidden; background: linear-gradient(135deg, #2b7e3a, #1a5c2a); position: relative; }
        .court-image img { width:100%; height:100%; object-fit:cover; transition:transform 0.4s; }
        .court-card:hover .court-image img { transform:scale(1.05); }
        .court-image .placeholder { display:flex; flex-direction:column; align-items:center; justify-content:center; height:100%; }
        .court-image .court-icon { font-size: 4rem; color: white; margin-bottom: 0.5rem; }
        .court-image .court-name-big { font-size: 1.3rem; font-weight: 700; color: white; }
        .court-image .court-location { font-size: 0.7rem; color: #aaffaa; margin-top: 0.3rem; }
        .court-type-badge { position: absolute; top: 12px; right: 12px; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); color: white; padding: 0.3rem 0.8rem; border-radius: 50px; font-size: 0.7rem; font-weight: 600; z-index: 2; }
        
        .court-info { padding: 1.2rem; }
        .court-name { font-size: 1.3rem; font-weight: 800; color:#2b7e3a; margin-bottom:0.3rem; }
        .court-details { color:#5a6e5c; font-size:0.85rem; margin-bottom:0.3rem; display:flex; align-items:center; gap:0.5rem; }
        .court-price { background:#f8faf5; padding:0.8rem; border-radius:16px; margin:0.8rem 0; }
        .price-row { display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.3rem; }
        .price-offpeak { color:#2b7e3a; font-weight:500; }
        .price-peak { color:#e67e22; font-weight:500; }
        .btn-book { background:#2b7e3a; color:white; border:none; padding:0.7rem 1rem; border-radius:50px; width:100%; cursor:pointer; font-weight:600; margin-top:0.5rem; display:inline-flex; align-items:center; justify-content:center; gap:0.5rem; text-decoration:none; transition:0.2s; }
        .btn-book:hover { background:#1f5a2a; transform:translateY(-2px); }
        
        /* Recent Bookings Table */
        .recent-section { margin-top:2rem; }
        .section-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; }
        .section-header h2 { color:#1e3a2a; font-size:1.5rem; }
        .recent-table { background:white; border-radius:24px; overflow-x:auto; }
        table { width:100%; border-collapse:collapse; }
        th { background:#2b7e3a; color:white; padding:1rem; text-align:left; font-weight:600; }
        td { padding:1rem; border-bottom:1px solid #e0e0e0; }
        .status { display:inline-block; padding:0.25rem 0.8rem; border-radius:50px; font-size:0.75rem; font-weight:600; }
        .status-Confirmed { background:#d4edda; color:#155724; }
        .status-Pending { background:#fff3cd; color:#856404; }
        .status-Completed { background:#cce5ff; color:#004085; }
        .status-Cancelled { background:#f8d7da; color:#721c24; }
        
        .quick-actions { display:flex; gap:1rem; flex-wrap:wrap; margin-top:1.5rem; }
        .action-btn { background:white; border:1px solid #2b7e3a; padding:0.6rem 1.2rem; border-radius:50px; color:#2b7e3a; text-decoration:none; font-weight:500; transition:0.2s; display:inline-flex; align-items:center; gap:0.5rem; }
        .action-btn:hover { background:#2b7e3a; color:white; transform:translateY(-2px); }
        
        @media (max-width:768px) { body { padding:1rem; } .filter-form { grid-template-columns:1fr; } .courts-grid { grid-template-columns:1fr; } .navbar { flex-direction:column; } }
    </style>
</head>
<body>
<div class="container">
    <!-- Navbar -->
    <div class="navbar">
        <div class="logo">
            <img src="../Admin_Module/Pictures/logo.png" alt="Smash Arena" onerror="this.style.display='none'">
        </div>
        <div class="nav-links">
            <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Courts</a>
            <a href="my_bookings.php"><i class="fas fa-bookmark"></i> My Bookings</a>
            <a href="../Payment_Module/wallet.php"><i class="fas fa-wallet"></i> Wallet</a>
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
        <div class="stat-card"><div class="stat-icon"><i class="fas fa-star"></i></div><div class="stat-info"><h3><?php echo floor($totalSpent * 0.1); ?></h3><p>Reward Points</p></div></div>
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
                $imagePath = getCourtImage($c['court_name']);
            ?>
                <div class="court-card">
                    <div class="court-image">
                        <?php if ($imagePath): ?>
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
            <a href="my_bookings.php" style="color:#2b7e3a;">View All →</a>
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
</body>
</html>