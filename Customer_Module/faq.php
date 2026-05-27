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
    <title>FAQs | Smash Arena</title>
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
        
        .faq-item { margin-bottom:1rem; border:1px solid #e0e8dc; border-radius:16px; overflow:hidden; }
        .faq-question { background:#f8faf5; padding:1rem 1.2rem; cursor:pointer; display:flex; justify-content:space-between; align-items:center; font-weight:600; color:#1e3a2a; transition:0.2s; }
        .faq-question:hover { background:#eaf5e6; }
        .faq-question i { transition:transform 0.3s; color:#2b7e3a; }
        .faq-answer { padding:0 1.2rem; max-height:0; overflow:hidden; transition:max-height 0.3s ease; background:white; }
        .faq-answer.show { padding:1rem 1.2rem; }
        .faq-answer p { color:#5a6e5c; line-height:1.6; }
        
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
            <h1>Frequently Asked Questions</h1>
            <p>Find answers to common questions about Smash Arena</p>
        </div>
        
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                How do I book a court? <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>To book a court, simply login to your account, go to the Dashboard, select your preferred court, choose date and time, select any optional add-ons, and proceed to payment. You'll receive a confirmation email once your booking is complete.</p>
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                What are the operating hours? <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>Smash Arena is open daily from 8:00 AM to 1:00 AM (next day). We operate every day including public holidays.</p>
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                What are the different pricing rates? <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p><strong>Off-Peak Hours (8am - 2pm):</strong> RM10 per hour<br>
                <strong>Peak Hours (3pm - 1am):</strong> RM15 per hour</p>
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                Can I cancel my booking? <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>Yes, you can cancel your booking up to 2 hours before the scheduled time. A cancellation fee of RM10 applies. The remaining amount will be refunded to your wallet.</p>
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                How do I get a refund? <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>Refunds for eligible cancellations will be automatically credited to your Smash Arena wallet. You can use the wallet balance for future bookings or request a withdrawal by contacting our support team.</p>
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                Can I book a coach? <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>Yes! Training courts (Court H, I, J) come with optional coach services. You can select Coach Lim (RM25/hr), Coach Wong (RM20/hr), or Coach Tan (RM30/hr) during the booking process.</p>
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                What equipment can I rent? <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>We offer racket rentals (RM10-30), shuttlecocks (RM55-95 per tube), grips (RM8-15), and stringing services (RM25-35). You can add these items during the booking process.</p>
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                How do I top up my wallet? <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>Go to the Wallet page from your Dashboard. You can top up using Credit Card, Bank Transfer, or E-Wallet. Minimum top-up amount is RM10.</p>
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                Is there a membership program? <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>Yes! Every RM10 spent earns you 1 reward point. Points can be redeemed for discounts on future bookings.</p>
            </div>
        </div>
        
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                How do I contact customer support? <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p>You can reach us at:<br>
                📞 Phone: +603-1234 5678<br>
                ✉️ Email: smasharenabadminton@gmail.com<br>
                💬 WhatsApp: +60 12-345 6789</p>
            </div>
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

<script>
    function toggleFAQ(element) {
        const answer = element.nextElementSibling;
        const icon = element.querySelector('i');
        answer.classList.toggle('show');
        if (answer.classList.contains('show')) {
            answer.style.maxHeight = answer.scrollHeight + 'px';
            icon.style.transform = 'rotate(180deg)';
        } else {
            answer.style.maxHeight = '0';
            icon.style.transform = 'rotate(0deg)';
        }
    }
</script>
</body>
</html>