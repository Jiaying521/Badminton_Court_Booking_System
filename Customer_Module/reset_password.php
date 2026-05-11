<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

// 设置时区
date_default_timezone_set('Asia/Kuala_Lumpur');

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$code = $data['code'] ?? '';
$newPassword = $data['new_password'] ?? '';

if (empty($email) || empty($code) || empty($newPassword)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// 获取用户信息
$userStmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
$userStmt->execute([$email]);
$user = $userStmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}

// 验证 OTP（使用当前时间）
$now = date('Y-m-d H:i:s');
$stmt = $pdo->prepare("SELECT id, expires_at FROM otp_codes WHERE email = ? AND code = ? AND type = 'reset' AND expires_at > ?");
$stmt->execute([$email, $code, $now]);
$otp = $stmt->fetch();

if (!$otp) {
    // 调试信息
    $checkStmt = $pdo->prepare("SELECT * FROM otp_codes WHERE email = ? AND type = 'reset' ORDER BY id DESC LIMIT 1");
    $checkStmt->execute([$email]);
    $lastOtp = $checkStmt->fetch();
    
    if ($lastOtp) {
        echo json_encode(['success' => false, 'message' => 'Invalid code. Your code: ' . $code . ', Expected: ' . $lastOtp['code'] . ', Expired at: ' . $lastOtp['expires_at'] . ', Current time: ' . $now]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No reset code found. Please request a new one.']);
    }
    exit;
}

// 验证密码强度
if (strlen($newPassword) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}
if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $newPassword)) {
    echo json_encode(['success' => false, 'message' => 'Password must contain at least one symbol (!@#$%^&* etc.)']);
    exit;
}

// 检查新密码是否与旧密码相同
if (password_verify($newPassword, $user['password'])) {
    echo json_encode(['success' => false, 'message' => 'New password cannot be the same as your old password']);
    exit;
}

// 更新密码
$hashed = password_hash($newPassword, PASSWORD_DEFAULT);
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
$stmt->execute([$hashed, $email]);

// 删除已使用的 OTP
$pdo->prepare("DELETE FROM otp_codes WHERE email = ? AND type = 'reset'")->execute([$email]);

echo json_encode(['success' => true, 'message' => 'Password reset successfully']);
?>