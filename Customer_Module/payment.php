<?php
require_once 'config.php';
if(!isLoggedIn()) redirect('homepage.php');
$booking_id = $_GET['booking_id'] ?? 0;
$stmt = $pdo->prepare("SELECT b.*, c.court_name FROM bookings b JOIN courts c ON b.court_id=c.id WHERE b.id=? AND b.user_id=?");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();
if(!$booking) redirect('dashboard.php');
?>
<!DOCTYPE html>
<html>
<head><title>Payment</title><link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet"><style>body{font-family:'Inter';background:#f5f9f0;padding:2rem;}.container{max-width:500px;margin:0 auto;background:white;border-radius:32px;padding:2rem;}button{background:#2b7e3a;color:white;border:none;padding:0.9rem;border-radius:60px;width:100%;font-weight:700;margin-top:1rem;cursor:pointer;}</style></head>
<body>
<div class="container">
    <h2>Complete Payment</h2>
    <p><strong>Court:</strong> <?=$booking['court_name']?></p>
    <p><strong>Date:</strong> <?=$booking['booking_date']?> at <?=substr($booking['start_time'],0,5)?></p>
    <p><strong>Total Amount:</strong> $<?=number_format($booking['total_price'],2)?></p>
    <form action="payment_process.php" method="POST">
        <input type="hidden" name="booking_id" value="<?=$booking_id?>">
        <label>Payment Method</label>
        <select name="payment_method" required>
            <option>Credit Card</option><option>Bank Transfer</option><option>E-Wallet</option>
        </select>
        <button type="submit">Pay Now</button>
    </form>
</div>
</body>
</html>