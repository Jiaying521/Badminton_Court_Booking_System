<?php
require_once __DIR__ . '/../config.php';

// 引入 PHPMailer（放在文件顶部）
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../Admin_Module/Email_System/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../Admin_Module/Email_System/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../Admin_Module/Email_System/phpmailer/src/Exception.php';

$isLoggedIn = isset($_SESSION['user_id']);
$back_link = $isLoggedIn ? 'dashboard.php' : 'homepage.php';

// 获取用户信息（如果已登录）
$user_name = '';
$user_email = '';
if ($isLoggedIn) {
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    if ($user) {
        $user_name = $user['name'];
        $user_email = $user['email'];
    }
}

$message_sent = '';
$error = '';

// 创建 contact_messages 表（如果不存在）
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `contact_messages` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `email` varchar(255) NOT NULL,
        `subject` varchar(255) NOT NULL,
        `message` text NOT NULL,
        `user_id` int(11) DEFAULT NULL,
        `status` enum('unread','read','replied') DEFAULT 'unread',
        `created_at` datetime NOT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // 保存到数据库
        try {
            $stmt = $pdo->prepare("
                INSERT INTO contact_messages (name, email, subject, message, user_id, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $user_id = $isLoggedIn ? $_SESSION['user_id'] : NULL;
            $stmt->execute([$name, $email, $subject, $message, $user_id]);
            
            // 发送邮件通知管理员
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'smasharenabadminton@gmail.com';
                $mail->Password   = 'mrxd evvy oyyl boak';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = 587;
                
                $mail->setFrom('smasharenabadminton@gmail.com', 'Smash Arena Contact Form');
                $mail->addAddress('smasharenabadminton@gmail.com');
                $mail->addReplyTo($email, $name);
                
                $mail->isHTML(true);
                $mail->Subject = "[Contact Form] $subject";
                $mail->Body = "
                <html>
                <body style='font-family: Arial, sans-serif;'>
                    <h2>New Contact Form Submission</h2>
                    <table style='border-collapse: collapse; width: 100%;'>
                        <tr><td style='padding: 8px; background: #f0f0f0;'><strong>Name:</strong></td><td style='padding: 8px;'>$name</td></tr>
                        <tr><td style='padding: 8px; background: #f0f0f0;'><strong>Email:</strong></td><td style='padding: 8px;'>$email</td></tr>
                        <tr><td style='padding: 8px; background: #f0f0f0;'><strong>Subject:</strong></td><td style='padding: 8px;'>$subject</td></tr>
                        <tr><td style='padding: 8px; background: #f0f0f0;'><strong>Message:</strong></td><td style='padding: 8px;'>" . nl2br(htmlspecialchars($message)) . "</td></tr>
                    </table>
                    <hr>
                    <p style='font-size: 12px; color: #888;'>Sent via Smash Arena Contact Form</p>
                </body>
                </html>
                ";
                $mail->AltBody = "New Contact Form Submission\n\nName: $name\nEmail: $email\nSubject: $subject\nMessage: $message";
                $mail->send();
                
                // 发送确认邮件给用户
                $userMail = new PHPMailer(true);
                $userMail->isSMTP();
                $userMail->Host       = 'smtp.gmail.com';
                $userMail->SMTPAuth   = true;
                $userMail->Username   = 'smasharenabadminton@gmail.com';
                $userMail->Password   = 'mrxd evvy oyyl boak';
                $userMail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $userMail->Port       = 587;
                
                $userMail->setFrom('smasharenabadminton@gmail.com', 'Smash Arena');
                $userMail->addAddress($email, $name);
                $userMail->isHTML(true);
                $userMail->Subject = "We've received your message - Smash Arena";
                $userMail->Body = "
                <html>
                <body style='font-family: Arial, sans-serif;'>
                    <h2>Thank you for contacting us, $name!</h2>
                    <p>We have received your message and will get back to you within <strong>24 hours</strong>.</p>
                    <hr>
                    <p><strong>Your message:</strong></p>
                    <p style='background: #f5f9f0; padding: 10px; border-radius: 8px;'>" . nl2br(htmlspecialchars($message)) . "</p>
                    <hr>
                    <p style='font-size: 12px; color: #888;'>Smash Arena - Your Game, Our Court</p>
                </body>
                </html>
                ";
                $userMail->AltBody = "Thank you for contacting us, $name!\n\nWe have received your message and will get back to you within 24 hours.\n\nYour message:\n$message\n\nSmash Arena";
                $userMail->send();
                
                $message_sent = 'Thank you for your message! We will get back to you within 24 hours. A confirmation email has been sent to your inbox.';
                
            } catch (Exception $e) {
                $message_sent = 'Thank you for your message! We have received it and will get back to you soon.';
                error_log("Email sending failed: " . $mail->ErrorInfo);
            }
        } catch (PDOException $e) {
            $error = 'Failed to save your message. Please try again later.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us | Smash Arena</title>
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
        
        .contact-grid { display:grid; grid-template-columns:1fr 1fr; gap:2rem; }
        .contact-info { background:#f8faf5; border-radius:24px; padding:1.5rem; }
        .contact-info h3 { color:#2b7e3a; margin-bottom:1rem; font-size:1.1rem; }
        .info-item { display:flex; align-items:center; gap:1rem; padding:0.8rem 0; border-bottom:1px solid #e0e8dc; }
        .info-item:last-child { border-bottom:none; }
        .info-item i { width:30px; color:#2b7e3a; font-size:1.2rem; }
        .info-item span { color:#5a6e5c; }
        
        .contact-form { background:white; }
        .form-group { margin-bottom:1.2rem; }
        .form-group label { display:block; font-weight:600; color:#1e3a2a; margin-bottom:0.5rem; font-size:0.85rem; }
        .form-group input, .form-group select, .form-group textarea { width:100%; padding:0.8rem 1rem; border:1.5px solid #e0e8dc; border-radius:16px; background:#fefdf8; font-family:'Inter',sans-serif; font-size:1rem; transition:0.2s; }
        .form-group input:focus, .form-group select:focus, .form-group textarea:focus { outline:none; border-color:#2b7e3a; }
        
        .btn-submit { background:#2b7e3a; color:white; border:none; padding:0.9rem; border-radius:50px; width:100%; font-size:1rem; font-weight:700; cursor:pointer; transition:0.2s; margin-top:0.5rem; }
        .btn-submit:hover { background:#1f5a2a; transform:translateY(-2px); }
        
        .message-success { background:#d4edda; color:#155724; padding:0.8rem; border-radius:16px; margin-bottom:1rem; border-left:4px solid #2b7e3a; }
        .message-error { background:#fee2dd; color:#b45f1b; padding:0.8rem; border-radius:16px; margin-bottom:1rem; border-left:4px solid #e67e22; }
        
        .map-placeholder { background:#e8efe2; border-radius:24px; padding:1.5rem; text-align:center; margin-top:1.5rem; }
        .map-placeholder i { font-size:3rem; color:#2b7e3a; margin-bottom:0.5rem; }
        
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
            .contact-grid { grid-template-columns:1fr; }
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
            <h1>Contact Us</h1>
            <p>We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
        </div>
        
        <div class="contact-grid">
            <div class="contact-info">
                <h3><i class="fas fa-address-card"></i> Get in Touch</h3>
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>123 Jalan Badminton, Kuala Lumpur, Malaysia</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-phone-alt"></i>
                    <span>+603-1234 5678</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <span>smasharenabadminton@gmail.com</span>
                </div>
                <div class="info-item">
                    <i class="fab fa-whatsapp"></i>
                    <span>+60 12-345 6789</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <span>Daily: 8:00 AM - 1:00 AM</span>
                </div>
                
                <div class="map-placeholder">
                    <i class="fas fa-map"></i>
                    <p>📍 Located at Main Hall, Smash Arena Complex</p>
                    <p style="font-size:0.8rem; margin-top:0.5rem;">Free parking available</p>
                </div>
            </div>
            
            <div class="contact-form">
                <?php if ($message_sent): ?>
                    <div class="message-success"><i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message_sent); ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="message-error"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Your Name</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($user_name); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user_email); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Subject</label>
                        <select name="subject">
                            <option value="Booking Inquiry">Booking Inquiry</option>
                            <option value="Technical Issue">Technical Issue</option>
                            <option value="Payment Problem">Payment Problem</option>
                            <option value="Cancellation Request">Cancellation Request</option>
                            <option value="Feedback / Suggestion">Feedback / Suggestion</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Message</label>
                        <textarea name="message" rows="5" placeholder="Please describe your inquiry in detail..." required></textarea>
                    </div>
                    <button type="submit" class="btn-submit"><i class="fas fa-paper-plane"></i> Send Message</button>
                </form>
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
</body>
</html>