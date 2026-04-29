<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$code = $data['code'] ?? '';
$type = $data['type'] ?? 'register';

// 查找匹配的 OTP
$stmt = $pdo->prepare("SELECT id, code, expires_at FROM otp_codes WHERE email = ? AND type = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$email, $type]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'No OTP found. Please request a new code.']);
    exit;
}

if ($row['code'] != $code) {
    echo json_encode(['success' => false, 'message' => 'Wrong code. Please try again.']);
    exit;
}

$now = date('Y-m-d H:i:s');
if ($row['expires_at'] < $now) {
    echo json_encode(['success' => false, 'message' => 'Code expired. Please request a new code.']);
    exit;
}

// 验证成功，删除 OTP
$pdo->prepare("DELETE FROM otp_codes WHERE email = ? AND type = ?")->execute([$email, $type]);

echo json_encode(['success' => true, 'message' => 'Verified successfully']);
?>