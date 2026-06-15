<?php
session_start();
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false]);
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "badminton_hub");
if (!$conn) {
    echo json_encode(['success' => false]);
    exit();
}

$role     = $_SESSION['role'];
$admin_id = (int)$_SESSION['id'];

// 如果是 Coach，需要获取 coaches.id
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

$input = json_decode(file_get_contents('php://input'), true);
$id    = isset($input['id']) ? (int)$input['id'] : 0;

if ($id > 0) {
    // 标记单条通知为已读
    mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE id = $id");
} else {
    // 标记所有相关通知为已读
    mysqli_query($conn, "UPDATE notifications SET is_read = 1
                         WHERE (recipient_role = '$role' OR recipient_role = 'All')
                           AND (recipient_id = $recipient_id OR recipient_id IS NULL)");
}

mysqli_close($conn);

header('Content-Type: application/json');
echo json_encode(['success' => true]);