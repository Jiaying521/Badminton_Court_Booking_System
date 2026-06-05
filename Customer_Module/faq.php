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
$cancellation_hours = getSetting('cancellation_hours', '2');
$cancellation_fee = getSetting('cancellation_fee', '10.00');
$contact_phone = getSetting('contact_phone', '+603-1234 5678');
$contact_email = getSetting('contact_email', 'smasharenabadminton@gmail.com');
$contact_whatsapp = getSetting('contact_whatsapp', '+60 12-345 6789');
$address = getSetting('address', '123 Jalan Badminton, Kuala Lumpur, Malaysia');

// 格式化时间显示
$open_time_display = date('h:i A', strtotime($open_time));
$close_time_display = date('h:i A', strtotime($close_time));
$peak_start_display = date('h:i A', strtotime($peak_start));

// ========== FAQ 内容 ==========
$faqs = [
    [
        'question' => 'How do I book a court?',
        'answer' => 'To book a court, simply login to your account, go to the Dashboard, select your preferred court, choose date and time, select any optional add-ons (rackets, shuttlecocks, grips, snacks, drinks), and proceed to payment. You\'ll receive a confirmation email once your booking is complete.'
    ],
    [
        'question' => 'What are the operating hours?',
        'answer' => 'Smash Arena is open daily from ' . $open_time_display . ' to ' . $close_time_display . '. We operate every day including public holidays. Last booking is at 12:00 AM for 1-hour sessions.'
    ],
    [
        'question' => 'What are the different pricing rates?',
        'answer' => '<strong>Off-Peak Hours (' . $open_time_display . ' - ' . $peak_start_display . '):</strong> RM ' . $off_peak_price . ' per hour<br><strong>Peak Hours (' . $peak_start_display . ' - ' . $close_time_display . '):</strong> RM ' . $peak_price . ' per hour<br><br>Training courts have the same pricing but include optional coach services (RM20-50 per hour).'
    ],
    [
        'question' => 'Can I cancel my booking?',
        'answer' => 'Yes, you can cancel your booking up to ' . $cancellation_hours . ' hours before the scheduled time. A cancellation fee of RM ' . $cancellation_fee . ' applies. The remaining amount will be refunded to your Smash Arena wallet. Cancellations within ' . $cancellation_hours . ' hours of booking time are not allowed.'
    ],
    [
        'question' => 'How do I get a refund?',
        'answer' => 'Refunds for eligible cancellations will be automatically credited to your Smash Arena wallet within minutes. You can use the wallet balance for future bookings or request a withdrawal by contacting our support team.'
    ],
    [
        'question' => 'Can I book a coach?',
        'answer' => 'Yes! Training courts (Court H, I, J) come with optional coach services. You can select:<br>🏆 Coach Lim - Singles Coaching (RM50/hr)<br>🎯 Coach Wong - Doubles Coaching (RM20/hr)<br>⭐ Coach Tan - Junior Development (RM30/hr)'
    ],
    [
        'question' => 'What equipment can I purchase?',
        'answer' => 'We offer a wide range of products:<br>🏸 Badminton Rackets: RM199 - RM899<br>🏸 Shuttlecocks: RM55 - RM95 per tube<br>🎾 Grips: RM8 - RM15<br>🧵 Badminton Strings: RM28 - RM45<br>🍪 Snacks: RM3.50 - RM6.50<br>🥤 Drinks: RM1.50 - RM3.50<br><br>You can add these items during the booking process.'
    ],
    [
        'question' => 'How do I top up my wallet?',
        'answer' => 'Go to the Wallet page from your Dashboard. You can top up using Credit Card, Bank Transfer, or E-Wallet. Minimum top-up amount is RM10.'
    ],
    [
        'question' => 'Is there a reward points program?',
        'answer' => 'Yes! Every RM1 spent earns you 1 reward point. Points can be redeemed for discounts on future bookings.<br>⭐ 50 points = RM5 off<br>⭐ 100 points = RM10 off<br>⭐ 180 points = RM20 off'
    ],
    [
        'question' => 'How do I contact customer support?',
        'answer' => 'You can reach us at:<br>📞 Phone: ' . $contact_phone . '<br>✉️ Email: ' . $contact_email . '<br>💬 WhatsApp: ' . $contact_whatsapp
    ],
    [
        'question' => 'Do you offer stringing services?',
        'answer' => 'Yes! We offer professional stringing services. You can purchase strings from our Add-ons page (Yonex BG-65, BG-66 Ultimax, BG-80 Power, Li-Ning No.1, Victor VBS-66N, Apacs L66) and our staff will string your racket for you. Prices range from RM28 to RM45 per string.'
    ],
    [
        'question' => 'What facilities are available?',
        'answer' => 'All courts come with shower facilities, locker rooms, free parking, and a pro shop. Training courts also feature video analysis equipment and dedicated coaching areas.'
    ],
    [
        'question' => 'Can I bring my own equipment?',
        'answer' => 'Absolutely! You are welcome to bring your own rackets, shuttlecocks, and grip tape. We also have rental rackets and shuttlecocks available for purchase if you need them.'
    ],
    [
        'question' => 'Is there parking available?',
        'answer' => 'Yes! We have a large free parking lot available for all customers. Parking is secure and well-lit for evening sessions.'
    ],
    [
        'question' => 'Can I book a court for a tournament or event?',
        'answer' => 'Yes! For tournament bookings, corporate events, or birthday parties, please contact our support team directly. We offer special rates for bulk bookings and event packages.'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQs | Smash Arena</title>
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
        
        .faq-item { 
            margin-bottom: 1rem; 
            border: 1px solid rgba(224,232,220,0.8);
            border-radius: 20px; 
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .faq-item:hover {
            border-color: #2b7e3a;
            box-shadow: 0 4px 15px rgba(43,126,58,0.08);
        }
        .faq-question { 
            background: rgba(248,250,245,0.6);
            padding: 1rem 1.2rem; 
            cursor: pointer; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            font-family: 'Montserrat', 'Poppins', sans-serif;
            font-weight: 600; 
            color: #1e3a2a; 
            transition: 0.3s;
        }
        .faq-question:hover { background: rgba(234,245,230,0.8); }
        .faq-question i { 
            transition: transform 0.3s ease; 
            color: #2b7e3a; 
            font-size: 0.9rem;
        }
        .faq-answer { 
            display: none;
            opacity: 0;
            padding: 0 1.2rem;
            background: rgba(255,255,255,0.5);
            transition: opacity 0.3s ease;
        }
        .faq-answer.open { 
            display: block;
            opacity: 1;
            padding: 1rem 1.2rem;
        }
        .faq-answer p { 
            font-family: 'DM Sans', sans-serif;
            color: #5a6e5c; 
            line-height: 1.6; 
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
            <h1>Frequently Asked Questions</h1>
            <p>Find answers to common questions about Smash Arena</p>
        </div>
        
        <?php foreach ($faqs as $faq): ?>
        <div class="faq-item">
            <div class="faq-question" onclick="toggleFAQ(this)">
                <span><?php echo $faq['question']; ?></span>
                <i class="fas fa-chevron-down"></i>
            </div>
            <div class="faq-answer">
                <p><?php echo $faq['answer']; ?></p>
            </div>
        </div>
        <?php endforeach; ?>
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

<script>
    function toggleFAQ(element) {
        var answer = element.nextElementSibling;
        var icon = element.querySelector('i');
        
        if (answer.classList.contains('open')) {
            answer.classList.remove('open');
            icon.style.transform = 'rotate(0deg)';
        } else {
            answer.classList.add('open');
            icon.style.transform = 'rotate(180deg)';
        }
    }
</script>
</body>
</html>