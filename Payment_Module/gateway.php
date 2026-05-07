<?php
// gateway.php - The Mock Payment Interfaces

$booking_id = $_POST['booking_id'] ?? 0;
$amount = $_POST['amount'] ?? 0;
$method = $_POST['payment_method'] ?? 'Unknown';
$promo  = $_POST['promo_code'] ?? '';

// Catch the specific choice (Bank, Card, or TNG) from checkout.php
$sub_method = $_POST['sub_method'] ?? ''; 
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
    </style>
</head>
<body style="background-color: #f4f7f6;">

<div class="container" style="text-align: center; border-top: 6px solid #2b7e3a;">
    
    <form action="process_payment.php" method="POST">
        <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking_id); ?>">
        <input type="hidden" name="amount" value="<?php echo htmlspecialchars($amount); ?>">
        <input type="hidden" name="promo_code" value="<?php echo htmlspecialchars($promo); ?>">
        
        <input type="hidden" name="payment_method" value="<?php echo ($method == 'Online Payment') ? htmlspecialchars($sub_method) : htmlspecialchars($method); ?>">

        <?php if ($method === 'Center App Wallet'): ?>
            <h2 style="color: #1a2930;">📱 Center e-Wallet</h2>
            <div style="background: linear-gradient(135deg, #00b33c, #004d1a); color: white; padding: 20px; border-radius: 12px; margin: 20px 0;">
                <p style="margin: 0; opacity: 0.9;">Available Balance</p>
                <h1 style="margin: 5px 0;">RM 15.00</h1>
            </div>
            <p>Paying: <strong>RM <?php echo number_format($amount, 2); ?></strong></p>

        <?php elseif ($sub_method === 'Bank'): ?>
            <div class="clone-container">
                <img src="https://upload.wikimedia.org/wikipedia/commons/a/a0/FPX_logo.png" width="100">
                <h3>Select Your Bank</h3>
                <select style="margin-bottom: 15px;">
                    <option>Maybank2u</option>
                    <option>CIMB Clicks</option>
                    <option>Public Bank</option>
                    <option>RHB Now</option>
                </select>
                <input type="text" placeholder="Username / Login ID" required>
                <input type="password" placeholder="Password" required>
                <p style="font-size: 12px; color: #777;">You will be redirected to your bank's secure site.</p>
            </div>

        <?php elseif ($sub_method === 'Card'): ?>
            <div class="clone-container" style="text-align: left;">
                <h3 style="text-align: center;">Secure Card Payment</h3>
                <label>Cardholder Name</label>
                <input type="text" placeholder="e.g. CHIN ZHEN XIN" required>
                <label>Card Number</label>
                <input type="text" placeholder="4111 1111 1111 1111" maxlength="16" required>
                <div style="display: flex; gap: 10px;">
                    <div style="flex:1;"><label>Expiry</label><input type="text" placeholder="MM/YY" maxlength="5" required></div>
                    <div style="flex:1;"><label>CVV</label><input type="password" placeholder="***" maxlength="3" required></div>
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
            <input type="text" placeholder="Or Enter Phone No: 601xxxxxxx" style="margin-top: 15px;">

        <?php endif; ?>

        <button type="submit" class="confirm-btn" style="margin-top: 20px; background-color: #2b7e3a;">Authorize Payment</button>
        
        <a href="checkout.php?booking_id=<?php echo htmlspecialchars($booking_id); ?>&amount=<?php echo htmlspecialchars($amount); ?>" 
           style="color: #ff4d4d; display: block; margin-top: 20px; text-decoration: none;">← Cancel and Return</a>
    </form>
</div>

</body>
</html>