<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

// 引入 PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../Admin_Module/Email_System/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../Admin_Module/Email_System/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../Admin_Module/Email_System/phpmailer/src/Exception.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$type = $data['type'] ?? 'register';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

// deepcode: generate a 6-digit OTP code
$code = sprintf("%06d", mt_rand(0, 999999));
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

try {
    // delete any existing OTP for this email and type
    $pdo->prepare("DELETE FROM otp_codes WHERE email=? AND type=?")->execute([$email, $type]);
    
    // insert new OTP
    $stmt = $pdo->prepare("INSERT INTO otp_codes (email, code, type, expires_at) VALUES (?,?,?,?)");
    $stmt->execute([$email, $code, $type, $expires]);
    
    // send the OTP via email
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'smasharenabadminton@gmail.com';
    $mail->Password   = 'mrxd evvy oyyl boak';  // 请填入您的密码
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    
    $mail->setFrom('smasharenabadminton@gmail.com', 'Smash Arena');
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Your OTP Code - Smash Arena';
    $mail->Body = "
    <html>
    <body>
        <h2>Smash Arena</h2>
        <p>Your OTP code is: <strong style='font-size:24px;color:#2b7e3a;'>$code</strong></p>
        <p>This code expires in 10 minutes.</p>
    </body>
    </html>
    ";
    $mail->AltBody = "Your OTP code is: $code";
    
    $mail->send();
    
    // success response
    echo json_encode(['success' => true, 'message' => 'OTP sent to your email']);
    
} catch (Exception $e) {
    // error response
    echo json_encode(['success' => false, 'message' => 'Failed to send OTP: ' . $mail->ErrorInfo]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>