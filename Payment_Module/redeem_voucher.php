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

// 1. 获取用户信息
// Secure query to fetch the user's name from their user ID
$userStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
$userStmt->execute([$user_id]);
$user = $userStmt->fetch();

// 2. 计算用户赚取到的总积分 (RM 1 spent = 1 Point)
// Sum up the subtotal prices of all their 'Confirmed' court bookings
$stmt = $pdo->prepare("SELECT SUM(total_price) FROM bookings WHERE user_id = ? AND status = 'Confirmed'");
$stmt->execute([$user_id]);
$totalSpent = $stmt->fetchColumn() ?? 0;
$totalPointsEarned = floor($totalSpent * 1); // Our new RM1 = 1 point formula rate

// 3. 计算用户已经消耗用掉的积分
// Sum up the point costs of all vouchers this user has already claimed
$stmt = $pdo->prepare("
    SELECT SUM(v.points_required) 
    FROM user_vouchers uv 
    JOIN voucher v ON uv.voucher_id = v.id 
    WHERE uv.user_id = ?
");
$stmt->execute([$user_id]);
$pointsUsed = $stmt->fetchColumn() ?? 0;

// Find their current available balance by subtracting used points from lifetime earned points
$current_points = $totalPointsEarned - $pointsUsed;

// 4. 处理用户点击 "Redeem" 按钮的后台动作逻辑
// Check if the user clicked a "Redeem" link button on the UI shelf
if (isset($_GET['action']) && $_GET['action'] === 'redeem') {
    $voucher_id = $_GET['voucher_id'] ?? 0;
    
    // Check if the voucher they are trying to buy actually exists on the shelf
    $stmt = $pdo->prepare("SELECT * FROM voucher WHERE id = ?");
    $stmt->execute([$voucher_id]);
    $v_item = $stmt->fetch();
    
    // If the voucher exists in our shop setup
    if ($v_item) {
        // Make sure the user's current points balance is high enough to afford it
        if ($current_points >= $v_item['points_required']) {
            // Success: Insert a new voucher entry row into their personal inventory wallet
            $ins = $pdo->prepare("INSERT INTO user_vouchers (user_id, voucher_id, is_used) VALUES (?, ?, 0)");
            $ins->execute([$user_id, $voucher_id]);
            
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

// 5. 从数据库里拉取所有可在商店里兑换的优惠券货架列表
// Pull all our standard shop vouchers, sorting them from cheapest to most expensive
$vouchers_shop_list = $pdo->query("SELECT * FROM voucher ORDER BY points_required ASC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Redeem Rewards | Smash Arena</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* General CSS layout resets to strip away default padding spaces */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:linear-gradient(145deg,#f5f9f0 0%,#e8efe2 100%); color:#1e2a2e; padding:2rem; }
        /* Central layout whiteboard panel frame parameters */
        .container { max-width:800px; margin:0 auto; background: white; padding: 2.5rem; border-radius: 28px; box-shadow:0 8px 25px rgba(0,0,0,0.04); text-align: center; border: 1px solid rgba(43,126,58,0.08);}
        
        /* Green active balance wallet card block element styling */
        .points-box { background: linear-gradient(135deg, #2b7e3a, #1b5e2a); color: white; padding: 1.8rem; border-radius: 24px; margin: 1.5rem auto; width: 100%; max-width: 460px; box-shadow: 0 6px 20px rgba(43,126,58,0.2);}
        .points-box p { opacity: 0.85; font-size: 0.9rem; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px;}
        .points-box h1 { font-size: 2.8rem; font-weight: 800; margin-top: 4px; }
        .points-box h1 span { font-size: 1.1rem; font-weight: 400; opacity: 0.9; }

        /* Vouchers listing row cards shelf styles framework definitions */
        .voucher-list { display: flex; flex-direction: column; gap: 1rem; margin-top: 2.5rem; text-align: left; }
        .voucher-row { border: 2px dashed #cbd5c0; background: #fafdf7; border-radius: 20px; padding: 1.5rem; display: flex; justify-content: space-between; align-items: center; transition: 0.2s; }
        .voucher-row:hover { border-color: #2b7e3a; background: #fdfefb; transform: translateY(-1px); }
        
        .voucher-details h3 { color: #1e3a2a; font-size: 1.15rem; font-weight: 700; display: flex; align-items: center; gap: 0.5rem; }
        .voucher-details p { color: #666; font-size: 0.85rem; margin-top: 5px; }
        
        /* Interactive green claim purchase action button module styling specifications */
        .btn-redeem { background: #2b7e3a; color: white; border: none; padding: 0.6rem 1.2rem; border-radius: 50px; font-weight: 600; text-decoration: none; font-size: 0.8rem; text-align: center; transition: 0.2s; display: inline-flex; align-items: center; }
        .btn-redeem:hover { background: #1f5a2a; transform: translateY(-1px); }
        /* Grey background locked state button style if funds are short */
        .btn-locked { background: #e0e0e0; color: #888; cursor: not-allowed; }
        
        .back-link { display: inline-block; margin-top: 2.5rem; color: #2b7e3a; text-decoration: none; font-weight: 600; font-size: 0.9rem; transition: 0.2s; }
        .back-link:hover { color: #1f5a2a; letter-spacing: -0.2px; }
    </style>
</head>
<body>

<div class="container">
    <h2 style="color: #2b7e3a; font-weight: 800;"><i class="fas fa-star"></i> Smash Arena Rewards Hub</h2>
    
    <a href="point_history.php" style="color: #e67e22; font-weight: 700; font-size: 0.85rem; text-decoration: underline; display: inline-block; margin-top: 5px; margin-bottom: 5px;">
        <i class="fas fa-list-alt"></i> View My Points History & Redeemed Vouchers →
    </a>

    <p style="color: #5a6e5c; font-size: 0.9rem; margin-top: 6px;">Hi <?php echo htmlspecialchars($user['name'] ?? 'Player'); ?>, trade your hard-earned points for court discount vouchers!</p>
    
    <div class="points-box">
        <p>Available Balance</p>
        <h1><?php echo $current_points; ?> <span>Points</span></h1>
    </div>

    <div class="voucher-list">
        <?php foreach($vouchers_shop_list as $v): ?>
            <div class="voucher-row">
                <div class="voucher-details">
                    <h3><i class="fas fa-ticket-alt" style="color: #e67e22;"></i> <?php echo htmlspecialchars($v['title']); ?></h3>
                    <p><?php echo htmlspecialchars($v['description']); ?> • Deducts <?php echo $v['points_required']; ?> points</p>
                </div>
                <div>
                    <?php if ($current_points >= $v['points_required']): ?>
                        <a href="redeem_voucher.php?action=redeem&voucher_id=<?php echo $v['id']; ?>" class="btn-redeem" onclick="return confirm('Confirm spending <?php echo $v['points_required']; ?> points to redeem this voucher?')">
                            <i class="fas fa-shopping-cart" style="margin-right: 5px;"></i> Redeem
                        </a>
                    <?php else: ?>
                        <button class="btn-redeem btn-locked" disabled>
                            <i class="fas fa-lock" style="margin-right: 5px;"></i> Insufficient Points
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <a href="../Customer_Module/dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Return to Dashboard</a>
</div>

</body>
</html>