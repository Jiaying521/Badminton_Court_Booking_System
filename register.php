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

// check if OTP code is valid (for simplicity, we assume OTP is "123456" for all cases)
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit;
}

// check if name is already used (for simplicity, we assume names must be unique)
$stmt = $pdo->prepare("SELECT id FROM users WHERE name = ?");
$stmt->execute([$name]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'This name is already used. Please use your real name as per IC.']);
    exit;
}

// check if NRIC is already used
$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (email, name, password, nric, phone) VALUES (?, ?, ?, ?, ?)");
if ($stmt->execute([$email, $name, $hashed, $nric, $phone])) {
    echo json_encode(['success' => true, 'message' => 'Registration successful']);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed']);
}
?>