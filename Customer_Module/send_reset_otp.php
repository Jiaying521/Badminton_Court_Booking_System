<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

// set timezone to Kuala Lumpur
date_default_timezone_set('Asia/Kuala_Lumpur');

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';

if (empty($email)) {
    echo json_encode(['success' => false, 'message' => 'Email is required']);
    exit;
}

// validate email format
$stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Email not found. Please register first.']);
    exit;
}

// generate a 6-digit OTP code
$code = sprintf("%06d", mt_rand(0, 999999));

// calculate expiration time (current time + 10 minutes)   
$now = date('Y-m-d H:i:s');
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// delete any existing reset OTP for this email
$pdo->prepare("DELETE FROM otp_codes WHERE email = ? AND type = 'reset'")->execute([$email]);

// insert new OTP
$stmt = $pdo->prepare("INSERT INTO otp_codes (email, code, type, expires_at) VALUES (?, ?, 'reset', ?)");
$stmt->execute([$email, $code, $expires]);

// send email
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../Admin_Module/Email_System/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/../Admin_Module/Email_System/phpmailer/src/SMTP.php';
require_once __DIR__ . '/../Admin_Module/Email_System/phpmailer/src/Exception.php';

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'smasharenabadminton@gmail.com';
    $mail->Password   = 'mrxd evvy oyyl boak';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    
    $mail->setFrom('smasharenabadminton@gmail.com', 'Smash Arena');
    $mail->addAddress($email, $user['name']);
    $mail->isHTML(true);
    $mail->Subject = 'Password Reset Code - Smash Arena';
    $mail->Body = "
    <html>
    <body>
        <h2>Smash Arena - Password Reset</h2>
        <p>Hello <strong>{$user['name']}</strong>,</p>
        <p>Your password reset code is: <strong style='font-size:24px;color:#2b7e3a;'>{$code}</strong></p>
        <p>This code expires in <strong>10 minutes</strong>.</p>
        <p>If you did not request this, please ignore this email.</p>
    </body>
    </html>
    ";
    $mail->AltBody = "Your password reset code is: $code\n\nThis code expires in 10 minutes.";
    
    $mail->send();
    echo json_encode(['success' => true, 'message' => 'Reset code sent to your email', 'debug_time' => $expires]);
    
} catch (Exception $e) {
    error_log("Password reset email failed: " . $mail->ErrorInfo);
    echo json_encode(['success' => true, 'message' => 'Reset code sent (debug mode)', 'debug_code' => $code, 'debug_time' => $expires]);
}
?>