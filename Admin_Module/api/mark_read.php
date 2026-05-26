<?php
session_start();
if (!isset($_SESSION['username'])) {
    echo json_encode(['success' => false]);
    exit();
}

$conn     = mysqli_connect("localhost", "root", "", "badminton_hub");
$role     = $_SESSION['role'];
$admin_id = (int)$_SESSION['id'];
$input    = json_decode(file_get_contents('php://input'), true);
$id       = isset($input['id']) ? (int)$input['id'] : 0;

if ($id > 0) {
    mysqli_query($conn, "UPDATE notifications SET is_read = 1 WHERE id = $id");
} else {
    mysqli_query($conn, "UPDATE notifications SET is_read = 1
                         WHERE (recipient_role = '$role' OR recipient_role = 'All')
                           AND (recipient_id = $admin_id OR recipient_id IS NULL)");
}

header('Content-Type: application/json');
echo json_encode(['success' => true]);