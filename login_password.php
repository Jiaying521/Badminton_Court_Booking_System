<?php
require_once 'config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';
$password = $data['password'] ?? '';

$stmt = $pdo->prepare("SELECT id, password FROM users WHERE email=?");
$stmt->execute([$email]);
$user = $stmt->fetch();
if($user && password_verify($password, $user['password'])) {
    $_SESSION['user_id'] = $user['id'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid email or password']);
}
?>