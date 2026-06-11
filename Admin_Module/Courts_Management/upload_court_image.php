<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['username']) || !in_array($_SESSION['role'], ['Superadmin', 'Admin'])) {
    echo json_encode(['success' => false, 'message' => 'Not authorized']);
    exit();
}

$conn = mysqli_connect("localhost", "root", "", "badminton_hub");
require_once __DIR__ . '/../log_activity.php';

// List action: return the current photo for every slot of a court (used by the edit modal)
if (($_GET['action'] ?? '') === 'list') {
    $court_id = intval($_GET['court_id'] ?? 0);
    $result = mysqli_query($conn, "SELECT court_name FROM courts WHERE id = $court_id");
    $court = mysqli_fetch_assoc($result);
    if (!$court) {
        echo json_encode(['success' => false, 'message' => 'Court not found']);
        exit();
    }
    $base_name = strtolower(str_replace(' ', '_', $court['court_name']));
    $dir = __DIR__ . '/../../Pictures/Admin_Module/courts/';
    $slots = [];
    foreach (['main', '1', '2', '3', '4', '5'] as $s) {
        $stem = ($s === 'main') ? $base_name : $base_name . '_' . $s;
        $slots[$s] = null;
        foreach (['jpg', 'jpeg', 'png'] as $ext) {
            if (file_exists($dir . $stem . '.' . $ext)) {
                $slots[$s] = '../../Pictures/Admin_Module/courts/' . $stem . '.' . $ext . '?v=' . filemtime($dir . $stem . '.' . $ext);
                break;
            }
        }
    }
    echo json_encode(['success' => true, 'slots' => $slots]);
    exit();
}

$court_id = intval($_POST['court_id'] ?? 0);
$slot     = $_POST['slot'] ?? '';
$action   = $_POST['action'] ?? 'upload';

// Slot is either 'main' or gallery position 1-5
$valid_slot = ($slot === 'main') || (ctype_digit($slot) && (int)$slot >= 1 && (int)$slot <= 5);
if ($court_id <= 0 || !$valid_slot) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$result = mysqli_query($conn, "SELECT court_name FROM courts WHERE id = $court_id");
$court = mysqli_fetch_assoc($result);
if (!$court) {
    echo json_encode(['success' => false, 'message' => 'Court not found']);
    exit();
}

// Same naming convention the customer pages read: court_a.jpg, court_a_1.jpg ... court_a_5.jpg
$base_name = strtolower(str_replace(' ', '_', $court['court_name']));
$file_stem = ($slot === 'main') ? $base_name : $base_name . '_' . $slot;
$dir = __DIR__ . '/../../Pictures/Admin_Module/courts/';

// Remove any existing file for this slot (both extensions) so we never keep stale duplicates
foreach (['jpg', 'jpeg', 'png'] as $ext) {
    if (file_exists($dir . $file_stem . '.' . $ext)) {
        unlink($dir . $file_stem . '.' . $ext);
    }
}

if ($action === 'delete') {
    if ($slot === 'main') {
        mysqli_query($conn, "UPDATE courts SET court_image = NULL WHERE id = $court_id");
    }
    logActivity($conn, 'Update', 'Court Management', "Removed photo ($slot) for court: " . $court['court_name']);
    echo json_encode(['success' => true]);
    exit();
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No image received']);
    exit();
}

// Make sure the upload is actually an image before saving it
$info = getimagesize($_FILES['image']['tmp_name']);
if ($info === false || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG])) {
    echo json_encode(['success' => false, 'message' => 'Invalid image file']);
    exit();
}

$filename = $file_stem . '.jpg';
if (!move_uploaded_file($_FILES['image']['tmp_name'], $dir . $filename)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save image']);
    exit();
}

if ($slot === 'main') {
    $safe_name = mysqli_real_escape_string($conn, $filename);
    mysqli_query($conn, "UPDATE courts SET court_image = '$safe_name' WHERE id = $court_id");
}

logActivity($conn, 'Update', 'Court Management', "Uploaded photo ($slot) for court: " . $court['court_name']);
echo json_encode(['success' => true, 'file' => $filename]);
?>
