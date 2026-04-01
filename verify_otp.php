<?php
// verify_otp.php
header('Content-Type: application/json');
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$code = trim($input['code'] ?? '');
$type = trim($input['type'] ?? '');

if (!$email || !$code || !$type) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM otp_codes WHERE email = ? AND code = ? AND type = ? AND expires_at > NOW()");
$stmt->execute([$email, $code, $type]);
$otp = $stmt->fetch();

if ($otp) {
    $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE id = ?");
    $stmt->execute([$otp['id']]);
    echo json_encode(['success' => true, 'message' => 'OTP verified']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
}
?>