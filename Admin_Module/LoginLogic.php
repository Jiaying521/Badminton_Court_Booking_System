<?php
session_start();
header('Content-Type: application/json');

$host = "localhost";
$db_user = "root";
$db_pass = "";
$db_name = "badminton_hub";

$conn = new mysqli($host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$user = $input['username'] ?? '';
$pass = $input['password'] ?? '';

// Selected 'status' only, 'first_login' is removed
$stmt = $conn->prepare("SELECT id, username, password, role, status, suspended_until FROM admins WHERE username = ?");
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();

    // Suspended accounts: auto-unban once the suspension date has passed, otherwise block
    if ($row['status'] === 'Suspended') {
        $until = $row['suspended_until'];

        if ($until === null) {
            // No date = permanent ban
            echo json_encode(['success' => false, 'message' => 'This account has been permanently suspended. Please contact Superadmin.']);
            exit;
        }

        $today = date('Y-m-d');

        if ($today >= $until) {
            // Suspension served — reactivate the account and let them in
            $aid = (int)$row['id'];
            $conn->query("UPDATE admins SET status = 'Active', suspended_until = NULL WHERE id = $aid");
            $conn->query("UPDATE coaches SET is_active = 1 WHERE admin_id = $aid");
            $row['status'] = 'Active';
        } else {
            // Still suspended — show how many days are left
            $days_left = (int) ceil((strtotime($until) - strtotime($today)) / 86400);
            echo json_encode(['success' => false, 'message' => "Your account is suspended until " . date('d M Y', strtotime($until)) . " ($days_left day(s) left)."]);
            exit;
        }
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