<?php
require_once __DIR__ . '/../config.php';
if (!isLoggedIn()) redirect('homepage.php');

$user_id = $_SESSION['user_id'];

// 获取用户信息
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) {
    session_destroy();
    redirect('homepage.php');
}

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

// 为每个场地分配图片URL
function getCourtImageUrl($courtName, $courtType) {
    // 使用 Unsplash 的羽毛球场地相关图片
    $images = [
        'Court A' => 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=500&h=250&fit=crop',
        'Court B' => 'https://images.unsplash.com/photo-1613918108466-2923af9f6f5a?w=500&h=250&fit=crop',
        'Court C' => 'https://images.unsplash.com/photo-1594919032176-6a0b81f1845f?w=500&h=250&fit=crop',
        'Court D' => 'https://images.unsplash.com/photo-1613921409641-4e574be92cf0?w=500&h=250&fit=crop',
        'Court E' => 'https://images.unsplash.com/photo-1574634268455-db4d22b6d02a?w=500&h=250&fit=crop',
        'Court F' => 'https://images.unsplash.com/photo-1554068865-24cecd4e34b8?w=500&h=250&fit=crop',
        'Court G' => 'https://images.unsplash.com/photo-1526453815751-29f3e5a63c9c?w=500&h=250&fit=crop',
        'Court H' => 'https://images.unsplash.com/photo-1519008638618-84feb34f79c6?w=500&h=250&fit=crop',
        'Court I' => 'https://images.unsplash.com/photo-1566738780863-f9958c7b6f9b?w=500&h=250&fit=crop',
        'Court J' => 'https://images.unsplash.com/photo-1607450367960-89d9b4d1ef72?w=500&h=250&fit=crop'
    ];
    
    // 如果场地有指定图片则返回，否则根据类型返回默认图片
    if (isset($images[$courtName])) {
        return $images[$courtName];
    } elseif ($courtType == 'Training') {
        return 'https://images.unsplash.com/photo-1519008638618-84feb34f79c6?w=500&h=250&fit=crop';
    } else {
        return 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?w=500&h=250&fit=crop';
    }
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
        .logo img { height: 45px; width: auto; transition:transform 0.3s; }
        .logo img:hover { transform:scale(1.02); }
        .nav-links a { margin-left:1.5rem; color:#2c4a2e; text-decoration:none; font-weight:500; transition:0.2s; }
        .nav-links a:hover { color:#2b7e3a; }
        .nav-links a.active { color:#2b7e3a; font-weight:600; }
        .user-greeting { color:#2b7e3a; margin-left:1rem; font-weight:500; }
        
        /* Welcome Banner */
        .welcome-banner { background:linear-gradient(135deg,#2b7e3a,#1b5e2a); color:white; padding:2rem; border-radius:32px; margin-bottom:2rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem; box-shadow:0 10px 30px rgba(43,126,58,0.2); }
        .welcome-banner h1 { font-size:1.8rem; margin-bottom:0.3rem; }
        .welcome-banner p { opacity:0.9; }
        .btn-my-bookings { background:white; color:#2b7e3a; border:none; padding:0.6rem 1.2rem; border-radius:50px; cursor:pointer; font-weight:600; transition:0.3s; text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem; }
        .btn-my-bookings:hover { transform:translateY(-2px); box-shadow:0 6px 16px rgba(0,0,0,0.15); }
        
        /* Filter Form */
        .filter-form { background:white; padding:1.5rem; border-radius:28px; margin-bottom:2rem; display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem; align-items:end; box-shadow:0 4px 15px rgba(0,0,0,0.03); border:1px solid rgba(43,126,58,0.1); }
        .filter-group label { font-weight:600; color:#2c4a2e; display:block; margin-bottom:0.3rem; font-size:0.85rem; }
        .filter-group select, .filter-group input { width:100%; padding:0.6rem 1rem; border:1.5px solid #e0e8dc; border-radius:50px; background:#fefdf8; font-family:'Inter',sans-serif; transition:0.2s; }
        .filter-group select:focus, .filter-group input:focus { outline:none; border-color:#2b7e3a; }
        .search-btn, .reset-btn { background:#2b7e3a; color:white; border:none; padding:0.6rem 1.2rem; border-radius:50px; cursor:pointer; font-weight:600; transition:0.2s; }
        .reset-btn { background:#cbd5c0; color:#2c4a2e; }
        .search-btn:hover, .reset-btn:hover { transform:translateY(-2px); }
        
        /* Courts Grid */
        .courts-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(360px,1fr)); gap:1.5rem; margin-top:1rem; }
        .court-card { background:white; border-radius:28px; overflow:hidden; box-shadow:0 8px 20px rgba(0,0,0,0.05); transition:0.3s; border-bottom:4px solid #2b7e3a; }
        .court-card:hover { transform:translateY(-5px); box-shadow:0 16px 32px rgba(43,126,58,0.12); }
        .court-image { height: 200px; overflow:hidden; background:#e8efe2; position:relative; }
        .court-image img { width:100%; height:100%; object-fit:cover; transition:transform 0.4s; }
        .court-card:hover .court-image img { transform:scale(1.05); }
        .court-type-badge { position:absolute; top:12px; right:12px; background:#2b7e3a; color:white; padding:0.3rem 0.8rem; border-radius:50px; font-size:0.7rem; font-weight:600; box-shadow:0 2px 8px rgba(0,0,0,0.2); }
        .court-info { padding:1.2rem; }
        .court-name { font-size:1.3rem; font-weight:800; color:#2b7e3a; margin-bottom:0.3rem; display:flex; align-items:center; gap:0.5rem; }
        .court-details { color:#5a6e5c; font-size:0.85rem; margin-bottom:0.3rem; display:flex; align-items:center; gap:0.5rem; }
        .court-price { background:#f8faf5; padding:0.8rem; border-radius:16px; margin:0.8rem 0; }
        .price-row { display:flex; justify-content:space-between; font-size:0.85rem; margin-bottom:0.3rem; }
        .price-offpeak { color:#2b7e3a; font-weight:500; }
        .price-peak { color:#e67e22; font-weight:500; }
        .btn-book { background:#2b7e3a; color:white; border:none; padding:0.7rem 1rem; border-radius:50px; width:100%; cursor:pointer; font-weight:600; margin-top:0.5rem; display:inline-flex; align-items:center; justify-content:center; gap:0.5rem; text-decoration:none; transition:0.2s; }
        .btn-book:hover { background:#1f5a2a; transform:translateY(-2px); }
        
        @media (max-width:768px) { body { padding:1rem; } .filter-form { grid-template-columns:1fr; } .courts-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="container">
    <!-- Navbar -->
    <div class="navbar">
        <div class="logo">
            <img src="../Admin_Module/Pictures/logo.png" alt="Smash Arena" style="height: 45px; width: auto;" onerror="this.style.display='none';">
        </div>
        <div class="nav-links">
            <a href="dashboard.php" class="active"><i class="fas fa-home"></i> Courts</a>
            <a href="my_bookings.php"><i class="fas fa-bookmark"></i> My Bookings</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            <span class="user-greeting">🏸 <?php echo htmlspecialchars($user['name'] ?? 'Player'); ?></span>
        </div>
    </div>
    
    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <div>
            <h1>Ready to play, <?php echo htmlspecialchars($user['name'] ?? 'Player'); ?>! 🏸</h1>
            <p>Find your perfect court below and start your game</p>
        </div>
        <a href="my_bookings.php" class="btn-my-bookings"><i class="fas fa-bookmark"></i> My Bookings</a>
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
                $imageUrl = getCourtImageUrl($c['court_name'], $c['court_type']);
            ?>
                <div class="court-card">
                    <div class="court-image">
                        <img src="<?php echo $imageUrl; ?>" alt="<?php echo htmlspecialchars($c['court_name']); ?>">
                        <span class="court-type-badge"><?php echo htmlspecialchars($c['court_type']); ?></span>
                    </div>
                    <div class="court-info">
                        <div class="court-name">🏸 <?php echo htmlspecialchars($c['court_name']); ?></div>
                        <div class="court-details"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($c['location'] ?? 'Main Hall'); ?></div>
                        <div class="court-details"><i class="fas fa-tools"></i> <?php echo htmlspecialchars($c['facilities'] ?? 'Shower, Locker'); ?></div>
                        <div class="court-price">
                            <div class="price-row">
                                <span><i class="fas fa-sun"></i> 8am - 2pm</span>
                                <span class="price-offpeak">RM <?php echo number_format($c['price_off_peak'], 2); ?> / hour</span>
                            </div>
                            <div class="price-row">
                                <span><i class="fas fa-moon"></i> 3pm - 1am</span>
                                <span class="price-peak">RM <?php echo number_format($c['price_peak'], 2); ?> / hour</span>
                            </div>
                        </div>
                        <a href="book_court.php?court_id=<?php echo $c['id']; ?>" class="btn-book"><i class="fas fa-calendar-check"></i> Book Now →</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="court-card" style="text-align:center; padding:2rem; color:#888;">No courts found. Try different filters.</div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>