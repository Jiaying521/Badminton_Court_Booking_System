<?php
// dashboard.php - Badminton Court Booking Dashboard
require_once __DIR__ . '/../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: homepage.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id, email, name, nric, phone FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// get filter values from URL
$court_type = $_GET['court_type'] ?? '';
$facility = $_GET['facility'] ?? '';
$price_range = $_GET['price_range'] ?? '';
$court_name = $_GET['court_name'] ?? '';

// fetch active courts (is_active = 1)
$sql = "SELECT * FROM courts WHERE is_active = 1";
$params = [];
if (!empty($court_type)) {
    $sql .= " AND court_type = ?";
    $params[] = $court_type;
}
if (!empty($facility)) {
    $sql .= " AND facilities LIKE ?";
    $params[] = "%$facility%";
}
if (!empty($price_range)) {
    if ($price_range == 'low') $sql .= " AND price_per_hour < 30";
    elseif ($price_range == 'mid') $sql .= " AND price_per_hour BETWEEN 30 AND 60";
    elseif ($price_range == 'high') $sql .= " AND price_per_hour > 60";
}
if (!empty($court_name)) {
    $sql .= " AND court_name LIKE ?";
    $params[] = "%$court_name%";
}
$sql .= " ORDER BY court_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$courts = $stmt->fetchAll();

// get all court types for filter dropdown
$type_sql = "SELECT DISTINCT court_type FROM courts WHERE is_active = 1 AND court_type IS NOT NULL AND court_type != ''";
$type_stmt = $pdo->query($type_sql);
$court_types = $type_stmt->fetchAll(PDO::FETCH_COLUMN);

