<?php
session_start();
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false]);
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "badminton_hub");
if (!$conn) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

$role     = $_SESSION['role'];
$admin_id = (int)$_SESSION['id'];

// 如果是 Coach，需要获取 coaches.id（因为通知存的是 coaches.id）
$recipient_id = $admin_id;
if (strtolower($role) === 'coach') {
    $coach_query = mysqli_query($conn, "SELECT id FROM coaches WHERE admin_id = '$admin_id'");
    if ($coach_query) {
        $coach_data = mysqli_fetch_assoc($coach_query);
        if ($coach_data) {
            $recipient_id = (int)$coach_data['id'];
        }
    }
}

// 查询通知
$sql = "SELECT * FROM notifications
        WHERE (recipient_role = '$role' OR recipient_role = 'All')
          AND (recipient_id = $recipient_id OR recipient_id IS NULL)
        ORDER BY created_at DESC
        LIMIT 20";

$result = mysqli_query($conn, $sql);
$notifications = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $notifications[] = $row;
    }
}

// 统计未读数量
$count_sql = "SELECT COUNT(*) AS cnt FROM notifications
              WHERE (recipient_role = '$role' OR recipient_role = 'All')
                AND (recipient_id = $recipient_id OR recipient_id IS NULL)
                AND is_read = 0";
$count_res = mysqli_query($conn, $count_sql);
$unread = 0;
if ($count_res) {
    $count_data = mysqli_fetch_assoc($count_res);
    $unread = (int)$count_data['cnt'];
}

mysqli_close($conn);

header('Content-Type: application/json');
echo json_encode([
    'success'       => true,
    'notifications' => $notifications,
    'unread_count'  => $unread
]);