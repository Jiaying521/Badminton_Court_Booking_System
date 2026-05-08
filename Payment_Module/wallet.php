<?php
require_once __DIR__ . '/../config.php';
include 'db_connect.php'; 
if (!isLoggedIn()) redirect('homepage.php');

$user_id = $_SESSION['user_id'];

// Fetch REAL balance from database
$stmt = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$user_data = $res->fetch_assoc();
$current_balance = $user_data['wallet_balance'] ?? 0.00;

// Smart Return Logic
$return_to = $_GET['return'] ?? 'dashboard'; 
$b_id = $_GET['booking_id'] ?? 0;
$b_amt = $_GET['amount'] ?? 0;

if ($return_to === 'checkout') {
    $back_url = "checkout.php?booking_id=$b_id&amount=$b_amt";
    $back_label = "Back to Checkout";
} else {
    $back_url = "../Customer_Module/dashboard.php";
    $back_label = "Back to Dashboard";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Wallet | Smash Arena</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        body { font-family:'Inter',sans-serif; background:#f5f9f0; padding:2rem; display:flex; justify-content:center; min-height:100vh; align-items:center; }
        .wallet-card { background: white; padding: 2.5rem; border-radius: 32px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); width: 100%; max-width: 480px; text-align: center; }
        .balance-box { background: linear-gradient(135deg,#2b7e3a,#1b5e2a); color: white; padding: 2rem; border-radius: 24px; margin: 1.5rem 0; }
        .quick-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; margin-bottom: 20px; }
        .amt-btn { background: #f8faf5; border: 1.5px solid #e0e8dc; padding: 12px; border-radius: 12px; cursor: pointer; font-weight: 700; color: #2b7e3a; transition: 0.2s; }
        .amt-btn:hover { background: #2b7e3a; color: white; border-color: #2b7e3a; }
        .method-card { border: 1.5px solid #eee; padding: 15px; border-radius: 12px; margin-bottom: 10px; display: flex; align-items: center; cursor: pointer; text-align: left; }
        .topup-input { width: 100%; padding: 1rem; border-radius: 15px; border: 1.5px solid #e0e8dc; margin-bottom: 5px; text-align: center; font-size: 1.5rem; font-weight: 800; color: #2b7e3a; }
        .btn-reload { background: #2b7e3a; color: white; border: none; padding: 1.2rem; border-radius: 50px; width: 100%; font-weight: 700; cursor: pointer; }
    </style>
</head>
<body>
    <div class="wallet-card">
        <h2 style="color: #2b7e3a;"><i class="fas fa-wallet"></i> Smash Wallet</h2>
        <div class="balance-box">
            <p style="opacity: 0.8; font-size: 0.9rem;">Current Balance</p>
            <h1 style="font-size: 2.8rem; margin: 5px 0;">RM <?php echo number_format($current_balance, 2); ?></h1>
        </div>

        <p style="text-align: left; font-weight: 600; font-size: 0.9rem; margin-bottom: 10px;">Quick Select:</p>
        <div class="quick-grid">
            <button type="button" class="amt-btn" onclick="selectAmt(10)">RM 10</button>
            <button type="button" class="amt-btn" onclick="selectAmt(20)">RM 20</button>
            <button type="button" class="amt-btn" onclick="selectAmt(50)">RM 50</button>
        </div>

        <form action="wallet_gateway.php" method="POST" onsubmit="return validateReload()">
            <input type="hidden" name="return_to" value="<?php echo $return_to; ?>">
            <input type="hidden" name="booking_id" value="<?php echo $b_id; ?>">
            <input type="hidden" name="amount" value="<?php echo $b_amt; ?>">

            <input type="number" name="reload_amount" id="reload_amt" class="topup-input" placeholder="0.00" min="1" max="1000" step="0.01">
            
            <p style="text-align: left; font-weight: 600; font-size: 0.9rem; margin: 15px 0 10px;">Payment Method:</p>
            <label class="method-card">
                <input type="radio" name="pay_method" value="Bank" checked>
                <span style="margin-left:10px;">🏦 Online Banking (FPX)</span>
            </label>
            <label class="method-card">
                <input type="radio" name="pay_method" value="TNG">
                <span style="margin-left:10px;">📱 Touch 'n Go eWallet</span>
            </label>

            <button type="submit" class="btn-reload" style="margin-top: 10px;">Next Step →</button>
        </form>
        <a href="<?php echo $back_url; ?>" style="display:block; margin-top:1.5rem; color:#888; text-decoration:none;">← <?php echo $back_label; ?></a>
    </div>

    <script>
        function selectAmt(v) { document.getElementById('reload_amt').value = v; }
        function validateReload() {
            const a = document.getElementById('reload_amt').value;
            if (a < 1) { alert("Minimum RM 1.00"); return false; }
            return true;
        }
    </script>
</body>
</html>