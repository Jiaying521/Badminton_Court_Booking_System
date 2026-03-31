<?php
// Enable error reporting for debugging purposes
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// 1. Receive and decode the JSON data from JS
$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$password = $input['password'] ?? '';

// Check if inputs are provided
if (empty($token) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing token or password.']);
    exit;
}

// 2. Database configuration and connection
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "care_connect";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

// 3. Verify the token and check if it has expired
$current_time = date('Y-m-d H:i:s');
$stmt = $conn->prepare("SELECT email FROM admins WHERE reset_token = ? AND token_expiry > ?");
$stmt->bind_param("ss", $token, $current_time);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // No matching token found or the link has already expired
    echo json_encode(['status' => 'error', 'message' => 'This reset link is outdated, invalid, or has already been used.']);
    exit;
}

$user = $result->fetch_assoc();
$email = $user['email'];

// 4. Securely hash the new password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 5. Update the database: Set new password AND clear the token (one-time use)
// By setting reset_token and token_expiry to NULL, the link becomes invalid immediately
$updateStmt = $conn->prepare("UPDATE admins SET password = ?, reset_token = NULL, token_expiry = NULL WHERE email = ?");
$updateStmt->bind_param("ss", $hashedPassword, $email);

if ($updateStmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Password updated successfully! You can now log in.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to update database.']);
}

// Cleanup
$stmt->close();
$updateStmt->close();
$conn->close();