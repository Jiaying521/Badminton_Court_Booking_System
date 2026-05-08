<?php
require_once __DIR__ . '/../config.php';
include 'db_connect.php'; 

// Ensure session is active to get the user ID
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 🟢 SECURITY: Use $_POST instead of $_REQUEST
    $user_id = $_SESSION['user_id'] ?? 0;
    $amt = $_POST['reload_amount'] ?? 0;
    $return_to = $_POST['return_to'] ?? 'dashboard';
    $b_id = $_POST['booking_id'] ?? 0;
    $b_amt = $_POST['amount'] ?? 0;

    // Basic validation
    if ($user_id == 0 || $amt <= 0) {
        die("Invalid transaction request.");
    }

    // 🟢 DATABASE UPDATE
    $sql = "UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("di", $amt, $user_id);
    
    if ($stmt->execute()) {
        // 🟢 SMART REDIRECT
        if ($return_to === 'checkout' && $b_id != 0) {
            // Send back to checkout to finish the court booking
            header("Location: checkout.php?booking_id=$b_id&amount=$b_amt&status=success");
        } else {
            // Send back to dashboard
            header("Location: ../Customer_Module/dashboard.php?status=success");
        }
        exit();
    } else {
        echo "Database Error: " . $conn->error;
    }
} else {
    // If someone tries to access this file directly without POSTing
    header("Location: ../Customer_Module/dashboard.php");
    exit();
}
?>