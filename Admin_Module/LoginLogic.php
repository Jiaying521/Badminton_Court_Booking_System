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

// Update: Select 'id' and 'role' along with username and password
$stmt = $conn->prepare("SELECT id, username, password, role FROM admins WHERE username = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    
    // Verify the password hash
    if (password_verify($pass, $row['password'])) {
        // Store user information and role in the session for security
        $_SESSION['id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];

        // Send back success and include the role for frontend navigation logic
        echo json_encode([
            'success' => true, 
            'username' => $row['username'],
            'role' => $row['role']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid Username or Password']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid Username or Password']);
}

$stmt->close();
$conn->close();
?>