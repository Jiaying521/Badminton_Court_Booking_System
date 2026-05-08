<?php
// gateway.php - The Mock Payment Interfaces
session_start();
include 'db_connect.php';

$booking_id = $_POST['booking_id'] ?? 0;
$amount = $_POST['amount'] ?? 0;
$method = $_POST['payment_method'] ?? 'Unknown';
$promo  = $_POST['promo_code'] ?? '';
$sub_method = $_POST['sub_method'] ?? ''; 

// 🟢 1. FETCH REAL WALLET BALANCE
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Payment Gateway</title>
    <link rel="stylesheet" href="style.css">
    <style>
        input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        input[type=number] { -moz-appearance: textfield; }
        .clone-container { background: white; padding: 20px; border-radius: 12px; border: 1px solid #eee; margin-top: 10px; }
        .container { max-width: 500px; margin: 50px auto; padding: 30px; background: white; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
    </style>
</head>
<body style="background-color: #f4f7f6; font-family: 'Inter', sans-serif;">

<div class="container" style="text-align: center; border-top: 6px solid #2b7e3a;">
    
    <form action="process_payment.php" method="POST">
        <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_id); ?>">
        <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
        <input type="hidden" name="promo_code" value="<?php echo htmlspecialchars($promo); ?>">
        
        <input type="hidden" name="payment_method" value="<?php echo ($method == 'Online Payment') ? htmlspecialchars($sub_method) : htmlspecialchars($method); ?>">

        <?php if ($method === 'App Wallet'): ?>
            <h2 style="color: #1a2930;">Smash Arena Wallet</h2>
            <div style="background: linear-gradient(135deg, #2b7e3a, #004d1a); color: white; padding: 20px; border-radius: 12px; margin: 20px 0;">
                <p style="margin: 0; opacity: 0.9;">Available Balance</p>
                <h1 style="margin: 5px 0;">RM <?php echo number_format($real_balance, 2); ?></h1>
            </div>
            <p>Paying: <strong style="color: #2b7e3a;">RM <?php echo number_format($amount, 2); ?></strong></p>
            
            <?php if ($real_balance < $amount): ?>
                <p style="color: #ff4d4d; font-weight: bold;">⚠️ Insufficient balance! Please go back and top up.</p>
            <?php endif; ?>

        <?php elseif ($sub_method === 'Bank'): ?>
            <div class="clone-container">
                <img src="https://upload.wikimedia.org/wikipedia/commons/a/a0/FPX_logo.png" width="100">
                <h3>Select Your Bank</h3>
                <select style="width: 100%; padding: 10px; margin-bottom: 15px; border-radius: 8px;">
                    <option>Maybank2u</option>
                    <option>CIMB Clicks</option>
                    <option>Public Bank</option>
                    <option>RHB Now</option>
                </select>
                <input type="text" placeholder="Username / Login ID" style="width: 100%; padding: 10px; margin-bottom: 10px; border-radius: 8px; border: 1px solid #ddd;" required>
                <input type="password" placeholder="Password" style="width: 100%; padding: 10px; margin-bottom: 10px; border-radius: 8px; border: 1px solid #ddd;" required>
                <p style="font-size: 12px; color: #777;">You will be redirected to your bank's secure site.</p>
            </div>

        <?php elseif ($sub_method === 'Card'): ?>
            <div class="clone-container" style="text-align: left;">
                <h3 style="text-align: center;">Secure Card Payment</h3>
                <label style="font-size: 12px; font-weight: bold;">Cardholder Name</label>
                <input type="text" placeholder="e.g. John Doe" style="width: 100%; padding: 10px; margin-bottom: 10px; border-radius: 8px; border: 1px solid #ddd;" required>
                <label style="font-size: 12px; font-weight: bold;">Card Number</label>
                <input type="text" placeholder="4111 1111 1111 1111" maxlength="16" style="width: 100%; padding: 10px; margin-bottom: 10px; border-radius: 8px; border: 1px solid #ddd;" required>
                <div style="display: flex; gap: 10px;">
                    <div style="flex:1;"><label style="font-size: 12px; font-weight: bold;">Expiry</label><input type="text" placeholder="MM/YY" maxlength="5" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;" required></div>
                    <div style="flex:1;"><label style="font-size: 12px; font-weight: bold;">CVV</label><input type="password" placeholder="***" maxlength="3" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;" required></div>
                </div>
            </div>

        <?php elseif ($sub_method === 'TNG'): ?>
            <div class="clone-container" style="background: #005eb8; color: white;">
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4e/Touch_%27n_Go_eWallet_logo.svg/1200px-Touch_%27n_Go_eWallet_logo.svg.png" width="80" style="background: white; border-radius: 5px; padding: 5px;">
                <h3>Scan QR to Pay</h3>
                <div style="background: white; padding: 10px; display: inline-block; border-radius: 8px; margin: 10px 0;">
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=SMASH-ARENA-PAYMENT-ID-<?php echo $booking_id; ?>" alt="QR Code">
                </div>
                <p style="font-size: 14px;">Total: RM <?php echo number_format($amount, 2); ?></p>
            </div>
            <input type="text" placeholder="Or Enter Phone No: 601xxxxxxx" style="width: 100%; padding: 10px; margin-top: 15px; border-radius: 8px; border: 1px solid #ddd;">

        <?php endif; ?>

        <?php if ($method !== 'App Wallet' || $real_balance >= $amount): ?>
            <button type="submit" class="confirm-btn" style="width: 100%; padding: 15px; margin-top: 20px; background-color: #2b7e3a; color: white; border: none; border-radius: 50px; font-weight: bold; cursor: pointer;">Authorize Payment</button>
        <?php endif; ?>
        
        <a href="checkout.php?booking_id=<?php echo htmlspecialchars($booking_id); ?>&amount=<?php echo htmlspecialchars($amount); ?>" 
           style="color: #ff4d4d; display: block; margin-top: 20px; text-decoration: none; font-weight: bold;">← Cancel and Return</a>
    </form>
</div>

</body>
</html>