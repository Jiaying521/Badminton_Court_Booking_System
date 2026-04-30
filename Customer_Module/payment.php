<?php
require_once __DIR__ . '/../config.php';
if(!isLoggedIn()) redirect('homepage.php');

$booking_id = $_GET['booking_id'] ?? 0;

// 获取预订详情
$stmt = $pdo->prepare("
    SELECT b.*, c.court_name, c.court_type, c.location, c.facilities 
    FROM bookings b 
    JOIN courts c ON b.court_id = c.id 
    WHERE b.id = ? AND b.user_id = ?
");
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch();

if(!$booking) {
    redirect('dashboard.php');
}

// 确保 total_hours 有值
$total_hours = $booking['total_hours'] ?? 1;
$coach_price_total = $booking['coach_price_total'] ?? 0;
$coach_hours = $booking['coach_hours'] ?? 0;

// 计算场地费
$court_fee = $booking['total_price'] - $coach_price_total;

// 获取教练信息（如果有）
$coach_name = '';
if($booking['coach_id'] && $booking['coach_id'] > 0) {
    $coachStmt = $pdo->prepare("SELECT name, price_per_hour FROM coaches WHERE id = ?");
    $coachStmt->execute([$booking['coach_id']]);
    $coach = $coachStmt->fetch();
    if($coach) {
        $coach_name = $coach['name'];
    }
}

// 格式化时间
$start_time_display = date('h:i A', strtotime($booking['start_time']));
$end_time_display = date('h:i A', strtotime($booking['end_time']));
$booking_date_display = date('l, F j, Y', strtotime($booking['booking_date']));
?>
<!DOCTYPE html>
<html>
<head>
    <title>Payment | Smash Arena</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:#f5f9f0; padding:2rem; }
        .container { max-width:800px; margin:0 auto; }
        
        .progress-bar { display:flex; justify-content:space-between; margin-bottom:2rem; background:white; padding:1rem 2rem; border-radius:60px; box-shadow:0 2px 10px rgba(0,0,0,0.05); }
        .progress-step { text-align:center; flex:1; }
        .progress-step .step-number { width:32px; height:32px; background:#e0e0e0; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; font-weight:700; margin-bottom:0.3rem; }
        .progress-step .step-label { font-size:0.8rem; color:#888; }
        .progress-step.active .step-number { background:#2b7e3a; color:white; }
        .progress-step.active .step-label { color:#2b7e3a; font-weight:600; }
        .progress-step.completed .step-number { background:#2b7e3a; color:white; }
        
        .payment-card { background:white; border-radius:32px; padding:2rem; box-shadow:0 12px 28px rgba(0,0,0,0.08); margin-bottom:1.5rem; }
        .payment-card h2 { color:#2b7e3a; margin-bottom:1.5rem; font-size:1.5rem; display:flex; align-items:center; gap:0.5rem; }
        
        .booking-details { background:#f8f9fa; border-radius:20px; padding:1.5rem; margin-bottom:1.5rem; }
        .detail-row { display:flex; justify-content:space-between; padding:0.6rem 0; border-bottom:1px solid #e0e0e0; }
        .detail-row:last-child { border-bottom:none; }
        .detail-label { font-weight:600; color:#555; }
        .detail-value { font-weight:500; color:#1e2a2e; }
        .court-badge { display:inline-block; background:#eaf5e6; color:#2b7e3a; padding:0.2rem 0.6rem; border-radius:20px; font-size:0.8rem; }
        
        .price-breakdown { background:#f8f9fa; border-radius:20px; padding:1.5rem; margin-bottom:1.5rem; }
        .price-item { display:flex; justify-content:space-between; padding:0.5rem 0; }
        .price-total { display:flex; justify-content:space-between; padding:1rem 0 0 0; margin-top:0.5rem; border-top:2px solid #2b7e3a; font-weight:700; font-size:1.2rem; color:#2b7e3a; }
        
        .payment-methods { display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem; }
        .payment-method { flex:1; min-width:120px; border:2px solid #e0e0e0; border-radius:16px; padding:1rem; text-align:center; cursor:pointer; transition:0.2s; background:white; }
        .payment-method:hover { border-color:#2b7e3a; background:#eaf5e6; }
        .payment-method.selected { border-color:#2b7e3a; background:#e0f0dc; }
        .payment-method-icon { font-size:2rem; margin-bottom:0.5rem; }
        .payment-method-name { font-weight:600; }
        
        .pay-btn { background:#2b7e3a; color:white; border:none; padding:1rem; border-radius:60px; width:100%; font-size:1.1rem; font-weight:700; cursor:pointer; transition:0.2s; }
        .pay-btn:hover { background:#1f5a2a; transform:translateY(-2px); box-shadow:0 8px 20px rgba(43,126,58,0.3); }
        .pay-btn:disabled { opacity:0.6; cursor:not-allowed; transform:none; }
        .back-link { display:inline-block; margin-top:1rem; color:#2b7e3a; text-decoration:none; text-align:center; width:100%; }
        .back-link:hover { text-decoration:underline; }
        
        .loading-overlay { position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); display:flex; align-items:center; justify-content:center; z-index:1000; display:none; }
        .loading-spinner { background:white; padding:2rem; border-radius:20px; text-align:center; }
        .spinner { width:40px; height:40px; border:4px solid #e0e0e0; border-top-color:#2b7e3a; border-radius:50%; animation:spin 1s linear infinite; margin:0 auto 1rem; }
        @keyframes spin { to { transform:rotate(360deg); } }
        
        @media (max-width:768px) { body { padding:1rem; } .progress-step .step-label { display:none; } }
    </style>
</head>
<body>
<div class="container">
    <div class="progress-bar">
        <div class="progress-step completed"><div class="step-number">✓</div><div class="step-label">Select Court</div></div>
        <div class="progress-step completed"><div class="step-number">✓</div><div class="step-label">Booking Details</div></div>
        <div class="progress-step active"><div class="step-number">3</div><div class="step-label">Payment</div></div>
        <div class="progress-step"><div class="step-number">4</div><div class="step-label">Confirmation</div></div>
    </div>

    <div class="payment-card">
        <h2>💰 Payment Summary</h2>
        
        <div class="booking-details">
            <div class="detail-row">
                <span class="detail-label">🏸 Court</span>
                <span class="detail-value"><?=htmlspecialchars($booking['court_name'])?> <span class="court-badge"><?=htmlspecialchars($booking['court_type'])?></span></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">📅 Date</span>
                <span class="detail-value"><?=$booking_date_display?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">⏰ Time</span>
                <span class="detail-value"><?=$start_time_display?> - <?=$end_time_display?> (<?=$total_hours?> hour<?=$total_hours > 1 ? 's' : ''?>)</span>
            </div>
            <?php if($coach_name): ?>
            <div class="detail-row">
                <span class="detail-label">🎓 Coach</span>
                <span class="detail-value"><?=htmlspecialchars($coach_name)?> (<?=$coach_hours?> hour<?=$coach_hours > 1 ? 's' : ''?>)</span>
            </div>
            <?php endif; ?>
            <div class="detail-row">
                <span class="detail-label">📍 Location</span>
                <span class="detail-value"><?=htmlspecialchars($booking['location'] ?? 'Main Hall')?></span>
            </div>
        </div>
        
        <div class="price-breakdown">
            <div class="price-item">
                <span>Court Fee (<?=$total_hours?> hour<?=$total_hours > 1 ? 's' : ''?>)</span>
                <span>RM <?=number_format($court_fee, 2)?></span>
            </div>
            <?php if($coach_name && $coach_price_total > 0): ?>
            <div class="price-item">
                <span>Coach Fee (<?=$coach_hours?> hour<?=$coach_hours > 1 ? 's' : ''?>)</span>
                <span>RM <?=number_format($coach_price_total, 2)?></span>
            </div>
            <?php endif; ?>
            <div class="price-total">
                <span>Total Amount</span>
                <span>RM <?=number_format($booking['total_price'], 2)?></span>
            </div>
        </div>
        
        <label style="font-weight:600; margin-bottom:0.5rem; display:block;">Select Payment Method</label>
        <div class="payment-methods">
            <div class="payment-method" data-method="credit_card" data-name="Credit Card">
                <div class="payment-method-icon">💳</div>
                <div class="payment-method-name">Credit Card</div>
            </div>
            <div class="payment-method" data-method="bank_transfer" data-name="Bank Transfer">
                <div class="payment-method-icon">🏦</div>
                <div class="payment-method-name">Bank Transfer</div>
            </div>
            <div class="payment-method" data-method="ewallet" data-name="E-Wallet">
                <div class="payment-method-icon">📱</div>
                <div class="payment-method-name">E-Wallet</div>
            </div>
        </div>
        
        <button type="button" id="payBtn" class="pay-btn" disabled>💳 Select Payment Method</button>
        <a href="my_bookings.php" class="back-link">← Cancel and Back to Bookings</a>
    </div>
</div>

<div id="loadingOverlay" class="loading-overlay">
    <div class="loading-spinner">
        <div class="spinner"></div>
        <p>Processing payment...</p>
    </div>
</div>

<script>
    const bookingId = <?=$booking_id?>;
    let selectedMethod = '';
    let selectedMethodName = '';
    
    document.querySelectorAll('.payment-method').forEach(method => {
        method.addEventListener('click', function() {
            document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
            this.classList.add('selected');
            selectedMethod = this.getAttribute('data-method');
            selectedMethodName = this.getAttribute('data-name');
            
            const payBtn = document.getElementById('payBtn');
            if(selectedMethod === 'credit_card') {
                payBtn.innerHTML = '💳 Pay with Credit Card';
            } else if(selectedMethod === 'bank_transfer') {
                payBtn.innerHTML = '🏦 Pay with Bank Transfer';
            } else if(selectedMethod === 'ewallet') {
                payBtn.innerHTML = '📱 Pay with E-Wallet';
            }
            payBtn.disabled = false;
        });
    });
    
    document.getElementById('payBtn').addEventListener('click', async function() {
        if(!selectedMethod) {
            alert('Please select a payment method');
            return;
        }
        
        document.getElementById('loadingOverlay').style.display = 'flex';
        
        try {
            const response = await fetch('Payment_Module/process_payment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    booking_id: bookingId,
                    payment_method: selectedMethod,
                    payment_method_name: selectedMethodName
                })
            });
            
            const data = await response.json();
            
            if(data.success) {
                window.location.href = `receipt.php?booking_id=${bookingId}`;
            } else {
                alert('Payment failed: ' + data.message);
                document.getElementById('loadingOverlay').style.display = 'none';
            }
        } catch(error) {
            console.error('Payment error:', error);
            alert('Payment processing error. Please try again.');
            document.getElementById('loadingOverlay').style.display = 'none';
        }
    });
</script>
</body>
</html>