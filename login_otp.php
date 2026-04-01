<?php
// login_otp.php
header('Content-Type: application/json');
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');

if (!$email) {
    echo json_encode(['success' => false, 'message' => 'Email required']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $email;
    echo json_encode(['success' => true, 'message' => 'Login successful']);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}
?>