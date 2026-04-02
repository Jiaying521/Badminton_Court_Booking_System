<?php
require_once 'config.php';

$email = 'xyyjiaying@gmail.com';  // 改成你自己的邮箱
$type = 'register';

// 手动调用发送逻辑（复制 send_otp.php 中的核心代码）
$code = '123456';
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

$stmt = $pdo->prepare("DELETE FROM otp_codes WHERE email = ? AND type = ?");
$stmt->execute([$email, $type]);

$stmt = $pdo->prepare("INSERT INTO otp_codes (email, code, type, expires_at) VALUES (?, ?, ?, ?)");
$stmt->execute([$email, $code, $type, $expires]);

$mail = new PHPMailer\PHPMailer\PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = SMTP_PORT;
    $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'Test OTP';
    $mail->Body    = "Test code: $code";
    $mail->send();
    echo "邮件发送成功，验证码：$code";
} catch (Exception $e) {
    echo "邮件发送失败: " . $mail->ErrorInfo;
}
?>