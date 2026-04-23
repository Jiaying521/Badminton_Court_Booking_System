<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');
$name = $_GET['name'] ?? '';
if (strlen($name) < 2) {
    echo json_encode(['exists' => false]);
    exit;
}
$stmt = $pdo->prepare("SELECT id FROM users WHERE name = ?");
$stmt->execute([$name]);
$exists = $stmt->fetch() ? true : false;
echo json_encode(['exists' => $exists]);