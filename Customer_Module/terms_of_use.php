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
    <title>Terms of Use | Smash Arena</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:linear-gradient(145deg,#f5f9f0 0%,#e8efe2 100%); color:#1e2a2e; line-height:1.5; }
        
        .navbar { display:flex; justify-content:space-between; align-items:center; padding:0.8rem 5%; background:rgba(255,255,255,0.98); backdrop-filter:blur(12px); position:sticky; top:0; z-index:100; border-bottom:1px solid rgba(43,126,58,0.15); }
        .logo-area { display:flex; align-items:center; gap:0.8rem; text-decoration:none; cursor:pointer; }
        .logo-area:hover .logo-text { transform:scale(1.02); }
        .logo-area img { height: 50px; width: auto; transition:transform 0.3s; }
        .logo-area:hover img { transform:scale(1.02); }
        .logo-text { 
            font-size:1.3rem; 
            font-weight:700; 
            background:linear-gradient(135deg,#2b7e3a,#1b5e2a,#0f3d1a); 
            -webkit-background-clip:text; 
            background-clip:text; 
            color:transparent;
            letter-spacing:-0.3px;
            transition:transform 0.3s;
        }
        .logo-text span { 
            background:linear-gradient(135deg,#e67e22,#f39c12); 
            -webkit-background-clip:text; 
            background-clip:text; 
            color:transparent;
        }
        .nav-links { display:flex; gap:1.5rem; align-items:center; }
        .nav-links a { color:#2c4a2e; text-decoration:none; font-weight:500; transition:0.2s; }
        .nav-links a:hover { color:#2b7e3a; }
        
        .container { max-width:1000px; margin:0 auto; padding:2rem 5%; }
        
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
        
        .highlight { background:#eaf5e6; padding:1rem; border-radius:16px; margin:1rem 0; border-left:4px solid #2b7e3a; }
        
        /* Footer */
        .footer { background:#0f1f12; color:#cbd5c0; padding:3rem 5% 1.5rem; margin-top:2rem; }
        .footer-container { max-width:1400px; margin:0 auto; display:grid; grid-template-columns:repeat(auto-fit,minmax(220px,1fr)); gap:2rem; margin-bottom:2rem; }
        .footer-col h3, .footer-col h4 { color:#2b7e3a; margin-bottom:1rem; }
        .footer-col p { margin-bottom:0.5rem; display:flex; align-items:center; gap:0.6rem; font-size:0.9rem; }
        .footer-col a { color:#cbd5c0; text-decoration:none; display:block; margin-bottom:0.6rem; transition:0.2s; font-size:0.9rem; }
        .footer-col a:hover { color:#2b7e3a; padding-left:5px; }
        .social-icons { display:flex; gap:1rem; margin-top:1rem; }
        .social-icons a { background:#2c4a2e; width:36px; height:36px; display:flex; align-items:center; justify-content:center; border-radius:50%; transition:0.2s; color:#cbd5c0; text-decoration:none; }
        .social-icons a:hover { background:#2b7e3a; transform:translateY(-3px); }
        .footer-bottom { text-align:center; border-top:1px solid #2c4a2e; padding-top:1.5rem; font-size:0.8rem; }
        
        @media (max-width:768px) {
            .navbar { flex-direction:column; gap:1rem; }
            .page-card { padding:1.5rem; }
            .footer-container { text-align:center; }
            .footer-col p { justify-content:center; }
            .social-icons { justify-content:center; }
        }
    </style>
</head>
<body>
<nav class="navbar">
    <a href="<?php echo $back_link; ?>" class="logo-area">
        <img src="../Pictures/Admin_Module/logo.png" alt="Smash Arena" onerror="this.style.display='none'">
        <div class="logo-text">Smash <span>Arena</span></div>
    </a>
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
            <p>For questions about these Terms of Use, please contact us at <strong>smasharenabadminton@gmail.com</strong>.</p>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-col">
            <h3>Smash Arena</h3>
            <p><i class="fas fa-map-marker-alt"></i> 123 Jalan Badminton, Kuala Lumpur</p>
            <p><i class="fas fa-phone-alt"></i> +603-1234 5678</p>
            <p><i class="fas fa-envelope"></i> smasharenabadminton@gmail.com</p>
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
            <p><i class="fas fa-clock"></i> Monday - Sunday: <?php echo getOperatingHours(); ?></p>
            <p><i class="fas fa-tag"></i> 8am - <?php echo date('h:i A', strtotime(getSetting('peak_start', '15:00'))); ?>: RM <?php echo getSetting('off_peak_price', '10'); ?>/hour</p>
            <p><i class="fas fa-tag"></i> <?php echo date('h:i A', strtotime(getSetting('peak_start', '15:00'))); ?> - <?php echo date('h:i A', strtotime(getSetting('close_time', '01:00'))); ?>: RM <?php echo getSetting('peak_price', '15'); ?>/hour</p>
            <p><i class="fas fa-calendar-alt"></i> Open daily including public holidays</p>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2025 Smash Arena – Your Game, Our Court. All rights reserved.</p>
    </div>
</footer>
</body>
</html>