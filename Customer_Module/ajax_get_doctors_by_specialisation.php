<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$specialisation = $_GET['specialisation'] ?? '';
if (empty($specialisation)) {
    echo json_encode([]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, username, specialisation FROM admins WHERE is_doctor = 1 AND specialisation = ? ORDER BY username");
$stmt->execute([$specialisation]);
$doctors = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($doctors);