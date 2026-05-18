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
        .logo img { height: 65px; width: auto; transition:transform 0.3s; }
        .logo img:hover { transform:scale(1.02); }
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
        
        @media (max-width:768px) {
            .navbar { flex-direction:column; gap:1rem; }
            .page-card { padding:1.5rem; }
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