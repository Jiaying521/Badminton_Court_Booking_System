<?php
session_start();
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false]);
    exit();
}

$conn     = mysqli_connect("localhost", "root", "", "badminton_hub");
$role     = $_SESSION['role'];
$admin_id = (int)$_SESSION['id'];

$sql = "SELECT * FROM notifications
        WHERE (recipient_role = '$role' OR recipient_role = 'All')
          AND (recipient_id = $admin_id OR recipient_id IS NULL)
        ORDER BY created_at DESC
        LIMIT 20";

$result        = mysqli_query($conn, $sql);
$notifications = [];
while ($row = mysqli_fetch_assoc($result)) {
    $notifications[] = $row;
}

$count_sql = "SELECT COUNT(*) AS cnt FROM notifications
              WHERE (recipient_role = '$role' OR recipient_role = 'All')
                AND (recipient_id = $admin_id OR recipient_id IS NULL)
                AND is_read = 0";
$count_res   = mysqli_fetch_assoc(mysqli_query($conn, $count_sql));
$unread      = (int)$count_res['cnt'];

header('Content-Type: application/json');
echo json_encode([
    'success'       => true,
    'notifications' => $notifications,
    'unread_count'  => $unread
]);