<?php
date_default_timezone_set('Asia/Kuala_Lumpur');
header('Content-Type: application/json');
require_once 'config.php';
// This file handles sending OTP codes for both registration and login. It expects email and type (register/login) in the request body as JSON.
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$type = trim($input['type'] ?? '');
// Validate input
if (!$email || !$type) {
    echo json_encode(['success' => false, 'message' => 'Missing email or type']);
    exit;
}
// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email address']);
    exit;
}
// Check if type is valid
if ($type === 'register') {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email already registered']);
        exit;
    }
} elseif ($type === 'login') {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Email not found']);
        exit;
    }
}

// Generate a 6-digit OTP code
$code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
// Store the OTP code in the database (for simplicity, we allow only one active code per email and type)
$stmt = $pdo->prepare("DELETE FROM otp_codes WHERE email = ? AND type = ?");
$stmt->execute([$email, $type]);
// Insert new OTP code
$stmt = $pdo->prepare("INSERT INTO otp_codes (email, code, type, expires_at) VALUES (?, ?, ?, ?)");
$stmt->execute([$email, $code, $type, $expires]);
// Send the OTP code via email using PHPMailer
$mail = new \PHPMailer\PHPMailer\PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;

    $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Your CareConnect Verification Code';
    $mail->Body    = "<h2>Verification Code</h2><p>Your $type verification code is: <strong>$code</strong></p><p>This code expires in 10 minutes.</p>";

    $mail->send();
    echo json_encode(['success' => true, 'message' => 'OTP sent successfully']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
}
?>