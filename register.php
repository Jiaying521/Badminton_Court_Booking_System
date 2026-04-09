<?php
// register.php
header('Content-Type: application/json');
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$name = trim($input['name'] ?? '');
$password = trim($input['password'] ?? '');
$nric = trim($input['nric'] ?? '');
$phone = trim($input['phone'] ?? '');
$otpCode = trim($input['otpCode'] ?? '');

if (!$email || !$name || !$password || !$nric || !$phone) {
    echo json_encode(['success' => false, 'message' => 'All fields required']);
    exit;
}

// 检查邮箱是否已存在
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit;
}

// 检查姓名是否已存在（不允许重复）
$stmt = $pdo->prepare("SELECT id FROM users WHERE name = ?");
$stmt->execute([$name]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'This name is already used. Please use your real name as per IC.']);
    exit;
}

// 密码哈希
$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (email, name, password, nric, phone) VALUES (?, ?, ?, ?, ?)");
if ($stmt->execute([$email, $name, $hashed, $nric, $phone])) {
    echo json_encode(['success' => true, 'message' => 'Registration successful']);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed']);
}
?>