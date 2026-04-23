<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$name = trim($data['name'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$phone = trim($data['phone'] ?? '');
$otpCode = trim($data['otpCode'] ?? '');

// 验证必填字段
if (empty($name) || empty($email) || empty($password) || empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// 验证用户名唯一性
$stmt = $pdo->prepare("SELECT id FROM users WHERE name = ?");
$stmt->execute([$name]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'This name is already taken. Please choose another.']);
    exit;
}

// 验证密码强度：至少6位，至少一个符号
if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit;
}
if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
    echo json_encode(['success' => false, 'message' => 'Password must contain at least one symbol (!@#$%^&* etc.)']);
    exit;
}

// 验证 OTP
$stmt = $pdo->prepare("SELECT id FROM otp_codes WHERE email=? AND code=? AND type='register' AND expires_at > NOW()");
$stmt->execute([$email, $otpCode]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
    exit;
}
// 删除已使用的 OTP
$pdo->prepare("DELETE FROM otp_codes WHERE email=? AND type='register'")->execute([$email]);

// 检查邮箱是否已存在
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit;
}

// 插入新用户（不再需要 nric 字段，假设该字段允许 NULL 或已删除）
$hashed = password_hash($password, PASSWORD_DEFAULT);
// 如果 users 表仍然有 nric 列且 NOT NULL，请先修改表结构：ALTER TABLE users MODIFY nric VARCHAR(50) NULL;
$stmt = $pdo->prepare("INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)");
$result = $stmt->execute([$name, $email, $hashed, $phone]);
if ($result) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
}