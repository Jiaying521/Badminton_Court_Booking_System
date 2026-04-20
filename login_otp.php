<?php
header('Content-Type: application/json');
require_once 'config.php';

// This file handles OTP login. For simplicity, we assume OTP is sent and verified separately.
$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email required']);
    exit;
}
// Check if user exists by email
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();
// If user exists, log them in (in real implementation, you would verify the OTP before this step)
if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $email;
    echo json_encode(['success' => true, 'message' => 'Login successful']);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}
?>