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
<html lang="en">
<head>
    <title>My Wallet | Smash Arena</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f5f9f0; padding:2rem; display:flex; justify-content:center; min-height:100vh; align-items:center; }
        
        .wallet-card { background: white; padding: 2.5rem; border-radius: 32px; box-shadow: 0 15px 35px rgba(0,0,0,0.05); width: 100%; max-width: 480px; text-align: center; border: 1px solid #eaf5e6; }
        
        .balance-box { background: linear-gradient(135deg,#2b7e3a,#113f19); color: white; padding: 2rem; border-radius: 24px; margin: 1.5rem 0; box-shadow: 0 8px 20px rgba(43,126,58,0.15); }
        
        .quick-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
        
        .amt-btn { background: #f8faf5; border: 1.5px solid #e0e8dc; padding: 14px; border-radius: 14px; cursor: pointer; font-weight: 700; color: #2b7e3a; transition: 0.2s; font-size: 0.95rem; }
        .amt-btn:hover { background: #eaf5e6; color: #1f5a2a; border-color: #2b7e3a; }
        .amt-btn.active { background: #2b7e3a; color: white; border-color: #2b7e3a; box-shadow: 0 4px 10px rgba(43,126,58,0.2); }
        
        .method-card { border: 1.5px solid #e0e0e0; padding: 16px; border-radius: 14px; margin-bottom: 12px; display: flex; align-items: center; cursor: pointer; text-align: left; background: white; transition: 0.2s; }
        .method-card:hover { border-color: #2b7e3a; box-shadow: 0 4px 12px rgba(0,0,0,0.03); }
        .method-card input[type="radio"] { accent-color: #2b7e3a; transform: scale(1.1); }
        
        .topup-input { width: 100%; padding: 1rem; border-radius: 16px; border: 2px solid #e0e8dc; margin-bottom: 15px; text-align: center; font-size: 1.6rem; font-weight: 800; color: #2b7e3a; background: #fafdfa; outline: none; transition: 0.2s; }
        .topup-input:focus { border-color: #2b7e3a; background: white; box-shadow: 0 0 0 4px rgba(43,126,58,0.08); }
        
        .btn-reload { background: #2b7e3a; color: white; border: none; padding: 1.1rem; border-radius: 50px; width: 100%; font-weight: 700; cursor: pointer; font-size: 1rem; transition: 0.2s; box-shadow: 0 4px 12px rgba(43,126,58,0.15); }
        .btn-reload:hover { background: #1f5a2a; transform: translateY(-1px); }
        
        .back-link { display: inline-block; margin-top: 1.5rem; color: #666; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: 0.2s; }
        .back-link:hover { color: #ff4d4d; text-decoration: underline; }
    </style>
</head>
<body>
    <div class="wallet-card">
        <h2 style="color: #2b7e3a; font-weight: 800; font-size: 1.6rem;"><i class="fas fa-wallet"></i> Smash Wallet</h2>
        
        <div class="balance-box">
            <p style="opacity: 0.85; font-size: 0.9rem; font-weight: 500; letter-spacing: 0.5px;">Current Balance</p>
            <h1 style="font-size: 2.8rem; margin: 5px 0; font-weight: 800; letter-spacing: -0.5px;">RM <?php echo number_format($current_balance, 2); ?></h1>
        </div>

        <p style="text-align: left; font-weight: 700; font-size: 0.9rem; color: #1e3a2a; margin-bottom: 10px;">Quick Select:</p>
        <div class="quick-grid">
            <button type="button" class="amt-btn" onclick="selectAmt(10, this)">RM 10</button>
            <button type="button" class="amt-btn" onclick="selectAmt(20, this)">RM 20</button>
            <button type="button" class="amt-btn" onclick="selectAmt(50, this)">RM 50</button>
        </div>

        <form action="wallet_gateway.php" method="POST" onsubmit="return validateReload()">
            <input type="hidden" name="return_to" value="<?php echo $return_to; ?>">
            <input type="hidden" name="booking_id" value="<?php echo $b_id; ?>">
            <input type="hidden" name="amount" value="<?php echo $b_amt; ?>">

            <input type="number" name="reload_amount" id="reload_amt" class="topup-input" placeholder="0.00" min="1" max="1000" step="0.01" oninput="this.value = this.value.replace(/[^0-9.]/g, '');">
            
            <p style="text-align: left; font-weight: 700; font-size: 0.9rem; color: #1e3a2a; margin: 5px 0 10px;">Payment Method:</p>
            
            <label class="method-card">
                <input type="radio" name="pay_method" value="Bank" checked>
                <span style="margin-left:12px; font-weight: 600; color: #333; font-size: 0.95rem;">Online Banking (FPX)</span>
            </label>

            <label class="method-card">
                <input type="radio" name="pay_method" value="Card">
                <span style="margin-left:12px; font-weight: 600; color: #333; font-size: 0.95rem;">Credit / Debit Card</span>
            </label>
            
            <label class="method-card">
                <input type="radio" name="pay_method" value="TNG">
                <span style="margin-left:12px; font-weight: 600; color: #333; font-size: 0.95rem;">Touch 'n Go eWallet</span>
            </label>

            <button type="submit" class="btn-reload" style="margin-top: 15px;">Next Step →</button>
        </form>
        
        <a href="<?php echo $back_url; ?>" class="back-link">← <?php echo $back_label; ?></a>
    </div>

    <script>
        function selectAmt(v, buttonElement) { 
            document.getElementById('reload_amt').value = v; 
            document.querySelectorAll('.amt-btn').forEach(btn => btn.classList.remove('active'));
            buttonElement.classList.add('active');
        }
        
        document.getElementById('reload_amt').addEventListener('input', function() {
            document.querySelectorAll('.amt-btn').forEach(btn => btn.classList.remove('active'));
        });

        // 🟢 JAVASCRIPT VALIDATION ENGINE: Strictly forces positive numbers
        function validateReload() {
            const a = parseFloat(document.getElementById('reload_amt').value);
            if (isNaN(a) || a < 1) { 
                alert("Security Error: Invalid reload allocation amount. Minimum deposit is RM 1.00"); 
                return false; 
            }
            return true;
        }
    </script>
</body>
</html>