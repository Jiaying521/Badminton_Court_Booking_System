<?php
session_start();
$reload_amt = $_POST['reload_amount'] ?? 0;
$pay_method = $_POST['pay_method'] ?? 'Bank';
$return_to = $_POST['return_to'] ?? 'dashboard';
$b_id = $_POST['booking_id'] ?? 0;
$b_amt = $_POST['amount'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Authorize Top-Up</title>
    <link rel="stylesheet" href="style.css">
</head>
<body style="background:#f4f7f6; display:flex; justify-content:center; align-items:center; min-height:100vh; font-family:sans-serif;">
    <div style="background:white; padding:40px; border-radius:20px; width:400px; text-align:center; box-shadow:0 10px 25px rgba(0,0,0,0.1); border-top: 5px solid #2b7e3a;">
        <form action="process_reload.php" method="POST">
            <input type="hidden" name="reload_amount" value="<?php echo $reload_amt; ?>">
            <input type="hidden" name="return_to" value="<?php echo $return_to; ?>">
            <input type="hidden" name="booking_id" value="<?php echo $b_id; ?>">
            <input type="hidden" name="amount" value="<?php echo $b_amt; ?>">

            <?php if ($pay_method == 'Bank'): ?>
                <img src="https://upload.wikimedia.org/wikipedia/commons/a/a0/FPX_logo.png" width="100" style="margin-bottom:20px;">
                <h3>FPX Secure Login</h3>
                <input type="text" placeholder="Username" style="width:100%; padding:12px; margin:10px 0; border:1px solid #ddd; border-radius:8px;" required>
                <input type="password" placeholder="Password" style="width:100%; padding:12px; margin:10px 0; border:1px solid #ddd; border-radius:8px;" required>
            <?php else: ?>
                <img src="https://upload.wikimedia.org/wikipedia/commons/thumb/4/4e/Touch_%27n_Go_eWallet_logo.svg/1200px-Touch_%27n_Go_eWallet_logo.svg.png" width="100" style="margin-bottom:20px;">
                <h3>TNG eWallet Payment</h3>
                <p>Please authorize the transaction for <b>RM <?php echo number_format($reload_amt, 2); ?></b></p>
                <input type="text" placeholder="Phone Number (601...)" style="width:100%; padding:12px; margin:10px 0; border:1px solid #ddd; border-radius:8px;" required>
            <?php endif; ?>

            <button type="submit" style="background:#2b7e3a; color:white; border:none; padding:15px; width:100%; border-radius:50px; font-weight:bold; cursor:pointer; margin-top:20px;">Authorize & Top Up</button>
        </form>
    </div>
</body>
</html>