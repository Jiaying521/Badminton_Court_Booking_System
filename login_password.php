<?php
// login_password.php
header('Content-Type: application/json');
require_once 'config.php';

$input = json_decode(file_get_contents('php://input'), true);
$email = trim($input['email'] ?? '');
$password = trim($input['password'] ?? '');

if (!$email || !$password) {
    echo json_encode(['success' => false, 'message' => 'Email and password required']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, password FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_email'] = $email;
    echo json_encode(['success' => true, 'message' => 'Login successful']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
}
?>