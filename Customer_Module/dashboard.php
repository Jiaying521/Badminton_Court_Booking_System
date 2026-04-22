<?php
require_once __DIR__ . '/../config.php';
if (!isLoggedIn()) redirect('homepage.php');

$user_id = $_SESSION['user_id'];

// 获取用户信息
$stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) {
    // 用户不存在，退出登录
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
$price_range = $_GET['price_range'] ?? '';
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
if ($price_range == 'low') {
    $sql .= " AND price_per_hour < 30";
} elseif ($price_range == 'mid') {
    $sql .= " AND price_per_hour BETWEEN 30 AND 60";
} elseif ($price_range == 'high') {
    $sql .= " AND price_per_hour > 60";
}
if ($court_name) {
    $sql .= " AND court_name LIKE ?";
    $params[] = "%$court_name%";
}
$sql .= " ORDER BY court_name";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courts = $stmt->fetchAll();

// 获取所有设施（用于筛选下拉）
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
?>
<!DOCTYPE html>
<html>
<head>
    <title>Dashboard | BadmintonHub</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f5f9f0; color:#1e2a2e; padding:2rem; }
        .container { max-width:1400px; margin:0 auto; }
        .navbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; flex-wrap:wrap; gap:1rem; }
        .logo { font-size:1.8rem; font-weight:800; background:linear-gradient(135deg,#2b7e3a,#1b5e2a); -webkit-background-clip:text; background-clip:text; color:transparent; }
        .welcome-banner { background:linear-gradient(135deg,#2b7e3a,#1b5e2a); color:white; padding:2rem; border-radius:32px; margin-bottom:2rem; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; }
        .filter-form { background:white; padding:1.5rem; border-radius:28px; margin-bottom:2rem; display:grid; grid-template-columns:repeat(auto-fit,minmax(200px,1fr)); gap:1rem; align-items:end; }
        .filter-group label { font-weight:600; color:#2c4a2e; display:block; margin-bottom:0.3rem; }
        .filter-group select, .filter-group input { width:100%; padding:0.6rem; border:1px solid #cbd5c0; border-radius:40px; }
        .search-btn, .reset-btn { background:#2b7e3a; color:white; border:none; padding:0.6rem 1.2rem; border-radius:40px; cursor:pointer; }
        .reset-btn { background:#cbd5c0; color:#2c4a2e; }
        .courts-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(320px,1fr)); gap:1.5rem; margin-top:1rem; }
        .court-card { background:white; border-radius:24px; padding:1.5rem; box-shadow:0 8px 20px rgba(0,0,0,0.05); border-bottom:3px solid #2b7e3a; }
        .court-name { font-size:1.4rem; font-weight:bold; color:#2b7e3a; margin-bottom:0.5rem; }
        .court-price { font-size:1.2rem; font-weight:700; color:#e67e22; margin:0.5rem 0; }
        .btn-book { background:#2b7e3a; color:white; border:none; padding:0.6rem 1rem; border-radius:40px; width:100%; cursor:pointer; font-weight:600; margin-top:1rem; display:inline-block; text-align:center; text-decoration:none; }
        .btn-book:hover { background:#1f5a2a; }
        .nav-links a { margin-right:1rem; color:#2c4a2e; text-decoration:none; }
        .nav-links a:hover { color:#2b7e3a; }
        @media (max-width:768px) { body { padding:1rem; } .filter-form { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="container">
    <div class="navbar">
        <div class="logo">BadmintonHub</div>
        <div class="nav-links">
            <a href="dashboard.php">Home</a>
            <a href="my_bookings.php">My Bookings</a>
            <a href="logout.php">Logout</a>
            <span>Hi, <?php echo htmlspecialchars($user['name'] ?? 'Player'); ?></span>
        </div>
    </div>
    <div class="welcome-banner">
        <div><h1>Ready to play?</h1><p>Find your perfect court below</p></div>
        <a href="my_bookings.php" class="btn-book" style="width:auto; background:white; color:#2b7e3a;">📋 My Bookings</a>
    </div>

    <div class="filter-form">
        <form method="GET" style="display:contents;">
            <div class="filter-group">
                <label>Court Type</label>
                <select name="court_type">
                    <option value="">All</option>
                    <?php foreach ($types as $t): ?>
                        <option value="<?php echo htmlspecialchars($t); ?>" <?php echo ($court_type == $t) ? 'selected' : ''; ?>><?php echo htmlspecialchars($t); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Facility</label>
                <select name="facility">
                    <option value="">All</option>
                    <?php foreach ($facilities as $f): ?>
                        <option value="<?php echo htmlspecialchars($f); ?>" <?php echo ($facility == $f) ? 'selected' : ''; ?>><?php echo htmlspecialchars($f); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Price Range</label>
                <select name="price_range">
                    <option value="">Any</option>
                    <option value="low" <?php echo ($price_range == 'low') ? 'selected' : ''; ?>>&lt; $30/hr</option>
                    <option value="mid" <?php echo ($price_range == 'mid') ? 'selected' : ''; ?>>$30–$60/hr</option>
                    <option value="high" <?php echo ($price_range == 'high') ? 'selected' : ''; ?>>&gt; $60/hr</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Court Name</label>
                <input type="text" name="court_name" value="<?php echo htmlspecialchars($court_name); ?>">
            </div>
            <div class="filter-group">
                <button type="submit" class="search-btn">🔍 Search</button>
                <button type="button" class="reset-btn" onclick="window.location.href='dashboard.php'">Reset</button>
            </div>
        </form>
    </div>

    <div class="courts-grid">
        <?php if (count($courts) > 0): ?>
            <?php foreach ($courts as $c): ?>
                <div class="court-card">
                    <div class="court-name"><?php echo htmlspecialchars($c['court_name']); ?></div>
                    <div class="court-type"><?php echo htmlspecialchars($c['court_type']); ?></div>
                    <div class="court-details">📍 <?php echo htmlspecialchars($c['location'] ?? 'Main Hall'); ?></div>
                    <div class="court-details">🛠️ <?php echo htmlspecialchars($c['facilities'] ?? 'Standard'); ?></div>
                    <div class="court-price">$<?php echo number_format($c['price_per_hour'], 2); ?> / hour</div>
                    <a href="book_court.php?court_id=<?php echo $c['id']; ?>" class="btn-book">Book Now</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="court-card">No courts found. Try different filters.</div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>