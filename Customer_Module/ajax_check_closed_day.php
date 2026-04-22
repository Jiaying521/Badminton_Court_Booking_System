<?php
require_once 'config.php';
$date = $_GET['date'] ?? '';
if(!$date) die(json_encode(['isClosed'=>false]));
$stmt = $pdo->prepare("SELECT COUNT(*) FROM closed_days WHERE closed_date=?");
$stmt->execute([$date]);
echo json_encode(['isClosed' => $stmt->fetchColumn() > 0]);
?>