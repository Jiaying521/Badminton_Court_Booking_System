<?php
require_once 'config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$code = $data['code'] ?? '';
$type = $data['type'] ?? 'register';

$stmt = $pdo->prepare("SELECT id FROM otp_codes WHERE email=? AND code=? AND type=? AND expires_at > NOW()");
$stmt->execute([$email, $code, $type]);
if($stmt->fetch()) {
    // 验证成功，删除该 OTP
    $pdo->prepare("DELETE FROM otp_codes WHERE email=? AND type=?")->execute([$email, $type]);
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
}
?>