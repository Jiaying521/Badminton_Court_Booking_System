<?php
require_once __DIR__ . '/../config.php';
header('Content-Type: application/json');

$date = $_GET['date'] ?? '';

if (empty($date)) {
    echo json_encode(['isClosed' => false]);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM closed_days WHERE closed_date = ?");
    $stmt->execute([$date]);
    $count = $stmt->fetchColumn();
    
    echo json_encode(['isClosed' => $count > 0]);
} catch (PDOException $e) {
    echo json_encode(['isClosed' => false]);
}
?>