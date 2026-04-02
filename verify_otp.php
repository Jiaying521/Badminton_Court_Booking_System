<?php
// verify_otp.php
// 设置时区为马来西亚（与 send_otp.php 中的 date() 保持一致）
date_default_timezone_set('Asia/Kuala_Lumpur');
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

// 获取当前时间（PHP 生成，避免 MySQL 时区问题）
$now = date('Y-m-d H:i:s');

// 查询匹配的 OTP，且未过期（使用 PHP 时间比较）
$stmt = $pdo->prepare("SELECT id, expires_at FROM otp_codes WHERE email = ? AND code = ? AND type = ? AND expires_at > ?");
$stmt->execute([$email, $code, $type, $now]);
$otp = $stmt->fetch();

if ($otp) {
    // 验证成功，删除该 OTP 防止重用
    $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE id = ?");
    $stmt->execute([$otp['id']]);
    echo json_encode(['success' => true, 'message' => 'OTP verified']);
} else {
    // 可选：记录调试信息到日志（生产环境可关闭）
    error_log("OTP verification failed: email=$email, code=$code, type=$type, current_time=$now");
    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
}
?>