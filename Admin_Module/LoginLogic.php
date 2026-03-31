<?php
session_start();
header('Content-Type: application/json');

$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "care_connect";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user = $input['username'] ?? '';
$pass = $input['password'] ?? '';

// 1. Only query by username first to get the hashed password
$stmt = $conn->prepare("SELECT username, password FROM admins WHERE username = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    
    // 2. Use password_verify to check the plain text password against the hash
    if (password_verify($pass, $row['password'])) {
        $_SESSION['username'] = $row['username'];
        echo json_encode(['success' => true, 'username' => $row['username']]);
    } else {
        // Password does not match the hash
        echo json_encode(['success' => false, 'message' => 'Invalid Username or Password']);
    }
} else {
    // Username not found
    echo json_encode(['success' => false, 'message' => 'Invalid Username or Password']);
}

$stmt->close();
$conn->close();