<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// 1. Receive data
$input = json_decode(file_get_contents('php://input'), true);
$token = $input['token'] ?? '';
$password = $input['password'] ?? '';

if (empty($token) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing token or password.']);
    exit;
}

// 2. Database Connection
$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "care_connect";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed.']);
    exit;
}

// 3. Verify Token and Fetch Current Password
$current_time = date('Y-m-d H:i:s');
// We select 'password' to compare it later
$stmt = $conn->prepare("SELECT email, password FROM admins WHERE reset_token = ? AND token_expiry > ?");
$stmt->bind_param("ss", $token, $current_time);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired reset link.']);
    exit;
}

$user = $result->fetch_assoc();
$email = $user['email'];
$currentHashedPassword = $user['password']; 

// --- CORE FEATURE: Check if password is reused ---
// Compare raw input password with the hashed password from database
if (password_verify($password, $currentHashedPassword)) {
    echo json_encode([
        'status' => 'error', 
        // This is the message you will see in the alert
        'message' => 'New password must be different from your previous password.'
    ]);
    exit; // Stop the script here
}

// 4. Hash the new password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 5. Update Database and clear token
$updateStmt = $conn->prepare("UPDATE admins SET password = ?, reset_token = NULL, token_expiry = NULL WHERE email = ?");
$updateStmt->bind_param("ss", $hashedPassword, $email);

if ($updateStmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Password updated successfully!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database update failed.']);
}

$stmt->close();
$updateStmt->close();
$conn->close();