<?php
// gateway.php - The Mock Payment Interfaces

// Catch the data from your checkout page
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
    
    <style>
        /* Chrome, Safari, Edge, Opera */
        input::-webkit-outer-spin-button,
        input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        /* Firefox */
        input[type=number] {
            -moz-appearance: textfield;
        }
    </style>
</head>
<body>

<div class="container" style="text-align: center;">
    
    <?php if ($method === 'Center App Wallet'): ?>
        <h2 style="color: #1a2930;">📱 Center e-Wallet</h2>
        
        <div style="background: linear-gradient(135deg, #00b33c, #004d1a); color: white; padding: 20px; border-radius: 12px; 
        margin: 20px 0; box-shadow: 0 4px 10px rgba(0,0,0,0.2);">
            <p style="margin: 0; font-size: 14px; opacity: 0.9;">Available Balance</p>
            <h1 style="margin: 5px 0; font-size: 36px;" id="wallet_balance">RM 15.00</h1>
            <p style="margin: 0; font-size: 14px; color: #ffcccc; font-weight: bold;" id="wallet_status">
                Insufficient Balance for RM <?php print $amount; ?></p>
        </div>

        <div id="topup_section" style="background-color: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #ddd; 
        margin-bottom: 20px;">
            <h3 style="margin-top: 0; color: #333; font-size: 18px;">Top-Up Required</h3>
            
            <div style="display: flex; gap: 10px; margin-bottom: 15px;">
                <button type="button" onclick="setTopUpAmount(10)" style="flex: 1; background-color: white; color: #333; 
                border: 2px solid #00b33c; margin: 0; padding: 10px;">RM 10</button>
                <button type="button" onclick="setTopUpAmount(20)" style="flex: 1; background-color: white; color: #333; 
                border: 2px solid #00b33c; margin: 0; padding: 10px;">RM 20</button>
                <button type="button" onclick="setTopUpAmount(50)" style="flex: 1; background-color: white; color: #333; 
                border: 2px solid #00b33c; margin: 0; padding: 10px;">RM 50</button>
            </div>

            <label style="display: block; text-align: center; font-weight: bold; margin-bottom: 8px; 
            color: #555;">Or Type Custom Amount:</label>
            <input type="number" id="custom_amount" placeholder="0.00" min="1" 
                   oninput="clearQuickButtons()" 
                   onkeydown="if(event.key === '-' || event.key === 'e') event.preventDefault();"
                   style="width: 100%; font-size: 32px; font-weight: bold; text-align: center; padding: 20px; 
                   margin-bottom: 25px; 
                   border: 3px solid #00b33c; border-radius: 8px; 
                   background-color: #e6ffed; box-sizing: border-box; color: #006622;">

            <label style="display: block; text-align: left; font-weight: bold; margin-bottom: 5px; 
            font-size: 14px;">Pay With:</label>
            <select id="topup_method" style="margin-bottom: 20px;">
                <option value="FPX">FPX Online Banking</option>
                <option value="Card">Credit/Debit Card</option>
                <option value="TNG">Touch 'n Go eWallet</option>
            </select>

            <button type="button" onclick="processTopUp()" style="background-color: #ffaa00; color: black; margin: 0; 
            font-size: 20px; padding: 15px;">Confirm Top-Up</button>
        </div>

        <form action="process_payment.php" method="POST" id="wallet_form">
            <input type="hidden" name="amount" value="<?php print $amount; ?>">
            <input type="hidden" name="payment_method" value="<?php print $method; ?>">
            <input type="hidden" name="promo_code" value="<?php print $promo; ?>">
            
            <button type="submit" id="pay_btn" style="background-color: #00b33c; display: none; 
            margin-top: 10px;">Pay RM 
                <?php print $amount; ?> Now</button>
        </form>

        <script>
            let currentBalance = 15.00;
            let requiredAmount = <?php print $amount; ?>;
            let selectedTopUp = 0;

            function setTopUpAmount(amount) {
                selectedTopUp = amount;
                document.getElementById('custom_amount').value = amount; 
            }

            function clearQuickButtons() {
                selectedTopUp = 0; 
            }

            function processTopUp() {
                let customVal = parseFloat(document.getElementById('custom_amount').value);
                if(customVal > 0) {
                    selectedTopUp = customVal;
                }

                if(selectedTopUp <= 0 || isNaN(selectedTopUp)) {
                    alert("Please enter a valid top-up amount.");
                    return;
                }

                currentBalance += selectedTopUp;
                document.getElementById('wallet_balance').innerText = "RM " + currentBalance.toFixed(2);

                if(currentBalance >= requiredAmount) {
                    document.getElementById('wallet_status').innerText = "✅ Balance Sufficient!";
                    document.getElementById('wallet_status').style.color = "#aaffaa";
                    document.getElementById('topup_section').style.display = 'none';
                    document.getElementById('pay_btn').style.display = 'block';
                } else {
                    let shortAmount = requiredAmount - currentBalance;
                    document.getElementById('wallet_status').innerText = "⚠️ Still short RM " + shortAmount.toFixed(2) + "!";
                    document.getElementById('custom_amount').value = ''; 
                    alert("Top-Up successful, but you still need RM " + shortAmount.toFixed(2) + " to book the court.");
                }
            }
        </script>

    <?php elseif ($method === 'Credit Card'): ?>
        <h2>💳 Enter Card Details</h2>
        <p style="color: #666;">Total to pay: RM <?php print $amount; ?></p>
        <form action="process_payment.php" method="POST" style="text-align: left;">
            <input type="hidden" name="amount" value="<?php print $amount; ?>">
            <input type="hidden" name="payment_method" value="<?php print $method; ?>">
            <input type="hidden" name="promo_code" value="<?php print $promo; ?>">
            <label>Card Number:</label>
            <input type="text" placeholder="0000 0000 0000 0000" required maxlength="19">
            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;"><label>Expiry (MM/YY):</label><input type="text" placeholder="12/25" required maxlength="5"></div>
                <div style="flex: 1;"><label>CVV:</label><input type="text" placeholder="123" required maxlength="3"></div>
            </div>
            <button type="submit">Secure Pay RM <?php print $amount; ?></button>
        </form>

    <?php elseif ($method === 'Bank Transfer' || $method === 'E-Wallet'): ?>
        <h2><?php print ($method === 'Bank Transfer') ? '🏦 FPX Online Banking' : '📱 Touch n Go eWallet'; ?></h2>
        <p>Merchant: Pro Court Sports Center<br>Amount: RM <?php print $amount; ?></p>
        <form action="process_payment.php" method="POST" style="text-align: left;">
            <input type="hidden" name="amount" value="<?php print $amount; ?>">
            <input type="hidden" name="payment_method" value="<?php print $method; ?>">
            <input type="hidden" name="promo_code" value="<?php print $promo; ?>">
            <label>Login ID / Phone Number:</label>
            <input type="text" placeholder="Enter details" required>
            <label>Password / PIN:</label>
            <input type="password" placeholder="Enter password or PIN" required style="width: 100%; padding: 12px; 
            margin: 8px 0 20px 0; border: 2px solid #e0e0e0; border-radius: 6px; 
            box-sizing: border-box; font-size: 16px; background-color: #f9f9f9;">
            <button type="submit">Authorize RM <?php print $amount; ?></button>
        </form>

    <?php else: ?>
        <h2>Error</h2>
        <p>No payment method selected.</p>
    <?php endif; ?>

    <a href="checkout.php" style="color: #ff4d4d; font-weight: normal; margin-top: 20px;">← Cancel and Return</a>
</div>

</body>
</html>