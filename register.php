<?php
// register.php
header('Content-Type: application/json');
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');
$nric = trim($input['nric'] ?? '');
$phone = trim($input['phone'] ?? '');
$otpCode = trim($input['otpCode'] ?? '');

if (!$email || !$password || !$nric || !$phone) {
    echo json_encode(['success' => false, 'message' => 'All fields required']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Email already registered']);
    exit;
}

$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (email, password, nric, phone) VALUES (?, ?, ?, ?)");
if ($stmt->execute([$email, $hashed, $nric, $phone])) {
    echo json_encode(['success' => true, 'message' => 'Registration successful']);
} else {
    echo json_encode(['success' => false, 'message' => 'Registration failed']);
}
?>