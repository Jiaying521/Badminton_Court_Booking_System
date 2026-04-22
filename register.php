<?php
require_once 'config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$name = $data['name'] ?? '';
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';
$nric = $data['nric'] ?? '';
$phone = $data['phone'] ?? '';
$otpCode = $data['otpCode'] ?? '';

// 验证 OTP（可选，因为前端已经验证过，但后端再验证一次更安全）
$stmt = $pdo->prepare("SELECT id FROM otp_codes WHERE email=? AND code=? AND type='register' AND expires_at > NOW()");
$stmt->execute([$email, $otpCode]);
if(!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'OTP verification required']);
    exit;
}
// 删除 OTP
$pdo->prepare("DELETE FROM otp_codes WHERE email=? AND type='register'")->execute([$email]);

// 检查邮箱是否已存在
$check = $pdo->prepare("SELECT id FROM users WHERE email=?");
$check->execute([$email]);
if($check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit;
}

$hashed = password_hash($password, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("INSERT INTO users (name, email, password, nric, phone) VALUES (?,?,?,?,?)");
if($stmt->execute([$name, $email, $hashed, $nric, $phone])) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed']);
}
?>