// get distinct facilities for filter (simple split from stored comma list)
$facility_sql = "SELECT DISTINCT facilities FROM courts WHERE is_active = 1 AND facilities IS NOT NULL";
$facility_stmt = $pdo->query($facility_sql);
$facility_rows = $facility_stmt->fetchAll(PDO::FETCH_COLUMN);
$all_facilities = [];
foreach ($facility_rows as $fac_str) {
    $items = explode(',', $fac_str);
    foreach ($items as $item) {
        $item = trim($item);
        if (!in_array($item, $all_facilities)) $all_facilities[] = $item;
    }
}
sort($all_facilities);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BadmintonHub | Player Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(145deg, #f5f9f0 0%, #e8efe2 100%); color: #1e2a2e; scroll-behavior: smooth; }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 1rem 5%; background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(12px); box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03); position: sticky; top: 0; z-index: 100; border-bottom: 1px solid rgba(43,126,58,0.2); flex-wrap: wrap; }
        .logo { font-size: 1.9rem; font-weight: 800; background: linear-gradient(135deg, #2b7e3a, #1b5e2a); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .logo span { background: none; color: #1e3a2a; }
        .nav-links { display: flex; gap: 2rem; align-items: center; flex-wrap: wrap; }
        .nav-links a { text-decoration: none; color: #2c4a2e; font-weight: 500; transition: 0.2s; }
        .nav-links a:hover, .nav-links a.active { color: #2b7e3a; }
        .btn-outline { background: transparent; border: 1.5px solid #2b7e3a; padding: 0.4rem 1.2rem; border-radius: 40px; color: #2b7e3a; cursor: pointer; font-weight: 600; transition: 0.2s; }
        .btn-outline:hover { background: #2b7e3a; color: white; transform: translateY(-2px); }
        .dashboard-container { max-width: 1400px; margin: 2rem auto; padding: 0 5%; }
        .welcome-banner { background: linear-gradient(135deg, #2b7e3a, #1b5e2a); color: white; padding: 2rem; border-radius: 32px; margin-bottom: 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; }
        .welcome-banner h1 { font-size: 2rem; margin-bottom: 0.5rem; }
        .action-buttons { display: flex; gap: 1rem; flex-wrap: wrap; }
        .action-btn { background: white; color: #2b7e3a; border: none; padding: 0.8rem 1.8rem; border-radius: 40px; font-weight: bold; cursor: pointer; transition: 0.2s; text-decoration: none; display: inline-block; font-size: 0.9rem; }
        .action-btn:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.2); }
        .filter-section { background: white; padding: 1.5rem; border-radius: 28px; margin-bottom: 2rem; box-shadow: 0 8px 24px rgba(0,0,0,0.05); }
        .filter-form { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px,1fr)); gap: 1rem; align-items: end; }
        .filter-group { display: flex; flex-direction: column; gap: 0.3rem; }
        .filter-group label { font-weight: 600; color: #2c4a2e; }
        .filter-group select, .filter-group input { padding: 0.6rem; border: 1px solid #cbd5c0; border-radius: 40px; font-size: 0.9rem; outline: none; }
        .filter-group select:focus, .filter-group input:focus { border-color: #2b7e3a; }
        .search-btn, .reset-btn { background: #2b7e3a; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 40px; cursor: pointer; font-weight: 600; height: 42px; }
        .reset-btn { background: #cbd5c0; color: #2c4a2e; }
        .courts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px,1fr)); gap: 1.5rem; margin-top: 1rem; }
        .court-card { background: white; border-radius: 24px; padding: 1.5rem; box-shadow: 0 8px 20px rgba(0,0,0,0.05); transition: 0.3s; border-bottom: 3px solid #2b7e3a; text-align: center; }
        .court-card:hover { transform: translateY(-5px); box-shadow: 0 16px 32px rgba(43,126,58,0.1); }
        .court-icon { font-size: 3rem; margin-bottom: 1rem; }
        .court-name { font-size: 1.4rem; font-weight: bold; color: #2b7e3a; margin-bottom: 0.5rem; }
        .court-type { color: #2c4a2e; margin-bottom: 0.5rem; font-weight: 500; }
        .court-details { color: #5a6e5c; font-size: 0.9rem; margin-bottom: 0.3rem; }
        .price { font-weight: 700; color: #e67e22; margin-top: 0.5rem; }
        .book-btn { background: #2b7e3a; color: white; border: none; padding: 0.6rem 1rem; border-radius: 40px; cursor: pointer; font-weight: 600; margin-top: 1rem; width: 100%; transition: 0.2s; }
        .book-btn:hover { background: #1f5a2a; }
        .no-results { text-align: center; padding: 3rem; background: white; border-radius: 28px; color: #5a6e5c; }
        .services-section { margin-top: 3rem; padding: 2rem 0; }
        .services-section h2 { font-size: 2.4rem; font-weight: 700; text-align: center; margin-bottom: 0.5rem; color: #1e3a2a; }
        .services-sub { text-align: center; color: #5a6e5c; margin-bottom: 2rem; font-size: 1rem; }
        .service-cards { display: flex; flex-wrap: wrap; gap: 1.5rem; justify-content: center; }
        .card { background: white; border-radius: 24px; padding: 1.5rem; width: 240px; box-shadow: 0 8px 20px rgba(0,0,0,0.05); transition: 0.3s; text-align: center; border: 1px solid rgba(43,126,58,0.1); }
        .card:hover { transform: translateY(-6px); box-shadow: 0 16px 32px rgba(43,126,58,0.12); border-color: rgba(43,126,58,0.3); }
        .card-icon { font-size: 2.8rem; margin-bottom: 0.8rem; background: #eaf5e6; width: 70px; height: 70px; display: flex; align-items: center; justify-content: center; border-radius: 60px; margin-left: auto; margin-right: auto; }
        .card h4 { font-size: 1.2rem; font-weight: 600; color: #2b7e3a; margin-bottom: 0.5rem; }
        .card p { font-size: 0.85rem; color: #5a6e5c; line-height: 1.4; }
        .main-footer { background: #142614; color: #cbd5c0; padding: 2rem 5%; margin-top: 3rem; text-align: center; border-radius: 24px 24px 0 0; }
        @media (max-width: 768px) {
            .navbar { flex-direction: column; gap: 1rem; }
            .welcome-banner { flex-direction: column; text-align: center; gap: 1rem; }
            .filter-form { grid-template-columns: 1fr; }
            .courts-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="logo">Badminton<span>Hub</span></div>
    <div class="nav-links">
        <a href="dashboard.php" class="active">Home</a>
        <a href="my_bookings.php">My Bookings</a>
        <a href="#">About Us</a>
        <span style="color:#2b7e3a;">Hi, <?php echo htmlspecialchars($user['name'] ?? explode('@', $user['email'])[0]); ?></span>
        <button class="btn-outline" id="logoutNavBtn">Logout</button>
    </div>
</nav>

<div class="dashboard-container">
    <div class="welcome-banner">
        <div>
            <h1>Welcome back, <?php echo htmlspecialchars($user['name'] ?? 'Player'); ?>!</h1>
            <p>Book courts, join tournaments, and elevate your game.</p>
        </div>
        <div class="action-buttons">
            <a href="book_appointment.php" class="action-btn">🏸 Book a Court</a>
            <a href="my_bookings.php" class="action-btn">📋 My Bookings</a>
            <a href="membership.php" class="action-btn">🎖️ Membership Plans</a>
        </div>
    </div>

    <div class="filter-section">
        <form method="GET" action="" class="filter-form">
            <div class="filter-group">
                <label>Court Type</label>
                <select name="court_type">
                    <option value="">All Types</option>
                    <?php foreach ($court_types as $type): ?>
                        <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($court_type == $type) ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Facilities</label>
                <select name="facility">
                    <option value="">All</option>
                    <?php foreach ($all_facilities as $fac): ?>
                        <option value="<?php echo htmlspecialchars($fac); ?>" <?php echo ($facility == $fac) ? 'selected' : ''; ?>><?php echo htmlspecialchars($fac); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label>Price Range</label>
                <select name="price_range">
                    <option value="">Any</option>
                    <option value="low" <?php echo ($price_range == 'low') ? 'selected' : ''; ?>>&lt; $30/hr</option>
                    <option value="mid" <?php echo ($price_range == 'mid') ? 'selected' : ''; ?>>$30 - $60/hr</option>
                    <option value="high" <?php echo ($price_range == 'high') ? 'selected' : ''; ?>>&gt; $60/hr</option>
                </select>
            </div>
            <div class="filter-group">
                <label>Court Name</label>
                <input type="text" name="court_name" placeholder="e.g., Court A" value="<?php echo htmlspecialchars($court_name); ?>">
            </div>
            <div class="filter-group">
                <button type="submit" class="search-btn">🔍 Search</button>
                <button type="button" class="reset-btn" id="resetFilter">Reset</button>
            </div>
        </form>
    </div>

    <h2 style="margin-bottom:1rem;">🏸 Available Badminton Courts</h2>
    <?php if (count($courts) > 0): ?>
        <div class="courts-grid">
            <?php foreach ($courts as $court): ?>
                <div class="court-card">
                    <div class="court-icon">🏸</div>
                    <div class="court-name"><?php echo htmlspecialchars($court['court_name']); ?></div>
                    <div class="court-type"><?php echo htmlspecialchars($court['court_type']); ?></div>
                    <div class="court-details">📍 <?php echo htmlspecialchars($court['location'] ?? 'Main Hall'); ?></div>
                    <div class="court-details">🛠️ <?php echo htmlspecialchars($court['facilities'] ?? 'Standard'); ?></div>
                    <div class="price">💰 $<?php echo number_format($court['price_per_hour'], 2); ?> / hour</div>
                    <button class="book-btn" data-court-id="<?php echo $court['id']; ?>" data-court-name="<?php echo htmlspecialchars($court['court_name']); ?>">Book Now</button>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-results">😞 No courts match your filters. Try different options.</div>
    <?php endif; ?>

    <div class="services-section">
        <h2>Smash Your Limits</h2>
        <div class="services-sub">Complete badminton experience — beyond just courts</div>
        <div class="service-cards">
            <div class="card"><div class="card-icon">🏸</div><h4>Court Rental</h4><p>Premium wooden floors & professional lighting</p></div>
            <div class="card"><div class="card-icon">🏆</div><h4>Tournaments</h4><p>Monthly leagues & prize events</p></div>
            <div class="card"><div class="card-icon">👨‍🏫</div><h4>Coaching</h4><p>Certified coaches for all levels</p></div>
            <div class="card"><div class="card-icon">🛍️</div><h4>Pro Shop</h4><p>Rackets, shoes, shuttlecocks</p></div>
            <div class="card"><div class="card-icon">🚿</div><h4>Shower & Lockers</h4><p>Refresh after your game</p></div>
            <div class="card"><div class="card-icon">☕</div><h4>Sports Cafe</h4><p>Energy drinks & healthy snacks</p></div>
        </div>
    </div>
</div>

<footer class="main-footer">
    <p>© 2025 BadmintonHub | Book, Play, Compete. | <a href="#" style="color:#2b7e3a;">Privacy Policy</a></p>
</footer>

<script>
    const baseUrl = '/Clinic_Booking_System/';
    const logoutBtn = document.getElementById('logoutNavBtn');
    if(logoutBtn) {
        logoutBtn.onclick = async () => {
            await fetch(baseUrl + 'logout.php', { method: 'POST' });
            window.location.href = baseUrl + 'Customer_Module/homepage.php';
        };
    }
    const bookBtns = document.querySelectorAll('.book-btn');
    bookBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            const courtId = btn.getAttribute('data-court-id');
            const courtName = btn.getAttribute('data-court-name');
            window.location.href = `book_appointment.php?court_id=${courtId}&court_name=${encodeURIComponent(courtName)}`;
        });
    });
    const resetBtn = document.getElementById('resetFilter');
    if(resetBtn) resetBtn.onclick = () => { window.location.href = window.location.pathname; };
</script>
</body>
</html>