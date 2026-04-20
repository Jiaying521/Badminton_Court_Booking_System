<?php
// gateway.php - The Mock Payment Interfaces

// 1. Catch the data from checkout
$amount = $_POST['amount'] ?? 0;
$method = $_POST['payment_method'] ?? 'Unknown';
$promo  = $_POST['promo_code'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Payment Gateway</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    
    <?php if ($method === 'Credit Card'): ?>
        <h2 style="color: #333;">Enter Card Details</h2>
        <p style="text-align: center; color: #666;">Total to pay: RM <?php print $amount; ?></p>
        
        <form action="process_payment.php" method="POST">
            <input type="hidden" name="amount" value="<?php print $amount; ?>">
            <input type="hidden" name="payment_method" value="<?php print $method; ?>">
            <input type="hidden" name="promo_code" value="<?php print $promo; ?>">
            
            <label>Card Number:</label>
            <input type="text" placeholder="0000 0000 0000 0000" required maxlength="19">
            
            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label>Expiry (MM/YY):</label>
                    <input type="text" placeholder="12/25" required maxlength="5">
                </div>
                <div style="flex: 1;">
                    <label>CVV:</label>
                    <input type="text" placeholder="123" required maxlength="3">
                </div>
            </div>
            
            <button type="submit" style="background-color: #28a745;">Secure Pay RM <?php print $amount; ?></button>
        </form>

    <?php elseif ($method === 'Bank Transfer'): ?>
        <h2 style="color: #e60000;">Bank2U Login</h2>
        <p style="text-align: center; font-size: 14px;">Merchant: Clinic Appointment System<br>Amount: RM <?php print $amount; ?></p>
        
        <form action="process_payment.php" method="POST">
            <input type="hidden" name="amount" value="<?php print $amount; ?>">
            <input type="hidden" name="payment_method" value="<?php print $method; ?>">
            <input type="hidden" name="promo_code" value="<?php print $promo; ?>">
            
            <label>Username:</label>
            <input type="text" placeholder="Enter bank username" required>
            
            <label>Password:</label>
            <input type="password" placeholder="Enter bank password" required style="width: 100%; padding: 12px; margin: 8px 0 20px 0; border: 1px solid #ccc; border-radius: 6px; box-sizing: border-box;">
            
            <button type="submit" style="background-color: #e60000;">Login & Authorize RM <?php print $amount; ?></button>
        </form>

    <?php elseif ($method === 'E-Wallet'): ?>
        <h2 style="color: #0080ff;">E-Wallet </h2>
        <div style="text-align: center; margin: 20px 0;">
            <div style="width: 150px; height: 150px; background-color: #eee; border: 2px dashed #0080ff; margin: 0 auto; display: flex; align-items: center; justify-content: center;">
                <span style="color: #0080ff; font-weight: bold;">[FAKE QR CODE]</span>
            </div>
            <p>Scan to pay RM <?php print $amount; ?></p>
        </div>
        
        <form action="process_payment.php" method="POST">
            <input type="hidden" name="amount" value="<?php print $amount; ?>">
            <input type="hidden" name="payment_method" value="<?php print $method; ?>">
            <input type="hidden" name="promo_code" value="<?php print $promo; ?>">
            
            <button type="submit" style="background-color: #0080ff;">Simulate Successful Scan</button>
        </form>
    <?php endif; ?>

    <a href="checkout.php" style="color: red;">Cancel and Return</a>
</div>

</body>
</html>