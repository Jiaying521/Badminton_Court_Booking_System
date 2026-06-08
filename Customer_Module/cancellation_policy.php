<?php
require_once __DIR__ . '/../config.php';
$isLoggedIn = isset($_SESSION['user_id']);
$back_link = $isLoggedIn ? 'dashboard.php' : 'homepage.php';
$home_link = $isLoggedIn ? 'dashboard.php' : 'homepage.php';

// 从 settings 表获取动态配置
function getSetting($key, $default = '') {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

// 获取各种设置
$cancellation_hours = getSetting('cancellation_hours', '2');
$cancellation_fee = getSetting('cancellation_fee', '10.00');
$contact_phone = getSetting('contact_phone', '+603-1234 5678');
$address = getSetting('address', '123 Jalan Badminton, Kuala Lumpur, Malaysia');
$contact_email = getSetting('contact_email', 'smasharenabadminton@gmail.com');
$open_time = getSetting('open_time', '08:00');
$close_time = getSetting('close_time', '01:00');
$peak_start = getSetting('peak_start', '15:00');
$off_peak_price = getSetting('off_peak_price', '10.00');
$peak_price = getSetting('peak_price', '15.00');

$open_time_display = date('h:i A', strtotime($open_time));
$close_time_display = date('h:i A', strtotime($close_time));
$peak_start_display = date('h:i A', strtotime($peak_start));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cancellation & Reschedule Policy | Smash Arena</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        
        body { 
            font-family: 'Inter', 'Poppins', 'Montserrat', sans-serif; 
            background: radial-gradient(circle at 10% 20%, rgba(240,245,236,1) 0%, rgba(226,236,217,1) 100%);
            color: #1e2a2e; 
            padding: 2rem;
            min-height: 100vh;
            position: relative;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(rgba(43,126,58,0.08) 1px, transparent 1px);
            background-size: 40px 40px;
            pointer-events: none;
            z-index: 0;
        }
        
        .container { 
            max-width: 1400px; 
            margin: 0 auto; 
            position: relative;
            z-index: 1;
        }
        
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #e0e8dc; border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #2b7e3a; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #1f5a2a; }
        
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            gap: 1rem;
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(15px);
            padding: 0.8rem 1.8rem;
            border-radius: 80px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.05);
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeInDown 0.6s ease-out;
        }
        
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .logo-area { 
            display: flex; 
            align-items: center; 
            gap: 0.8rem; 
            text-decoration: none; 
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .logo-area::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: linear-gradient(90deg, #2b7e3a, #e67e22);
            transition: width 0.4s ease;
        }
        
        .logo-area:hover::after { width: 100%; }
        .logo-area:hover .logo-text { transform: scale(1.02); }
        .logo-area img { 
            height: 45px; 
            width: auto; 
            transition: transform 0.3s ease;
        }
        .logo-area:hover img { transform: scale(1.02) rotate(5deg); }
        .logo-text { 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-size: 1.4rem; 
            font-weight: 800; 
            background: linear-gradient(135deg, #2b7e3a 0%, #e67e22 80%);
            -webkit-background-clip: text; 
            background-clip: text; 
            color: transparent;
            letter-spacing: -1px;
            transition: transform 0.3s ease;
            text-transform: uppercase;
        }
        .logo-text span { 
            background: linear-gradient(135deg, #e67e22 0%, #f39c12 100%); 
            -webkit-background-clip: text; 
            background-clip: text; 
            color: transparent;
        }
        
        .nav-links { 
            display: flex; 
            align-items: center; 
            gap: 0.8rem; 
            flex-wrap: wrap; 
        }
        .nav-links a { 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 600;
            letter-spacing: 0.2px;
            color: #2c4a2e; 
            text-decoration: none; 
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            position: relative;
            overflow: hidden;
        }
        
        .nav-links a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .nav-links a:hover::before { left: 100%; }
        .nav-links a:hover, .nav-links a.active { 
            background: #2b7e3a;
            color: white; 
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(43,126,58,0.3);
        }
        
        .btn-back { 
            display: inline-flex; 
            align-items: center; 
            gap: 0.6rem; 
            background: linear-gradient(135deg, #2b7e3a, #1f5a2a);
            color: white; 
            text-decoration: none; 
            padding: 0.6rem 1.5rem; 
            border-radius: 50px; 
            font-family: 'Montserrat', 'Inter', sans-serif;
            font-weight: 600; 
            font-size: 0.85rem; 
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
            display: inline-block;
            position: relative;
            overflow: hidden;
        }
        
        .btn-back::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.5s ease;
        }
        
        .btn-back:hover::before { left: 100%; }
        .btn-back:hover { 
            background: #1f5a2a; 
            transform: translateY(-3px); 
            box-shadow: 0 8px 20px rgba(43,126,58,0.3);
        }
        
        .page-card { 
            background: rgba(255,255,255,0.7);
            backdrop-filter: blur(10px);
            border-radius: 32px; 
            padding: 2.5rem; 
            box-shadow: 0 12px 28px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.3);
            animation: fadeInScale 0.5s ease-out 0.1s both;
        }
        
        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        
        .page-header { text-align: center; margin-bottom: 2rem; }
        .page-header h1 { 
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-size: 2rem; 
            font-weight: 800; 
            background: linear-gradient(135deg, #2b7e3a, #1b5e2a); 
            -webkit-background-clip: text; 
            background-clip: text; 
            color: transparent; 
        }
        .page-header p { 
            font-family: 'DM Sans', sans-serif;
            color: #5a6e5c; 
            margin-top: 0.5rem; 
        }
        
        .section { margin-bottom: 2rem; }
        .section h2 { 
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-size: 1.3rem; 
            font-weight: 700; 
            color: #2b7e3a; 
            margin-bottom: 1rem; 
            padding-bottom: 0.5rem; 
            border-bottom: 2px solid rgba(234,245,230,0.8);
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }
        .section p { 
            font-family: 'DM Sans', sans-serif;
            color: #5a6e5c; 
            margin-bottom: 0.8rem; 
            line-height: 1.6; 
        }
        .section ul { margin-left: 1.5rem; margin-bottom: 1rem; }
        .section li { 
            font-family: 'DM Sans', sans-serif;
            color: #5a6e5c; 
            margin-bottom: 0.5rem; 
        }
        
        .notice { 
            background: rgba(255,248,225,0.8);
            backdrop-filter: blur(5px);
            padding: 1rem;
            border-radius: 16px; 
            margin: 1rem 0; 
            border-left: 4px solid #e67e22;
        }
        
        .notice-success {
            background: rgba(212,237,218,0.8);
            border-left-color: #2b7e3a;
        }
        
        .refund-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 1rem 0; 
            border-radius: 16px;
            overflow: hidden;
        }
        .refund-table th, .refund-table td { 
            padding: 0.8rem; 
            text-align: left; 
            border-bottom: 1px solid rgba(224,232,220,0.8);
        }
        .refund-table th { 
            background: rgba(234,245,230,0.6);
            font-family: 'Montserrat', sans-serif;
            color: #1e3a2a; 
            font-weight: 700;
        }
        .refund-table td { font-family: 'DM Sans', sans-serif; }
        
        .badge {
            display: inline-block;
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .footer { 
            background: #0f1f12; 
            color: #cbd5c0; 
            padding: 3rem 5% 1.5rem; 
            margin-top: 4rem;
            border-radius: 32px 32px 0 0;
        }
        .footer-container { 
            max-width: 1400px; 
            margin: 0 auto; 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); 
            gap: 2rem; 
            margin-bottom: 2rem; 
        }
        .footer-col h3, .footer-col h4 { 
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            color: #2b7e3a; 
            margin-bottom: 1rem;
        }
        .footer-col p { 
            margin-bottom: 0.5rem; 
            display: flex; 
            align-items: center; 
            gap: 0.6rem; 
            font-size: 0.9rem; 
        }
        .footer-col a { 
            color: #cbd5c0; 
            text-decoration: none; 
            display: block; 
            margin-bottom: 0.6rem; 
            transition: 0.2s; 
            font-size: 0.9rem; 
        }
        .footer-col a:hover { 
            color: #2b7e3a; 
            padding-left: 5px; 
            transform: translateX(3px);
        }
        .social-icons { 
            display: flex; 
            gap: 1rem; 
            margin-top: 1rem; 
        }
        .social-icons a { 
            background: #2c4a2e; 
            width: 36px; 
            height: 36px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            border-radius: 50%; 
            transition: all 0.3s ease; 
            color: #cbd5c0; 
            text-decoration: none;
        }
        .social-icons a:hover { 
            background: #2b7e3a; 
            transform: translateY(-5px) rotate(360deg);
        }
        .footer-bottom { 
            text-align: center; 
            border-top: 1px solid #2c4a2e; 
            padding-top: 1.5rem; 
            font-size: 0.8rem; 
        }
        
        @media (max-width: 768px) {
            body { padding: 1rem; }
            .navbar { flex-direction: column; border-radius: 28px; }
            .page-card { padding: 1.5rem; }
            .refund-table th, .refund-table td { padding: 0.5rem; font-size: 0.85rem; }
            .footer-container { text-align: center; }
            .footer-col p { justify-content: center; }
            .social-icons { justify-content: center; }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <a href="<?php echo $home_link; ?>" class="logo-area">
        <img src="../Pictures/Admin_Module/logo.png" alt="Smash Arena" onerror="this.style.display='none'">
        <div class="logo-text">Smash <span>Arena</span></div>
    </a>
    <div class="nav-links">
        <a href="<?php echo $home_link; ?>">Home</a>
        <a href="dashboard.php">Courts</a>
        <a href="my_bookings.php">My Bookings</a>
        <a href="coaches.php">Coaches</a>
    </div>
</nav>

<div class="container">
    <div class="page-card">
        <a href="<?php echo $back_link; ?>" class="btn-back"><i class="fas fa-arrow-left"></i> Back to <?php echo $isLoggedIn ? 'Dashboard' : 'Homepage'; ?></a>
        
        <div class="page-header">
            <h1><i class="fas fa-file-alt"></i> Cancellation & Reschedule Policy</h1>
            <p>Last updated: June 2025</p>
        </div>
        
        <!-- 取消政策总览表 -->
        <div class="section">
            <h2><i class="fas fa-table"></i> Cancellation Policy Overview</h2>
            <table class="refund-table">
                <thead>
                    <tr>
                        <th>Time Before Booking</th>
                        <th>Play Only Mode</th>
                        <th>Training Mode (with Coach)</th>
                        <th>Add-ons</th>
                        <th>Reschedule Allowed?</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="badge badge-success">≥ 48 hours</span></td>
                        <td>✅ Full Refund</td>
                        <td>✅ Full Refund</td>
                        <td>✅ Full Refund</td>
                        <td><span class="badge badge-success">✓ Allowed (No fee)</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-warning">24 - 48 hours</span></td>
                        <td>⚠️ RM10 fee, rest refunded</td>
                        <td>✅ Full Refund</td>
                        <td>✅ Full Refund</td>
                        <td><span class="badge badge-success">✓ Allowed (No fee)</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-warning">2 - 24 hours</span></td>
                        <td>❌ Court fee NOT refunded<br>✅ Add-ons refunded</td>
                        <td>❌ Court fee NOT refunded<br>⚠️ 50% coach fee refunded<br>✅ Add-ons refunded</td>
                        <td>✅ Full Refund</td>
                        <td><span class="badge badge-danger">✗ Not Allowed (Locked)</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-danger">1 - 2 hours</span></td>
                        <td>❌ No Refund</td>
                        <td>❌ Court fee NOT refunded<br>❌ Coach fee NOT refunded<br>✅ Add-ons refunded</td>
                        <td>✅ Full Refund</td>
                        <td><span class="badge badge-danger">✗ Not Allowed (Locked)</span></td>
                    </tr>
                    <tr>
                        <td><span class="badge badge-danger">&lt; 1 hour</span></td>
                        <td>❌ No Refund</td>
                        <td>❌ No Refund</td>
                        <td>❌ No Refund</td>
                        <td><span class="badge badge-danger">✗ Not Allowed (Locked)</span></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- 改期政策 -->
        <div class="section">
            <h2><i class="fas fa-calendar-alt"></i> Reschedule Policy</h2>
            <p>You may reschedule your booking under the following conditions:</p>
            <ul>
                <li><strong>≥ 24 hours before booking</strong> - Reschedule allowed with <strong>NO additional fee</strong></li>
                <li><strong>&lt; 24 hours before booking</strong> - Reschedule <strong>NOT allowed</strong> (Locked)</li>
                <li>Each booking can only be rescheduled <strong>once</strong></li>
            </ul>
            <div class="notice notice-success">
                <i class="fas fa-info-circle"></i> <strong>Note:</strong> Rescheduling is free of charge. Your booking status and price remain the same.
            </div>
        </div>
        
        <!-- 教练取消 -->
        <div class="section">
            <h2><i class="fas fa-chalkboard-user"></i> Coach Cancellation Policy</h2>
            <table class="refund-table">
                <thead>
                    <tr>
                        <th>Coach Cancellation Time</th>
                        <th>Customer Compensation</th>
                        <th>Coach Penalty</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>≥ 24 hours</td>
                        <td>Full refund + System auto-cancel, customer can rebook</td>
                        <td>No penalty</td>
                    </tr>
                    <tr>
                        <td>&lt; 24 hours (Late cancel / No-show)</td>
                        <td>Full refund + <strong>RM 10 compensation</strong> OR continue court without coach</td>
                        <td>Severe penalty (deduction from next month's salary)</td>
                    </tr>
                </tbody>
            </table>
            <div class="notice">
                <i class="fas fa-exclamation-triangle"></i> <strong>Coach Penalty System:</strong>
                <ul style="margin-top: 0.5rem;">
                    <li>1st late cancel / no-show: Account suspended for <strong>3 days</strong></li>
                    <li>2nd late cancel / no-show: Account suspended for <strong>7 days</strong></li>
                    <li>3rd late cancel / no-show: Account <strong>permanently banned</strong></li>
                </ul>
            </div>
        </div>
        
        <!-- 取消费用详情 -->
        <div class="section">
            <h2><i class="fas fa-receipt"></i> Cancellation Fee Details</h2>
            <ul>
                <li><strong>Standard Cancellation Fee:</strong> RM 10.00 (applies for 24-48 hours notice on Play Only mode)</li>
                <li><strong>2nd Cancellation Penalty:</strong> Additional RM 5.00 for second cancellation</li>
                <li><strong>No-Show:</strong> Full amount charged, no refund</li>
            </ul>
        </div>
        
        <!-- Add-on 处理 -->
        <div class="section">
            <h2><i class="fas fa-shopping-cart"></i> Add-ons Refund Policy</h2>
            <p>Add-on items (rackets, shuttlecocks, grips, strings, snacks, drinks) follow this refund policy:</p>
            <ul>
                <li><strong>≥ 2 hours before booking:</strong> Add-ons are <strong>FULLY refunded</strong></li>
                <li><strong>1-2 hours before booking:</strong> Add-ons are <strong>FULLY refunded</strong> (except Play Only mode where no refund)</li>
                <li><strong>&lt; 1 hour before booking:</strong> Add-ons are <strong>NOT refunded</strong> (except Training mode where add-ons are refunded up to 1 hour)</li>
            </ul>
        </div>
        
        <!-- 如何取消 -->
        <div class="section">
            <h2><i class="fas fa-times-circle"></i> How to Cancel</h2>
            <p>To cancel a booking:</p>
            <ol style="margin-left:1.5rem; color:#5a6e5c;">
                <li>Go to <strong>"My Bookings"</strong> from your dashboard</li>
                <li>Find the booking you wish to cancel</li>
                <li>Click the <strong>"Cancel"</strong> button</li>
                <li>Review the cancellation policy and confirm</li>
                <li>Refund will be automatically credited to your wallet</li>
            </ol>
        </div>
        
        <!-- 退款流程 -->
        <div class="section">
            <h2><i class="fas fa-credit-card"></i> Refund Process</h2>
            <ul>
                <li>Refunds are credited to your <strong>Smash Arena wallet</strong> instantly upon cancellation</li>
                <li>Wallet balance can be used for future bookings</li>
                <li>To withdraw wallet balance to bank account, please contact customer support</li>
            </ul>
        </div>
        
        <!-- 联系支持 -->
        <div class="section">
            <h2><i class="fas fa-headset"></i> Need Help?</h2>
            <div class="notice">
                <i class="fas fa-phone-alt"></i> Phone: <strong><?php echo htmlspecialchars($contact_phone); ?></strong><br>
                <i class="fas fa-envelope"></i> Email: <strong><?php echo htmlspecialchars($contact_email); ?></strong><br>
                <i class="fas fa-clock"></i> Support Hours: Daily 9:00 AM - 9:00 PM
            </div>
            <p>For urgent cancellation requests or special circumstances, please contact us immediately.</p>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-col"><h3>Smash Arena</h3><p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($address); ?></p><p><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($contact_phone); ?></p><p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contact_email); ?></p><div class="social-icons"><a href="#"><i class="fab fa-facebook-f"></i></a><a href="#"><i class="fab fa-instagram"></i></a><a href="#"><i class="fab fa-twitter"></i></a><a href="#"><i class="fab fa-whatsapp"></i></a></div></div>
        <div class="footer-col"><h4>Quick Links</h4><a href="dashboard.php">Find a Court</a><a href="my_bookings.php">My Bookings</a><a href="../Payment_Module/wallet.php">Wallet</a></div>
        <div class="footer-col"><h4>Support</h4><a href="faq.php">FAQs</a><a href="cancellation_policy.php">Cancellation Policy</a><a href="privacy_policy.php">Privacy Policy</a><a href="terms_of_use.php">Terms of Use</a><a href="contact_us.php">Contact Us</a></div>
        <div class="footer-col"><h4>Operating Hours</h4><p><i class="fas fa-clock"></i> Monday - Sunday: <?php echo $open_time_display; ?> - <?php echo $close_time_display; ?></p><p><i class="fas fa-tag"></i> <?php echo $open_time_display; ?> - <?php echo $peak_start_display; ?>: RM <?php echo $off_peak_price; ?>/hour</p><p><i class="fas fa-tag"></i> <?php echo $peak_start_display; ?> - <?php echo $close_time_display; ?>: RM <?php echo $peak_price; ?>/hour</p><p><i class="fas fa-calendar-alt"></i> Open daily including public holidays</p></div>
    </div>
    <div class="footer-bottom"><p>&copy; 2025 Smash Arena – Your Game, Our Court. All rights reserved.</p></div>
</footer>
</body>
</html>