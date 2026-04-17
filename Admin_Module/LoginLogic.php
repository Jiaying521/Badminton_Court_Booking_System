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

// Selected 'status' only, 'first_login' is removed
$stmt = $conn->prepare("SELECT id, username, password, role, status FROM admins WHERE username = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    
    // Check if the account status is Suspended (Complete block)
    if ($row['status'] === 'Suspended') {
        echo json_encode(['success' => false, 'message' => 'This account has been suspended. Please contact Superadmin.']);
        exit;
    }

    // Verify the password hash
    if (password_verify($pass, $row['password'])) {
        // Store user information and role in the session for security
        $_SESSION['id'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['role'] = $row['role'];

        // Logic change: If status is Inactive, it means it is a new account
        $is_new_account = ($row['status'] === 'Inactive');

        // Send back success and use 'first_login' key for compatibility with your JS
        echo json_encode([
            'success' => true, 
            'username' => $row['username'],
            'role' => $row['role'],
            'first_login' => $is_new_account
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