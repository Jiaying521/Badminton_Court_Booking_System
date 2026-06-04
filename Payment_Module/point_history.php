<?php
// Payment_Module/point_history.php - Loyalty Points Log & Voucher Inventory
// Start our session tracking to access the logged-in user's data
session_start();
// Bring in our database connection setup file
include 'db_connect.php';

// If the user isn't logged in, kick them back to the customer home page safely
$user_id = $_SESSION['user_id'] ?? 0;
if ($user_id == 0) {
    die("<script>window.location.href='../Customer_Module/homepage.php';</script>");
}

// 1. Fetch User Info for greeting header layout elements
$user_name = "Player";
// Secure query to find the user's name from their user ID
$u_stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$u_stmt->bind_param("i", $user_id);
$u_stmt->execute();
$u_res = $u_stmt->get_result();
if ($u_row = $u_res->fetch_assoc()) {
    $user_name = $u_row['name'];
}

// 2. Read live points balance directly from users.loyalty_points (single source of truth)
$stmt = $conn->prepare("SELECT loyalty_points FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$current_points = (int)($stmt->get_result()->fetch_row()[0] ?? 0);


// 3. FETCH EARNED LOG ITEMS (From Confirmed Bookings)
$earned_list = [];
// Query to pull all successful bookings so we can show them as point-earning history entries
$e_stmt = $conn->prepare("
    SELECT id, booking_date, total_price 
    FROM bookings 
    WHERE user_id = ? AND status = 'Confirmed'
    ORDER BY booking_date DESC, created_at DESC
");
$e_stmt->bind_param("i", $user_id);
$e_stmt->execute();
$e_res = $e_stmt->get_result();
while ($row = $e_res->fetch_assoc()) {
    // Structure each row as an 'Earned' point transaction type log item
    $earned_list[] = [
        'type' => 'Earned',
        'title' => 'Court Booking Reward (ID: #' . $row['id'] . ')',
        'date' => $row['booking_date'],
        'points' => floor($row['total_price'] * 1), // RM1 = 1 point formatting layout
        'amount_info' => 'Spent RM ' . number_format($row['total_price'], 2)
    ];
}

// 4. FETCH SPENT LOG ITEMS (From Redeemed Vouchers)
$spent_list = [];
// Query to pull all the shop voucher redemptions so we can show them as points spent
$s_stmt = $conn->prepare("
    SELECT uv.redeemed_at, v.title, v.points_required 
    FROM user_vouchers uv 
    JOIN voucher v ON uv.voucher_id = v.id 
    WHERE uv.user_id = ?
    ORDER BY uv.redeemed_at DESC
");
$s_stmt->bind_param("i", $user_id);
$s_stmt->execute();
$s_res = $s_stmt->get_result();
while ($row = $s_res->fetch_assoc()) {
    // Structure each row as a 'Spent' point transaction type log item
    $spent_list[] = [
        'type' => 'Spent',
        'title' => 'Redeemed ' . $row['title'],
        'date' => date('Y-m-d', strtotime($row['redeemed_at'])),
        'points' => $row['points_required'],
        'amount_info' => 'Voucher Claimed'
    ];
}

// Combine both arrays (Earned + Spent) into a single master history list stream
$history_log = array_merge($earned_list, $spent_list);
// Sort the master logs array so the newest points events appear right at the top
usort($history_log, function($a, $b) {
    return strcmp($b['date'], $a['date']);
});


// 5. FETCH ALL USER OWNED VOUCHERS INVENTORY IN DETAIL
$user_inventory = [];
// Query to pull every voucher the user owns to display in their visual wallet collection
$inv_stmt = $conn->prepare("
    SELECT uv.id, uv.is_used, uv.redeemed_at, v.title, v.discount_amount 
    FROM user_vouchers uv
    JOIN voucher v ON uv.voucher_id = v.id
    WHERE uv.user_id = ?
    ORDER BY uv.is_used ASC, uv.redeemed_at DESC
");
$inv_stmt->bind_param("i", $user_id);
$inv_stmt->execute();
$inv_res = $inv_stmt->get_result();
while ($row = $inv_res->fetch_assoc()) {
    // Push each voucher record into our active client inventory array list
    $user_inventory[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Points & Vouchers History | Smash Arena</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* CSS resets to clear uneven baseline browser margins spacing */
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:linear-gradient(145deg,#f5f9f0 0%,#e8efe2 100%); color:#1e2a2e; padding:2rem; }
        .container { max-width:1200px; margin:0 auto; }
        
        /* Layout navigation header options rows style guidelines blueprint templates grids */
        .navbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:2rem; padding-bottom:1rem; border-bottom:1px solid rgba(43,126,58,0.15); }
        .logo-area { display:flex; align-items:center; gap:0.8rem; text-decoration:none; color: inherit; }
        .logo-text { font-size:1.3rem; font-weight:700; color:#2b7e3a; }
        .logo-text span { color:#e67e22; }
        .nav-links { display:flex; align-items:center; gap:1.5rem; }
        .nav-links a { color:#2c4a2e; text-decoration:none; font-weight:500; }
        .nav-links a:hover { color:#2b7e3a; }
        
        .header-section { display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem; }
        .header-section h1 { color:#1e3a2a; font-size:1.8rem; }
        
        /* Grand totals indicator green summary block parameters layouts styling properties links keys */
        .points-summary-card { background:linear-gradient(135deg,#2b7e3a,#1b5e2a); color:white; padding:1.5rem 2rem; border-radius:24px; display:flex; align-items:center; justify-content:space-between; margin-bottom:2rem; box-shadow:0 6px 18px rgba(43,126,58,0.15); }
        .points-val { font-size:2.5rem; font-weight:800; }
        .btn-shop { background:white; color:#2b7e3a; padding:0.6rem 1.2rem; border-radius:50px; font-weight:700; text-decoration:none; font-size:0.85rem; display:inline-flex; align-items:center; gap:5px; transition:0.2s; }
        .btn-shop:hover { transform:translateY(-1px); box-shadow:0 4px 12px rgba(0,0,0,0.1); }

        /* Split display panels schema: 2 columns wide split blueprint framework columns */
        .history-grid { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; }
        .panel-box { background:white; border-radius:24px; padding:1.5rem; box-shadow:0 4px 15px rgba(0,0,0,0.02); border:1px solid rgba(43,126,58,0.06); }
        .panel-title { font-size:1.15rem; font-weight:700; color:#1e3a2a; margin-bottom:1.2rem; display:flex; align-items:center; gap:0.5rem; border-bottom:2px solid #eaf5e6; padding-bottom:0.5rem; }
        
        /* Log history listing sheet row lines templates formatting rules columns properties keys */
        .log-table { width:100%; border-collapse:collapse; }
        .log-table td { padding:1rem 0.5rem; border-bottom:1px solid #f0f4ee; vertical-align:middle; font-size:0.85rem; }
        .text-earned { color:#2b7e3a; font-weight:700; font-size:1rem; }
        .text-spent { color:#e67e22; font-weight:700; font-size:1rem; }
        
        /* Coupons inventory items container blueprints templates elements styles links */
        .coupon-grid { display:flex; flex-direction:column; gap:0.8rem; }
        .coupon-card { border:1px dashed #cbd5c0; background:#fafdf7; border-radius:14px; padding:1rem; display:flex; justify-content:space-between; align-items:center; }
        .coupon-card.used-coupon { background:#f5f5f5; border-style:solid; border-color:#e0e0e0; opacity:0.7; }
        
        .badge { display:inline-block; padding:0.25rem 0.6rem; border-radius:50px; font-size:0.7rem; font-weight:600; }
        .badge-active { background:#d4edda; color:#155724; }
        .badge-used { background:#e0e0e0; color:#555; }

        /* Responsive design utility adjusting column structure grid definitions automatically on phone viewports scale */
        @media (max-width:768px) { .history-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body>
<div class="container">

    <div class="navbar">
        <a href="../Customer_Module/dashboard.php" class="logo-area">
            <div class="logo-text">Smash <span>Arena</span></div>
        </a>
        <div class="nav-links">
            <a href="../Customer_Module/dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
            <a href="../Customer_Module/my_bookings.php"><i class="fas fa-bookmark"></i> My Bookings</a>
            <a href="wallet.php"><i class="fas fa-wallet"></i> Wallet</a>
        </div>
    </div>

    <div class="header-section">
        <h1><i class="fas fa-history" style="color:#2b7e3a;"></i> Reward Points Ledger</h1>
    </div>

    <div class="points-summary-card">
        <div>
            <p style="text-transform:uppercase; font-size:0.75rem; font-weight:700; letter-spacing:0.5px; opacity:0.85;">Current Rewards Balance</p>
            <div class="points-val"><?php echo $current_points; ?> <span style="font-size:1.1rem; font-weight:400;">Available Points</span></div>
        </div>
        <a href="redeem_voucher.php" class="btn-shop"><i class="fas fa-store"></i> Redeem More Vouchers</a>
    </div>

    <div class="history-grid">
        
        <div class="panel-box">
            <div class="panel-title"><i class="fas fa-exchange-alt" style="color:#2b7e3a;"></i> Points Transaction Log</div>
            
            <?php if(count($history_log) > 0): ?>
                <table class="log-table">
                    <tbody>
                        <?php foreach($history_log as $log): ?>
                            <tr>
                                <td>
                                    <div style="font-weight:600; color:#2c4a2e;"><?php echo htmlspecialchars($log['title']); ?></div>
                                    <small style="color:#888;"><?php echo date('M j, Y', strtotime($log['date'])); ?> • <?php echo $log['amount_info']; ?></small>
                                </td>
                                <td style="text-align:right;">
                                    <?php if($log['type'] === 'Earned'): ?>
                                        <span class="text-earned">+<?php echo $log['points']; ?> Pts</span>
                                    <?php else: ?>
                                        <span class="text-spent">-<?php echo $log['points']; ?> Pts</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color:#888; text-align:center; padding:2rem; font-size:0.9rem;">No reward points movements logged yet.</p>
            <?php endif; ?>
        </div>

        <div class="panel-box">
            <div class="panel-title"><i class="fas fa-ticket-alt" style="color:#e67e22;"></i> My Redeemed Vouchers Collection</div>
            
            <div class="coupon-grid">
                <?php if(count($user_inventory) > 0): ?>
                    <?php foreach($user_inventory as $coupon): ?>
                        <div class="coupon-card <?php echo ($coupon['is_used'] == 1) ? 'used-coupon' : ''; ?>">
                            <div>
                                <h4 style="color:#1e3a2a; font-weight:700;"><i class="fas fa-gift" style="color:#e67e22; margin-right:4px;"></i> <?php echo htmlspecialchars($coupon['title']); ?></h4>
                                <small style="color:#777; display:block; margin-top:3px;">Claimed: <?php echo date('M j, Y', strtotime($coupon['redeemed_at'])); ?></small>
                            </div>
                            <div style="text-align:right;">
                                <?php if($coupon['is_used'] == 0): ?>
                                    <span class="badge badge-active"><i class="fas fa-check"></i> Active (Unused)</span>
                                    <p style="font-size:10px; color:#2b7e3a; font-weight:bold; margin-top:4px;">Ready at Checkout</p>
                                <?php else: ?>
                                    <span class="badge badge-used"><i class="fas fa-minus-circle"></i> Spent / Applied</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#888; text-align:center; padding:2rem; font-size:0.9rem;">Your voucher wallet is currently empty.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>
</body>
</html>