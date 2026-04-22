<?php
require_once 'config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$type = $data['type'] ?? 'register'; // 'register' or 'login'

// 生成6位随机码
$code = sprintf("%06d", mt_rand(0, 999999));
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

// 删除旧的同类型 OTP
$pdo->prepare("DELETE FROM otp_codes WHERE email=? AND type=?")->execute([$email, $type]);
// 插入新 OTP
$stmt = $pdo->prepare("INSERT INTO otp_codes (email, code, type, expires_at) VALUES (?,?,?,?)");
$stmt->execute([$email, $code, $type, $expires]);

// 实际环境中应通过邮件发送 $code，这里为测试直接返回（可注释掉）
// 生产环境需使用 PHPMailer 等发送邮件
// 此处模拟返回成功（实际应发送邮件）
// 为了测试，我们返回 code 在前端显示（仅开发用，正式应注释）
echo json_encode(['success' => true, 'message' => 'OTP sent', 'debug_code' => $code]); // 调试用，正式删除 debug_code
// 正式应只返回 success => true
?>