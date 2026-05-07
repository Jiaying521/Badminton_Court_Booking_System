<?php
session_start();
include 'db_connect.php';

// 🟢 1. FETCH REAL BALANCE FROM DATABASE
// This ensures "RM 15.00" isn't just fake text anymore
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
    <title>Checkout</title>
    <link rel="stylesheet" href="style.css?v=6"> 
    <style>
        .fee-badge { background: #e6f2e6; color: #004d1a; padding: 2px 8px; border-radius: 10px; font-weight: bold; }
        .confirm-btn { background-color: #004d1a; color: white; border: none; padding: 15px; width: 100%; border-radius: 10px; font-weight: bold; cursor: pointer; font-size: 16px; margin-top: 20px; }
        .topup-btn { background: #004d1a; color: white; padding: 5px 15px; border-radius: 50px; text-decoration: none; font-size: 12px; font-weight: bold; display: inline-block; }
        .payment-option-card { border: 1px solid #eee; padding: 15px; border-radius: 12px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    </style>
</head>
<body>

    <div class="container">
        <h2 style="border-bottom: none; text-align: left; font-size: 24px;">Checkout</h2>
        
        <form action="gateway.php" method="POST">
            
            <input type="hidden" name="booking_id" value="<?php echo $booking_id; ?>">
            <input type="hidden" name="amount" value="<?php echo $amount; ?>">

            <div style="margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
                <p style="display: flex; justify-content: space-between; margin: 8px 0;"><strong>Venue</strong> <span>Jebat Racquet Sports Centre</span></p>
                <p style="display: flex; justify-content: space-between; margin: 8px 0;"><strong>Date</strong> <span><?php echo $booking_date; ?></span></p>
                <p style="display: flex; justify-content: space-between; margin: 8px 0;"><strong>Time</strong> <span><?php echo $time_slot; ?></span></p>
                <p style="display: flex; justify-content: space-between; margin: 8px 0;"><strong>Court</strong> <span><?php echo $court_name; ?></span></p>
                <hr style="border: 0; border-top: 1px solid #eee; margin: 15px 0;">
                <p style="display: flex; justify-content: space-between; margin: 8px 0; font-size: 18px;">
                    <strong>TOTAL</strong> <strong style="color: #004d1a;">MYR <?php echo number_format($amount, 2); ?></strong>
                </p>
            </div>

            <h3 style="text-align: left; font-size: 18px; margin-bottom: 15px;">Payment Methods</h3>

            <div class="payment-option-card">
                <div style="display: flex; align-items: center;">
                    <input type="radio" name="payment_method" value="App Wallet" 
                        <?php echo ($real_balance < $amount) ? 'disabled' : 'checked'; ?> 
                        onclick="document.getElementById('online-choices').style.display='none'">
                    <div style="margin-left: 10px;">
                        <strong style="font-size: 15px;">Smash Arena Wallet</strong>
                        <span class="fee-badge">Zero Fee</span>
                        <p style="margin: 3px 0 0 0; font-size: 12px; color: <?php echo ($real_balance < $amount) ? '#ff4d4d' : '#777'; ?>;">
                            Balance: RM <?php echo number_format($real_balance, 2); ?>
                            <?php if($real_balance < $amount) echo " (Insufficient Balance)"; ?>
                        </p>
                    </div>
                </div>
                <a href="wallet.php?return=checkout&booking_id=<?php echo $booking_id; ?>&amount=<?php echo $amount; ?>" class="topup-btn">Top Up</a>
            </div>

            <div class="payment-option-card" onclick="document.getElementById('online_radio').checked = true; document.getElementById('online-choices').style.display='block'">
                <div style="display: flex; align-items: center;">
                    <input type="radio" name="payment_method" value="Online Payment" id="online_radio">
                    <div style="margin-left: 10px;">
                        <strong style="font-size: 15px;">Online Payment</strong>
                        <p style="margin: 3px 0 0 0; font-size: 12px; color: #777;">FPX, Card, or e-Wallet</p>
                    </div>
                </div>
            </div>

            <div id="online-choices" style="display:none; margin: -10px 0 20px 40px; padding: 15px; background: #fdfdfd; border-left: 4px solid #004d1a; border: 1px solid #eee; border-radius: 0 0 10px 10px;">
                <p style="font-size: 13px; font-weight: bold; margin-bottom: 10px; color: #004d1a;">Select Provider:</p>
                <label style="display: block; margin-bottom: 10px; cursor: pointer;"><input type="radio" name="sub_method" value="Bank" checked> 🏦 Online Banking (FPX)</label>
                <label style="display: block; margin-bottom: 10px; cursor: pointer;"><input type="radio" name="sub_method" value="Card"> 💳 Credit / Debit Card</label>
                <label style="display: block; cursor: pointer;"><input type="radio" name="sub_method" value="TNG"> 📱 Touch 'n Go eWallet</label>
            </div>

            <button type="submit" class="confirm-btn">Confirm Booking</button>
            
            <div style="text-align: center; margin-top: 15px;">
                <a href="../Customer_Module/book_court.php?court_id=<?php echo $court_id; ?>" style="color: #666; text-decoration: none; font-size: 14px;">
                    ← Cancel and return to <?php echo $court_name; ?>
                </a>
            </div>
        </form>
    </div>
</body>
</html>     