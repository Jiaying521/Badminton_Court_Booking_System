<?php
require_once __DIR__ . '/../config.php';
$isLoggedIn = isset($_SESSION['user_id']);
$back_link = $isLoggedIn ? 'dashboard.php' : 'homepage.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancellation Policy | Smash Arena</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:linear-gradient(145deg,#f5f9f0 0%,#e8efe2 100%); color:#1e2a2e; line-height:1.5; }
        
        .navbar { display:flex; justify-content:space-between; align-items:center; padding:0.8rem 5%; background:rgba(255,255,255,0.98); backdrop-filter:blur(12px); position:sticky; top:0; z-index:100; border-bottom:1px solid rgba(43,126,58,0.15); }
        .logo img { height: 65px; width: auto; transition:transform 0.3s; }
        .logo img:hover { transform:scale(1.02); }
        .nav-links { display:flex; gap:1.5rem; align-items:center; }
        .nav-links a { color:#2c4a2e; text-decoration:none; font-weight:500; transition:0.2s; }
        .nav-links a:hover { color:#2b7e3a; }
        
        .container { max-width:900px; margin:0 auto; padding:2rem 5%; }
        
        .back-button { margin-bottom:1.5rem; }
        .btn-back { display:inline-flex; align-items:center; gap:0.6rem; background:#2b7e3a; color:white; text-decoration:none; padding:0.6rem 1.2rem; border-radius:50px; font-weight:600; font-size:0.85rem; transition:0.2s; }
        .btn-back:hover { background:#1f5a2a; transform:translateY(-2px); box-shadow:0 4px 12px rgba(43,126,58,0.3); }
        
        .page-card { background:white; border-radius:32px; padding:2.5rem; box-shadow:0 12px 28px rgba(0,0,0,0.08); }
        .page-header { text-align:center; margin-bottom:2rem; }
        .page-header h1 { font-size:2rem; font-weight:800; background:linear-gradient(135deg,#2b7e3a,#1b5e2a); -webkit-background-clip:text; background-clip:text; color:transparent; }
        .page-header p { color:#5a6e5c; margin-top:0.5rem; }
        
        .section { margin-bottom:2rem; }
        .section h2 { font-size:1.3rem; font-weight:700; color:#2b7e3a; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:2px solid #eaf5e6; }
        .section p { color:#5a6e5c; margin-bottom:0.8rem; line-height:1.6; }
        .section ul { margin-left:1.5rem; margin-bottom:1rem; }
        .section li { color:#5a6e5c; margin-bottom:0.5rem; }
        
        .notice { background:#fff8e1; padding:1rem; border-radius:16px; margin:1rem 0; border-left:4px solid #e67e22; }
        .refund-table { width:100%; border-collapse:collapse; margin:1rem 0; }
        .refund-table th, .refund-table td { padding:0.8rem; text-align:left; border-bottom:1px solid #e0e8dc; }
        .refund-table th { background:#eaf5e6; color:#1e3a2a; }
        
        @media (max-width:768px) {
            .navbar { flex-direction:column; gap:1rem; }
            .page-card { padding:1.5rem; }
            .refund-table th, .refund-table td { padding:0.5rem; font-size:0.85rem; }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="logo"><img src="../Admin_Module/Pictures/logo.png" alt="Smash Arena" onerror="this.style.display='none'"></div>
    <div class="nav-links">
        <a href="homepage.php">Home</a>
        <a href="dashboard.php">Courts</a>
        <a href="my_bookings.php">My Bookings</a>
    </div>
</nav>

<div class="container">
    <div class="page-card">
        <div class="back-button">
            <a href="<?php echo $back_link; ?>" class="btn-back"><i class="fas fa-arrow-left"></i> Back to <?php echo $isLoggedIn ? 'Dashboard' : 'Homepage'; ?></a>
        </div>
        <div class="page-header">
            <h1>Cancellation Policy</h1>
            <p>Understanding our cancellation and refund process</p>
        </div>
        
        <div class="section">
            <h2>1. Cancellation Timeline</h2>
            <p>You may cancel your booking up to 2 hours before the scheduled start time. Cancellations made less than 2 hours before the booking time will not be accepted.</p>
            <div class="notice">
                <i class="fas fa-clock"></i> <strong>Cancellation Deadline:</strong> 2 hours before your booking time
            </div>
        </div>
        
        <div class="section">
            <h2>2. Cancellation Fees</h2>
            <p>A standard cancellation fee of <strong>RM 10.00</strong> applies to all cancellations. The remaining amount will be refunded to your Smash Arena wallet.</p>
            <table class="refund-table">
                <thead>
                    <tr><th>Cancellation Time</th><th>Cancellation Fee</th><th>Refund Amount</th></tr>
                </thead>
                <tbody>
                    <tr><td>More than 2 hours before booking</td><td>RM 10</td><td>Total Price - RM 10</td></tr>
                    <tr><td>Less than 2 hours before booking</td><td>Full amount</td><td>RM 0 (No cancellation allowed)</td></tr>
                </tbody>
            </table>
        </div>
        
        <div class="section">
            <h2>3. How to Cancel</h2>
            <p>To cancel a booking:</p>
            <ol style="margin-left:1.5rem; color:#5a6e5c;">
                <li>Go to "My Bookings" from your dashboard</li>
                <li>Find the booking you wish to cancel</li>
                <li>Click the "Cancel" button</li>
                <li>Confirm the cancellation</li>
            </ol>
        </div>
        
        <div class="section">
            <h2>4. Refund Process</h2>
            <p>When you cancel a booking:</p>
            <ul>
                <li>Refunds are credited to your Smash Arena wallet</li>
                <li>Processing time is immediate upon cancellation</li>
                <li>Wallet balance can be used for future bookings</li>
                <li>To withdraw wallet balance to bank account, please contact customer support</li>
            </ul>
        </div>
        
        <div class="section">
            <h2>5. No-Show Policy</h2>
            <p>If you do not show up for your booking without prior cancellation:</p>
            <ul>
                <li>Full booking amount will be charged</li>
                <li>No refund will be provided</li>
                <li>The booking will be marked as "Completed"</li>
            </ul>
        </div>
        
        <div class="section">
            <h2>6. Exceptions</h2>
            <p>In case of emergencies or unforeseen circumstances, please contact our support team. We may consider waiving the cancellation fee on a case-by-case basis.</p>
            <div class="notice">
                <i class="fas fa-phone-alt"></i> Contact us immediately for any urgent cancellation requests: <strong>+603-1234 5678</strong>
            </div>
        </div>
    </div>
</div>
</body>
</html>