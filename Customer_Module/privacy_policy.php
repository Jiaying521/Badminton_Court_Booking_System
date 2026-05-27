<?php
require_once __DIR__ . '/../config.php';
// 不需要登录也可以查看隐私政策

// 检查是否已登录，决定返回链接
$isLoggedIn = isset($_SESSION['user_id']);
$back_link = $isLoggedIn ? 'dashboard.php' : 'homepage.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy | Smash Arena</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Inter',sans-serif; background:linear-gradient(145deg,#f5f9f0 0%,#e8efe2 100%); color:#1e2a2e; line-height:1.5; }
        
        /* Navbar */
        .navbar { display:flex; justify-content:space-between; align-items:center; padding:0.8rem 5%; background:rgba(255,255,255,0.98); backdrop-filter:blur(12px); position:sticky; top:0; z-index:100; border-bottom:1px solid rgba(43,126,58,0.15); box-shadow:0 2px 20px rgba(0,0,0,0.03); }
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
        
        /* Container */
        .container { max-width:1000px; margin:0 auto; padding:2rem 5%; }
        
        /* Back Button */
        .back-button { margin-bottom:1.5rem; }
        .btn-back-dashboard {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            background: #2b7e3a;
            color: white;
            text-decoration: none;
            padding: 0.6rem 1.2rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.2s;
            border: none;
            cursor: pointer;
        }
        .btn-back-dashboard:hover {
            background: #1f5a2a;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(43, 126, 58, 0.3);
        }
        .btn-back-dashboard i {
            font-size: 1rem;
        }
        
        /* Policy Card */
        .policy-card { background:white; border-radius:32px; padding:2.5rem; box-shadow:0 12px 28px rgba(0,0,0,0.08); margin-bottom:2rem; }
        .policy-header { text-align:center; margin-bottom:2rem; }
        .policy-header h1 { font-size:2rem; font-weight:800; background:linear-gradient(135deg,#2b7e3a,#1b5e2a); -webkit-background-clip:text; background-clip:text; color:transparent; margin-bottom:0.5rem; }
        .policy-header p { color:#5a6e5c; font-size:0.9rem; }
        .last-updated { background:#eaf5e6; display:inline-block; padding:0.3rem 1rem; border-radius:50px; font-size:0.8rem; color:#2b7e3a; margin-top:0.5rem; }
        
        .section { margin-bottom:2rem; }
        .section h2 { font-size:1.3rem; font-weight:700; color:#2b7e3a; margin-bottom:1rem; padding-bottom:0.5rem; border-bottom:2px solid #eaf5e6; }
        .section h3 { font-size:1rem; font-weight:600; color:#1e3a2a; margin:1rem 0 0.5rem; }
        .section p { color:#5a6e5c; margin-bottom:0.8rem; line-height:1.6; }
        .section ul { margin-left:1.5rem; margin-bottom:1rem; }
        .section li { color:#5a6e5c; margin-bottom:0.5rem; line-height:1.5; }
        
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
            .policy-card { padding:1.5rem; }
            .section h2 { font-size:1.1rem; }
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
        <a href="homepage.php#features">Features</a>
    </div>
</nav>

<div class="container">
    <div class="policy-card">
        <!-- 返回 Dashboard 按钮 -->
        <div class="back-button">
            <a href="<?php echo $back_link; ?>" class="btn-back-dashboard">
                <i class="fas fa-arrow-left"></i> Back to <?php echo $isLoggedIn ? 'Dashboard' : 'Homepage'; ?>
            </a>
        </div>
        
        <div class="policy-header">
            <h1>Privacy Policy</h1>
            <p>Your privacy is important to us. This policy explains how we collect, use, and protect your information.</p>
            <div class="last-updated"><i class="fas fa-calendar-alt"></i> Last Updated: May 18, 2025</div>
        </div>

        <div class="section">
            <h2>1. Information We Collect</h2>
            <p>When you use Smash Arena, we collect the following types of information:</p>
            <ul>
                <li><strong>Personal Information:</strong> Name, email address, phone number, and NRIC/passport number (for identification purposes).</li>
                <li><strong>Booking Information:</strong> Court preferences, booking dates and times, coach selections, and add-on purchases.</li>
                <li><strong>Payment Information:</strong> Transaction details, payment method, and wallet balance (payment card details are processed securely by our payment partners).</li>
                <li><strong>Usage Data:</strong> IP address, browser type, device information, and how you interact with our website.</li>
            </ul>
        </div>

        <div class="section">
            <h2>2. How We Use Your Information</h2>
            <p>We use your information for the following purposes:</p>
            <ul>
                <li>To process and manage your court bookings.</li>
                <li>To communicate with you about your bookings, updates, and promotions.</li>
                <li>To process payments and manage your wallet balance.</li>
                <li>To improve our services and website experience.</li>
                <li>To comply with legal obligations and prevent fraudulent activities.</li>
            </ul>
        </div>

        <div class="section">
            <h2>3. Information Sharing</h2>
            <p>We do not sell, trade, or rent your personal information to third parties. We may share your information with:</p>
            <ul>
                <li><strong>Service Providers:</strong> Payment processors, email service providers, and hosting services that help us operate our platform.</li>
                <li><strong>Legal Authorities:</strong> When required by law or to protect our rights and safety.</li>
                <li><strong>Business Transfers:</strong> In the event of a merger, acquisition, or sale of assets.</li>
            </ul>
            <div class="highlight">
                <i class="fas fa-shield-alt"></i> <strong>Your data is never sold to third parties for marketing purposes.</strong>
            </div>
        </div>

        <div class="section">
            <h2>4. Data Security</h2>
            <p>We implement appropriate technical and organizational measures to protect your personal information, including:</p>
            <ul>
                <li>SSL encryption for all data transmission.</li>
                <li>Secure database storage with access controls.</li>
                <li>Regular security audits and updates.</li>
                <li>Password hashing using industry-standard algorithms.</li>
            </ul>
            <p>However, no method of transmission over the Internet is 100% secure. While we strive to protect your data, we cannot guarantee absolute security.</p>
        </div>

        <div class="section">
            <h2>5. Your Rights</h2>
            <p>You have the following rights regarding your personal information:</p>
            <ul>
                <li><strong>Access:</strong> Request a copy of the data we hold about you.</li>
                <li><strong>Correction:</strong> Update or correct inaccurate information.</li>
                <li><strong>Deletion:</strong> Request deletion of your account and personal data.</li>
                <li><strong>Opt-out:</strong> Unsubscribe from marketing communications.</li>
            </ul>
            <p>To exercise these rights, please contact us at <strong>smasharenabadminton@gmail.com</strong>.</p>
        </div>

        <div class="section">
            <h2>6. Cookies and Tracking</h2>
            <p>We use cookies and similar tracking technologies to enhance your browsing experience. Cookies help us:</p>
            <ul>
                <li>Remember your login session.</li>
                <li>Understand how you use our website.</li>
                <li>Personalize content and recommendations.</li>
            </ul>
            <p>You can control cookie settings through your browser preferences. Disabling cookies may affect certain features of our website.</p>
        </div>

        <div class="section">
            <h2>7. Children's Privacy</h2>
            <p>Our services are not intended for individuals under the age of 13. We do not knowingly collect personal information from children. If you believe a child has provided us with personal information, please contact us immediately.</p>
        </div>

        <div class="section">
            <h2>8. Changes to This Policy</h2>
            <p>We may update this Privacy Policy from time to time. Any changes will be posted on this page with an updated "Last Updated" date. We encourage you to review this policy periodically.</p>
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