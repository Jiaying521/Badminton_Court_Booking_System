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
$open_time = getSetting('open_time', '08:00');
$close_time = getSetting('close_time', '01:00');
$peak_start = getSetting('peak_start', '15:00');
$off_peak_price = getSetting('off_peak_price', '10.00');
$peak_price = getSetting('peak_price', '15.00');
$contact_phone = getSetting('contact_phone', '+603-1234 5678');
$contact_email = getSetting('contact_email', 'smasharenabadminton@gmail.com');
$address = getSetting('address', '123 Jalan Badminton, Kuala Lumpur, Malaysia');

// 格式化时间显示
$open_time_display = date('h:i A', strtotime($open_time));
$close_time_display = date('h:i A', strtotime($close_time));
$peak_start_display = date('h:i A', strtotime($peak_start));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms of Use | Smash Arena</title>
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
        
        .highlight { 
            background: rgba(234,245,230,0.6);
            backdrop-filter: blur(5px);
            padding: 1rem; 
            border-radius: 16px; 
            margin: 1rem 0; 
            border-left: 4px solid #2b7e3a;
        }
        
        .footer { 
            background: #0f1f12; 
            color: #cbd5c0; 
            padding: 3rem 5% 1.5rem; 
            margin-top: 4rem;
            margin-left: -2rem;
            margin-right: -2rem;
            margin-bottom: -2rem;
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
            <h1>Terms of Use</h1>
            <p>Please read these terms carefully before using our services</p>
        </div>
        
        <div class="section">
            <h2>1. Acceptance of Terms</h2>
            <p>By accessing or using Smash Arena's website and services, you agree to be bound by these Terms of Use. If you do not agree to these terms, please do not use our services.</p>
        </div>
        
        <div class="section">
            <h2>2. User Accounts</h2>
            <p>To use our booking services, you must create an account. You are responsible for:</p>
            <ul>
                <li>Maintaining the confidentiality of your password</li>
                <li>All activities that occur under your account</li>
                <li>Providing accurate and complete information</li>
                <li>Notifying us immediately of any unauthorized use</li>
            </ul>
        </div>
        
        <div class="section">
            <h2>3. Booking and Payments</h2>
            <ul>
                <li>All bookings are subject to availability</li>
                <li>Prices are as displayed at the time of booking</li>
                <li>Payments must be made in full at the time of booking</li>
                <li>We reserve the right to change prices without prior notice</li>
                <li>Wallet credits are non-transferable and have no cash value</li>
            </ul>
        </div>
        
        <div class="section">
            <h2>4. Cancellation Policy</h2>
            <p>Cancellations are subject to our Cancellation Policy. Please review it separately for detailed information about cancellation fees and refunds.</p>
        </div>
        
        <div class="section">
            <h2>5. Code of Conduct</h2>
            <p>When using our facilities, you agree to:</p>
            <ul>
                <li>Respect other players and staff</li>
                <li>Follow safety guidelines and facility rules</li>
                <li>Not engage in any disruptive or harmful behavior</li>
                <li>Use equipment properly and report any damages</li>
            </ul>
        </div>
        
        <div class="section">
            <h2>6. Prohibited Activities</h2>
            <p>You may not:</p>
            <ul>
                <li>Use our services for any illegal purpose</li>
                <li>Attempt to gain unauthorized access to our systems</li>
                <li>Resell or transfer bookings without authorization</li>
                <li>Use automated systems to make bookings</li>
                <li>Post false or misleading information</li>
            </ul>
        </div>
        
        <div class="section">
            <h2>7. Liability</h2>
            <p>Smash Arena is not liable for:</p>
            <ul>
                <li>Any injuries sustained during play (please play responsibly)</li>
                <li>Loss or damage of personal belongings</li>
                <li>Service interruptions due to maintenance or emergencies</li>
                <li>Third-party services or products</li>
            </ul>
            <div class="highlight">
                <i class="fas fa-shield-alt"></i> <strong>Play at your own risk.</strong> We recommend warming up properly and using appropriate safety equipment.
            </div>
        </div>
        
        <div class="section">
            <h2>8. Intellectual Property</h2>
            <p>All content on this website, including logos, images, and text, is the property of Smash Arena and may not be used without permission.</p>
        </div>
        
        <div class="section">
            <h2>9. Modifications to Terms</h2>
            <p>We may update these Terms of Use from time to time. Continued use of our services constitutes acceptance of any changes.</p>
        </div>
        
        <div class="section">
            <h2>10. Governing Law</h2>
            <p>These terms shall be governed by and construed in accordance with the laws of Malaysia.</p>
        </div>
        
        <div class="section">
            <h2>11. Contact Information</h2>
            <p>For questions about these Terms of Use, please contact us at <strong><?php echo htmlspecialchars($contact_email); ?></strong>.</p>
        </div>
    </div>
</div>

<footer class="footer">
    <div class="footer-container">
        <div class="footer-col">
            <h3>Smash Arena</h3>
            <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($address); ?></p>
            <p><i class="fas fa-phone-alt"></i> <?php echo htmlspecialchars($contact_phone); ?></p>
            <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($contact_email); ?></p>
            <div class="social-icons">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-twitter"></i></a>
                <a href="#"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
        <div class="footer-col">
            <h4>Quick Links</h4>
            <a href="dashboard.php">Find a Court</a>
            <a href="my_bookings.php">My Bookings</a>
            <a href="../Payment_Module/wallet.php">Wallet</a>
        </div>
        <div class="footer-col">
            <h4>Support</h4>
            <a href="faq.php">FAQs</a>
            <a href="cancellation_policy.php">Cancellation Policy</a>
            <a href="privacy_policy.php">Privacy Policy</a>
            <a href="terms_of_use.php">Terms of Use</a>
            <a href="contact_us.php">Contact Us</a>
        </div>
        <div class="footer-col">
            <h4>Operating Hours</h4>
            <p><i class="fas fa-clock"></i> Monday - Sunday: <?php echo $open_time_display; ?> - <?php echo $close_time_display; ?></p>
            <p><i class="fas fa-tag"></i> <?php echo $open_time_display; ?> - <?php echo $peak_start_display; ?>: RM <?php echo $off_peak_price; ?>/hour</p>
            <p><i class="fas fa-tag"></i> <?php echo $peak_start_display; ?> - <?php echo $close_time_display; ?>: RM <?php echo $peak_price; ?>/hour</p>
            <p><i class="fas fa-calendar-alt"></i> Open daily including public holidays</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2025 Smash Arena – Your Game, Our Court. All rights reserved.</p>
    </div>
</footer>
</body>
</html>