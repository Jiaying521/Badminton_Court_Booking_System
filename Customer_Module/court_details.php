<?php
require_once __DIR__ . '/../config.php';
if (!isLoggedIn()) redirect('homepage.php');

$court_id = $_GET['id'] ?? 0;
if (!$court_id) redirect('dashboard.php');

$stmt = $pdo->prepare("SELECT * FROM courts WHERE id = ? AND is_active = 1");
$stmt->execute([$court_id]);
$court = $stmt->fetch();

if (!$court) redirect('dashboard.php');
?>
<!DOCTYPE html>
<html>
<head>
    <title><?php echo htmlspecialchars($court['court_name']); ?> | Smash Arena</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f5f9f0; padding:2rem; }
        .container { max-width:1000px; margin:0 auto; }
        .court-card { background:white; border-radius:32px; overflow:hidden; box-shadow:0 12px 28px rgba(0,0,0,0.08); }
        .court-image { height:300px; background:#2b7e3a; display:flex; align-items:center; justify-content:center; color:white; }
        .court-image i { font-size:6rem; }
        .court-info { padding:2rem; }
        .court-name { font-size:2rem; color:#2b7e3a; margin-bottom:0.5rem; }
        .court-type { display:inline-block; background:#eaf5e6; padding:0.2rem 1rem; border-radius:50px; font-size:0.8rem; color:#2b7e3a; margin-bottom:1rem; }
        .details-grid { display:grid; grid-template-columns:repeat(2,1fr); gap:1rem; margin:1.5rem 0; }
        .detail-item { background:#f8faf5; padding:1rem; border-radius:16px; }
        .detail-item i { color:#2b7e3a; margin-right:0.5rem; }
        .price-box { background:#eaf5e6; padding:1rem; border-radius:16px; margin:1rem 0; }
        .price-row { display:flex; justify-content:space-between; padding:0.5rem 0; }
        .btn-book { background:#2b7e3a; color:white; border:none; padding:1rem; border-radius:50px; width:100%; font-size:1.1rem; font-weight:600; cursor:pointer; margin-top:1rem; text-decoration:none; display:inline-block; text-align:center; }
        .btn-book:hover { background:#1f5a2a; }
        .back-link { display:inline-block; margin-top:1rem; color:#2b7e3a; text-decoration:none; }
    </style>
</head>
<body>
<div class="container">
    <div class="court-card">
        <div class="court-image">
            <i class="fas fa-shuttlecock"></i>
        </div>
        <div class="court-info">
            <h1 class="court-name">🏸 <?php echo htmlspecialchars($court['court_name']); ?></h1>
            <span class="court-type"><?php echo htmlspecialchars($court['court_type']); ?></span>
            
            <div class="details-grid">
                <div class="detail-item"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($court['location'] ?? 'Main Hall'); ?></div>
                <div class="detail-item"><i class="fas fa-tools"></i> <?php echo htmlspecialchars($court['facilities'] ?? 'Shower, Locker'); ?></div>
            </div>
            
            <div class="price-box">
                <div class="price-row"><span><i class="fas fa-sun"></i> 8am - 2pm (Off-Peak)</span><span>RM <?php echo number_format($court['price_off_peak'], 2); ?> / hour</span></div>
                <div class="price-row"><span><i class="fas fa-moon"></i> 3pm - 1am (Peak)</span><span>RM <?php echo number_format($court['price_peak'], 2); ?> / hour</span></div>
            </div>
            
            <a href="book_court.php?court_id=<?php echo $court['id']; ?>" class="btn-book">Book This Court →</a>
            <a href="dashboard.php" class="back-link">← Back to Courts</a>
        </div>
    </div>
</div>
</body>
</html>