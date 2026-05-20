<?php
session_start();
include 'db_connect.php';

// 🟢 1. FETCH REAL BALANCE FROM DATABASE
$user_id = $_SESSION['user_id'] ?? 0;
$real_balance = 0.00;

if ($user_id > 0) {
    $stmt_bal = $conn->prepare("SELECT wallet_balance FROM users WHERE id = ?");
    $stmt_bal->bind_param("i", $user_id);
    $stmt_bal->execute();
    $res_bal = $stmt_bal->get_result();
    if ($row_bal = $res_bal->fetch_assoc()) {
        $real_balance = $row_bal['wallet_balance'];
    }
}

$booking_id = $_GET['booking_id'] ?? 0;
$amount = $_GET['amount'] ?? 0;

if($booking_id) {
    $check_sql = "
        SELECT bookings.*, courts.court_name 
        FROM bookings 
        JOIN courts ON bookings.court_id = courts.id
        WHERE bookings.id = '$booking_id' AND bookings.status = 'Pending'
    ";
    $check_result = $conn->query($check_sql);
    
    if($check_result->num_rows == 0) {
        die("Error: Booking not found.");
    }
    
    $booking_data = $check_result->fetch_assoc();
    $court_id = $booking_data['court_id']; 
    $court_name = $booking_data['court_name'];
    $booking_date = $booking_data['booking_date']; 
    $time_slot = $booking_data['start_time'] . ' to ' . $booking_data['end_time'];

} else {
    die("Error: Missing booking ID.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment | Smash Arena</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f5f9f0; padding:2rem; }
        .container { max-width:1400px; margin:0 auto; }
        
        /* 🟢 EXACT MATCH TO FRIEND'S STEP HEADER BAR STYLE */
        .progress-bar { display:flex; justify-content:space-between; margin-bottom:2rem; background:white; padding:1rem 2rem; border-radius:60px; }
        .progress-step { text-align:center; flex:1; }
        .progress-step .step-number { width:32px; height:32px; background:#e0e0e0; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; margin-bottom:0.3rem; color: #333; }
        .progress-step.active .step-number { background:#2b7e3a; color:white; }
        .progress-step.completed .step-number { background:#2b7e3a; color:white; }
        .progress-step .step-label { font-size:0.75rem; color:#888; }
        .progress-step.active .step-label { color:#2b7e3a; font-weight:600; }
        
        /* 🟢 SPECIFIC SPLIT CONTENT COLUMN SCHEME */
        .row-2cols { display:grid; grid-template-columns:2fr 1fr; gap:1.5rem; }
        
        .payment-section { background:white; border-radius:24px; padding:1.5rem; margin-bottom:1.5rem; text-align: left; }
        .section-title { font-size:1.3rem; font-weight:700; color:#2b7e3a; margin-bottom:1.5rem; padding-bottom:0.5rem; border-bottom:2px solid #eaf5e6; }
        
        .payment-option-card { border: 1px solid #e0e0e0; padding: 20px; border-radius: 16px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; background: white; transition: 0.2s; cursor: pointer; }
        .payment-option-card:hover { border-color: #2b7e3a; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .fee-badge { background: #eaf5e6; color: #2b7e3a; padding: 2px 10px; border-radius: 10px; font-weight: bold; font-size: 12px; margin-left: 8px; }
        
        .cart-summary { background:white; border-radius:24px; padding:1.5rem; position:sticky; top:2rem; text-align: left; }
        .summary-item { display:flex; justify-content:space-between; padding:0.6rem 0; border-bottom:1px solid #eee; font-size:0.9rem; color:#555; }
        .cart-total { display:flex; justify-content:space-between; padding:1rem 0; margin-top:0.5rem; border-top:2px solid #2b7e3a; font-weight:700; font-size:1.3rem; color:#2b7e3a; }
        
        .btn-continue { background:#2b7e3a; color:white; border:none; padding:1rem; border-radius:50px; width:100%; font-weight:700; font-size:1rem; cursor:pointer; margin-top:1rem; transition:0.2s; display: block; text-align: center; text-decoration: none; }
        .btn-continue:hover { background:#1f5a2a; transform:translateY(-2px); }
        .btn-skip { background:#e0e0e0; color:#333; border:none; padding:0.8rem; border-radius:50px; width:100%; margin-top:0.5rem; cursor:pointer; text-align: center; text-decoration: none; display: block; font-size: 0.9rem; }
        
        #online-choices { margin: 5px 0 15px 35px; padding: 15px; background: #fafdf7; border-left: 4px solid #2b7e3a; border-radius: 8px; border: 1px solid #eee; }
        @media (max-width:768px) { .row-2cols { grid-template-columns:1fr; } body { padding:1rem; } }
    </style>
</head>
<body>
<div class="container">

    <div class="progress-bar">
        <div class="progress-step completed"><div class="step-number">1</div><div class="step-label">Court</div></div>
        <div class="progress-step completed"><div class="step-number">2</div><div class="step-label">Time</div></div>
        <div class="progress-step completed"><div class="step-number">3</div><div class="step-label">Add-ons</div></div>
        <div class="progress-step active"><div class="step-number">4</div><div class="step-label">Payment</div></div>
    </div>
    
    <form action="gateway.php" method="POST">
        <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
        <input type="hidden" name="amount" value="<?php echo $amount; ?>">

        <div class="row-2cols">
            
            <div>
                <div class="payment-section">
                    <div class="section-title"><i class="fas fa-credit-card"></i> Select Payment Method</div>
                    
                    <div class="payment-option-card" onclick="document.getElementById('wallet_radio').checked = true; document.getElementById('online-choices').style.display='none'">
                        <div style="display: flex; align-items: center;">
                            <input type="radio" name="payment_method" value="App Wallet" id="wallet_radio"
                                <?php echo ($real_balance < $amount) ? 'disabled' : 'checked'; ?>>
                            <div style="margin-left: 15px;">
                                <strong style="font-size: 16px; color:#1e3a2a;">Smash Arena Wallet</strong>
                                <span class="fee-badge">Zero Fee</span>
                                <p style="margin: 4px 0 0 0; font-size: 13px; color: <?php echo ($real_balance < $amount) ? '#ff4d4d' : '#666'; ?>;">
                                    Balance: RM <?php echo number_format($real_balance, 2); ?>
                                    <?php if($real_balance < $amount) echo " (Insufficient Balance)"; ?>
                                </p>
                            </div>
                        </div>
                        <a href="wallet.php?return=checkout&booking_id=<?php echo $booking_id; ?>&amount=<?php echo $amount; ?>" class="btn-skip" style="margin-top:0; padding:6px 15px; width:auto; font-weight:bold; background:#2b7e3a; color:white;">Top Up</a>
                    </div>

                    <div class="payment-option-card" onclick="document.getElementById('online_radio').checked = true; document.getElementById('online-choices').style.display='block'">
                        <div style="display: flex; align-items: center;">
                            <input type="radio" name="payment_method" value="Online Payment" id="online_radio" <?php echo ($real_balance < $amount) ? 'checked' : ''; ?>>
                            <div style="margin-left: 15px;">
                                <strong style="font-size: 16px; color:#1e3a2a;">Online Payment Options</strong>
                                <p style="margin: 4px 0 0 0; font-size: 13px; color: #666;">Instant transaction via FPX Banking, Cards, or digital e-Wallets</p>
                            </div>
                        </div>
                    </div>

                    <div id="online-choices" style="display: <?php echo ($real_balance < $amount) ? 'block' : 'none'; ?>;">
                        <p style="font-size: 14px; font-weight: bold; margin-bottom: 12px; color: #2b7e3a;">Select Provider Gateway:</p>
                        <label style="display: block; margin-bottom: 10px; cursor: pointer; font-size:14px;"><input type="radio" name="sub_method" value="Bank" checked> Online Banking (FPX Direct)</label>
                        <label style="display: block; margin-bottom: 10px; cursor: pointer; font-size:14px;"><input type="radio" name="sub_method" value="Card"> Visa / MasterCard / Debit</label>
                        <label style="display: block; cursor: pointer; font-size:14px;"><input type="radio" name="sub_method" value="TNG"> Touch 'n Go eWallet App</label>
                    </div>

                </div>
            </div>
            
            <div>
                <div class="cart-summary">
                    <h3><i class="fas fa-receipt"></i> Booking Summary</h3>
                    <div style="margin-top: 15px; margin-bottom: 15px;">
                        <div class="summary-item"><strong>Venue Target:</strong> <span>Jebat Racquet Sports Centre</span></div>
                        <div class="summary-item"><strong>Court Unit:</strong> <span><?php echo htmlspecialchars($court_name); ?></span></div>
                        <div class="summary-item"><strong>Reserved Date:</strong> <span><?php echo date('M j, Y', strtotime($booking_date)); ?></span></div>
                        <div class="summary-item"><strong>Time Windows:</strong> <span><?php echo htmlspecialchars($time_slot); ?></span></div>
                    </div>
                    
                    <div class="cart-total">
                        <span>Total Due:</span>
                        <span>MYR <?php echo number_format($amount, 2); ?></span>
                    </div>
                    
                    <button type="submit" class="btn-continue"><i class="fas fa-check-circle"></i> Continue to Payment</button>
                    <a href="../Customer_Module/addons.php?booking_id=<?php echo $booking_id; ?>" class="btn-skip">← Back to Add-ons</a>
                </div>
            </div>

        </div>
    </form>
</div>
</body>
</html>