<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'] ?? '';

$stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
$stmt->execute([$email]);
$user = $stmt->fetch();
if($user) {
    $_SESSION['user_id'] = $user['id'];
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}
?>