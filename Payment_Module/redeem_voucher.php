<?php
// Payment_Module/redeem_voucher.php - The Rewards Shop Interface Flow
// Bring in core system configuration settings
require_once __DIR__ . '/../config.php';
// Include functions from the customer module to check sessions or redirects
require_once __DIR__ . '/../Customer_Module/functions.php'; 

// If the user isn't logged in, send them straight to the homepage
if(!isLoggedIn()) redirect('../Customer_Module/homepage.php');
// Grab the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Fetch user info, wallet balance, avatar
$userStmt = $pdo->prepare("SELECT name, profile_picture, wallet_balance, loyalty_points FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch();

$current_points = (int)($user['loyalty_points'] ?? 0);
$real_balance   = $user['wallet_balance'] ?? 0.00;

$defaultAvatarPath = '../image/default_image.png';
$avatarPath = $defaultAvatarPath;
$profile_picture = $user['profile_picture'] ?? '';
if (!empty($profile_picture)) {
    $fullPath = __DIR__ . '/../' . $profile_picture;
    if (file_exists($fullPath)) {
        $avatarPath = '../' . $profile_picture . '?v=' . filemtime($fullPath);
    }
}

// Check if the user clicked a "Redeem" link button on the UI shelf
if (isset($_GET['action']) && $_GET['action'] === 'redeem') {
    $voucher_id = $_GET['voucher_id'] ?? 0;
    
    // Check if the voucher they are trying to buy actually exists on the shelf
    $stmt = $pdo->prepare("SELECT * FROM voucher WHERE id = ?");
    $stmt->execute([$voucher_id]);
    $v_item = $stmt->fetch();
    
    // If the voucher exists in our shop setup
    if ($v_item) {
        $now = date('Y-m-d H:i:s');

        // Availability window: must be started and not expired
        $not_started = !empty($v_item['valid_from'])  && $now < $v_item['valid_from'];
        $expired     = !empty($v_item['valid_until']) && $now > $v_item['valid_until'];

        // Stock: count how many have already been claimed (empty quantity = unlimited)
        $sold_out = false;
        if ($v_item['quantity'] !== null && $v_item['quantity'] !== '') {
            $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM user_vouchers WHERE voucher_id = ?");
            $cntStmt->execute([$voucher_id]);
            $claimed  = (int)$cntStmt->fetchColumn();
            $sold_out = $claimed >= (int)$v_item['quantity'];
        }

        // Per-customer redeem limit: check how many times they already claimed this voucher
        $per_user_limit = max(1, (int)($v_item['per_user_limit'] ?? 1));
        $dupStmt = $pdo->prepare("SELECT COUNT(*) FROM user_vouchers WHERE user_id = ? AND voucher_id = ?");
        $dupStmt->execute([$user_id, $voucher_id]);
        $my_claimed = (int)$dupStmt->fetchColumn();

        if ($my_claimed >= $per_user_limit) {
            echo "<script>alert('⚠️ You have reached the redeem limit for this voucher ($per_user_limit time(s) per account).'); window.location.href='redeem_voucher.php';</script>";
            exit;
        }
        if ($not_started) {
            echo "<script>alert('This voucher is not available yet.'); window.location.href='redeem_voucher.php';</script>";
            exit;
        }
        if ($expired) {
            echo "<script>alert('This voucher has expired.'); window.location.href='redeem_voucher.php';</script>";
            exit;
        }
        if ($sold_out) {
            echo "<script>alert('This voucher is out of stock.'); window.location.href='redeem_voucher.php';</script>";
            exit;
        }

        // Make sure the user's current points balance is high enough to afford it
        if ($current_points >= $v_item['points_required']) {
            // Success: Insert a new voucher entry row into their personal inventory wallet
            $ins = $pdo->prepare("INSERT INTO user_vouchers (user_id, voucher_id, is_used) VALUES (?, ?, 0)");
            $ins->execute([$user_id, $voucher_id]);

            // Deduct points from users.loyalty_points
            $deduct = $pdo->prepare("UPDATE users SET loyalty_points = loyalty_points - ? WHERE id = ?");
            $deduct->execute([(int)$v_item['points_required'], $user_id]);

            // Pop up a clean success alert box and refresh the shop interface page
            echo "<script>alert('Successfully redeemed " . $v_item['title'] . "!'); window.location.href='redeem_voucher.php';</script>";
            exit;
        } else {
            // Failure: Pop up a warning box if they try to buy something they can't afford
            echo "<script>alert('⚠️ You do not have enough points to redeem this voucher!'); window.location.href='redeem_voucher.php';</script>";
            exit;
        }
    }
}

// Pull all our standard shop vouchers, sorting them from cheapest to most expensive
$shop_stmt = $pdo->prepare("
    SELECT v.*,
           (SELECT COUNT(*) FROM user_vouchers uv WHERE uv.voucher_id = v.id) AS claimed_count,
           (SELECT COUNT(*) FROM user_vouchers uv2 WHERE uv2.voucher_id = v.id AND uv2.user_id = ?) AS my_claimed
    FROM voucher v
    ORDER BY v.points_required ASC
");
$shop_stmt->execute([$user_id]);
$vouchers_shop_list = $shop_stmt->fetchAll();

// Used to check each voucher's availability window while rendering the shelf
$now = date('Y-m-d H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Redeem Rewards | Smash Arena</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Montserrat:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
* { margin: 0; padding: 0; box-sizing: border-box; }

body {
    font-family: 'Inter', sans-serif;
    background: linear-gradient(145deg, #f5f9f0 0%, #e8efe2 100%);
    color: #1e2a2e;
    padding: 2rem;
    min-height: 100vh;
}

.container { max-width: 1200px; margin: 0 auto; }

/* ── Navbar (dashboard style) ── */
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
}

.logo-area {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    text-decoration: none;
}

.logo-area img { height: 45px; width: auto; }

.logo-text {
    font-family: 'Montserrat', sans-serif;
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
    gap: 0.8rem;
    flex-wrap: wrap;
}

.nav-links a {
    font-family: 'Montserrat', sans-serif;
    font-weight: 600;
    color: #2c4a2e;
    text-decoration: none;
    transition: all 0.3s ease;
    padding: 0.5rem 1.2rem;
    border-radius: 50px;
    display: inline-block;
}

.nav-links a:hover, .nav-links a.active {
    background: #2b7e3a;
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(43,126,58,0.3);
}

.nav-links a.user-profile,
.user-profile {
    display: flex !important;
    align-items: center;
    flex-wrap: nowrap;
    gap: 0.6rem;
    background: rgba(234,245,230,0.6);
    padding: 0.2rem 0.8rem 0.2rem 0.3rem;
    border-radius: 50px;
}

.user-profile:hover { background: rgba(43,126,58,0.15); transform: translateY(-2px); }

.user-avatar {
    width: 32px; height: 32px; min-width: 32px;
    border-radius: 50%; overflow: hidden;
    background: #2b7e3a; flex-shrink: 0;
}

.user-avatar img { width: 100%; height: 100%; object-fit: cover; }

.user-info { text-align: left; }

.user-name {
    font-family: 'Montserrat', sans-serif;
    font-weight: 600; font-size: 0.75rem;
    color: #1e3a2a; white-space: nowrap;
}

.user-balance { font-size: 0.65rem; color: #2b7e3a; font-weight: 500; }

.btn-back {
    background: #2b7e3a;
    color: white;
    padding: 0.5rem 1.2rem;
    border-radius: 50px;
    font-weight: 600;
    font-size: 0.85rem;
    text-decoration: none;
    transition: background 0.2s ease, transform 0.2s ease;
}

.btn-back:hover { background: #1f5a2a; transform: translateY(-1px); }

/* ── Tabs ── */
.tabs-container {
    display: flex; gap: 10px;
    margin-bottom: 1.5rem;
    border-bottom: 2px solid #eef3eb;
    padding-bottom: 10px;
}

.tab-btn {
    flex: 1; padding: 12px 20px; border-radius: 14px;
    font-weight: 700; font-size: 0.9rem;
    text-decoration: none; text-align: center;
    transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
}

.tab-btn.active { background: #2b7e3a; color: white; box-shadow: 0 4px 12px rgba(43,126,58,0.15); }
.tab-btn.inactive { background: white; color: #5a6e5c; border: 1px solid #e4ebe0; }
.tab-btn:hover { transform: translateY(-2px) scale(1.02); box-shadow: 0 6px 15px rgba(0,0,0,0.08); }
.tab-btn.active:hover { background: #1f5a2a; box-shadow: 0 6px 15px rgba(43,126,58,0.25); }
.tab-btn.inactive:hover { background: #f4f7f2; color: #1e3a2a; border-color: #2b7e3a; }
.tab-btn:active { transform: translateY(1px) scale(0.98); }

/* ── Points summary ── */
.points-summary-card {
    background: linear-gradient(135deg, #2b7e3a, #1b5e2a);
    color: white; padding: 1.5rem 2rem; border-radius: 24px;
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 2rem;
    box-shadow: 0 6px 18px rgba(43,126,58,0.15);
}

.points-val { font-size: 2.5rem; font-weight: 800; }

/* ── Voucher list ── */
.voucher-list { display: flex; flex-direction: column; gap: 1rem; }

.voucher-row {
    border: 2px dashed #cbd5c0; background: #fafdf7;
    border-radius: 20px; padding: 1.5rem;
    display: flex; justify-content: space-between; align-items: center;
    transition: 0.2s;
}

.voucher-row:hover { border-color: #2b7e3a; background: #fdfefb; transform: translateY(-1px); }

.voucher-details h3 {
    color: #1e3a2a; font-size: 1.1rem; font-weight: 700;
    display: flex; align-items: center; gap: 0.5rem;
}

.voucher-details p { color: #666; font-size: 0.85rem; margin-top: 5px; }

.btn-redeem {
    background: #2b7e3a; color: white; border: none;
    padding: 0.6rem 1.2rem; border-radius: 50px;
    font-weight: 600; text-decoration: none; font-size: 0.8rem;
    text-align: center; transition: 0.2s;
    display: inline-flex; align-items: center; white-space: nowrap;
}

.btn-redeem:hover { background: #1f5a2a; transform: translateY(-1px); }
.btn-locked { background: #e0e0e0; color: #888; cursor: not-allowed; }

@media (max-width: 768px) {
    .navbar { flex-direction: column; border-radius: 28px; }
    .voucher-row { flex-direction: column; align-items: flex-start; gap: 1rem; }
}
    </style>
</head>
<body>
<div class="container">

    <!-- Navbar -->
    <div class="navbar">
        <a href="../Customer_Module/dashboard.php" class="logo-area">
            <img src="../Pictures/Admin_Module/logo.png" alt="Smash Arena" onerror="this.style.display='none'">
            <div class="logo-text">Smash <span>Arena</span></div>
        </a>
        <a href="../Customer_Module/dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
    </div>

    <!-- Tabs -->
    <div class="tabs-container">
        <a href="redeem_voucher.php" class="tab-btn active">
            <i class="fas fa-ticket-alt"></i> Redeem Vouchers
        </a>
        <a href="point_history.php" class="tab-btn inactive">
            <i class="fas fa-history"></i> My Claims & Point History
        </a>
    </div>

    <!-- Points Summary -->
    <div class="points-summary-card">
        <div>
            <p style="text-transform:uppercase; font-size:0.75rem; font-weight:700; letter-spacing:0.5px; opacity:0.85;">Current Rewards Balance</p>
            <div class="points-val"><?php echo $current_points; ?> <span style="font-size:1.1rem; font-weight:400;">Available Points</span></div>
        </div>
    </div>

    <!-- Voucher Shop -->
    <div class="voucher-list">
        <?php foreach($vouchers_shop_list as $v):
            $not_started = !empty($v['valid_from'])  && $now < $v['valid_from'];
            $expired     = !empty($v['valid_until']) && $now > $v['valid_until'];
            $has_qty     = $v['quantity'] !== null && $v['quantity'] !== '';
            $remaining   = $has_qty ? max(0, (int)$v['quantity'] - (int)$v['claimed_count']) : null;
            $sold_out    = $has_qty && $remaining <= 0;
            $available   = !$not_started && !$expired && !$sold_out;
            $affordable  = $current_points >= $v['points_required'];
            $limit       = max(1, (int)($v['per_user_limit'] ?? 1));
            $mine        = (int)$v['my_claimed'] >= $limit;
        ?>
            <div class="voucher-row">
                <div class="voucher-details">
                    <h3><i class="fas fa-ticket-alt" style="color:#e67e22;"></i> <?php echo htmlspecialchars($v['title']); ?></h3>
                    <p><?php echo htmlspecialchars($v['description']); ?> • Deducts <?php echo $v['points_required']; ?> points</p>
                    <?php if ($v['valid_until']): ?>
                        <p style="color:#9aa59b; font-size:0.78rem; margin-top:3px;">
                            <i class="fas fa-clock"></i> Available until <?php echo date('d M Y g:i A', strtotime($v['valid_until'])); ?>
                        </p>
                    <?php endif; ?>
                    <?php if ($has_qty && $available): ?>
                        <p style="color:#9aa59b; font-size:0.78rem; margin-top:3px;">
                            <i class="fas fa-box"></i> <?php echo $remaining; ?> left
                        </p>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if ($mine): ?>
                        <button class="btn-redeem btn-locked" disabled>
                            <i class="fas fa-check-circle" style="margin-right:5px;"></i> Redeem Limit Reached
                        </button>
                    <?php elseif (!$available): ?>
                        <button class="btn-redeem btn-locked" disabled>
                            <i class="fas fa-lock" style="margin-right:5px;"></i>
                            <?php echo $sold_out ? 'Out of Stock' : ($expired ? 'Expired' : 'Not Available Yet'); ?>
                        </button>
                    <?php elseif ($affordable): ?>
                        <a href="redeem_voucher.php?action=redeem&voucher_id=<?php echo $v['id']; ?>" class="btn-redeem" onclick="return confirm('Confirm spending <?php echo $v['points_required']; ?> points to redeem this voucher?')">
                            <i class="fas fa-shopping-cart" style="margin-right:5px;"></i> Redeem
                        </a>
                    <?php else: ?>
                        <button class="btn-redeem btn-locked" disabled>
                            <i class="fas fa-lock" style="margin-right:5px;"></i> Insufficient Points
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>
</body>
</html>