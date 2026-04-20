<?php
date_default_timezone_set('Asia/Kuala_Lumpur');
header('Content-Type: application/json');
require_once 'config.php';
// This file handles OTP verification for both registration and login. It expects email, code, and type (register/login) in the request body as JSON.
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$code = trim($input['code'] ?? '');
$type = trim($input['type'] ?? '');

if (!$email || !$code || !$type) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters']);
    exit;
}

// get current time for OTP expiration check
$now = date('Y-m-d H:i:s');

// check if OTP code is valid and not expired
$stmt = $pdo->prepare("SELECT id, expires_at FROM otp_codes WHERE email = ? AND code = ? AND type = ? AND expires_at > ?");
$stmt->execute([$email, $code, $type, $now]);
$otp = $stmt->fetch();

if ($otp) {
    // verifyed successfully, delete the OTP code to prevent reuse
    $stmt = $pdo->prepare("DELETE FROM otp_codes WHERE id = ?");
    $stmt->execute([$otp['id']]);
    echo json_encode(['success' => true, 'message' => 'OTP verified']);
} else {
    // log the failed OTP verification attempt for debugging
    error_log("OTP verification failed: email=$email, code=$code, type=$type, current_time=$now");
    echo json_encode(['success' => false, 'message' => 'Invalid or expired OTP']);
}
?>